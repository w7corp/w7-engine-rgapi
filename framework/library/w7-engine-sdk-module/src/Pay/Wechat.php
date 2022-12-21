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

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use W7\Sdk\Module\Exceptions\ApiException;
use W7\Sdk\Module\Exceptions\ApiHttpException;
use W7\Sdk\Module\Support\ApiResponse;

class Wechat extends BasePay
{
    /** @var string  */
    protected $type = 'wechat';

    /**
     * 申请退款
     *
     * @param string $out_refund_no  商户系统内部的退款单号，商户系统内部唯一，只能是数字、大小写字母_-|*@ ，同一退款单号多次请求只退一笔。
     * @param int    $refund         退款金额，单位为分，只能为整数，不能超过原订单支付金额。
     * @param int    $total          原支付交易的订单总金额，单位为分，只能为整数。
     * @param string $transaction_id 原支付交易对应的微信订单号
     * @param string $out_trade_no   原支付交易对应的商户订单号
     * @param array  $other          其他非必填参数
     *
     * @return ApiResponse|ResponseInterface
     *
     * @throws ApiException
     * @throws ApiHttpException
     * @throws GuzzleException
     *
     * @noinspection PhpMultipleClassDeclarationsInspection
     */
    public function refund(
        string $out_refund_no,
        int $refund,
        int $total,
        string $transaction_id = '',
        string $out_trade_no = '',
        array $other = []
    ) {
        return $this->request(
            'refund',
            array_merge(compact(
                'out_refund_no',
                'out_trade_no',
                'refund',
                'total',
                'transaction_id',
                'out_trade_no'
            ), $other)
        );
    }

    /**
     * JSAPI下单
     *
     * @param string $description  商品描述
     * @param string $out_trade_no 商户系统内部订单号，只能是数字、大小写字母_-*且在同一个商户号下唯一
     * @param int    $total        订单总金额，单位为分。
     * @param string $openid       用户在直连商户appid下的唯一标识。
     * @param array  $other        其他非必填参数
     *
     * @return ApiResponse|ResponseInterface
     *
     * @throws ApiException
     * @throws ApiHttpException
     * @throws GuzzleException
     *
     * @noinspection PhpMultipleClassDeclarationsInspection
     */
    public function payTransactionsJsapi(
        string $description,
        string $out_trade_no,
        int $total,
        string $openid,
        array $other = []
    ) {
        return $this->request(
            'payTransactionsJsapi',
            array_merge(compact(
                'description',
                'out_trade_no',
                'total',
                'openid'
            ), $other)
        );
    }

    /**
     * Native下单
     *
     * @param string $description  商品描述
     * @param string $out_trade_no 商户系统内部订单号，只能是数字、大小写字母_-*且在同一个商户号下唯一
     * @param int    $total        订单总金额，单位为分。
     * @param array  $other        其他非必填参数
     *
     * @return ResponseInterface|ApiResponse
     *
     * @throws GuzzleException
     * @throws ApiException
     * @throws ApiHttpException
     *
     * @noinspection PhpMultipleClassDeclarationsInspection
     */
    public function payTransactionsNative(
        string $description,
        string $out_trade_no,
        int $total,
        array $other = []
    ) {
        return $this->request(
            'payTransactionsNative',
            array_merge(compact(
                'description',
                'out_trade_no',
                'total'
            ), $other)
        );
    }
}
