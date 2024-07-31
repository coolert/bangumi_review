<?php

namespace App\Http\Services;

use App\Models\AnimeOffline;
use App\Models\BangumiImage;
use App\Models\Config;
use App\Models\DataItem;
use App\Models\DataSite;
use App\Tools\GuzzleRequest;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\File;
use MongoDB\BSON\UTCDateTime;

class MetaDataService extends BaseService
{
    /** 站点信息地址
     * @var string
     */
    protected static string $bangumi_site_path;
    /** 番剧数据地址
     * @var string
     */
    protected static string $bangumi_data_path;

    protected static string $anime_offline_path;

    public function __construct()
    {
        self::$bangumi_site_path = storage_path('bangumi_metadata/bangumi-data/site.json');
        self::$bangumi_data_path = storage_path('bangumi_metadata/bangumi-data/bangumi.json');
        self::$anime_offline_path = storage_path('bangumi_metadata/anime-offline/anime-offline-database/anime-offline-database-minified.json');
    }
    /** 检查更新
     * @param $package_name
     * @return void
     * @throws \Exception
     * @author Lv
     * @date 2024/7/22
     */
    public function npm_update($package_name): void
    {
        try {
            //检查是否安装
            $list_re = $this->shell_exec('npm list ' . $package_name);
            //检查是否安装
            if (str_contains($list_re['output'], 'empty')) {
                //安装
                $install_re = $this->shell_exec('npm install ' . $package_name .'@0.3.110');
                //提取文件
                $data_str = file_get_contents(base_path('node_modules/' . $package_name . '/dist/data.json'));
                $data = json_decode($data_str, true);
                //存入站点数据
                file_put_contents(self::$bangumi_site_path, json_encode($data['siteMeta']));
                //存入动漫数据
                file_put_contents(self::$bangumi_data_path, json_encode($data['items']));
                //存入数据库
                $this->data_init();
                return;
            }
            //检查更新
            $check_re = $this->shell_exec('npm outdated ' . $package_name);
            if (empty($check_re['output'])) {
                //无须更新
                return;
            }
            $version_arr = explode('  ', explode("\n", $check_re['output'])[1]);
            if ($version_arr['1'] != $version_arr['3']) {
                //更新
                $update_re = $this->shell_exec('npm update ' . $package_name);
                if (str_contains($update_re['output'], 'changed 1 package')) {
                    //更新成功
                    //提取文件
                    $data_str = file_get_contents(base_path('node_modules/' . $package_name . '/dist/data.json'));
                    $data = json_decode($data_str, true);
                    //存入新站点数据
                    file_put_contents(storage_path('bangumi_metadata/bangumi-data/site.json'), json_encode($data['siteMeta']));
                    //存入新动漫数据
                    file_put_contents(storage_path('bangumi_metadata/bangumi-data/bangumi.json'), json_encode($data['items']));
                }
            }
        } catch (\Exception $exception) {
            if (str_contains($exception->getMessage(), 'New major version of npm available!')) {
                //如果是npm需要更新，则忽略，继续执行
                $this->npm_update($package_name);
            } else {
                throw new \Exception($exception->getMessage());
            }
        }
    }

    /** 初始化动漫离线数据
     * @author Lv
     * @date 2024/7/29
     */
    public function anime_offline_init()
    {
        $json_data = File::get(self::$anime_offline_path);
        $data = json_decode($json_data, true)['data'];
        $animeOfflineModel = new AnimeOffline();
        $animeOfflineModel->truncate();
        $animeOfflineModel->insert($data);
    }

    /** 初始化表数据
     * @author Lv
     * @date 2024/7/29
     */
    public function data_init(): void
    {
        $site_data = json_decode(File::get(self::$bangumi_site_path), true);
        $site_list = [];
        foreach ($site_data as $key => $value) {
            $value['name'] = $key;
            $site_list[] = $value;
        }
        $dataSiteModel = new DataSite();
        //清空站点数据
        $dataSiteModel->truncate();
        $dataSiteModel->insert($site_list);
        $item_data = json_decode(File::get(self::$bangumi_data_path), true);
        $dataItemModel = new DataItem();
        //清空动漫数据
        $dataItemModel->truncate();
        foreach ($item_data as &$value) {
            $value['begin'] = $this->trans_date($value['begin']);
            if (!empty($value['end'])) {
                $value['end'] = $this->trans_date($value['end']);
            }
        }
        $dataItemModel->insert($item_data);
    }

    /** 将SO 8601 日期字符串转换为 UTCDateTime 对象
     * @param $dateString
     * @return UTCDateTime
     * @throws \Exception
     * @author Lv
     * @date 2024/7/30
     */
    public function trans_date($dateString)
    {
        // ISO 8601 日期字符串
        // 创建 DateTime 对象并设置为 UTC 时区
        $dateTime = new \DateTime($dateString, new \DateTimeZone('UTC'));
        // 获取 Unix 时间戳并转换为毫秒
        $timestamp = $dateTime->getTimestamp() * 1000;
        // 创建 UTCDateTime 对象
        return new UTCDateTime($timestamp);
    }

    /** 更新番剧信息
     * @param $id
     * @param $subject_id
     * @param $data
     * @throws GuzzleException
     * @author Lv
     * @date 2024/7/26
     */
    public function update_bangumi_info ($id, $subject_id, $data)
    {
        if (empty($data)) {
            $info = $this->get_subject_info($subject_id);
            if (empty($info['images'])) {
                $image = '';
            }else{
                $image = $info['images']['common'];
            }
            $bangumiImageModel = new BangumiImage();
            $bangumiImageModel->create([
                'subject_id' => $subject_id,
                'summary' => $info['summary'],
                'image' => $image,
            ]);
            $data = [
                'summary' => $info['summary'],
                'image' => $image,
            ];
        }
        $dataItemModel = new DataItem();
        $dataItemModel->where('_id', $id)->update($data);
    }

    /** 从番组计划api获取番剧信息
     * @param $subject_id
     * @return mixed
     * @throws GuzzleException
     * @author Lv
     * @date 2024/7/25
     */
    public function get_subject_info($subject_id)
    {
        $configModel = new Config();
        $guzzle = new GuzzleRequest();
        $headers = json_decode($configModel->where('title', 'bangumi_api')->value('content'), true);
        $url = 'https://api.bgm.tv/v0/subjects/' . $subject_id;
        return $guzzle->send_request($url, 'GET', [], 'FORM', $headers);
    }

    /** 执行命令
     * @param $command
     * @return array|void
     * @throws \Exception
     * @author Lv
     * @date 2024/7/22
     */
    public function shell_exec($command)
    {
        // 定义描述符规范
        $descriptorspec = [
            0 => ["pipe", "r"],  // 标准输入，子进程从此管道读取
            1 => ["pipe", "w"],  // 标准输出，子进程向此管道写入
            2 => ["pipe", "w"]   // 标准错误，子进程向此管道写入
        ];
        // 启动进程
        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // 向子进程写入输入（如果需要）
            // fwrite($pipes[0], "input data");
            fclose($pipes[0]);

            // 读取子进程的输出
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // 读取子进程的错误输出
            $error_output = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // 关闭进程并获取返回值
            $return_value = proc_close($process);

            if (!empty($error_output)) {
                // 处理错误输出
                throw new \Exception($error_output);
            } else {
                return ['output' => $output, 'return_value' => $return_value];
            }
        } else {
            echo "Failed to open process.";
        }
    }
}
