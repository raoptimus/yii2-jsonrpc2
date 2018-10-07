<?php

namespace raoptimus\jsonrpc2\tests;

use PHPUnit\Framework\MockObject\MockObject;
use raoptimus\jsonrpc2\Connection;
use raoptimus\jsonrpc2\Request;
use raoptimus\jsonrpc2\Socket;
use Yii;
use yii\console\Application;

/**
 * This file is part of the raoptimus/yii2-jsonrpc2 library
 *
 * @copyright Copyright (c) Evgeniy Urvantsev <resmus@gmail.com>
 * @license https://github.com/raoptimus/yii2-jsonrpc2/blob/master/LICENSE.md
 * @link https://github.com/raoptimus/yii2-jsonrpc2
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();
    }

    protected function mockApplication(): void
    {
        new Application(
            [
                'id' => 'testapp',
                'basePath' => __DIR__,
                'vendorPath' => \dirname(__DIR__) . '/vendor',
                'runtimePath' => __DIR__ . '/runtime',
            ]
        );
    }

    protected function tearDown()
    {
        $this->destroyApplication();
        parent::tearDown();
    }

    protected function destroyApplication(): void
    {
        Yii::$app = null;
    }

    /**
     * @param null|string $responseBody
     *
     * @return MockObject|Socket
     */
    protected function mockSocket(?string $responseBody = null)
    {
        $methods = [
            'readResponseBody' => $responseBody,
            'writeRequest' => null,
            'getIsActive' => true,
            'close' => null,
            'open' => true,
        ];
        $socket = $this
            ->getMockBuilder(Socket::class)
            ->disableOriginalConstructor()
            ->setMethods(array_keys($methods))
            ->getMock();

        foreach ($methods as $method => $return) {
            $socket->method($method)->willReturn($return);
        }

        return $socket;
    }

    /**
     * @param Request $request
     * @param Socket $socket
     *
     * @return MockObject|Connection
     */
    protected function mockConnection(Request $request, Socket $socket)
    {
        $jsonrpc = $this->getMockBuilder(Connection::class)
                        ->setConstructorArgs([['socket' => $socket, 'spec' => $request->jsonrpc]])
                        ->setMethods(['createRequest'])
                        ->getMock();
        $jsonrpc->method('createRequest')->willReturn($request);

        return $jsonrpc;
    }
}
