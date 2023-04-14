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

namespace W7\Sdk\Module;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use W7\Sdk\Module\Exceptions\InvalidArgumentException;
use W7\Sdk\Module\Middlewares\AddSignMiddleware;
use W7\Sdk\Module\Middlewares\ResponseHandlerMiddleware;
use W7\Sdk\Module\Pay\AliPay;
use W7\Sdk\Module\Pay\Wechat;
use W7\Sdk\Module\Support\Account;
use W7\Sdk\Module\Support\AccountRequest;

class Api
{
    /** @var Client */
    protected $client;

    /** @var Wechat */
    protected $wechatPay;

    /** @var AliPay */
    protected $aliPay;

    /** @var AccountRequest */
    protected $app;

    /** @var string */
    protected $appId;

    /** @var string */
    protected $linkAppId;

    /** @var string */
    protected $appSecret;

    /** @var int  */
    protected $accountType;

    /**
     * @param string $app_id       应用关联的Appid
     * @param string $app_secret   应用关联AppSecret
     * @param string $link_app_id  应用关联App Id
     * @param int    $account_type 号码类型，使用
     * @param string $base_uri     基础通信URI
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $app_id,
        string $app_secret,
        string $link_app_id,
        int $account_type = Account::TYPE_NONE,
        string $base_uri = 'https://api.w7.cc/w7api/mgw/w7_rangineapi/'
    ) {
        $this->appId       = $app_id;
        $this->appSecret   = $app_secret;
        $this->accountType = $account_type;
        $this->linkAppId   = $link_app_id;

        $handler = new CurlHandler();
        $stack   = HandlerStack::create($handler);
        $stack->push($this->getMiddleware(AddSignMiddleware::class, $this->appId, $this->appSecret, $this->linkAppId, $this->accountType));
        $stack->push($this->getMiddleware(ResponseHandlerMiddleware::class));

        $this->client = new Client([
            'http_errors' => false,
            'base_uri'    => $base_uri,
            'handler'     => $stack
        ]);
    }

    public function app(): AccountRequest
    {
        if (!isset($this->app)) {
            $this->app = new AccountRequest($this->client);
        }

        return $this->app;
    }

    public function wechatPay(string $notify_url = ''): Wechat
    {
        if (!isset($this->wechatPay)) {
            $this->wechatPay = new Wechat($this->client, $notify_url);
        }

        return $this->wechatPay;
    }

    public function aliPay(string $notify_url = ''): AliPay
    {
        if (!isset($this->aliPay)) {
            $this->aliPay = new AliPay($this->client, $notify_url);
        }

        return $this->aliPay;
    }

    /**
     * 获取关联下的号码列表
     *
     * @return \Psr\Http\Message\ResponseInterface|\W7\Sdk\Module\Support\ApiResponse
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \W7\Sdk\Module\Exceptions\ApiException
     * @throws \W7\Sdk\Module\Exceptions\ApiHttpException
     *
     * @noinspection PhpDocRedundantThrowsInspection
     * @noinspection PhpReturnDocTypeMismatchInspection
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function getAccountList()
    {
        return $this->client->post('open/api/account/list');
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getMiddleware(string $class, ...$params)
    {
        if (class_exists($class) && method_exists($class, '__invoke')) {
            $handler = function (...$args) use ($class) {
                return (new $class())(...$args);
            };
            return $handler(...$params);
        }

        throw new InvalidArgumentException('中间件配置错误');
    }
}
