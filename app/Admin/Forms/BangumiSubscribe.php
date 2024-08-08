<?php

namespace App\Admin\Forms;

use App\Http\Services\VideoSourceService;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;
use Illuminate\Http\Request;

class BangumiSubscribe extends Form implements LazyRenderable
{
    use LazyWidget;

    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        if ($input['type_0'] == 'search') {
            if (empty($input['title'])) {
                return $this->response()->error('番剧名不能为空');
            }
            if (!empty($this->payload['params']['id'])) {
                return $this->response()->redirect('bangumi_subscribe/form?id=' . $this->payload['params']['id'] .'&title=' . $input['title']);
            }
            return $this->response()->redirect('bangumi_subscribe/form?title=' . $input['title']);
        }
        $suffix = 0;
        $sub_ids = [];
        $download_ids = [];
        while (isset($input['type_' . $suffix])) {
            if ($input['type_' . $suffix] == 'subscribe') {
                $sub_ids[] = $suffix;
            }
            if ($input['type_' . $suffix] == 'download') {
                $download_ids[] = $suffix;
            }
            $suffix++;
        }
        //处理订阅
        foreach ($sub_ids as $sub_id) {
            $data = $this->format_input($input, $sub_id);
        }
        //处理下载
        foreach ($download_ids as $download_id) {
            $data = $this->format_input($input, $download_id);
        }
//         return $this->response()->error('Your error message.');

        return $this
            ->response()
            ->success('操作成功')
            ->redirect('bangumi_subscribe/form');
//				->refresh();
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $videoSourceService = new VideoSourceService();
        if (!empty($this->payload['params']['title']) || !empty($this->payload['params']['id'])) {
            $group_info = $videoSourceService->mikan_search($this->payload['params']['title']);
            if (!empty($group_info['bangumi_list'])) {
                foreach ($group_info['bangumi_list'] as $key => $value) {
                    $resource_group = $videoSourceService->mikan_rss_search('', $value['bangumi_id']);
                    $this->generate_tab($value['title'], $resource_group, $key);
                }
            }else{
                $resource_group = $videoSourceService->mikan_rss_search($this->payload['params']['title']);
                $this->generate_tab($this->payload['params']['title'], $resource_group, '0');
            }
        }else{
            $this->text('title', '搜索番剧名');
            $this->hidden('type_0')->value('search');
        }
    }

    /** 生成标签
     * @param $tab_title
     * @param $resource_group
     * @param $suffix
     * @return BangumiSubscribe
     * @author Lv
     * @date 2024/8/8
     */
    protected function generate_tab($tab_title, $resource_group, $suffix): BangumiSubscribe
    {
        return $this->tab($tab_title, function () use ($resource_group, $suffix) {
            if (isset($this->payload['params']['id'])) {
                $this->hidden('bangumi_id')->value($this->payload['params']['id']);
            }
            if ($suffix == '0') {
                $this->text('title', '搜索番剧名')->value($this->payload['params']['title']);
                $this->radio('type_' . $suffix, '操作类型')->options(['subscribe' => '订阅', 'download' => '下载', 'search' => '重新搜索', 'none' => '无操作'])->default('none');
            }else{
                $this->radio('type_' . $suffix, '操作类型')->options(['subscribe' => '订阅', 'download' => '下载', 'none' => '无操作'])->default('none');
            }
            $group_list = array_keys($resource_group);
            $group_form = $this->select('group_' . $suffix, '字幕组');
            foreach ($group_list as $group_id => $group) {
                $torrent_list = [];
                foreach ($resource_group[$group] as $v) {
                    $torrent_list[$v['url']] = $v['title'];
                }
                $group_form->when($group_id, function (Form $form) use ($torrent_list, $suffix, $group_id) {
                    $form->checkbox('torrent_' . $group_id . '_' . $suffix, '资源信息')->options($torrent_list);
                });
            }
            $group_form->options($group_list);
            $this->text('torrent_keywords_' . $suffix, '资源关键字筛选');
            $this->hidden('group_list_' . $suffix)->value(json_encode($group_list));
        });
    }

    /** 整理单个番剧请求数据
     * @param $input
     * @param $suffix
     * @return array
     * @author Lv
     * @date 2024/8/8
     */
    protected function format_input($input, $suffix): array
    {
        $name_list = ['type', 'group', 'torrent_keywords', 'group_list'];
        $data = [];
        foreach ($name_list as $value) {
            $data[$value] = $input[$value . '_' . $suffix];
        }
        $data['group_list'] = json_decode($data['group_list'], true);
        $data['group_name'] = $data['group_list'][$data['group']];
        $data['torrent'] = $input['torrent' . '_' . $data['group'] . '_' . $suffix];
        return $data;
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [
            'name'  => 'John Doe',
            'email' => 'John.Doe@gmail.com',
        ];
    }
}
