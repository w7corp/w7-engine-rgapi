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

use GuzzleHttp\Psr7\Response;

class ApiResponse extends Response
{
    /** @var array */
    protected $data = [];

    public function withData(array $data)
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
