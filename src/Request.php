<?php

namespace raoptimus\jsonrpc2;

use yii\base\BaseObject;
use yii\helpers\Json;

/**
 * This file is part of the raoptimus/yii2-jsonrpc2 library
 *
 * @copyright Copyright (c) Evgeniy Urvantsev <resmus@gmail.com>
 * @license https://github.com/raoptimus/yii2-jsonrpc2/blob/master/LICENSE.md
 * @link https://github.com/raoptimus/yii2-jsonrpc2
 * @property bool $isActive
 */
class Request extends BaseObject
{
    public $jsonrpc = Connection::SPEC_2_0;
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $method;
    /**
     * @var array
     */
    public $params;

    public function init(): void
    {
        if ($this->id === null) {
            $this->id = Helper::buildUUID();
        }
    }

    public function encode(): string
    {
        $this->validate();
        $data = [
            'id' => $this->id,
            'method' => $this->method,
            'jsonrpc' => $this->jsonrpc,
        ];

        if ($this->params !== null) {
            $data['params'] = $this->params;
        }

        return Json::encode($data);
    }

    private function validate(): void
    {
        if (empty($this->method)) {
            throw new Exception('Invalid data to be sent to JSON-RPC server. Method cannot be blank');
        }
        if (empty($this->id)) {
            throw new Exception('Invalid data to be sent to JSON-RPC server. ID cannot be blank');
        }
        if (!\in_array($this->jsonrpc, [Connection::SPEC_1_0, Connection::SPEC_2_0], true)) {
            throw new Exception('Invalid data to be sent to JSON-RPC server. Unknown version JSON-RPC');
        }

        if ($this->params === null || \count($this->params) === 0) {
            $this->params = null;

            return;
        }

        if ($this->jsonrpc === Connection::SPEC_1_0) {
            if (\count(array_filter(array_keys($this->params), '\is_string')) > 0) {
                throw new Exception('JSON-RPC 1.0 doesn\'t allow named parameters');
            }
        }
    }
}
