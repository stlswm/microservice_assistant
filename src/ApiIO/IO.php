<?php

namespace stlswm\MicroserviceAssistant\ApiIO;


/**
 * Class IO
 *
 * @package stlswm\MicroserviceAssistant\ApiIO
 */
class IO
{
    /**
     * @param int    $code
     * @param array  $data
     * @param string $msg
     *
     * @return array
     * @Author wm
     * @Date   2018/7/12
     * @Time   14:11
     */
    private static function out(int $code, array $data, string $msg): array
    {
        return [
            "code" => $code,
            "data" => $data,
            "msg"  => $msg,
        ];
    }

    /**
     * 响应成功
     *
     * @param array  $data
     * @param string $msg
     *
     * @return array
     * @Author wm
     * @Date   2018/7/12
     * @Time   14:11
     */
    public static function success(array $data, string $msg = "ok"): array
    {
        return self::out(ErrCode::OK, $data, $msg);
    }

    /**
     * 响应失败
     *
     * @param int    $code
     * @param string $msg
     * @param array  $data
     *
     * @return array
     * @Author wm
     * @Date   2018/7/12
     * @Time   14:11
     */
    public static function fail(int $code, string $msg, array $data = []): array
    {
        return self::out($code, $data, $msg);
    }
}
