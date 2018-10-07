<?php

namespace raoptimus\jsonrpc2;

use Yii;
use yii\base\Component;
use yii\base\UnknownMethodException;

/**
 * This file is part of the raoptimus/yii2-jsonrpc2 library
 *
 * @copyright Copyright (c) Evgeniy Urvantsev <resmus@gmail.com>
 * @license https://github.com/raoptimus/yii2-jsonrpc2/blob/master/LICENSE.md
 * @link https://github.com/raoptimus/yii2-jsonrpc2
 * @property bool $isActive
 */
class Connection extends Component
{
    /**
     * @event Event an event that is triggered after a connection is established
     */
    public const EVENT_AFTER_OPEN = 'afterOpen';
    public const SPEC_1_0 = '1.0';
    public const SPEC_2_0 = '2.0';
    /**
     * @var string the hostname or ip address to use for connecting to the jsonrpc server. Defaults to 'localhost'.
     * If [[unixSocket]] is specified, hostname and port will be ignored.
     */
    public $hostname = 'localhost';
    /**
     * @var integer the port to use for connecting to the jsonrpc server. Default port is 8666.
     * If [[unixSocket]] is specified, hostname and port will be ignored.
     */
    public $port = 8666;
    /**
     * @var string the unix socket path (e.g. `/var/run/yii/jsonrpc.sock`) to use for connecting to the jsonrpc server.
     * This can be used instead of [[hostname]] and [[port]] to connect to the server using a unix socket.
     * If a unix socket path is specified, [[hostname]] and [[port]] will be ignored.
     */
    public $unixSocket;
    /**
     * @var float timeout to use for connection to jsonrpc. If not set the timeout set in php.ini will be used:
     *     ini_get("default_socket_timeout")
     */
    public $connectionTimeout;
    /**
     * @var float timeout to use for jsonrpc socket when reading and writing data. If not set the php default value
     *     will be used.
     */
    public $dataTimeout;
    /**
     * @var string
     */
    public $spec = self::SPEC_1_0;
    /**
     * @var Socket
     */
    protected $socket;
    /**
     * @var Request
     */
    private $request;

    /**
     * Closes the currently active connection.
     * It does nothing if the connection is already closed.
     */
    public function close(): void
    {
        $this->socket->close();
    }

    /**
     * Returns a value indicating whether the connection is established.
     *
     * @return boolean whether the connection is established
     */
    public function getIsActive(): bool
    {
        return $this->socket->getIsActive();
    }

    /**
     * @param string $method
     * @param array $params
     *
     * @return mixed
     */
    public function __call($method, $params)
    {
        try {
            return parent::__call($method, $params);
        } catch (UnknownMethodException $ex) {
            $request = $this->createRequest($method, $params);
            $this->sendRequest($request);

            return $this->readResponse()->result;
        }
    }

    public function createRequest(string $method, ?array $params = null): Request
    {
        return new Request(
            [
                'method' => $method,
                'params' => $params,
                'jsonrpc' => $this->spec,
            ]
        );
    }

    /**
     * Sends a JSON-RPC request over plain socket.
     *
     * @param Request $request
     */
    public function sendRequest(Request $request): void
    {
        if ($this->request !== null) {
            throw new Exception('Previous request was not processed');
        }
        $this->request = $request;
        $this->open();

        Yii::debug('Sending request to JSON-RPC server: ' . $request->method, __METHOD__);
        $this->socket->writeRequest($request);
    }

    /**
     * Establishes a connection.
     * It does nothing if a connection has already been established.
     */
    public function open(): void
    {
        $this->initSocket();
        if ($this->socket->open()) {
            $this->initConnection();
        }
    }

    public function readResponse(): Response
    {
        $request = $this->request;
        $this->request = null;
        Yii::debug('Reading response from JSON-RPC server: ' . $request->method, __METHOD__);
        $response = $this->socket->readResponse();
        $response->validate($request);

        return $response;
    }

    protected function setSocket(Socket $socket): void
    {
        $this->socket = $socket;
    }

    protected function initSocket(): void
    {
        if ($this->socket !== null) {
            return;
        }

        $connectionStr = $this->unixSocket
            ? 'unix://' . $this->unixSocket
            : 'tcp://' . $this->hostname . ':' . $this->port;
        $this->socket = new Socket($connectionStr, $this->connectionTimeout, $this->dataTimeout);
    }

    /**
     * Initializes the connection.
     * This method is invoked right after the connection is established.
     * The default implementation triggers an [[EVENT_AFTER_OPEN]] event.
     */
    protected function initConnection(): void
    {
        $this->trigger(self::EVENT_AFTER_OPEN);
    }
}
