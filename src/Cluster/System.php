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
     * @var array 集群服务器IP
     */
    public static $clusterIP = [
        '127.0.0.1',
    ];

    /**
     * @var array
     */
    private static $system = [];

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
     */
    public static function innerRequest(string $systemAlias, string $router, $data): array
    {
        try {
            $domain = self::getSysDomain($systemAlias);
            if (!$domain) {
                throw new Exception('系统错误：'.$systemAlias.'域名设置有误');
            }
            if (is_array($data)) {
                $data = json_encode($data);
            }
            //请求对象
            $request = new Client\Request('POST', $domain.'/'.ltrim($router, '/'), [
                "Content-Type" => 'application/json',
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