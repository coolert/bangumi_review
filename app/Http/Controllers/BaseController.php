<?php

namespace App\Http\Controllers;

class BaseController extends Controller
{
    /**
     * Success return.
     *
     * @param string $msg
     * @param array $data
     *
     * @return array
     */
    public function success(string $msg = 'success',array $data = []): array
    {
        return [
            'code' => 200,
            'msg' => $msg,
            'data' => $data
        ];
    }

    /**
     * Error return.
     *
     * @param string $msg
     *
     * @return array
     */
    public function error(string $msg = 'error'): array
    {
        return [
            'code' => 400,
            'msg' => $msg
        ];
    }
}