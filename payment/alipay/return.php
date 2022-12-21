<?php
error_reporting(0);
define('IN_MOBILE', true);
if (empty($_GET['out_trade_no'])) {
    exit('request failed.');
}
require '../../framework/bootstrap.inc.php';
load()->app('common');
load()->app('template');
$_W['uniacid'] = $_W['weid'] = intval($_GET['body']);
$setting = uni_setting($_W['uniacid'], array('payment'));
if (!is_array($setting['payment'])) {
    exit('request failed.');
}
$alipay = $setting['payment']['alipay'];
if (empty($alipay)) {
    exit('request failed.');
}
$prepares = array();
foreach ($_GET as $key => $value) {
    if ($key != 'sign' && $key != 'sign_type') {
        $prepares[] = "{$key}={$value}";
    }
}
sort($prepares);
$string = implode('&', $prepares);
$string .= $alipay['secret'];
$sign = md5($string);
if ($sign == $_GET['sign']) {
    $_GET['query_type'] = 'return';
    // 同步验证通过，就记录状态(用户可能手动点击提前关闭页面，导致同步请求未发送成功)
    // 如果return请求来时，未接到notify通知，则模拟notify通知先请求
    WeUtility::logging('pay-alipay', var_export($_GET, true));
    if ($_GET['is_success'] == 'T' && ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS')) {
        if ($_GET['subject'] == '测试支付接口' && $_GET['total_fee'] == 0.01) {
            message('支付回调成功！', $_W['siteroot'] . 'web/index.php?c=profile&a=payment', 'success');
        }
        $log = table('core_paylog')
            ->where(array('uniontid' => $_GET['out_trade_no']))
            ->get();
        if (!empty($log)) {
            $site = WeUtility::createModuleSite($log['module']);
            $method = 'payResult';
            if ($log['status'] == 0 && ($_GET['total_fee'] == $log['card_fee'])) {
                $log['transaction_id'] = $_GET['trade_no'];
                $record = array();
                $record['status'] = '1';
                table('core_paylog')
                    ->where(array('plid' => $log['plid']))
                    ->fill($record)
                    ->save();
                if (!is_error($site)) {
                    $site->weid = $_W['weid'];
                    $site->uniacid = $_W['uniacid'];
                    $site->inMobile = true;
                    if (method_exists($site, $method)) {
                        $ret = array();
                        $ret['weid'] = $log['weid'];
                        $ret['uniacid'] = $log['uniacid'];
                        $ret['result'] = 'success';
                        $ret['type'] = $log['type'];
                        $ret['from'] = 'notify';
                        $ret['tid'] = $log['tid'];
                        $ret['uniontid'] = $log['uniontid'];
                        $ret['transaction_id'] = $log['transaction_id'];
                        $ret['user'] = $log['openid'];
                        $ret['fee'] = $log['fee'];
                        $ret['is_usecard'] = $log['is_usecard'];
                        $ret['card_type'] = $log['card_type'];
                        $ret['card_fee'] = $log['card_fee'];
                        $ret['card_id'] = $log['card_id'];
                        $site->$method($ret);
                    }
                }
            }
            // 状态已为支付成功
            if (!is_error($site)) {
                $ret['tid'] = $log['tid'];
                $ret['result'] = 'success';
                $ret['from'] = 'return';
                $site->$method($ret);
                exit;
            }
        }
    }
} else {
    message('支付异常，请返回微信客户端查看订单状态或是联系管理员', '', 'error');
}
