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

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use W7\Sdk\Module\Exceptions\ApiException;
use W7\Sdk\Module\Exceptions\ApiHttpException;

class AccountRequest implements ApiRequest
{
    /** @var Client */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return ApiResponse|ResponseInterface
     *
     * @throws ApiException
     * @throws ApiHttpException
     *
     * @noinspection PhpDocRedundantThrowsInspection
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getAccessToken()
    {
        return $this->client->post('/open/api/account/getAccessToken');
    }
}
