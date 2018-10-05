<?php

namespace raoptimus\jsonrpc2;

use Yii;

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
    private $_socket;

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
     *
     */
    public function close(): void
    {
        if ($this->_socket === null) {
            return;
        }
        Yii::debug(self::INFO_ON_CLOSING . $this->connectionString, __METHOD__);
        stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);
        $this->_socket = null;
    }

    /**
     * Returns a value indicating whether the connection is established.
     *
     * @return boolean whether the connection is established
     */
    public function getIsActive(): bool
    {
        return $this->_socket !== null;
    }

    /**
     * Establishes a connection.
     * It does nothing if a connection has already been established.
     */
    public function open(): void
    {
        if ($this->_socket !== null) {
            return;
        }
        Yii::debug(self::INFO_ON_OPENING . $this->connectionString, __METHOD__);
        $this->_socket = @stream_socket_client(
            $this->connectionString,
            $errorNumber,
            $errorDescription,
            $this->connectionTimeout
        );
        if ($this->_socket) {
            if ($this->readWriteTimeout !== null) {
                stream_set_timeout(
                    $this->_socket,
                    $timeout = (int)$this->readWriteTimeout,
                    (int)(($this->readWriteTimeout - $timeout) * 1000000)
                );
            }
        } else {
            $extra = sprintf(' (%s): {%d} - {%s}',
                $this->connectionString, (int)$errorNumber, $errorDescription);

            Yii::debug(self::ERROR_ON_CONNECTION . $extra);
            throw new Exception(self::ERROR_ON_CONNECTION, $errorDescription, (int)$errorNumber);
        }
    }

    /**
     * @param string $request
     */
    public function writeRequest(string $request): void
    {
        fwrite($this->_socket, $request);
        fwrite($this->_socket, "\n");
        fflush($this->_socket);
    }

    /**
     * @return bool|string
     */
    public function readResponse()
    {
        return fgets($this->_socket);
    }
}
