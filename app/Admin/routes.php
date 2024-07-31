<?php

use App\Admin\Controllers\BangumiController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    //番剧管理
    $router->resource('bangumi', 'BangumiController');
    //番剧数据库
    $router->resource('anime_offline', 'AnimeOfflineController');
    $router->get('api/search_year', [BangumiController::class,'search_year']);
});
