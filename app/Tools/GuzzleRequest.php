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
     * @param array $headers
     * @return mixed
     *
     * @throws GuzzleException
     */
    public function send_request(string $url,string $method = 'GET',array $data = [],string $post_type = 'FORM', array $headers = []): mixed
    {
        //use GuzzleHttp\Client;
        $client = new Client();
        $params = [];
        if (!empty($headers)) {
            $params['headers'] = $headers;
        }
        if ($method == 'GET') {
            $params['query'] = $data;
        } elseif ($method == 'POST') {
            if ($post_type == 'FORM') {
                $params['form_params'] = $data;
            } elseif ($post_type == 'JSON') {
                $params['json'] = $data;
            }
        }
        $response = $client->request($method, $url, $params);
        $status_code = $response->getStatusCode();
        if ($status_code == 200) {
            return json_decode($response->getBody()->getContents(), true);
        } else {
            throw new \Exception($response->getBody()->getContents(), $status_code);
        }
    }
}