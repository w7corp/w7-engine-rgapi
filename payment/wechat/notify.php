<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/payment/wechat/notify.php : v a4b6a17a6d8a : 2015/09/14 08:41:00 : yanghf $
 */
define('IN_MOBILE', true);
require '../../framework/bootstrap.inc.php';
$input = file_get_contents('php://input');
$isxml = true;

if (!empty($input) && empty($_GET['out_trade_no'])) {
    $data = json_decode($input, true);
    if (empty($data)) {
        $result = array(
            'return_code' => 'FAIL',
            'return_msg' => ''
        );
        echo array2xml($result);
        exit;
    }
    if ($data['trade_state'] != 'SUCCESS') {
        $result = array(
            'return_code' => 'FAIL',
            'return_msg' => empty($data['return_msg']) ? $data['err_code_des'] : $data['return_msg']
        );
        echo array2xml($result);
        exit;
    }
    $get = $data;
} else {
    $isxml = false;
    $get = $_GET;
}
load()->web('common');
WeUtility::logging('pay', var_export($get, true));
$log = table('core_paylog')
    ->where(array('uniontid' => $get['out_trade_no']))
    ->get();
$_W['uniacid'] = $_W['weid'] = intval($log['uniacid']);
$_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);
if (!empty($log) && $log['status'] == '0' && (($get['amount']['payer_total'] / 100) == $log['card_fee'])) {
    table('core_paylog')->where(array('plid' => $log['plid']))->fill(array('status' => 1))->save();
    if ($log['type'] == 'wxapp') {
        $site = WeUtility::createModuleWxapp($log['module']);
    } else {
        $site = WeUtility::createModuleSite($log['module']);
    }
    if (!is_error($site)) {
        $method = 'payResult';
        if (method_exists($site, $method)) {
            $ret = array();
            $ret['weid'] = $log['weid'];
            $ret['uniacid'] = $log['uniacid'];
            $ret['acid'] = $log['acid'];
            $ret['result'] = 'success';
            $ret['type'] = $log['type'];
            $ret['from'] = 'notify';
            $ret['tid'] = $log['tid'];
            $ret['uniontid'] = $log['uniontid'];
            $ret['transaction_id'] = $log['transaction_id'];
            $ret['trade_type'] = $get['trade_type'];
            $ret['follow'] = $get['is_subscribe'] == 'Y' ? 1 : 0;
            $ret['user'] = empty($log['openid']) ? $get['openid'] : $log['openid'];
            $ret['fee'] = $log['fee'];
            $ret['tag'] = $log['tag'];
            $ret['is_usecard'] = $log['is_usecard'];
            $ret['card_type'] = $log['card_type'];
            $ret['card_fee'] = $log['card_fee'];
            $ret['card_id'] = $log['card_id'];
            if (!empty($get['time_end'])) {
                $ret['paytime'] = strtotime($get['time_end']);
            }
            $site->$method($ret);
            if ($isxml) {
                $result = array(
                    'return_code' => 'SUCCESS',
                    'return_msg' => 'OK'
                );
                echo array2xml($result);
                exit;
            } else {
                exit('success');
            }
        }
    }
}
if ($isxml) {
    $result = array(
        'return_code' => 'SUCCESS',
        'return_msg' => 'OK'
    );
    echo array2xml($result);
    exit;
} else {
    exit('fail');
}
