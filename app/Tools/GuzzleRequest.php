<?php

namespace App\Tools;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GuzzleRequest
{
    /**
     * 发送Http请求
     *
     * @param string $url
     * @param string $method
     * @param array $data
     * @param string $post_type
     *
     * @return mixed
     *
     * @throws GuzzleException
     */
    public function send_request(string $url,string $method = 'GET',array $data = [],string $post_type = 'FORM')
    {
        //use GuzzleHttp\Client;
        $client = new Client();
        $params = [];
        if ($method == 'GET') {
            $params['query'] = $data;
        } elseif ($method == 'POST') {
            if ($post_type == 'FORM') {
                $params['form_params'] = $data;
            } elseif ($post_type == 'JSON') {
                $params['json'] = $data;
            }
        }
        return json_decode($client->request($method, $url, $params)->getBody()->getContents(), true);
    }
}