<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace Bytom\Tests;

use Bytom\BytomClient;

class KeyTest extends BytomTestCase
{
    public function testCreateKey()
    {
        $alias = 'test_name';
        $pwd = '123456';
        $bytom = new BytomClient(URL);
        $res = $bytom->createKey($alias, $pwd);
        $this->assertEquals(401, $res->getHTTPStatus());

        $bytom = new BytomClient(URL, TOKEN);
        $res = $bytom->createKey($alias, $pwd);
        $this->assertEquals(200, $res->getHTTPStatus());
        $this->assertTrue($res->isSucceeded());

        $data = $res->getJSONDecodedBody();
        $this->assertEquals('success', $data['status']);
        $this->assertEquals($alias, $data['data']['alias']);
    }

    public function testListKeys()
    {
        $bytom = new BytomClient(URL);
        $res = $bytom->listKeys();
        $this->assertEquals(401, $res->getHTTPStatus());

        $bytom = new BytomClient(URL, TOKEN);
        $res = $bytom->listKeys();
        $this->assertEquals(200, $res->getHTTPStatus());
        $this->assertTrue($res->isSucceeded());

        $data = $res->getJSONDecodedBody();
        $this->assertEquals('success', $data['status']);
    }

    public function testDeleteKey()
    {
        $xpub = 'xpub';
        $password = '123456';
        $bytom = new BytomClient(URL, TOKEN);
        $res = $bytom->deleteKey($xpub, $password);
        $this->assertEquals(200, $res->getHTTPStatus());
        $this->assertTrue($res->isSucceeded());

        $data = $res->getJSONDecodedBody();
        $this->assertEquals('success', $data['status']);
    }
}
