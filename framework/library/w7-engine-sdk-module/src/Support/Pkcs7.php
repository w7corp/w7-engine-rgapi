<?php

/**
 * WeEngine System
 *
 * (c) We7Team 2022 <https://www.w7.cc>
 *
 * This is not a free software
 * Using it under the license terms
 * visited https://www.w7.cc for more details
 */

namespace W7\Sdk\Module\Support;

class Pkcs7
{
    public static function padding(string $contents, int $blockSize): string
    {
        if ($blockSize > 256) {
            throw new \InvalidArgumentException('$blockSize 不能超过 256 位。');
        }
        $padding = $blockSize - (strlen($contents) % $blockSize);
        $pattern = chr($padding);

        return $contents . str_repeat($pattern, $padding);
    }

    public static function unpadding(string $contents, int $blockSize): string
    {
        $pad = ord(substr($contents, -1));
        if ($pad < 1 || $pad > $blockSize) {
            $pad = 0;
        }

        return substr($contents, 0, (strlen($contents) - $pad));
    }
}
