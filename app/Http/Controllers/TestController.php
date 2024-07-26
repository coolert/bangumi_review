<?php

namespace App\Http\Controllers;

use App\Http\Services\BangumiDataService;
use App\Http\Services\MetaDataService;
use App\Http\Services\TorrentService;
use App\Jobs\UpdateBangumiImages;
use App\Models\Bangumi;
use App\Models\BangumiSite;
use App\Models\DataItem;
use App\Models\DataSite;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TestController extends BaseController
{
    public function index(MetaDataService $dataService)
    {
        $dataService->compare_json();
        return $this->success('success');
//        $torrent = new TorrentService();
//        return $torrent->get_torrent_list('暗杀教室', TorrentService::RSS_URLS['mikan']);
    }
}
