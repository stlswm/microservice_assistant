<?php

namespace Cluster;

use PHPUnit\Framework\TestCase;
use stlswm\MicroserviceAssistant\Cluster\System;

class SystemTest extends TestCase
{
    /**
     *
     */
    public function testReq()
    {
        System::addSystem('user', "http://api.local.hpwcd.com/user");
        $res = System::innerRequest('user', '/api/region/get-select-option', []);
        $this->assertSame($res['code'], 0);
    }
}