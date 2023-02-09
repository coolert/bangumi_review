<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    /**
     * 新增站点数据
     *
     * @param array $siteMeta
     */
    public function insert_site_meta(array $siteMeta): void
    {
        $site_data = [];
        foreach ($siteMeta as $site => $site_info) {
            $site_data[] = [
                'site' => $site,
                'title' => $site_info['title'],
                'url' => $site_info['urlTemplate'],
                'regions' => isset($site_info['regions']) ? json_encode($site_info['regions']) : '',
                'type' => $site_info['type'],
            ];
        }
        if (!empty($siteMeta)) $this->insert($site_data);
    }
}
