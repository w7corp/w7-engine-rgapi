<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.w7.cc/ for more details.
 */

defined('IN_IA') or exit('Access Denied');

/**
 * 判断订单是否符合退款条件
 * @params string $module  需要退款的模块
 * @params string $tid 模块内订单id
 * @return bool true 成功返回true，失败返回error结构错误
 */
function refund_order_can_refund($module, $tid) {
    global $_W;
    $params = array('tid' => $tid, 'module' => $module);
    $params['uniacid'] = $_W['uniacid'];
    $paylog = pdo_get('core_paylog', $params);
    if (empty($paylog)) {
        return error(1, '订单不存在');
    }
    if ($paylog['status'] != 1) {
        return error(1, '此订单还未支付成功不可退款');
    }
    $refund_params = array('status' => 1, 'uniontid' => $paylog['uniontid']);
    $refund_params['uniacid'] = $_W['uniacid'];
    $refund_amount = pdo_getcolumn('core_refundlog', $refund_params, 'SUM(fee)');
    if ($refund_amount >= $paylog['card_fee']) {
        return error(1, '订单已退款成功');
    }
    return true;
}

/**
 * 创建退款订单
 * @params string $tid  模块内订单id
 * @params string $module 需要退款的模块
 * @params string $fee 退款金额
 * @params string $reason 退款原因
 * @return int  成功返回退款单id，失败返回error结构错误
 */
function refund_create_order($tid, $module, $fee = 0, $reason = '') {
    global $_W;
    load()->model('module');
    $order_can_refund = refund_order_can_refund($module, $tid);
    if (is_error($order_can_refund)) {
        return $order_can_refund;
    }
    $module_info = module_fetch($module);
    $moduleid = empty($module_info['mid']) ? '000000' : sprintf("%06d", $module_info['mid']);
    $refund_uniontid = date('YmdHis') . $moduleid . random(8, 1);
    $params = array('tid' => $tid, 'module' => $module);
    $params['uniacid'] = $_W['uniacid'];
    $paylog = pdo_get('core_paylog', $params);
    $uniacid = $_W['uniacid'];
    $refund = array(
        'uniacid' => $uniacid,
        'uniontid' => $paylog['uniontid'],
        'fee' => empty($fee) ? $paylog['card_fee'] : number_format($fee, 2, '.', ''),
        'status' => 0,
        'refund_uniontid' => $refund_uniontid,
        'reason' => safe_gpc_string($reason),
    );
    pdo_insert('core_refundlog', $refund);
    return pdo_insertid();
}

/**
 * 退款
 * @params int $refund_id  退款单id
 * @return array  成功返回退款详情，失败返回error结构错误
 */
function refund($refund_id) {
    load()->classs('pay');
    global $_W;
    $refundlog = pdo_get('core_refundlog', array('id' => $refund_id));
    $params = array('uniontid' => $refundlog['uniontid']);
    $params['uniacid'] = $_W['uniacid'];
    $paylog = pdo_get('core_paylog', $params);
    if ($paylog['type'] == 'wechat' || $paylog['type'] == 'wxapp') {
        $refund_param = reufnd_wechat_build($refund_id);
        if (is_error($refund_param)) {
            return $refund_param;
        }
        $wechat = Pay::create();
        $response = $wechat->refundV3($refund_param);
        if (is_error($response)) {
            pdo_update('core_refundlog', array('status' => '-1'), array('id' => $refund_id));
            return $response;
        } else {
            return $response;
        }
    } elseif ($paylog['type'] == 'alipay') {
        $refund_param = reufnd_ali_build($refund_id);
        if (is_error($refund_param)) {
            return $refund_param;
        }
        $module = '';
        $ali = Pay::create('alipay', $module);
        $response = $ali->refund($refund_param, $refund_id);
        if (is_error($response)) {
            pdo_update('core_refundlog', array('status' => '-1'), array('id' => $refund_id));
            return $response;
        } else {
            return $response;
        }
    }
    return error(1, '此订单退款方式不存在');
}

/**
 * 构造支付宝退款参数
 * @params int $refund_id  退款单id
 * @return array  成功返回请求支付宝退款接口所需参数，失败返回error结构错误
 */
function reufnd_ali_build($refund_id) {
    global $_W;
    $setting = uni_setting_load('payment', $_W['uniacid']);
    $refund_setting = $setting['payment']['ali_refund'];
    if ($refund_setting['switch'] != 1) {
        return error(1, '未开启支付宝退款功能！');
    }
    if (empty($refund_setting['private_key'])) {
        return error(1, '缺少支付宝密钥证书！');
    }

    $refundlog = pdo_get('core_refundlog', array('id' => $refund_id));
    $uniacid = $_W['uniacid'];
    $paylog = pdo_get('core_paylog', array('uniacid' => $uniacid, 'uniontid' => $refundlog['uniontid']));
    $refund_param = array(
        'app_id' => $refund_setting['app_id'],
        'method' => 'alipay.trade.refund',
        'charset' => 'utf-8',
        'sign_type' => 'RSA2',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0',
        'biz_content' => array(
            'out_trade_no' => $refundlog['uniontid'],
            'refund_amount' => $refundlog['fee'],
            'refund_reason' => $refundlog['reason'],
        )
    );
    $refund_param['biz_content'] = json_encode($refund_param['biz_content']);
    return $refund_param;
}

/**
 * 构造微信退款参数
 * @params int $refund_id  退款单id
 * @return array  成功返回请求微信退款接口所需参数，失败返回error结构错误
 */
function reufnd_wechat_build($refund_id) {
    global $_W;
    $setting = uni_setting_load('payment', $_W['uniacid']);
    $refund_setting = $setting['payment']['wechat'];

    if ($refund_setting['refund_switch'] != 1) {
        return error(1, '未开启微信退款功能！');
    }
    if (empty($refund_setting['apiclient_cert']) || empty($refund_setting['apiclient_key'])) {
        return error(1, '缺少微信证书！');
    }
    $refundlog = pdo_get('core_refundlog', array('id' => $refund_id));
    $paylog = pdo_get('core_paylog', array('uniacid' => $_W['uniacid'], 'uniontid' => $refundlog['uniontid']));
    $refund_param = [
        'out_trade_no' => $refundlog['uniontid'],
        'out_refund_no' => $refundlog['refund_uniontid'],
        'reason' => empty($refundlog['reason']) ? '系统退款' : $refundlog['reason'],
        'notify_url' => $_W['siteroot'] . 'payment/wechat/refund.php/' . $_W['uniacid'],
        'amount' => ['refund' => $refundlog['fee'] * 100, 'total' => $paylog['card_fee'] * 100, 'currency' => 'CNY']
    ];
    return $refund_param;
}
