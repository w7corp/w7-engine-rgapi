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

class AliPay extends BasePay
{
    /** @var string  */
    protected $type = 'alipay';

    /**
     * 电脑网站支付
     *
     * @see https://opendocs.alipay.com/open/028r8t?scene=22
     *
     * @param string    $subject        订单标题
     * @param string    $out_trade_no   交易创建时传入的商户订单号
     * @param float|int $amount         订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
     * @param string    $return_url     支付成功后同步跳转的页面，是一个http/https开头的字符串
     * @param array     $other          其他非必填参数
     *
     * @return \Psr\Http\Message\ResponseInterface|\W7\Sdk\Module\Support\ApiResponse
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \W7\Sdk\Module\Exceptions\ApiException
     * @throws \W7\Sdk\Module\Exceptions\ApiHttpException
     *
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function payForPc(string $subject, string $out_trade_no, $amount, string $return_url = '', array $other = [])
    {
        return $this->request('payForPc', compact('subject', 'out_trade_no', 'amount', 'return_url', 'other'));
    }

    /**
     * 手机网站支付
     *
     * @see https://opendocs.alipay.com/open/02ivbs
     *
     * @param string    $subject       订单标题
     * @param string    $out_trade_no  交易创建时传入的商户订单号
     * @param float|int $amount        订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
     * @param string    $quit_url      用户付款中途退出返回商户网站的地址
     * @param string    $return_url    支付成功后同步跳转的页面，是一个http/https开头的字符串
     * @param array     $other         其他非必填参数
     *
     * @return \Psr\Http\Message\ResponseInterface|\W7\Sdk\Module\Support\ApiResponse
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \W7\Sdk\Module\Exceptions\ApiException
     * @throws \W7\Sdk\Module\Exceptions\ApiHttpException
     *
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function payForWap(string $subject, string $out_trade_no, $amount, string $quit_url, string $return_url = '', array $other = [])
    {
        return $this->request('payForWap', compact('subject', 'out_trade_no', 'amount', 'quit_url', 'return_url', 'other'));
    }

    /**
     * 交易退款
     *
     * @see https://opendocs.alipay.com/open/028sm9
     *
     * @param string    $out_trade_no 交易创建时传入的商户订单号
     * @param float|int $amount       需要退款的金额，该金额不能大于订单金额，单位为元，支持两位小数
     * @param array     $other        其他非必填参数
     *
     * @return \Psr\Http\Message\ResponseInterface|\W7\Sdk\Module\Support\ApiResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \W7\Sdk\Module\Exceptions\ApiException
     * @throws \W7\Sdk\Module\Exceptions\ApiHttpException
     *
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function refund(string $out_trade_no, $amount, array $other = [])
    {
        return $this->request('refund', compact('out_trade_no', 'amount', 'other'));
    }
}
