<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\AnimeOffline;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class AnimeOfflineController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new AnimeOffline(), function (Grid $grid) {
            $grid->id('_id');
            $grid->column('picture', '封面')->image();
            $grid->column('title', '标题');
            $grid->column('type', '类型');
            $grid->column('episodes', '集数');
            $grid->column('status', '状态');
            $grid->column('animeSeason', '季度');
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
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
        return Show::make($id, new AnimeOffline(), function (Show $show) {
            $show->field('id');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new AnimeOffline(), function (Form $form) {
            $form->display('id');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
