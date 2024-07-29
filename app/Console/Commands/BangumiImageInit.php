<?php

namespace App\Console\Commands;

use App\Http\Services\MetaDataService;
use App\Models\BangumiImage;
use App\Models\DataItem;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class BangumiImageInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get bangumi image from internet';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws GuzzleException
     */
    public function handle(): int
    {
        $service = new MetaDataService();
        $bangumiImageModel = new BangumiImage();
        $list = $bangumiImageModel->get()->toArray();
        $data = [];
        foreach ($list as $value) {
            $data[$value['subject_id']] = [
                'summary' => $value['summary'],
                'image' => $value['image'],
            ];
        }
        $no_site_item_num = 0;
        $this->info('初始化数据中...');
        $this->withProgressBar(DataItem::where('summary', 'exists', false)->get(), function ($item) use ($service, &$no_site_item_num, $data) {
            $subject_id = '';
            foreach ($item->sites as $site) {
                if ($site['site'] == 'bangumi') {
                    $subject_id = $site['id'];
                    break;
                }
            }
            if ($subject_id != '') {
                $bangumi_info = [];
                if (array_key_exists($subject_id, $data)) {
                    $bangumi_info = $data[$subject_id];
                }
                $service->update_bangumi_info($item->_id, $subject_id, $bangumi_info);
            } else {
                $no_site_item_num += 1;
            }
        });
        $this->newLine();
        $this->info('无站点数据番剧个数: ' . $no_site_item_num);
        $this->info('初始化完成');
        return Command::SUCCESS;
    }
}
