<?php

namespace raoptimus\jsonrpc2;

use yii\base\BaseObject;

class Response extends BaseObject
{
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $jsonrpc;
    /**
     * @var string
     */
    public $error;
    /**
     * @var mixed
     */
    public $result;

    public function hasError(): bool
    {
        return !empty($this->error);
    }

    public function validate(Request $request): void
    {
        if ($this->id !== $request->id) {
            throw new Exception('Invalid response. Id ' . $request->id . ' is not equals to ' . $this->id);
        }
        if ($this->jsonrpc !== $request->jsonrpc) {
            throw new Exception(
                'Invalid response. Version ' . $request->jsonrpc . ' is not equals to ' . $this->jsonrpc
            );
        }
        if ($this->hasError()) {
            throw new Exception($this->error);
        }
    }
}
