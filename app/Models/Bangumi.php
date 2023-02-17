<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bangumi extends Model
{
    use HasFactory;

    protected $table = 'bangumi';
    public $timestamps= false;

    public function translate()
    {
        return $this->hasMany(BangumiTranslate::class, 'bangumi_id', 'id');
    }

    public function site()
    {
        return $this->belongsToMany(Site::class, 'bangumi_site', 'bangumi_id', 'site_id')->withPivot('site_bangumi_id','begin');
    }

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
        $sort_sites = $this->get_sort_sites();
        foreach ($data as $value) {
            $bangumi_info = $this->organize_bangumi_info($value);
            $bangumi_id = $this->insertGetId($bangumi_info);
            if (isset($value['sites']) && !empty($value['sites'])) {
                $bangumi_site_list = $this->organize_bangumi_site($value['sites'], $bangumi_site_list, $bangumi_id, $sort_sites);
            }
            if (isset($value['titleTranslate']) && !empty($value['titleTranslate'])) {
                $translate_list = $this->organize_bangumi_translate($value['titleTranslate'], $translate_list, $bangumi_id);
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
        $sort_sits = $this->get_sort_sites();
        foreach ($data as $key => $value) {
            $bangumi_info = $this->organize_bangumi_info($value);
            $bangumi_id = $this->where('title', $bangumi_info['title'])->value('id');
            $this->where('id', $bangumi_id)->update($bangumi_info);
            if (isset($value['sites']) && !empty($value['sites'])) {
                $bangumi_site_list = $this->organize_bangumi_site($value['sites'], [], $bangumi_id, $sort_sits);
                if (!empty($bangumi_site_list)) {
                    $bangumiSiteModel->where('bangumi_id', $bangumi_id)->delete();
                    $chunk_site = array_chunk($bangumi_site_list, 1000);
                    foreach ($chunk_site as $site_data) {
                        $bangumiSiteModel->insert($site_data);
                    }
                }
            }
            if (isset($value['titleTranslate']) && !empty($value['titleTranslate'])) {
                $translate_list = $this->organize_bangumi_translate($value['titleTranslate'], [], $bangumi_id);
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

    /**
     * Organize bangumi data to insert database.
     *
     * @param array $value
     *
     * @return array
     */
    protected function organize_bangumi_info(array $value): array
    {
        $begin = empty($value['begin']) ? 0 : strtotime($value['begin']);
        if(isset($value['broadcast'])) {
            $broadcast_begin = strtotime(substr($value['broadcast'], 2, strlen($value['broadcast']) - 6));
            $broadcast_frequency = substr($value['broadcast'], -3);
        } else {
            $broadcast_begin = 0;
            $broadcast_frequency = '';
        }
        $begin_search = $broadcast_begin != 0 ? $broadcast_begin : $begin;
        return [
            'title' => $value['title'],
            'type' => $value['type'],
            'lang' => $value['lang'],
            'official_site' => $value['officialSite'],
            'begin' => $begin,
            'end' => empty($value['end']) ? 0 : strtotime($value['end']),
            'broadcast' => $value['broadcast'] ?? '',
            'broadcast_begin' => $broadcast_begin,
            'broadcast_frequency' => $broadcast_frequency,
            'comment' => $value['comment'] ?? '',
            'begin_search' => $begin_search,
            'search_year' => date('Y', $begin_search),
            'search_month' => date('m', $begin_search),
        ];
    }

    /**
     * Organize site.
     *
     * @param $sites
     * @param $bangumi_site_list
     * @param $bangumi_id
     * @param $sort_sites
     *
     * @return array
     */
    protected function organize_bangumi_site($sites, $bangumi_site_list, $bangumi_id, $sort_sites): array
    {
        foreach ($sites as $site_info) {
            $bangumi_site_list[] = [
                'site_id' => $sort_sites[$site_info['site']],
                'site_bangumi_id' => $site_info['id'] ?? '',
                'bangumi_id' => $bangumi_id,
                'url' => $site_info['url'] ?? '',
                'begin' => isset($site_info['begin']) ? (empty($site_info['begin']) ? 0 : strtotime($site_info['begin'])) : 0,
            ];
        }
        return $bangumi_site_list;
    }

    /**
     * Organize translate.
     *
     * @param $titleTranslate
     * @param $translate_list
     * @param $bangumi_id
     *
     * @return array
     */
    protected function organize_bangumi_translate($titleTranslate, $translate_list, $bangumi_id): array
    {
        foreach ($titleTranslate as $lang => $name_list) {
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
        return $translate_list;
    }

    /**
     * Get sorted sites array.
     *
     * @return array
     */
    protected function get_sort_sites(): array
    {
        $siteModel = new Site();
        $site_list = $siteModel->select('id', 'site')->get()->toArray();
        $sort_sites = [];
        foreach ($site_list as $value) {
            $sort_sites[$value['site']] = $value['id'];
        }
        return $sort_sites;
    }
}
