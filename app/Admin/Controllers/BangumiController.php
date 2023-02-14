<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Bangumi;
use App\Models\BangumiTranslate;
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
            $grid->model()->with('translate');
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->column('id')->sortable();
            $grid->column('image')->image();
            $grid->column('translate', '名称')->display(function ($translate) {
                $name_info = [];
                foreach ($translate as $value) {
                    if ($value['type'] == 2 && empty($name_info)) {
                        $name_info = [
                            'type' => $value['type'],
                            'name' => $value['title'],
                        ];
                    } elseif ($value['type'] == 4 && (empty($name_info) || $name_info['type'] == 2)) {
                        $name_info = [
                            'type' => $value['type'],
                            'name' => $value['title'],
                        ];
                    } elseif ($value['type'] == 3) {
                        $name_info = [
                            'type' => $value['type'],
                            'name' => $value['title'],
                        ];
                    }
                }
                return empty($name_info) ? '无' : $name_info['name'];
            });
            $grid->column('title');
            $grid->column('type');
            $grid->column('lang')->display(function ($lang) {
                switch ($lang) {
                    case 'ja':
                        $language = '日语';
                        break;
                    case 'en':
                        $language = '英语';
                        break;
                    case 'zh-Hans':
                        $language = '简体中文';
                        break;
                    case 'zh-Hant':
                        $language = '繁体中文';
                        break;
                }
                return $language;
            });
            $grid->column('begin_search', '上映时间')->display(function ($begin_search) {
                return date('Y-m-d', $begin_search);
            });
            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->where('番剧名称', function ($query) {
                    $query->where('title', 'like', "%{$this->input}%")
                        ->orWhere(function ($query){
                            $query->whereHas('translate', function ($query) {
                                $query->where('title', 'like', "%{$this->input}%");
                            });
                        });
                })->width(3);
                $filter->equal('search_year', '年份')->select('api/search_year')->width(3);
                $filter->equal('search_month','月份')->select([
                    '01' => '01',
                    '02' => '02',
                    '03' => '03',
                    '04' => '04',
                    '05' => '05',
                    '06' => '06',
                    '07' => '07',
                    '08' => '08',
                    '09' => '09',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12',
                ])->width(3);
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
            $show->field('id');
            $show->field('image', '封面')->image();
            $show->field('title');
            $show->field('简中')->as(function () {
                $name_list = BangumiTranslate::where('bangumi_id', $this->id)->where('type',3)->pluck('title');
                return !empty($name_list) ? implode('、', $name_list->toArray()) : '';
            });
            $show->field('繁中')->as(function () {
                $name_list = BangumiTranslate::where('bangumi_id', $this->id)->where('type',4)->pluck('title');
                return !empty($name_list) ? implode('、', $name_list->toArray()) : '';
            });
            $show->field('英文')->as(function () {
                $name_list = BangumiTranslate::where('bangumi_id', $this->id)->where('type',2)->pluck('title');
                return !empty($name_list) ? implode('、', $name_list->toArray()) : '';
            });
            $show->field('日文')->as(function () {
                $name_list = BangumiTranslate::where('bangumi_id', $this->id)->where('type',1)->pluck('title');
                return !empty($name_list) ? implode('、', $name_list->toArray()) : '';
            });
            $show->field('type');
            $show->field('lang');
            $show->field('official_site');
            $show->field('comment');
            $show->field('begin_search', '上映时间')->as(function ($time) {
                return date('Y-m-d', $time);
            });
            $bangumiModel = new \App\Models\Bangumi();
            $site_info = $bangumiModel->where('id', $id)->with('site')->first();
            if (!empty($site_info['site'])) {
                foreach ($site_info['site'] as $value) {
                    $show->field($value['title'])->as(function () use ($value){
                        return str_replace('{{id}}', $value['pivot']['site_bangumi_id'], $value['url']);
                    })->link();
                }
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
            $form->display('id');
            $form->text('image');
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
