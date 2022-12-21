<?php

namespace W7\Sdk\Module\Support;

use GuzzleHttp\Client;

interface ApiRequest
{
    public function __construct(Client $client);
}