<?php
/**
 * @author 微擎团队
 * @url
 */
defined('IN_IA') or exit('Access Denied');

class Demo_rgapiModuleSite extends WeModuleSite {
    const TABLE = 'demo_rgapi_riji';
    public function __construct() {
    }

    public function doMobileIndex() {
        global $_W;
        include $this->template('index');
    }

    public function doMobilePay() {
        global $_W, $_GPC;
        $out_trade_no = 'wechat' . date('YmdHis', time()) . time() . rand(11, 99);

        $insert = array(
            'no' => $out_trade_no,
            'code' => '',
            'status' => 0,
            'type' => 3,
            'createtime' => TIMESTAMP,
            'updatetime' => TIMESTAMP,
            'uid' => $_W['uid'],
            'uniacid' => $_W['uniacid'],
        );
        pdo_insert('demo_rgapi_paylog', $insert);

        $params['tid'] = $out_trade_no;
        $params['ordersn'] = $out_trade_no;
        $params['user'] = $_W['uid'];
        $params['fee'] = 0.01;
        $params['title'] = '测试支付';
        $this->pay($params);
    }
    //小程序-日记列表
    public function doWebList() {
        global $_W, $_GPC;
        $data = pdo_getall(self::TABLE, array(), '', 'orderBy createtime desc');
        foreach ($data as &$value) {
            $value['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
        }
        include $this->template('list');
    }
    //获取accesstoken
    public function doWebAccesstoken() {
        global $_W, $_GPC;
        $accesstoken = $_W['account']->getAccessToken();
        include $this->template('accesstoken');
    }
    //code换token
    public function doWebCode_to_token() {
        global $_W, $_GPC;
        include $this->template('code_to_token');
    }
    //其他功能
    public function doWebOther() {
        global $_W, $_GPC;
        $accesstoken = $_W['account']->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token=' . $accesstoken;
        $result = $this->requestApi($url);
        include $this->template('other');
    }
    //内购参数设置
    public function doWebW7pay_setting() {
        global $_W, $_GPC;
        $w7pay_setting = setting_load('w7pay_setting')['w7pay_setting'] ?: [];
        if (checksubmit()) {
            $w7pay_appid = safe_gpc_string($_GPC['w7pay_appid']);
            $w7pay_appsecret = safe_gpc_string($_GPC['w7pay_appsecret']);
            $w7pay_id = intval($_GPC['w7pay_id']);
            if (empty($w7pay_appid) || empty($w7pay_appsecret) || empty($w7pay_id)) {
                itoast('参数不能为空！');
            }
            $w7pay_setting['id'] = $w7pay_id;
            $w7pay_setting['appid'] = $w7pay_appid;
            $w7pay_setting['appsecret'] = $w7pay_appsecret;
            setting_save($w7pay_setting, 'w7pay_setting');
            itoast('保存成功！', '', 'success');
        }
        $pay_notify_url = $_W['siteroot'] . 'web/' . substr($this->createWebUrl('w7pay_notify'), 2);
        $refund_notify_url = $_W['siteroot'] . 'web/' . substr($this->createWebUrl('w7pay_refund'), 2);
        include $this->template('w7pay_setting');
    }
    //内购商品管理
    public function doWebW7pay_goods() {
        global $_W, $_GPC;
        $w7pay_setting = setting_load('w7pay_setting')['w7pay_setting'] ?: [];
        $w7pay_setting['goods'] = $w7pay_setting['goods'] ?? [];
        if (checksubmit('goods')) {
            $good_id = intval($_GPC['goods_id']);
            $good_name = safe_gpc_string($_GPC['goods_name']);
            $good_fee = number_format($_GPC['goods_fee'], 2);
            $w7pay_setting['goods'][$good_id] = ['id' => $good_id, 'name' => $good_name, 'fee' => $good_fee];
            setting_save($w7pay_setting, 'w7pay_setting');
            itoast('商品添加成功！', '', 'success');
        }
        include $this->template('w7pay_goods');
    }
    //内购订单管理
    public function doWebW7pay_order() {
        global $_W, $_GPC;
        $w7pay_setting = setting_load('w7pay_setting')['w7pay_setting'] ?: [];
        $w7pay_setting['goods'] = $w7pay_setting['goods'] ?? [];
        $order = pdo_getall('demo_rgapi_paylog', array('type in' => array(5)), '', '', 'id DESC');
        foreach ($order as $key => &$value) {
            $value['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
        }
        $can_pay = !empty($w7pay_setting['goods']) && !empty($w7pay_setting['id']) && !empty($w7pay_setting['appid']) && !empty($w7pay_setting['appsecret']);
        include $this->template('w7pay_order');
    }
    //删除内购商品
    public function doWebDelete_good() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $w7pay_setting = setting_load('w7pay_setting')['w7pay_setting'] ?: [];
        if (empty($w7pay_setting['goods'])) {
            itoast('', referer());
        }
        if (!empty($w7pay_setting['goods'][$id])) {
            unset($w7pay_setting['goods'][$id]);
            setting_save($w7pay_setting, 'w7pay_setting');
            itoast('删除成功！', referer(), 'success');
        }
        itoast('', referer());
    }
    //内购支付
    public function doWebW7pay() {
        global $_W, $_GPC;
        $good_id = intval($_GPC['good_id']);
        $uniontid = safe_gpc_string($_GPC['pay_sn']);
        if (empty($uniontid) || empty($good_id)) {
            iajax(-1, '参数错误');
        }
        $w7pay_setting = setting_load('w7pay_setting')['w7pay_setting'] ?: [];
        if (empty($w7pay_setting['goods'])) {
            iajax(-1, '不存在任何商品，请先添加');
        }
        if (empty($w7pay_setting['goods'][$good_id])) {
            iajax(-1, '商品不存在！');
        }
        $fee = number_format($w7pay_setting['goods'][$good_id]['fee'], 2);
        $out_trade_no = 'w7pay' . date('YmdHis', time()) . time() . rand(11, 99);
        $log = [
            'type' => 'w7pay',
            'uniacid' => $_W['uniacid'],
            'acid' => $_W['uniacid'],
            'openid' => $_W['uid'],
            'module' => 'demo_rgapi',
            'uniontid' => $uniontid,
            'tid' => $out_trade_no,
            'fee' => $fee,
            'status' => '0',
        ];
        pdo_insert('core_paylog', $log);
        $insert = [
            'no' => $out_trade_no,
            'code' => '',
            'status' => 0,
            'type' => 5,
            'createtime' => TIMESTAMP,
            'updatetime' => TIMESTAMP,
            'uid' => $_W['uid'],
            'uniacid' => $_W['uniacid'],
        ];
        pdo_insert('demo_rgapi_paylog', $insert);
        iajax(0, array('no' => $out_trade_no));
    }
    //内购发起退款
    public function doWebW7refund() {
        global $_W, $_GPC;
        ini_set('display_errors', '1');
        error_reporting(E_ALL ^ E_NOTICE);
        try {
            load()->library('sdk-console');
            $no = safe_gpc_string($_GPC['no']);
            $code = safe_gpc_string($_GPC['code']);
            $rgapi_paylog = pdo_get('demo_rgapi_paylog', ['no' => $no, 'code' => $code]);
            if (empty($rgapi_paylog)) {
                throw new \Exception('订单不存在！');
            }
            $paylog = pdo_get('core_paylog', ['type' => 'w7pay', 'tid' => $no, 'module' => 'demo_rgapi']);
            $w7pay_setting = setting_load('w7pay_setting')['w7pay_setting'] ?: [];
            $refund = new \W7\Sdk\Console\Api\Refund($w7pay_setting['appid'], $w7pay_setting['appsecret']);
            $refund_order_sn = 'w7payrefund' . date('YmdHis', time()) . time() . rand(11, 99);
            $data = new \W7\Sdk\Console\Domain\Pay\RefundRequestData($rgapi_paylog['code'], $refund_order_sn, $paylog['fee'], '退款', '退款remark');
            $refund->refund($data);
            $refund = [
                'uniacid' => $_W['uniacid'],
                'uniontid' => $paylog['uniontid'],
                'fee' => $paylog['fee'],
                'status' => 0,
                'refund_uniontid' => $refund_order_sn,
                'reason' => '退款',
            ];
            pdo_insert('core_refundlog', $refund);
            iajax(0, '已发起退款申请，预计1分钟后退款成功！');
        } catch (\Exception $e) {
            WeUtility::logging('start_refund', var_export($e, true));
            iajax(-1, '退款失败！错误详情: ' . $e->getMessage());
        }
    }
    //内购退款通知处理
    public function doWebW7pay_refund() {
        global $_W, $_GPC;
        $data = array_filter($_GPC, function ($key) {
            return !in_array($key, ['c', 'a', 'do', 'module_name', '__entry', '__state', 'state', 'm']);
        }, ARRAY_FILTER_USE_KEY);
        if (empty($data)) {
            exit('fail.empty data');
        }
        if ($data['refund_status'] !== 'finished') {
            exit('fail.refund_status not finished');
        }
        WeUtility::logging('refund', var_export($data, true));
        try {
            load()->library('sdk-console');
            $w7pay_setting = setting_load('w7pay_setting')['w7pay_setting'] ?: [];
            $refund = new \W7\Sdk\Console\Api\Refund($w7pay_setting['appid'], $w7pay_setting['appsecret']);
            if (!$refund->verifySign($data)) {
                throw new \Exception('退款验签错误');
            }
            $refund_log = table('core_refundlog')->where(['refund_uniontid' => $data['refund_order_sn']])->get();
            if (!empty($refund_log) && $refund_log['status'] == '0' && ($data['refund_fee'] == $refund_log['fee'])) {
                table('core_refundlog')->where(['id' => $refund_log['id']])->fill(['status' => 1])->save();
                pdo_update('demo_rgapi_paylog', ['status' => 2], ['code' => $data['paylog_sn']]);
                exit('success');
            }
        } catch (\Exception $e) {
            WeUtility::logging('W7refund_notify', var_export($e->getMessage(), true), FILE_APPEND);
            throw new \Exception($e->getMessage());
        }
        exit('fail.table_log not exist');
    }
    //内购支付通知处理
    public function doWebW7pay_notify() {
        global $_W, $_GPC;
        $data = array_filter($_GPC, function ($key) {
            return !in_array($key, ['c', 'a', 'do', 'module_name', '__entry', '__state', 'state', 'm']);
        }, ARRAY_FILTER_USE_KEY);
        if (empty($data)) {
            exit('fail.empty data');
        }
        if ($data['paid_status'] !== 'finished') {
            exit('fail.paid_status not finished');
        }
        WeUtility::logging('pay', var_export($data, true));
        try {
            load()->library('sdk-console');
            $w7pay_setting = setting_load('w7pay_setting')['w7pay_setting'] ?: [];
            $pay = new \W7\Sdk\Console\Api\Pay($w7pay_setting['appid'], $w7pay_setting['appsecret']);
            $paylog = $pay->verify($data);
            WeUtility::logging('pay', var_export($paylog, true), FILE_APPEND);
            if ($paylog && $paylog->isPaid()) {
                $log = table('core_paylog')
                    ->where(['uniontid' => $data['paylog_biz_sn']])
                    ->get();
                if (!empty($log) && $log['status'] == '0' && ($data['total_fee'] == $log['fee'])) {
                    table('core_paylog')->where(['plid' => $log['plid']])->fill(['status' => 1])->save();
                    pdo_update('demo_rgapi_paylog', ['status' => 1, 'code' => $data['paylog_sn']], ['no' => $log['tid']]);
                    exit(json_encode(['data' => 'success']));
                }

            }
        } catch (\Exception $e) {
            WeUtility::logging('W7pay_notify', var_export($e->getMessage(), true), FILE_APPEND);
            throw new \Exception($e->getMessage());
        }
        exit(json_encode(['data' => 'fail']));
    }
    //微信支付菜单功能
    public function doWebWechatpay() {
        global $_W;
        $data = pdo_getall('demo_rgapi_paylog', array('type in' => array(1, 3, 4)), '', '', 'id DESC');
        foreach ($data as $key => &$value) {
            $value['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
        }
        include $this->template('wechatpay');
    }
    //支付宝支付菜单功能
    public function doWebAlipay() {
        global $_W;
        $data = pdo_getall('demo_rgapi_paylog', array('type' => 2), '', '', 'id DESC');
        foreach ($data as $key => &$value) {
            $value['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
        }
        include $this->template('alipay');
    }
    //微信、支付宝支付处理
    public function doWebPay() {
        global $_W, $_GPC;
        try {
            $type = safe_gpc_string($_GPC['type']);
            if (empty($type) || !in_array($type, array('wechat', 'ali'))) {
                iajax(-1, '支付类型错误！');
            }
            load()->library('sdk-module');
            $api = new \W7\Sdk\Module\Api(getenv('APP_ID'), getenv('APP_SECRET'), $_W['setting']['server_setting']['app_id'], 1, V3_API_DOMAIN);
            $uniontid = date('YmdHis') . random(14, 1);
            $out_trade_no = $type . date('YmdHis', time()) . time() . rand(11, 99);
            if ('wechat' == $type) {
                $pay = $api->wechatPay($_W['siteroot'] . 'payment/wechat/notify.php');
                $data = $pay->payTransactionsNative("测试支付", $uniontid, 1, array('attach' => json_encode(array('uniacid' => $_W['uniacid']))))->toArray();
                if (empty($data['code_url'])) {
                    iajax(-1, '支付失败！');
                }
                $code = $data['code_url'];
            } else {
                $pay = $api->aliPay($_W['siteroot'] . 'payment/alipay/notify.php');
                $data = $pay->payForPc("测试支付", $uniontid, 0.01)->toArray();
                if (empty($data['data'])) {
                    iajax(-1, '支付失败！');
                }
                $code = $data['data'];
            }

            $log = array(
                'type' => 'wechat' == $type ? 'wechat' : 'alipay',
                'uniacid' => $_W['uniacid'],
                'acid' => $_W['acid'],
                'openid' => $_W['member']['uid'],
                'module' => 'demo_rgapi',
                'uniontid' => $uniontid,
                'tid' => $out_trade_no,
                'fee' => 0.01,
                'card_fee' => 0.01,
                'status' => '0',
                'is_usecard' => '0',
            );
            pdo_insert('core_paylog', $log);
            $insert = array(
                'no' => $out_trade_no,
                'code' => $code,
                'status' => 0,
                'type' => 'wechat' == $type ? 1 : 2,
                'createtime' => TIMESTAMP,
                'updatetime' => TIMESTAMP,
                'uid' => $_W['uid'],
                'uniacid' => $_W['uniacid'],
            );
            pdo_insert('demo_rgapi_paylog', $insert);
            iajax(0, array('type' => $type, 'code' => $code, 'no' => $out_trade_no));
        } catch (Exception $e) {
            iajax(-1, '支付失败！错误详情: ' . $e->getMessage());
        }
    }
    //退款功能
    public function doWebRefund() {
        global $_W, $_GPC;
        try {
            $type = safe_gpc_string($_GPC['type']);
            if (empty($type) || !in_array($type, array('wechat', 'ali'))) {
                iajax(-1, '退款类型错误！');
            }
            load()->library('sdk-module');
            $out_trade_no = safe_gpc_string($_GPC['__input']['no']);
            $paylog = pdo_get('core_paylog', array('tid' => $out_trade_no));
            $account_type = 'wxapp' == $paylog['type'] ? 2 : 1;
            $api = new \W7\Sdk\Module\Api(getenv('APP_ID'), getenv('APP_SECRET'), $_W['setting']['server_setting']['app_id'], $account_type, V3_API_DOMAIN);
            if ('wechat' == $type) {
                $pay = $api->wechatPay($_W['siteroot'] . 'payment/wechat/refund.php');
                $data = $pay->refund($out_trade_no, 1, 1, '', $paylog['uniontid'])->toArray();
                if (!empty($data['status']) && 'SUCCESS' == $data['status']) {
                    iajax(0, '已申请退款!');
                }
            } else {
                $pay = $api->aliPay($_W['siteroot'] . 'payment/alipay/refund.php');
                $data = $pay->refund($paylog['uniontid'], 0.01)->toArray();
            }
            $refund = array(
                'uniacid' => $_W['uniacid'],
                'uniontid' => $out_trade_no,
                'fee' => 0.01,
                'status' => 0,
                'refund_uniontid' => $out_trade_no,
                'reason' => '',
            );
            pdo_insert('core_refundlog', $refund);
            if ('ali' == $type && !empty($data['alipay_trade_refund_response']) && 'Success' == $data['alipay_trade_refund_response']['msg']) {
                pdo_update('core_refundlog', array('status' => 1), array('refund_uniontid' => $out_trade_no));
                pdo_update('demo_rgapi_paylog', array('status' => 2), array('no' => $out_trade_no));
                iajax(0, '退款成功!', referer());
            }
            iajax(0, '已发起退款申请，预计1分钟后退款成功！');
        } catch (Exception $e) {
            iajax(-1, '退款失败！错误详情: ' . $e->getMessage());
        }
    }
    //微信、支付宝支付回调
    public function payResult($params) {
        $paylog = pdo_get('core_paylog', array('uniontid' => $params['uniontid'], 'uniacid' => $params['uniacid']));
        if (!empty($paylog['status'])) {
            pdo_update('demo_rgapi_paylog', array('status' => 1), array('no' => $paylog['tid']));
        }
        exit('success');
    }
    //微信、支付宝退款回调
    public function refundResult($params) {
        $paylog = pdo_get('core_refundlog', array('refund_uniontid' => $params['refund_uniontid']));
        if (!empty($paylog['status'])) {
            pdo_update('demo_rgapi_paylog', array('status' => 2), array('no' => $paylog['uniontid']));
        }
        exit('success');
    }
    //请求支付状态
    public function doWebPayStatus() {
        global $_GPC;
        $out_trade_no = safe_gpc_string($_GPC['__input']['no']);
        $paylog = pdo_get('demo_rgapi_paylog', array('no' => $out_trade_no));
        if (!empty($paylog['status'])) {
            iajax(0, '支付成功!', referer());
        }
        iajax(-1, '支付中!');
    }

    private function requestApi($url, $post = '', $extra = []) {
        $response = ihttp_request($url, $post, $extra);

        $result = @json_decode($response['content'], true);
        if (is_error($response)) {
            if (empty($result)) {
                return error(-1, "接口调用失败, 元数据: {$response['message']}");
            }
            return error($result['errcode'], "访问公众平台接口失败, 错误详情: " . $result['errcode']);
        }
        if (empty($result)) {
            return error(-1, "接口调用失败, 元数据: {$response['meta']}");
        } elseif (!empty($result['errcode'])) {
            return error($result['errcode'], "访问公众平台接口失败, 错误: {$result['errmsg']},错误详情：" . $result['errcode']);
        }

        return $result;
    }
}
