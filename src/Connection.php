<?php

namespace raoptimus\jsonrpc2;

use Yii;
use yii\base\Component;
use yii\base\UnknownMethodException;
use yii\helpers\Json;

/**
 *
 * @property bool $isActive
 */
class Connection extends Component
{
    /**
     * @event Event an event that is triggered after a connection is established
     */
    public const EVENT_AFTER_OPEN = 'afterOpen';
    private const SPEC_1_0 = '1.0';
    private const SPEC_2_0 = '2.0';
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
     * @var float timeout to use for connection to jsonrpc. If not set the timeout set in php.ini will be used: ini_get("default_socket_timeout")
     */
    public $connectionTimeout;
    /**
     * @var float timeout to use for jsonrpc socket when reading and writing data. If not set the php default value will be used.
     */
    public $dataTimeout;
    public $spec = self::SPEC_1_0;
    /**
     * @var resource jsonrpc socket connection
     */
    private $_socket;

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
        $connection = ($this->unixSocket ?: $this->hostname . ':' . $this->port);
        Yii::debug('Opening jsonrpc connection: ' . $connection, __METHOD__);
        $this->_socket = @stream_socket_client(
            $this->unixSocket ? 'unix://' . $this->unixSocket : 'tcp://' . $this->hostname . ':' . $this->port,
            $errorNumber,
            $errorDescription,
            $this->connectionTimeout ? $this->connectionTimeout : ini_get("default_socket_timeout")
        );
        if ($this->_socket) {
            if ($this->dataTimeout !== null) {
                stream_set_timeout(
                    $this->_socket,
                    $timeout = (int)$this->dataTimeout,
                    (int)(($this->dataTimeout - $timeout) * 1000000)
                );
            }
            $this->initConnection();
        } else {
            Yii::error(
                "Failed to open jsonrpc connection ({$connection}): {$errorNumber} - {$errorDescription}",
                __CLASS__
            );
            $message = YII_DEBUG ? "Failed to open jsonrpc connection ($connection): $errorNumber - $errorDescription" : 'Failed to open jsonrpc connection.';
            throw new Exception($message, $errorDescription, (int)$errorNumber);
        }
    }

    /**
     * Closes the currently active connection.
     * It does nothing if the connection is already closed.
     */
    public function close(): void
    {
        if ($this->_socket !== null) {
            $connection = ($this->unixSocket ?: $this->hostname . ':' . $this->port);
            Yii::debug('Closing jsonrpc connection: ' . $connection, __METHOD__);
            stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);
            $this->_socket = null;
        }
    }

    /**
     *
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
            return $this->sendRequest($method, $params);
        }
    }

    /**
     * Sends a JSON-RPC request over plain socket.
     *
     * @param string $method
     * @param array $params
     *
     * @return mixed jsonrpc response result
     */
    public function sendRequest($method, array $params = null)
    {
        $this->open();
        if (empty($method) || empty($params)) {
            throw new Exception('Invalid data to be sent to JSON-RPC server');
        }
        $id = $this->uuid();
        $request = [
            'method' => $method,
            'id' => $id,
        ];
        switch ($this->spec) {
            case self::SPEC_2_0:
                $request['jsonrpc'] = self::SPEC_2_0;
                if ($params !== null) {
                    $request['params'] = $params;
                }
                break;
            case self::SPEC_1_0:
                if ($params !== null) {
                    if ((bool)\count(array_filter(array_keys($params), '\is_string'))) {
                        throw new Exception('JSON-RPC 1.0 doesn\'t allow named parameters');
                    }
                    $request['params'] = $params;
                }
                break;
            default:
                throw new Exception('Unknown version JSON-RPC');
        }
        $request = Json::encode($request);
        Yii::debug("Sending request to JSON-RPC server: {$method}", __METHOD__);
        fwrite($this->_socket, $request);
        fwrite($this->_socket, "\n");
        fflush($this->_socket);

        return $this->parseResponse($id);
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

    /**
     * Parse a JSON-RPC response
     *
     * @param $id string
     *
     * @return mixed jsonrpc response result
     * @throws Exception
     */
    private function parseResponse($id)
    {
        $response = fgets($this->_socket);
        if ($response === false) {
            throw new Exception('Failed to read from socket');
        }
        if (trim($response) === '') {
            throw new Exception('No response received');
        }
        $response = Json::decode($response, true);
        if ($response === null) {
            throw new Exception('Invalid response decoding');
        }
        if (!isset($response['jsonrpc'])) {
            $response['jsonrpc'] = self::SPEC_1_0;
        }
        foreach (['jsonrpc', 'result', 'error', 'result', 'id'] as $key) {
            if (!array_key_exists($key, $response)) {
                throw new Exception('Invalid response, not found key = {$key}' . PHP_EOL . var_export($response, true));
            }
        }
        if ($response['id'] !== $id) {
            throw new Exception("Invalid response id {$id} is not equals to {$response['id']}");
        }
        if ($response['jsonrpc'] !== $this->spec) {
            throw new Exception("Invalid response version {$this->spec} is not equals to {$response['jsonrpc']}");
        }
        if (!empty($response['error'])) {
            throw new Exception($response['error']);
        }

        return $response['result'];
    }

    /**
     * @return string A v4 uuid
     */
    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff), // time_low
            random_int(0, 0xffff), // time_mid
            random_int(0, 0x0fff) | 0x4000, // time_hi_and_version
            random_int(0, 0x3fff) | 0x8000, // clk_seq_hi_res/clk_seq_low
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff) // node
        );
    }
}
