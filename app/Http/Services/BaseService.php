<?php

namespace App\Http\Services;

abstract class BaseService
{
    /** Success return data;
     *
     * @param array $data
     * @return array
     */
    protected function success(array $data = []): array
    {
        return [
            'status' => true,
            'msg' => 'success',
            'data' => $data,
        ];
    }

    /** Error return data;
     *
     * @param string $msg
     * @param array $data
     * @return array
     */
    protected function error(string $msg = 'error', array $data = []): array
    {
        return [
            'status' => false,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}