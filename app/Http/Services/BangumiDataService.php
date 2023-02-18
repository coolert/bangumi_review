<?php

namespace App\Http\Services;

use App\Jobs\UpdateBangumiImages;
use App\Mail\UpdateDataNotify;
use App\Models\Bangumi;
use App\Models\BangumiSite;
use App\Models\BangumiTranslate;
use App\Models\Site;
use App\Tools\GuzzleRequest;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BangumiDataService extends BaseService
{
    private string $latest_path;
    private string $backup_path;
    private string $notify_email;
    private string $bangumi_search_api = 'https://api.bgm.tv/search/subject/{keywords}?type=2';
    private GuzzleRequest $guzzle;
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
        $this->guzzle = new GuzzleRequest();
    }

    /**
     * Database init.
     *
     * @author Lv
     * @date 2023/2/7
     */
    public function data_init(): void
    {
        $data = json_decode(file_get_contents($this->latest_path), true);
        //Truncate Tables
        $this->siteModel->truncate();
        $this->bangumiModel->truncate();
        $this->bangumiSiteModel->truncate();
        $this->bangumiTranslateModel->truncate();
        DB::beginTransaction();
        try {
            $this->siteModel->insert_site_meta($data['siteMeta']);
            $this->bangumiModel->insert_items($data['items']);
            DB::commit();
            UpdateBangumiImages::dispatch('all')->onQueue('update_image');
        } catch (\Exception $e) {
            DB::rollBack();
            $subject = 'BangumiReview数据初始化失败通知';
            $content = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();
            Mail::to($this->notify_email)->send(new UpdateDataNotify($subject, $content));
        }
    }

    /**
     *  Update database by npm.
     *
     * @author Lv
     * @date 2023/2/7
     */
    public function update_data(): void
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
    protected function update_database():void
    {
        $data = json_decode(file_get_contents($this->latest_path), true);
        $this->siteModel->truncate();
        DB::beginTransaction();
        try {
            $this->siteModel->insert_site_meta($data['siteMeta']);
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
        $update_list = [];
        $insert_list = $new_data;
        foreach ($old_data as $value) {
            foreach ($new_data as $k => $v) {
                if ($value['title'] == $v['title']) {
                    unset($insert_list[$k]);
                    if ($value != $v) {
                        $update_list[] = $v;
                    }
                }
            }
        }
        return [
            'insert' => array_values($insert_list),
            'update' => $update_list,
        ];
    }

    /**
     * 更新图片
     *
     * @param $type
     * @param $bangumi_id
     *
     * @return array|void
     *
     * @throws GuzzleException
     */
    public function update_images($type = 'single', $bangumi_id = '')
    {
        if ($type == 'single') {
            if ($bangumi_id == '') {
                return $this->error('番剧id为空');
            }
            $this->save_image($bangumi_id);
        } elseif ($type == 'all'){
            $lastest_bangumi_id = $this->bangumiModel->where('image', '<>', '')->orderBy('id', 'desc')->value('id');
            $bangumi_ids = $this->bangumiModel->where('id', '>=', $lastest_bangumi_id)->pluck('id')->take(500);
            if (!empty($bangumi_ids)) {
                foreach ($bangumi_ids as $bangumi_id) {
                    $this->save_image($bangumi_id);
                }
                UpdateBangumiImages::dispatch('all')->onQueue('update_image');
            }
        } elseif ($type == 'replenish') {
            $bangumi_ids = $this->bangumiModel->where('image', '')->pluck('id');
            foreach ($bangumi_ids as $bangumi_id) {
                $this->save_image($bangumi_id, true);
            }
        }
    }

    /**
     * 获取并存入图片地址
     *
     * @param $bangumi_id
     * @param bool $is_replenish
     *
     * @throws GuzzleException
     */
    protected function save_image($bangumi_id, $is_replenish = false): void
    {
        $translate_list = $this->bangumiTranslateModel->where('type', 3)->where('bangumi_id', $bangumi_id)->get();
        $image = '';
        foreach ($translate_list as $value) {
            $url = str_replace('{keywords}', urlencode($value['title']), $this->bangumi_search_api);
            $result = $this->guzzle->send_request($url);
            if (isset($result['list']) && !empty($result['list'])) {
                foreach ($result['list'] as $info) {
                    if ($info['name_cn'] == $value['title']) {
                        if (isset($info['images']['large'])) {
                            $image = $info['images']['large'];
                            break;
                        }
                    } elseif ($is_replenish) {
                        if (isset($info['images']['large'])) {
                            $image = $info['images']['large'];
                            break;
                        }
                    }
                }
            }
            if ($image != '') {
                break;
            }
        }
        if ($image != '') {
            $this->bangumiModel->where('id', $bangumi_id)->update(['image' => $image,]);
        }
    }
}
