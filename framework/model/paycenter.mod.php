<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$
 */
defined('IN_IA') or exit('Access Denied');

function paycenter_order_status() {
    /*		SUCCESS—支付成功
        REFUND—转入退款
        NOTPAY—未支付
        CLOSED—已关闭
        REVOKED—已撤销（刷卡支付）
        USERPAYING--用户支付中
        PAYERROR--支付失败(其他原因，如银行返回失败)*/
    return array(
        '0' => array(
            'text' => '未支付',
            'class' => 'text-danger',
        ),
        '1' => array(
            'text' => '已支付',
            'class' => 'text-success',
        ),
        '2' => array(
            'text' => '已支付,退款中...',
            'class' => 'text-default',
        ),
    );
}

function paycenter_order_types() {
    return array(
        'wechat' => '微信支付',
        'alipay' => '支付宝支付',
        'credit' => '余额支付',
        'baifubao' => '百付宝'
    );
}

function paycenter_order_trade_types() {
    return array(
        'native' => '扫码支付',
        'jsapi' => '公众号支付',
        'micropay' => '刷卡支付'
    );
}

function paycenter_check_login() {
    global $_W, $_GPC;
    if (empty($_W['uid']) && $_GPC['do'] != 'login') {
        itoast('抱歉，您无权进行该操作，请先登录', murl('entry', array('m' => 'we7_coupon', 'do' => 'clerk', 'op' => 'login'), true, true), 'error');
    }
}
