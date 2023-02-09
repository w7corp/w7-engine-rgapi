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

namespace W7\Sdk\Module\Support\Message;

use W7\Sdk\Module\Exceptions\ApiException;

class Message implements MessageInterface
{
    /** @var string string */
    protected $type = '';

    protected static $studlyCache = [];

    public function getType(): string
    {
        return $this->type;
    }

    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }

    protected static function studly($value)
    {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }

    public function toXmlArray(): array
    {
        throw new ApiException(sprintf('Class "%s" cannot support transform to XML message.', __CLASS__));
    }

    public function toArray(): array
    {
        throw new ApiException(sprintf('Class "%s" cannot support transform to array message.', __CLASS__));
    }
}
