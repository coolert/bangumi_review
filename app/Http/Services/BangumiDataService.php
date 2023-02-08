<?php

namespace App\Http\Services;

use App\Mail\UpdateDataNotify;
use App\Models\Bangumi;
use App\Models\BangumiSite;
use App\Models\BangumiTranslate;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BangumiDataService extends BaseService
{
    private string $latest_path;
    private string $backup_path;
    private string $notify_email;
    private Site $siteModel;
    private Bangumi $bangumiModel;
    private BangumiSite $bangumiSiteModel;
    private BangumiTranslate $bangumiTranslateModel;
    public function __construct(Site $siteModel, Bangumi $bangumiModel, BangumiSite $bangumiSiteModel, BangumiTranslate $bangumiTranslateModel)
    {
        $this->notify_email = env('NOTIFY_EMAIL');
        $this->latest_path = base_path('node_modules/bangumi-data/dist/data.json');
        $this->backup_path = resource_path('backup/data.json');
        $this->siteModel = $siteModel;
        $this->bangumiModel = $bangumiModel;
        $this->bangumiSiteModel = $bangumiSiteModel;
        $this->bangumiTranslateModel = $bangumiTranslateModel;
    }

    /**
     * Database init.
     *
     * @author Lv
     * @date 2023/2/7
     */
    public function data_init()
    {
        $data = json_decode(file_get_contents($this->latest_path), true);
        $this->siteModel->truncate();
        $this->bangumiModel->truncate();
        $this->bangumiSiteModel->truncate();
        $this->bangumiTranslateModel->truncate();
        $site_data = [];
        foreach ($data['siteMeta'] as $site => $site_info) {
            $site_data[] = [
                'site' => $site,
                'title' => $site_info['title'],
                'url' => $site_info['urlTemplate'],
                'regions' => isset($site_info['regions']) ? json_encode($site_info['regions']) : '',
                'type' => $site_info['type'],
            ];
        }
        DB::beginTransaction();
        try {
            $this->siteModel->insert($site_data);
            $this->bangumiModel->insert_items($data['items']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $subject = 'BangumiReview数据初始化失败通知';
            $content = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();
            Mail::to('keith920627@gmail.com')->send(new UpdateDataNotify($subject, $content));
        }
    }

    /**
     *  Update database by npm.
     *
     * @author Lv
     * @date 2023/2/7
     */
    public function update_data()
    {
        if ($this->check_update()) {
            //backup
            file_put_contents($this->backup_path, file_get_contents($this->latest_path));
            shell_exec('npm update bangumi-data --save');
            $result = $this->check_update();
            if ($result) {
                //update failed
                $subject = 'BangumiReview更新数据失败通知';
                $content = '数据更新失败，npm包更新失败';
                Mail::to($this->notify_email)->send(new UpdateDataNotify($subject, $content));
            } else {
                $this->update_database();
            }
        }
    }

    /** Check if it needs to update the data.
     *
     * @param string $package_name
     * @return bool
     * @author Lv
     * @date 2023/2/6
     */
    public function check_update(string $package_name = 'bangumi-data'): bool
    {
        $local_version  = $this->get_local_version($package_name);
        $latest_version = $this->get_latest_version($package_name);
        if ($latest_version != $local_version) {
            return true;
        } else {
            return false;
        }
    }

    /** Get local npm package version.
     *
     * @param string $package_name
     * @return string
     * @author Lv
     * @date 2023/2/6
     */
    public function get_local_version(string $package_name): string
    {
        $result = shell_exec('npm ls ' . $package_name);
        return trim(substr($result, strripos($result, '@')+1));
    }

    /** Get latest npm package version
     *
     * @param string $package_name
     * @return string
     * @author Lv
     * @date 2023/2/6
     */
    public function get_latest_version(string $package_name): string
    {
        $result = shell_exec('npm view ' . $package_name . ' version');
        return trim($result);
    }

    /**
     * Update bangumi database.
     */
    protected function update_database()
    {
        $data = json_decode(file_get_contents($this->latest_path), true);
        //Update site data
        $this->siteModel->truncate();
        $site_data = [];
        foreach ($data['siteMeta'] as $site => $site_info) {
            $site_data[] = [
                'site' => $site,
                'title' => $site_info['title'],
                'url' => $site_info['urlTemplate'],
                'regions' => isset($site_info['regions']) ? json_encode($site_info['regions']) : '',
                'type' => $site_info['type'],
            ];
        }
        DB::beginTransaction();
        try {
            $this->siteModel->insert($site_data);
            $item_data = $this->compare_data($data['items'], json_decode(file_get_contents($this->backup_path), true)['items']);
            $this->bangumiModel->insert_items($item_data['insert']);
            $this->bangumiModel->update_items($item_data['update']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $subject = 'BangumiReview更新数据失败通知';
            $content = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();
            Mail::to($this->notify_email)->send(new UpdateDataNotify($subject, $content));
        }
    }

    /**
     * Compare new data with old data.
     *
     * @param array $new_data
     * @param array $old_data
     *
     * @return array
     */
    public function compare_data(array $new_data,array $old_data): array
    {
        $old_count = count($old_data);
        $new_count = count($new_data);
        if ($new_count > $old_count) {
            $insert_data = array_slice($new_data, $old_count, $new_count - $old_count);
            $update_data = array_slice($new_data, 0, $old_count);
        } else {
            $insert_data = [];
            $update_data = $new_data;
        }
        $update_items = [];
        if ($update_data != $old_data) {
            foreach ($old_data as $key => $value) {
                foreach ($update_data as $k => $v) {
                    if ($key == $k) {
                        if ($value != $v) {
                            $update_items[$key] = $v;
                        }
                    }
                }
            }
        }
        return [
            'insert' => $insert_data,
            'update' => $update_items,
        ];
    }
}
