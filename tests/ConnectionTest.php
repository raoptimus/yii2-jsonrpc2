<?php

namespace raoptimus\jsonrpc2\tests;

use raoptimus\jsonrpc2\Connection;
use raoptimus\jsonrpc2\Exception;
use raoptimus\jsonrpc2\Helper;
use raoptimus\jsonrpc2\Request;
use yii\helpers\Json;

/**
 * This file is part of the raoptimus/yii2-jsonrpc2 library
 *
 * @copyright Copyright (c) Evgeniy Urvantsev <resmus@gmail.com>
 * @license https://github.com/raoptimus/yii2-jsonrpc2/blob/master/LICENSE.md
 * @link https://github.com/raoptimus/yii2-jsonrpc2
 */
class ConnectionTest extends TestCase
{
    /**
     * @dataProvider dataProviderValidRequests
     *
     * @param array $requestData
     */
    public function testRequestsResponses(array $requestData): void
    {
        $request = new Request($requestData);
        $expectedResponse = ['id' => $request->id, 'jsonrpc' => $request->jsonrpc, 'result' => 'success'];

        $socket = $this->mockSocket(Json::encode($expectedResponse));
        $jsonrpc = $this->mockConnection($request, $socket);
        $result = $jsonrpc->test();
        self::assertEquals($expectedResponse['result'], $result);
    }

    public function testErrorResponse(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test error');
        $id = Helper::buildUUID();
        $request = new Request(
            [
                'id' => $id,
                'jsonrpc' => Connection::SPEC_2_0,
                'method' => 'test',
            ]
        );
        $expectedResponse = [
            'id' => $id,
            'jsonrpc' => $request->jsonrpc,
            'error' => 'Test error',
        ];

        $socket = $this->mockSocket(Json::encode($expectedResponse));
        $jsonrpc = $this->mockConnection($request, $socket);
        $jsonrpc->test();
    }

    /**
     * @dataProvider dataProviderInvalidRequests
     *
     * @param array $requestData
     * @param array|string|bool|int $expectedResponse
     */
    public function testErrorResponses(array $requestData, $expectedResponse = false): void
    {
        $this->expectException(Exception::class);

        $request = new Request($requestData);
        $responseBody = $expectedResponse ? Json::encode($expectedResponse) : '';
        $socket = $this->mockSocket($responseBody);
        $jsonrpc = $this->mockConnection($request, $socket);
        $jsonrpc->test();
    }

    public function dataProviderInvalidRequests(): array
    {
        return [
            'invalid request/v1.0. without id' => [
                [
                    'jsonrpc' => Connection::SPEC_1_0,
                    'id' => Helper::buildUUID(),
                    'method' => 'test',
                ],
                [
                    'jsonrpc' => Connection::SPEC_1_0,
                    'id' => Helper::buildUUID(),
                ],
            ],
            'invalid request/v1.0, invalid arguments' => [
                [
                    'id' => Helper::buildUUID(),
                    'jsonrpc' => Connection::SPEC_1_0,
                    'method' => 'test',
                    'params' => ['name1' => 'arg1'],
                ],
            ],
            'invalid request/v2.0, empty result' => [
                [
                    'jsonrpc' => Connection::SPEC_2_0,
                    'id' => Helper::buildUUID(),
                    'method' => 'test',
                ],
            ],
        ];
    }

    public function dataProviderValidRequests(): array
    {
        $id = Helper::buildUUID();

        return [
            'valid request/v1.0' => [
                [
                    'id' => $id,
                    'jsonrpc' => Connection::SPEC_1_0,
                    'method' => 'test',
                ],
            ],
            'valid request/v1.0 with arguments' => [
                [
                    'id' => $id,
                    'jsonrpc' => Connection::SPEC_1_0,
                    'method' => 'test',
                    'params' => ['arg1'],
                ],
            ],
            'valid request/v2.0' => [
                [
                    'id' => $id,
                    'jsonrpc' => Connection::SPEC_2_0,
                    'method' => 'test',
                ],
            ],
            'valid request/v2.0 with arguments' => [
                [
                    'id' => $id,
                    'jsonrpc' => Connection::SPEC_2_0,
                    'method' => 'test',
                    'params' => ['arg1'],
                ],
            ],
        ];
    }
}
