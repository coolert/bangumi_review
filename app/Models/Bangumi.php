<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bangumi extends Model
{
    use HasFactory;

    protected $table = 'bangumi';

    /**
     * Insert bangumi data.
     *
     * @param $data
     */
    public function insert_items($data)
    {
        $bangumiSiteModel = new BangumiSite();
        $bangumiTranslateModel = new BangumiTranslate();
        $bangumi_site_list = [];
        $translate_list = [];
        foreach ($data as $value) {
            $begin_search = 0;
            if (!empty($value['begin'])) {
                $begin_search = strtotime($value['begin']);
            }
            if(isset($value['broadcast'])) {
                $begin_search = $broadcast_begin = strtotime(substr($value['broadcast'], 2, strlen($value['broadcast']) - 6));
                $broadcast_frequency = substr($value['broadcast'], -1, 3);
            } else {
                $broadcast_begin = 0;
                $broadcast_frequency = '';
            }
            $bangumi_list = [
                'title' => $value['title'],
                'type' => $value['type'],
                'lang' => $value['lang'],
                'official_site' => $value['officialSite'],
                'begin' => empty($value['begin']) ? 0 : strtotime($value['begin']),
                'end' => empty($value['end']) ? 0 : strtotime($value['end']),
                'broadcast' => $value['broadcast'] ?? '',
                'broadcast_begin' => $broadcast_begin,
                'broadcast_frequency' => $broadcast_frequency,
                'comment' => $value['comment'] ?? '',
                'begin_search' => $begin_search,
            ];
            $bangumi_id = $this->insertGetId($bangumi_list);
            if (isset($value['sites']) && !empty($value['sites'])) {
                foreach ($value['sites'] as $site_info){
                    if (empty($site_info['site'])) {
                        dd($site_info);
                    }
                    $bangumi_site_list[] = [
                        'site' => $site_info['site'],
                        'site_bangumi_id' => $site_info['id'] ?? '',
                        'bangumi_id' => $bangumi_id,
                        'url' => $site_info['url'] ?? '',
                        'begin' => isset($site_info['begin']) ? (empty($site_info['begin']) ? 0 : strtotime($site_info['begin'])) : 0,
                    ];
                }
            }
            if (isset($value['titleTranslate']) && !empty($value['titleTranslate'])) {
                foreach ($value['titleTranslate'] as $lang => $name_list) {
                    $type = match ($lang) {
                        'ja' => 1,
                        'en' => 2,
                        'zh-Hans' => 3,
                        'zh-Hant' => 4,
                        default => 0,
                    };
                    foreach ($name_list as $name) {
                        $translate_list[] = [
                            'type' => $type,
                            'title' => $name,
                            'bangumi_id' => $bangumi_id,
                        ];
                    }
                }
            }
        }
        if (!empty($bangumi_site_list)) {
            $chunk_site = array_chunk($bangumi_site_list, 1000);
            foreach ($chunk_site as $site_data) {
                $bangumiSiteModel->insert($site_data);
            }
        }
        if (!empty($translate_list)) {
            $chunk_translate = array_chunk($translate_list, 1000);
            foreach ($chunk_translate as $translate_data) {
                $bangumiTranslateModel->insert($translate_data);
            }
        }
    }

    /**
     * Update bangumi data.
     *
     * @param $data
     */
    public function update_items($data)
    {
        $bangumiSiteModel = new BangumiSite();
        $bangumiTranslateModel = new BangumiTranslate();
        foreach ($data as $key => $value) {
            $begin_search = 0;
            if (!empty($value['begin'])) {
                $begin_search = strtotime($value['begin']);
            }
            if(isset($value['broadcast'])) {
                $begin_search = $broadcast_begin = strtotime(substr($value['broadcast'], 2, strlen($value['broadcast']) - 6));
                $broadcast_frequency = substr($value['broadcast'], -1, 3);
            } else {
                $broadcast_begin = 0;
                $broadcast_frequency = '';
            }
            $bangumi_list = [
                'title' => $value['title'],
                'type' => $value['type'],
                'lang' => $value['lang'],
                'official_site' => $value['officialSite'],
                'begin' => empty($value['begin']) ? 0 : strtotime($value['begin']),
                'end' => empty($value['end']) ? 0 : strtotime($value['end']),
                'broadcast' => $value['broadcast'] ?? '',
                'broadcast_begin' => $broadcast_begin,
                'broadcast_frequency' => $broadcast_frequency,
                'comment' => $value['comment'] ?? '',
                'begin_search' => $begin_search,
            ];
            $bangumi_id = $key + 1;
            $this->where('id', $bangumi_id)->update($bangumi_list);
            if (isset($value['sites']) && !empty($value['sites'])) {
                foreach ($value['sites'] as $site_info) {
                    $bangumi_site_list[] = [
                        'site' => $site_info['site'],
                        'site_bangumi_id' => $site_info['id'] ?? '',
                        'bangumi_id' => $bangumi_id,
                        'url' => $site_info['url'] ?? '',
                        'begin' => isset($site_info['begin']) ? (empty($site_info['begin']) ? 0 : strtotime($site_info['begin'])) : 0,
                    ];
                }
                if (!empty($bangumi_site_list)) {
                    $bangumiSiteModel->where('bangumi_id', $bangumi_id)->delete();
                    $chunk_site = array_chunk($bangumi_site_list, 1000);
                    foreach ($chunk_site as $site_data) {
                        $bangumiSiteModel->insert($site_data);
                    }

                }
            }
            if (isset($value['titleTranslate']) && !empty($value['titleTranslate'])) {
                foreach ($value['titleTranslate'] as $lang => $name_list) {
                    $type = match ($lang) {
                        'ja' => 1,
                        'en' => 2,
                        'zh-Hans' => 3,
                        'zh-Hant' => 4,
                        default => 0,
                    };
                    foreach ($name_list as $name) {
                        $translate_list[] = [
                            'type' => $type,
                            'title' => $name,
                            'bangumi_id' => $bangumi_id,
                        ];
                    }
                }
                if (!empty($translate_list)) {
                    $bangumiTranslateModel->where('bangumi_id', $bangumi_id)->delete();
                    $chunk_translate = array_chunk($translate_list, 1000);
                    foreach ($chunk_translate as $translate_data) {
                        $bangumiTranslateModel->insert($translate_data);
                    }
                }
            }
        }
    }
}
