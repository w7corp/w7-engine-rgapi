<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/payment/baifubao/bfb_pay.cfg.php : v 7dc20c577be1 : 2015/03/14 09:57:55 : RenChao $
 */

final class sp_conf {
    // 商户在百付宝的商户ID
    public static $SP_NO;
    //const SP_NO = '';
    // 密钥文件路径，该文件中保存了商户的百付宝合作密钥，该文件需要放在一个安全的地方，切勿让外人知晓或者外网访问
    //const SP_KEY_FILE = 'sp.key';
    public static $SP_KEY_FILE;
    // 日志文件
    //const LOG_FILE = 'sdk.log';
    public static $LOG_FILE;
    // 商户订单支付成功
    const SP_PAY_RESULT_SUCCESS = 1;
    // 商户订单等待支付
    const SP_PAY_RESULT_WAITING = 2;
    // 百付宝即时到账支付接口URL（不需要用户登录百付宝）
    const BFB_PAY_DIRECT_NO_LOGIN_URL = "https://www.baifubao.com/api/0/pay/0/direct";
    // 百付宝即时到账支付接口URL（需要用户登录百付宝，不支付银行网关支付）
    const BFB_PAY_DIRECT_LOGIN_URL = "https://www.baifubao.com/api/0/pay/0/direct/0";
    // 百付宝移动端即时到账支付接口URL（不需要用户登录百付宝，不支付银行网关支付）
    const BFB_PAY_WAP_DIRECT_URL = "https://www.baifubao.com/api/0/pay/0/wapdirect";
    // 百付宝订单号查询支付结果接口URL
    const BFB_QUERY_ORDER_URL = "https://www.baifubao.com/api/0/query/0/pay_result_by_order_no";
    // 百付宝订单号查询重试次数
    const BFB_QUERY_RETRY_TIME = 3;
    // 百付宝支付成功
    const BFB_PAY_RESULT_SUCCESS = 1;
    // 百付宝支付通知成功后的回执
    const BFB_NOTIFY_META = "<meta name=\"VIP_BFB_PAYMENT\" content=\"BAIFUBAO\">";
    // 签名校验算法
    const SIGN_METHOD_MD5 = 1;
    const SIGN_METHOD_SHA1 = 2;
    // 百付宝即时到账接口服务ID
    const BFB_PAY_INTERFACE_SERVICE_ID = 1;
    // 百付宝查询接口服务ID
    const BFB_QUERY_INTERFACE_SERVICE_ID = 11;
    // 百付宝接口版本
    const BFB_INTERFACE_VERSION = 2;
    // 百付宝接口字符编码
    const BFB_INTERFACE_ENCODING = 1;
    // 百付宝接口返回格式：XML
    const BFB_INTERFACE_OUTPUT_FORMAT = 1;
    // 百付宝接口货币单位：人民币
    const BFB_INTERFACE_CURRENTCY = 1;
}

sp_conf::$LOG_FILE = IA_ROOT . '/data/logs/bfb_' . date('Ymd') . '.log';
sp_conf::$SP_NO = $payment['mchid'];
sp_conf::$SP_KEY_FILE = $payment['signkey'];
