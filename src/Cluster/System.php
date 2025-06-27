<?php

namespace stlswm\MicroserviceAssistant\Cluster;

use Exception;
use stlswm\MicroserviceAssistant\ApiIO\ErrCode;
use stlswm\MicroserviceAssistant\ApiIO\IO;

/**
 * Class System
 * @package stlswm\MicroserviceAssistant\Cluster
 */
class System
{
    /**
     * @var array
     */
    private static $system = [];

    /**
     * @var string 集群验证秘钥
     */
    private static $clusterKey;

    /**
     * @var array 集群服务器IP
     */
    public static $clusterIP = [
        '127.0.0.1',
    ];

    /**
     * 获取随机字符串
     * @param  int  $len
     * @return string
     */
    private static function getRandomString(int $len): string
    {
        $seek = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $seek = str_shuffle($seek);
        return substr($seek, 0, $len);
    }

    /**
     * 生成授权秘钥
     * @param  string  $str
     * @return string
     */
    private static function generatorAuthKey(string $str): string
    {
        return md5(self::$clusterKey.$str);
    }

    /**
     * 设置系统秘钥
     * @param  string  $str
     * @throws Exception
     */
    public static function setClusterKey(string $str): void
    {
        if (strlen($str) != 32) {
            throw new Exception('集群秘钥必须是32位字符串');
        }
        self::$clusterKey = $str;
    }

    /**
     * 是否是集群成员服务器
     * @param  string  $ip
     * @return bool
     */
    public static function isClusterMemberServer(string $ip): bool
    {
        return in_array($ip, self::$clusterIP);
    }

    /**
     * 验证请求是否来原与内部系统
     * @param  string  $ip
     * @param  string  $authStr
     * @param  string  $random
     * @param  int     $timestamp
     * @return bool
     */
    public static function isInnerReq(string $ip, string $authStr, string $random, int $timestamp): bool
    {
        if (!self::isClusterMemberServer($ip)) {
            return false;
        }
        if (abs(time() - $timestamp) > 10) {
            return false;
        }
        return $authStr == self::generatorAuthKey($random."&".$timestamp);
    }

    /**
     * @param  string  $alias
     * @param  string  $domain
     */
    public static function addSystem(string $alias, string $domain)
    {
        self::$system[$alias] = $domain;
    }

    /**
     * @param  string  $alias
     * @param  bool    $forceHttps
     * @return string
     */
    public static function getSysDomain(string $alias, bool $forceHttps = false): string
    {
        if (isset(self::$system[$alias])) {
            $host = self::$system[$alias];
            if ($forceHttps) {
                $host = str_replace('http://', 'https://', $host);
            }
            return $host;
        }
        return $alias;
    }

    /**
     * 子系统内部请求
     * @param  string  $systemAlias  系统别名|完整的域名地址
     * @param  string  $router
     * @param          $data
     * @return array
     * @throws Exception
     */
    public static function innerRequest(string $systemAlias, string $router, $data): array
    {
        if (empty(self::$clusterKey)) {
            throw new Exception('必需设置集群秘钥');
        }
        try {
            $domain = self::getSysDomain($systemAlias);
            if (!$domain) {
                throw new Exception('系统错误：'.$systemAlias.'域名设置有误');
            }
            if (is_array($data)) {
                $data = json_encode($data);
            }
            $random = self::getRandomString(32);
            $timestamp = time();
            //请求对象
            $headers = [
                "Cluster-Random: ".$random,
                "Cluster-Timestamp: ".$timestamp,
                "Cluster-Auth: ".self::generatorAuthKey($random.'&'.$timestamp),
                "Content-Type: application/json",
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $domain.'/'.ltrim($router, '/'));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 8000);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 30000);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $resInfo = curl_getinfo($ch);
            curl_close($ch);
            if ($resInfo["http_code"] != 200) {
                throw new Exception("response status code is not valid. status code: ".$resInfo["http_code"]);
            }
            $resData = json_decode($response, true);
            if (is_array($resData)) {
                return (array)$resData;
            }
            return IO::fail(ErrCode::NetErr, "无法解析API：{$domain}{$router} 返回：".$response);
        } catch (Exception $e) {
            return IO::fail(ErrCode::NetErr, $e->getMessage());
        }
    }

    /**
     * 子系统内部请求直接发送
     * @param  string  $url  系统地址
     * @param          $data
     * @return array
     * @throws Exception
     */
    public static function innerDirectRequest(string $url, $data): array
    {
        if (empty(self::$clusterKey)) {
            throw new Exception('必需设置集群秘钥');
        }
        try {
            if (is_array($data)) {
                $data = json_encode($data);
            }
            $random = self::getRandomString(32);
            $timestamp = time();
            //请求对象
            $headers = [
                "Cluster-Random: ".$random,
                "Cluster-Timestamp: ".$timestamp,
                "Cluster-Auth: ".self::generatorAuthKey($random.'&'.$timestamp),
                "Content-Type: application/json",
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 8000);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 30000);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $resInfo = curl_getinfo($ch);
            curl_close($ch);
            if ($resInfo["http_code"] != 200) {
                throw new Exception("response status code is not valid. status code: ".$resInfo["http_code"]);
            }
            $resData = json_decode($response, true);
            if (is_array($resData)) {
                return (array)$resData;
            }
            return IO::fail(ErrCode::NetErr, "无法解析API：{$url} 返回：".$response);
        } catch (Exception $e) {
            return IO::fail(ErrCode::NetErr, $e->getMessage());
        }
    }
}