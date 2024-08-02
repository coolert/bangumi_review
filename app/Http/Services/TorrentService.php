<?php

namespace App\Http\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TorrentService extends BaseService
{
    /** 下载种子文件
     * @param $url
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Lv
     * @date 2024/8/1
     */
    public function download_torrent($url): string
    {
        $client = new Client();
        try {
            $savePath = storage_path('torrent/'. uniqid() . '.torrent');
            $response = $client->get($url, ['sink' => $savePath]);
            $re_path = '';
            if ($response->getStatusCode() == 200) {
                $re_path = $savePath;
            }
        } catch (RequestException $e) {
            throw new \Exception("Request failed: " . $e->getMessage());
        }
        return $re_path;
    }

    /** 将torrent转换为magnet
     * @param $torrentPath
     * @return string
     * @author Lv
     * @date 2024/8/2
     */
    public function torrentToMagnet($torrentPath): string
    {
        $torrentData = file_get_contents($torrentPath);
        $info = $this->bdecode(substr($torrentData, 0, 16000));
        $infoHash = hash('sha1', $this->bencode($info['info']));
        return "magnet:?xt=urn:btih:" . $infoHash;
    }

    private function bdecode($string)
    {
        if (empty($string)) {
            return null;
        }
        $pos = 0;
        return $this->bdecodeElement($string, $pos);
    }

    private function bdecodeElement($string, &$pos)
    {
        switch ($string[$pos]) {
            case 'i':
                $pos++;
                $value = 0;
                while ($string[$pos] !== 'e') {
                    $value = $value * 10 + $string[$pos];
                    $pos++;
                }
                $pos++;
                return (int) $value;

            case 'l':
                $pos++;
                $list = [];
                while ($string[$pos] !== 'e') {
                    $list[] = $this->bdecodeElement($string, $pos);
                }
                $pos++;
                return $list;

            case 'd':
                $pos++;
                $dict = [];
                while ($string[$pos] !== 'e') {
                    $key = $this->bdecodeElement($string, $pos);
                    $dict[$key] = $this->bdecodeElement($string, $pos);
                }
                $pos++;
                return $dict;

            default:
                $len = 0;
                while ($string[$pos] !== ':') {
                    $len = $len * 10 + $string[$pos];
                    $pos++;
                }
                $pos++;
                $value = substr($string, $pos, $len);
                $pos += $len;
                return $value;
        }
    }

    private function bencode($value)
    {
        if (is_int($value)) {
            return 'i' . $value . 'e';
        } elseif (is_string($value)) {
            return strlen($value) . ':' . $value;
        } elseif (is_array($value)) {
            $isList = array_keys($value) === range(0, count($value) - 1);
            if ($isList) {
                $encoded = 'l';
                foreach ($value as $v) {
                    $encoded .= $this->bencode($v);
                }
                return $encoded . 'e';
            } else {
                ksort($value);
                $encoded = 'd';
                foreach ($value as $k => $v) {
                    $encoded .= $this->bencode($k) . $this->bencode($v);
                }
                return $encoded . 'e';
            }
        }
        return '';
    }
}