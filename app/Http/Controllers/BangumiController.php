<?php

namespace App\Http\Controllers;

use App\Http\Services\BangumiDataService;
use Illuminate\Http\Request;

class BangumiController extends BaseController
{
    /**
     * Database Init.
     *
     * @param BangumiDataService $bangumiDataService
     * @return array
     * @author Lv
     * @date 2023/2/7
     */
    public function database_init(BangumiDataService $bangumiDataService): array
    {
        $bangumiDataService->data_init();
        return $this->success();
    }
}
