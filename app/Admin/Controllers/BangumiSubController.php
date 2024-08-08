<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\BangumiSubscribe;
use App\Http\Controllers\Controller;
use App\Http\Services\VideoSourceService;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Form;
use Illuminate\Http\Request;

class BangumiSubController extends Controller
{
    public function index(Request $request,Content $content)
    {
        $params = $request->all();
        return $content->title('番剧订阅')
            ->body(new Card(BangumiSubscribe::make()->payload(['params' => $params])));
    }

    protected function form($list)
    {

    }

    public function store(Request $request)
    {
        dd($request->all());
    }


}