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

namespace W7\Sdk\Module\Pay;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use W7\Sdk\Module\Exceptions\ApiException;
use W7\Sdk\Module\Exceptions\ApiHttpException;
use W7\Sdk\Module\Support\ApiRequest;
use W7\Sdk\Module\Support\ApiResponse;

class BasePay implements ApiRequest
{
    /** @var string */
    protected $uri = 'open/pay/create';

    /** @var string */
    protected $type;

    /** @var Client */
    protected $client;

    /** @var string */
    protected $notifyUrl = '';

    public function __construct(Client $client, string $notify_url = '')
    {
        $this->client = $client;
        $this->setNotifyUrl($notify_url);
    }

    public function setNotifyUrl(string $notify_url = ''): BasePay
    {
        $this->notifyUrl = $notify_url;
        return $this;
    }

    public function getNotifyUrl(): string
    {
        return $this->notifyUrl;
    }

    /**
     * @param string $method
     * @param array $data
     *
     * @return ApiResponse|ResponseInterface
     *
     * @throws GuzzleException
     * @throws ApiHttpException
     * @throws ApiException
     *
     * @noinspection PhpDocRedundantThrowsInspection
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    protected function request(string $method, array $data = [])
    {
        return $this->client->post($this->uri, [
            'body' => $this->createBody($this->type, $method, $this->notifyUrl, $data)
        ]);
    }

    protected function createBody(string $pay_type, string $method, string $notify_url = null, array $body = []): string
    {
        return json_encode(array_filter(array_merge($body, compact('pay_type', 'method', 'notify_url'))));
    }
}
