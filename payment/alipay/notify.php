<?php
error_reporting(0);
define('IN_MOBILE', true);

require '../../framework/bootstrap.inc.php';
$input = file_get_contents('php://input');
if (!empty($input)) {
    WeUtility::logging('pay-alipay', var_export($input, true));
    load()->web('common');
    load()->classs('coupon');
    load()->library('sdk-module');
    $appEncryptor = new \W7\Sdk\Module\Support\AppEncryptor($_W['setting']['server_setting']['app_id'], $_W['setting']['server_setting']['token'], $_W['setting']['server_setting']['encodingaeskey']);
    $data = $appEncryptor->decrypt($input);
    if (!empty($data['trade_status']) && 'TRADE_SUCCESS' == $data['trade_status']) {
        $log = table('core_paylog')
            ->where(array('uniontid' => $data['out_trade_no']))
            ->get();
        $_W['uniacid'] = $_W['weid'] = intval($log['uniacid']);
        $_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);

        if (!empty($log) && $log['status'] == '0' && ($data['total_amount'] == $log['card_fee'])) {
            $log['transaction_id'] = $data['trade_no'];
            $record = array();
            $record['status'] = '1';
            table('core_paylog')
                ->where(array('plid' => $log['plid']))
                ->fill($record)
                ->save();

            $site = WeUtility::createModuleSite($log['module']);
            if (!is_error($site)) {
                $method = 'payResult';
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
                    exit('success');
                }
            }
        }
    }
}
exit('fail');
