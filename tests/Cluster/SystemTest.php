<?php

namespace Cluster;

use Exception;
use PHPUnit\Framework\TestCase;
use stlswm\MicroserviceAssistant\Cluster\System;

class SystemTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testReq()
    {
        System::setClusterKey('609b54169bef26e55950e89d8ad40daf');
        System::addSystem('user', "http://api.local.hpwcd.com/user");
        $res = System::innerRequest('user', '/api/region/get-select-option', []);
        $this->assertSame($res['code'], 0);
    }
}