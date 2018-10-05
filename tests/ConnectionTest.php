<?php

namespace raoptimus\jsonrpc2\tests;

use raoptimus\jsonrpc2\Connection;

/**
 * This file is part of the raoptimus/yii2-jsonrpc2 library
 *
 * @copyright Copyright (c) Evgeniy Urvantsev <resmus@gmail.com>
 * @license https://github.com/raoptimus/yii2-jsonrpc2/blob/master/LICENSE.md
 * @link https://github.com/raoptimus/yii2-jsonrpc2
 */
class ConnectionTest extends TestCase
{
    public function testResponse(): void
    {
        $id = '6731e526-1af0-4c3f-81bf-40ad3c98b8a2';
        $expectedResponse = [
            'jsonrpc' => Connection::SPEC_1_0,
            'error' => null,
            'id' => $id,
            'result' => 'success',
        ];
        $socket = $this->mockSocket($expectedResponse);
        $jsonrcp = $this->getMockBuilder(Connection::class)
            ->setMethods(['buildUUID'])
            ->getMock();
        $jsonrcp->method('buildUUID')
            ->willReturn($id);

        $jsonrcp->socket = $socket;
        $result = $jsonrcp->test();
        self::assertEquals($expectedResponse['result'], $result);
    }
}
