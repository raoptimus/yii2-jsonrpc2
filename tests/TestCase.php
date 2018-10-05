<?php

namespace raoptimus\jsonrpc2\tests;

use PHPUnit\Framework\MockObject\MockObject;
use raoptimus\jsonrpc2\Socket;
use Yii;
use yii\console\Application;
use yii\helpers\Json;

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
     * @param array $response
     *
     * @return MockObject|Socket
     */
    protected function mockSocket(array $response)
    {
        return $this->createConfiguredMock(
            Socket::class,
            [
                'readResponse' => Json::encode($response),
                'writeRequest' => null,
                'getIsActive' => true,
                'close' => null,
                'open' => null,
            ]
        );
    }
}
