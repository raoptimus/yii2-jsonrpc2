<?php

namespace raoptimus\jsonrpc2;

/**
 * This file is part of the raoptimus/yii2-jsonrpc2 library
 *
 * @copyright Copyright (c) Evgeniy Urvantsev <resmus@gmail.com>
 * @license https://github.com/raoptimus/yii2-jsonrpc2/blob/master/LICENSE.md
 * @link https://github.com/raoptimus/yii2-jsonrpc2
 * @property bool $isActive
 */
class Helper
{
    /**
     * @return string A v4 uuid
     */
    public static function buildUUID(): string
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
