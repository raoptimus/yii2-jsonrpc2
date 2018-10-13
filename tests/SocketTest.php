<?php

namespace raoptimus\jsonrpc2\tests;

use raoptimus\jsonrpc2\Exception;
use raoptimus\jsonrpc2\Socket;

class SocketTest extends TestCase
{
    public function testCannotOpenConnection(): void
    {
        $this->expectException(Exception::class);
        $socket = new Socket('');
        $socket->open();
    }
}
