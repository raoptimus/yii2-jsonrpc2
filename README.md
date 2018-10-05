[![Stable Version](https://poser.pugx.org/raoptimus/yii2-jsonrpc2/v/stable)](https://packagist.org/packages/raoptimus/yii2-jsonrpc2)
[![Untable Version](https://poser.pugx.org/raoptimus/yii2-jsonrpc2/v/unstable)](https://packagist.org/packages/raoptimus/yii2-jsonrpc2)
[![License](https://poser.pugx.org/raoptimus/yii2-jsonrpc2/license)](https://packagist.org/packages/raoptimus/yii2-jsonrpc2)
[![Total Downloads](https://poser.pugx.org/raoptimus/yii2-jsonrpc2/downloads)](https://packagist.org/packages/raoptimus/yii2-jsonrpc2)
[![Build Status](https://travis-ci.com/raoptimus/yii2-jsonrpc2.svg?branch=master)](https://travis-ci.com/raoptimus/yii2-jsonrpc2)

# yii2-jsonrpc2
Json RPC 1.0 and 2.0 protocol for Yii2

## Installation

Install with composer:

```bash
composer require raoptimus/yii2-jsonrpc2
```

## Usage samples

Configuration

```php
return [
    //....
    'components' => 
        'jsonrpc' => [
            'class' => raoptimus\jsonrpc2\Connection::class,
            'hostname' => 'localhost',
            'port' => 8666,
        ],
];
```

```php
return [
    //....
    'components' => 
        'jsonrpc' => [
            'class' => raoptimus\jsonrpc2\Connection::class,
            'unixSocket' => '/tmp/jsonrpc2.sock',
            'spec' => raoptimus\jsonrpc2\Connection::SPEC_2_0,
        ],
];
```

Use connection

```php
$rpc = \Yii::$app->get('jsonrpc');
$method = "SomeMethodName";
$param = "SomeEnterParam";
$result = $rpc->sendRequest($method, [$param]);
```
