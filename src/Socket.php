<?php

namespace raoptimus\jsonrpc2;

use Yii;
use yii\helpers\Json;

/**
 * This file is part of the raoptimus/yii2-jsonrpc2 library
 *
 * @copyright Copyright (c) Evgeniy Urvantsev <resmus@gmail.com>
 * @license https://github.com/raoptimus/yii2-jsonrpc2/blob/master/LICENSE.md
 * @link https://github.com/raoptimus/yii2-jsonrpc2
 */
class Socket
{
    private const ERROR_ON_CONNECTION = 'Failed to open jsonrpc connection.';
    private const INFO_ON_CLOSING = 'Closing jsonrpc connection:';
    private const INFO_ON_OPENING = 'Opening jsonrpc connection:';
    /**
     * @var float Number of seconds until the connect() system call
     */
    private $connectionTimeout;
    /**
     * @var float Number of seconds period on a stream
     */
    private $readWriteTimeout;
    /**
     * @var string
     */
    private $connectionString;
    /**
     * @var resource jsonrpc socket connection
     */
    private $socket;

    /**
     * Socket constructor.
     *
     * @param string $connectionStr
     * @param float|null $connectionTimeout Number of seconds until the connect() system call
     * @param float|null $readWriteTimeout Number of seconds period on a stream
     */
    public function __construct(string $connectionStr, ?float $connectionTimeout, ?float $readWriteTimeout)
    {
        $this->connectionString = $connectionStr;
        $this->connectionTimeout = $connectionTimeout ?? ini_get('default_socket_timeout');
        $this->readWriteTimeout = $readWriteTimeout;
    }

    /**
     * Closes the connection when this component is being serialized.
     *
     * @return array
     */
    public function __sleep()
    {
        $this->close();

        return array_keys(get_object_vars($this));
    }

    /**
     *
     */
    public function close(): void
    {
        if ($this->socket === null) {
            return;
        }
        Yii::debug(self::INFO_ON_CLOSING . $this->connectionString, __METHOD__);
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
        $this->socket = null;
    }

    /**
     * Establishes a connection.
     * It does nothing if a connection has already been established.
     */
    public function open(): bool
    {
        if ($this->getIsActive()) {
            return false;
        }
        Yii::debug(self::INFO_ON_OPENING . $this->connectionString, __METHOD__);
        $socket = stream_socket_client(
            $this->connectionString,
            $errorNumber,
            $errorDescription,
            $this->connectionTimeout
        );

        if ($socket) {
            if ($this->readWriteTimeout !== null) {
                stream_set_timeout(
                    $socket,
                    $timeout = (int)$this->readWriteTimeout,
                    (int)(($this->readWriteTimeout - $timeout) * 1000000)
                );
            }
            $this->socket = $socket;

            return true;
        }

        $extra = sprintf(
            ' (%s): {%d} - {%s}',
            $this->connectionString,
            (int)$errorNumber,
            $errorDescription
        );

        Yii::debug(self::ERROR_ON_CONNECTION . $extra);
        throw new Exception(self::ERROR_ON_CONNECTION, $errorDescription, (int)$errorNumber);
    }

    /**
     * Returns a value indicating whether the connection is established.
     *
     * @return boolean whether the connection is established
     */
    public function getIsActive(): bool
    {
        return $this->socket !== null && !stream_get_meta_data($this->socket)['timed_out'];
    }

    public function writeRequest(Request $request): void
    {
        $this->writeRequestBody($request->encode());
    }

    public function readResponse(): Response
    {
        $responseBody = $this->readResponseBody();
        if ($responseBody === false) {
            throw new Exception('Failed to read from socket');
        }
        if (trim($responseBody) === '') {
            throw new Exception('No response received');
        }

        $responseData = Json::decode($responseBody);
        if ($responseData === null) {
            throw new Exception('Invalid response decoding');
        }

        return new Response($responseData);
    }

    protected function writeRequestBody(string $content): void
    {
        fwrite($this->socket, $content);
        fwrite($this->socket, "\n");
        fflush($this->socket);
    }

    /**
     * @return bool|string
     */
    protected function readResponseBody()
    {
        return fgets($this->socket);
    }
}
