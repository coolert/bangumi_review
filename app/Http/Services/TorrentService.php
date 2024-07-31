<?php

namespace App\Http\Services;

class TorrentService extends BaseService
{
    public const RSS_URLS = [
        //动漫花园
        'anima_flower' => 'https://dmhy.b168.net/topics/rss/rss.xml?keyword={keywords}',
        //萌番组
        'anima_group' => 'https://bangumi.moe/rss/search/{keywords}',
    ];
    /**
     * 蜜柑计划URL
     */
    public const MIKAN_URL = [
        //搜索地址
        'search_url' => 'https://mikanani.me/Home/Search?searchstr={keywords}',
        //RSS地址
        'rss_url' => 'https://mikanani.me/RSS/Search?',
    ];
    public function __construct()
    {
        //
    }

    /**
     * 获取下载信息列表
     *
     * @param $key_words
     * @param $url
     *
     * @return array
     */
    public function get_torrent_list($key_words, $url)
    {
        $path = str_replace('{keywords}', urlencode($key_words), $url);
        return $this->analysis_rss($path);
    }

    /**
     * 解析蜜柑RSS订阅
     *
     * @param string $rss_subscribe_url
     *
     * @return array
     */
    public function analysis_mikan_rss(string $rss_subscribe_url): array
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
                if ($tag == "ENCLOSURE") {
                    $list[$item_key]['url'] = $v['attributes']['URL'];
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
        $param_str = '';
        if (!empty($keywords)) {
            $param_str .= 'searchstr=' . urlencode($keywords);
        }
        if (!empty($bangumi_id)) {
            if (str_ends_with($param_str, '?')) {
                $param_str .= 'bangumiId=' . $bangumi_id;
            }else{
                $param_str .= '&bangumiId=' . $bangumi_id;

            }
        }
        if (!empty($group_id)) {
            if (str_ends_with($param_str, '?')) {
                $param_str .= 'subgroupid=' . $group_id;
            }else{
                $param_str .= '&subgroupid=' . $group_id;

            }
        }
        $url = self::MIKAN_URL['rss_url'] . $param_str;
        return $this->analysis_mikan_rss($url);
    }
}
