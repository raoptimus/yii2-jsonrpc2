<?php

namespace raoptimus\validators\tests;

use Yii;
use yii\base\InvalidConfigException;
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

    protected function tearDown()
    {
        $this->destroyApplication();
        parent::tearDown();
    }

    /**
     * @throws InvalidConfigException
     */
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

    protected function destroyApplication(): void
    {
        Yii::$app = null;
    }
}
