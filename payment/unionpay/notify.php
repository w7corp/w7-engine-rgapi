<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$
 */
error_reporting(0);
define('IN_MOBILE', true);
require '../../framework/bootstrap.inc.php';
$_W['uniacid'] = intval($_POST['reqReserved']);
load()->web('common');
$_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);
$_W['acid'] = $_W['uniaccount']['acid'];
$setting = uni_setting($_W['uniacid'], array('payment'));
if (!is_array($setting['payment'])) {
    exit('没有设定支付参数.');
}
$payment = $setting['payment']['unionpay'];
require '__init.php';

if (!empty($_POST) && verify($_POST) && $_POST['respMsg'] == 'success') {
    $log = table('core_paylog')
        ->where(array('uniontid' => $_POST['orderId']))
        ->get();
    if (!empty($log) && $log['status'] == '0') {
        $log['tag'] = iunserializer($log['tag']);
        $log['tag']['queryId'] = $_POST['queryId'];

        $record = array();
        $record['status'] = 1;
        $record['tag'] = iserializer($log['tag']);
        table('core_paylog')
            ->where(array('plid' => $log['plid']))
            ->fill($record)
            ->save();

        $site = WeUtility::createModuleSite($log['module']);
        if (!is_error($site)) {
            $method = 'payResult';
            if (method_exists($site, $method)) {
                $ret = array();
                $ret['weid'] = $log['uniacid'];
                $ret['uniacid'] = $log['uniacid'];
                $ret['result'] = 'success';
                $ret['type'] = $log['type'];
                $ret['from'] = 'nofity';
                $ret['tid'] = $log['tid'];
                $ret['user'] = $log['openid'];
                $ret['fee'] = $log['fee'];
                $ret['tag'] = $log['tag'];
                //支付成功后新增是否使用优惠券信息【需要模块去处理】
                $ret['is_usecard'] = $log['is_usecard'];
                $ret['card_type'] = $log['card_type'];
                $ret['card_fee'] = $log['card_fee'];
                $ret['card_id'] = $log['card_id'];
                $site->$method($ret);
                exit('success');
            }
        }
    }
}
exit('fail');
