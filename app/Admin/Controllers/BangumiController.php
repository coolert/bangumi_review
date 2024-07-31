<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Bangumi;
use App\Models\BangumiTranslate;
use App\Models\DataItem;
use App\Models\DataSite;
use Carbon\Carbon;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class BangumiController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Bangumi(), function (Grid $grid) {
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->column('title', '名称')->display(function () {
                return array_key_exists('zh-Hans', $this->titleTranslate) ? $this->titleTranslate['zh-Hans'][0] : $this->title;
            })->width(200);
            $grid->column('image')->display(function () {
                return !empty($this->image) ? $this->image : env('APP_URL') . '/storage/images/no_image.png';
            })->image()->width(300);
            $grid->column('type', '类型')->width(200);
            $grid->column('begin', '上映时间')->display(function () {
                return $this->begin->toDateTime()->format('Y-m-d');
            })->sortable();
            $grid->column('end', '完结时间')->display(function () {
                return empty($this->end) ? '' : $this->end->toDateTime()->format('Y-m-d');
            });
            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->where('番剧名称', function ($query) {
                    $query->where('titleTranslate.zh-Hans', 'like', "%{$this->input}%");
                })->width(3);
                $filter->where('年份', function ($query) {
                    $query->whereRaw([
                        '$expr' => [
                            '$eq' => [
                                ['$year' => '$begin'], intval($this->input)
                            ]
                        ]
                    ]);
                })->integer()->width(3);
                $filter->where('月', function ($query) {
                    $query->whereRaw([
                        '$expr' => [
                            '$eq' => [
                                ['$month' => '$begin'], intval($this->input)
                            ]
                        ]
                    ]);
                })->integer()->width(3);
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Bangumi(), function (Show $show) use ($id){
            $show->panel()
                ->tools(function ($tools) {
                    $tools->disableDelete();
                });
            $show->field('_id', 'ID');
            $show->field('image', '封面')->image();
            $show->field('title', '原始名称');
            $show->titleTranslate('番剧名称')->as(function ($item) {
                if (isset($item['zh-Hans'])) {
                    $str = '';
                    foreach ($item['zh-Hans'] as $value) {
                        $str .= $value . '、';
                    }
                    $str = rtrim($str, '、');
                } else {
                    $str = $this->title;
                }
                return $str;
            });
            $show->field('summary', '简介');
            $show->field('type', '类型');
            $show->field('lang', '语言');
            $show->field('officialSite', '官方网站')->link();
            $show->begin('上映时间')->as(function ($begin) {
                return $begin->toDateTime()->format('Y-m-d');
            });
            $show->end('完结时间')->as(function ($end) {
                return empty($end) ? '' : $end->toDateTime()->format('Y-m-d');
            });
            $sites = DataItem::where('_id', $id)->value('sites');
            foreach ($sites as $site) {
                $site_info = DataSite::where('name', $site['site'])->first()->toArray();
                $url = str_replace('{{id}}', $site['id'], $site_info['urlTemplate']);
                $show->field($site['site'], $site_info['title'])->as(function () use ($url) {
                    return $url;
                })->link();
            }
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Bangumi(), function (Form $form) {
            $form->tools(function (Form\Tools $tools) {
                $tools->disableDelete();
            });
            $form->display('_id', 'ID');
            $form->display('title', '标题');
            $form->display('titleTranslate','番剧名称')->with(function ($item) {
                if (isset($item['zh-Hans'])) {
                    $str = '';
                    foreach ($item['zh-Hans'] as $value) {
                        $str .= $value . '、';
                    }
                    $str = rtrim($str, '、');
                } else {
                    $str = $this->title;
                }
                return $str;
            });
            $form->text('image', '封面');
        });
    }

    /**
     * Year List.
     *
     * @return array
     */
    public function search_year()
    {
        $bangumiModel = new \App\Models\Bangumi();
        $year_group = $bangumiModel->select('search_year')->orderBy('search_year', 'desc')->groupBy('search_year')->get();
        $year_list = [];
        foreach ($year_group as $value) {
            $year_list[] = [
                'id' => $value['search_year'],
                'text' => $value['search_year'],
            ];
        }
        return $year_list;
    }
}
