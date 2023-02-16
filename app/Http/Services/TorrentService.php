<?php

namespace App\Http\Services;

class TorrentService extends BaseService
{
    public const RSS_URLS = [
        //动漫花园
        'anima_flower' => 'https://dmhy.b168.net/topics/rss/rss.xml?keyword={keywords}',
        //萌番组
        'anima_group' => 'https://bangumi.moe/rss/search/{keywords}',
        //蜜柑
        'mikan' => 'https://mikanani.me/RSS/Search?searchstr={keywords}',
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
     * 解析RSS订阅
     *
     * @param string $rss_subscribe_url
     *
     * @return array
     */
    public function analysis_rss(string $rss_subscribe_url): array
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
}
