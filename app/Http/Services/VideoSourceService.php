<?php

namespace App\Http\Services;

use App\Tools\GuzzleRequest;

class VideoSourceService extends BaseService
{
    public const SITE_INFO = [
        'mikan' => [
            'url_type' => 'torrent',
            'source_type' => ['bangumi']
        ],
        'moe' => [
            'url_type' => 'torrent',
            'source_type' => ['bangumi']
        ],
        'dmhy' => [
            'url_type' => 'magnet',
            'source_type' => ['bangumi', 'comic', 'music', 'jp_drama', 'game', 'tokusatsu']
        ],
        'nyaa' => [
            'url_type' => 'torrent',
            'source_type' => ['bangumi']
        ],
        'acgnx' => [
            'url_type' => 'magnet',
            'source_type' => ['bangumi', 'comic', 'music', 'jp_drama', 'game', 'tokusatsu']
        ]
    ];
    /**
     * 蜜柑计划URL
     */
    public const MIKAN_URL = [
        //搜索地址
        'search_url' => 'https://mikanani.me/Home/Search?searchstr={keywords}',
        //RSS地址(关键词搜索)
        'rss_search_url' => 'https://mikanani.me/RSS/Search?',
        //Rss地址(番剧id搜索)
        'rss_id_url' => 'https://mikanani.me/RSS/Bangumi?',
    ];

    /**
     * 萌番组URL
     */
    public const MOE_URL = [
        //标签搜索
        'tag_url' => 'https://bangumi.moe/api/tag/search',
        //字幕组
        'group_url' => 'https://bangumi.moe/api/tag/team',
        //常用标签
        'common_tag_url' => 'https://bangumi.moe/api/tag/common',
        //RSS地址
        'rss_url' => 'https://bangumi.moe/rss/tags/',
    ];

    /**
     * 动漫花园URL
     */
    public const DMHY_URL = [
        'rss_url' => 'https://share.dmhy.org/topics/rss/rss.xml?keyword={keywords}',
    ];

    /**
     * Nyaa URL
     */
    public const NYAA_URL = [
        'rss_url' => 'https://nyaa.si/?page=rss&q={keywords}',
    ];

    /**
     * 末日动漫URL
     */
    public const ACGNX_URL = [
        'rss_url' => 'https://share.acgnx.se/rss.xml?keyword={keywords}',
    ];

    /**
     * 解析蜜柑RSS订阅
     *
     * @param string $rss_subscribe_url
     * @param string $url_label
     * @return array
     */
    public function analysis_mikan_rss(string $rss_subscribe_url, string $url_label = 'ENCLOSURE'): array
    {
        $text = '';
        $fp = fopen($rss_subscribe_url, 'r') or die('无法打开该网站 Feed');
        while (!feof($fp)) {
            $text .= fgets($fp, 4096);
        }
        fclose($fp);
        //建立一个 XML 解析器
        $parser = xml_parser_create();
        //xml_parser_set_option -- 为指定 XML 解析进行选项设置
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        //xml_parse_into_struct -- 将 XML 数据解析到数组$values 中
        $values = [];
        xml_parse_into_struct($parser, $text, $values, $idx);
        //xml_parser_free -- 释放指定的 XML 解析器
        xml_parser_free($parser);
        $list = [];
        $item_key = -1;
        foreach ($values as $v) {
            $tag = $v["tag"];
            $type = $v["type"];
            if ($tag == "ITEM" && $type == "open") {
                $item_key++;
            }
            //仅读取 item 标签中的内容
            if ($item_key >= 0) {
                if ($tag == "TITLE") {
                    $list[$item_key]['title'] = $v["value"];
                }
                if ($url_label == 'ENCLOSURE') {
                    if ($tag == "ENCLOSURE") {
                        $list[$item_key]['url'] = $v['attributes']['URL'];
                    }
                } else {
                    if ($tag == "LINK") {
                        $list[$item_key]['url'] = $v["value"];
                    }
                }
            }
        }
        return $list;
    }

    /** 根据关键词搜索蜜柑计划字幕组列表与番剧列表
     * @param $keywords
     * @return array[]
     * @author Lv
     * @date 2024/7/31
     */
    public function mikan_search($keywords)
    {
        $url = str_replace('{keywords}', urlencode($keywords), self::MIKAN_URL['search_url']);
        $content = file_get_contents($url);
        $doc = new \DOMDocument();
        @$doc->loadHTML($content);
        $xpath = new \DOMXPath($doc);
        //字幕组信息
        $groupElements = $xpath->query('//ul[contains(@class, "list-unstyled")]');
        $bangumiElements = $xpath->query('//ul[contains(@class, "list-inline an-ul")]');
        $subtitle_group = [];
        foreach ($groupElements[0]->getElementsByTagName('li') as $key => $li) {
            if ($key == 0) {
                continue;
            }
            $aElements = $li->getElementsByTagName('a')[0];
            $subtitle_group[] = [
                'title' => $aElements->nodeValue,
                'group_id' => $aElements->getAttribute('data-subgroupid'),
            ];
        }
        $bangumi_list = [];
        foreach ($bangumiElements[0]->getElementsByTagName('li') as $k => $v) {
            $bangumi_list[] = [
                'title' => $xpath->query('.//div[contains(@class, "an-text")]', $v)[0]->nodeValue,
                'bangumi_id' => ltrim($v->getElementsByTagName('a')[0]->getAttribute('href'), '/Home/Bangumi/') ,
            ];
        }
        return ['group_list' => $subtitle_group, 'bangumi_list' => $bangumi_list];
    }

    /** 搜索蜜柑计划RSS数据
     * @param string $keywords
     * @param string $bangumi_id
     * @param string $group_id
     * @return array
     * @author Lv
     * @date 2024/7/31
     */
    public function mikan_rss_search(string $keywords = '', string $bangumi_id = '', string $group_id = ''): array
    {
        if (!empty($keywords)) {
            $url = self::MIKAN_URL['rss_search_url'] . 'searchstr=' . urlencode($keywords);
        }else{
            $url = self::MIKAN_URL['rss_id_url'];
            if (!empty($bangumi_id)) {
                if (str_ends_with($url, '?')) {
                    $url .= 'bangumiId=' . $bangumi_id;
                }else{
                    $url .= '&bangumiId=' . $bangumi_id;
                }
            }
            if (!empty($group_id)) {
                if (str_ends_with($url, '?')) {
                    $url .= 'subgroupid=' . $group_id;
                }else{
                    $url .= '&subgroupid=' . $group_id;

                }
            }
        }
        $resource_list = $this->analysis_mikan_rss($url);
        $resource_group = [];
        foreach ($resource_list as $v) {
            $group_name = $this->get_group_name($v['title']);
            $resource_group[$group_name][] = $v;
        }
        return $resource_group;
    }

    /** 解析字幕组
     * @param $title
     * @return string
     * @throws \Exception
     * @author Lv
     * @date 2024/8/8
     */
    public function get_group_name($title): string
    {
        $pos_one = mb_strpos($title, ']');
        $pos_two = mb_strpos($title, '】');
        $pos_three = mb_strpos($title, '字幕组');
        if ($pos_one !== false && $pos_two !== false) {
            $end_pos = min($pos_one - 1, $pos_two - 1);
            return mb_substr($title, 1, $end_pos, 'utf-8');
        } else if ($pos_one !== false) {
            $end_pos = $pos_one - 1;
            return mb_substr($title, 1, $end_pos, 'utf-8');
        } else if ($pos_two !== false) {
            $end_pos = $pos_two - 1;
            return mb_substr($title, 1, $end_pos, 'utf-8');
        } else if ($pos_three !== false) {
            $end_pos = $pos_three;
            return mb_substr($title, 0, $end_pos, 'utf-8');
        } else {
            throw new \Exception('字幕组名称解析失败');
        }
    }

    /** moe标签搜索
     * @param $search
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Lv
     * @date 2024/8/1
     */
    public function moe_tag_search($search): array
    {
        $guzzle = new GuzzleRequest();
        $request_data = [
            'keywords' => true,
            'multi' => true,
            'name' => $search
        ];
        $re = $guzzle->send_request(self::MOE_URL['tag_url'], 'POST', $request_data, 'JSON');
        $data = [];
        if ($re['success'] && $re['found']) {
            foreach ($re['tag'] as $v) {
                $data[] = [
                    'id' => $v['_id'],
                    'title' => $v['locale']['zh_cn']
                ];
            }
        }
        return $data;
    }

    /** moe通用标签/字幕组标签
     * @param $type
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Lv
     * @date 2024/8/1
     */
    public function moe_common_tag($type = 'common'): array
    {
        $url = $type == 'common' ? self::MOE_URL['common_tag_url'] : self::MOE_URL['group_url'];
        $guzzle = new GuzzleRequest();
        $re = $guzzle->send_request($url);
        $data = [];
        foreach ($re as $v) {
            $data[] = [
                'id' => $v['_id'],
                'title' => $v['name']
            ];
        }
        return $data;
    }

    /** moeRSS搜索
     * @param array $tag_ids
     * @return array
     * @throws \Exception
     * @author Lv
     * @date 2024/8/1
     */
    public function moe_rss_search(array $tag_ids): array
    {
        if (empty($tag_ids)) {
            throw new \Exception('标签不能为空');
        }
        $url = self::MOE_URL['rss_url'];
        foreach ($tag_ids as $tag_id) {
            $url .= $tag_id . '+';
        }
        $url = rtrim($url, '+');
        return $this->analysis_mikan_rss($url);
    }

    /** dmhyRSS搜索
     * @param $keywords
     * @return array
     * @throws \Exception
     * @author Lv
     * @date 2024/8/2
     */
    public function dmhy_rss_search($keywords)
    {
        if (empty($keywords)) {
            throw new \Exception('关键字不能为空');
        }
        $url = str_replace('{keywords}', urlencode($keywords), self::DMHY_URL['rss_url']);
        return $this->analysis_mikan_rss($url);
    }

    /** nyaaRSS搜索
     * @param $keywords
     * @return array
     * @throws \Exception
     * @author Lv
     * @date 2024/8/2
     */
    public function nyaa_rss_search($keywords)
    {
        if (empty($keywords)) {
            throw new \Exception('关键字不能为空');
        }
        $url = str_replace('{keywords}', urlencode($keywords), self::NYAA_URL['rss_url']);
        return $this->analysis_mikan_rss($url,'LINK');
    }

    /** acgnxRSS搜索
     * @param $keywords
     * @return array
     * @throws \Exception
     * @author Lv
     * @date 2024/8/2
     */
    public function acgnx_rss_search($keywords)
    {
        if (empty($keywords)) {
            throw new \Exception('关键字不能为空');
        }
        $url = str_replace('{keywords}', urlencode($keywords), self::ACGNX_URL['rss_url']);
        return $this->analysis_mikan_rss($url);
    }
}
