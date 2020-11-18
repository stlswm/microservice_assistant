<?php

namespace stlswm\MicroserviceAssistant\Cluster;

use Exception;
use http\Client;
use http\Message\Body;
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
     * @param  int  $timestamp
     * @return bool
     */
    public static function isInnerReq(string $ip, string $authStr, string $random, int $timestamp): bool
    {
        if (!self::isClusterMemberServer($ip)) {
            return false;
        }
        if (abs(time()->$timestamp) > 10) {
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
     * @return string
     */
    public static function getSysDomain(string $alias): string
    {
        if (isset(self::$system[$alias])) {
            return self::$system[$alias];
        }
        return $alias;
    }

    /**
     * 子系统内部请求
     * @param  string  $systemAlias  系统别名|完整的域名地址
     * @param  string  $router
     * @param        $data
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
            $request = new Client\Request('POST', $domain.'/'.ltrim($router, '/'), [
                "Cluster-Random"    => $random,
                "Cluster-Timestamp" => $timestamp,
                "Cluster-Auth"      => self::generatorAuthKey($random.'&'.$timestamp),
                "Content-Type"      => 'application/json',
            ]);
            $body = new Body();
            $body->append($data);
            $request->setBody($body);
            //发送请求
            $cli = new Client();
            $cli->requeue($request);
            $response = $cli->send()->getResponse($request);
            if ($response->getResponseCode() != 200) {
                return IO::fail(ErrCode::NetErr, "网络请求错误：".$response->getResponseCode());
            }
            $resData = json_decode($response->getBody()->toString(), true);
            if (is_array($resData)) {
                return (array)$resData;
            }
            return IO::fail(ErrCode::NetErr, "无法解析API：{$domain}{$router} 返回：".(string)$response->getBody());
        } catch (Exception $e) {
            return IO::fail(ErrCode::NetErr, $e->getMessage());
        }
    }
}