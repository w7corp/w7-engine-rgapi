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

    public function doWebList() {
        global $_W, $_GPC;
        $data = pdo_getall(self::TABLE, array(), '', 'orderBy createtime desc');
        foreach ($data as &$value) {
            $value['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
        }
        include $this->template('list');
    }

    public function doWebAccesstoken() {
        global $_W, $_GPC;
        $_W['page']['title'] = '号码accesstoken';
        $accesstoken = $_W['account']->getAccessToken();
        include $this->template('accesstoken');
    }

    public function doWebCode_to_token() {
        global $_W, $_GPC;
        $_W['page']['title'] = 'code换accesstoken';
        include $this->template('code_to_token');
    }

    public function doWebOther() {
        global $_W, $_GPC;
        $accesstoken = $_W['account']->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token=' . $accesstoken;
        $result = $this->requestApi($url);
        include $this->template('other');
    }

    public function doWebWechatpay() {
        global $_W;
        $data = pdo_getall('demo_rgapi_paylog', array('type in' => array(1, 3, 4)), '', '', 'id DESC');
        foreach ($data as $key => &$value) {
            $value['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
        }
        include $this->template('wechatpay');
    }

    public function doWebAlipay() {
        global $_W;
        $data = pdo_getall('demo_rgapi_paylog', array('type' => 2), '', '', 'id DESC');
        foreach ($data as $key => &$value) {
            $value['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
        }
        include $this->template('alipay');
    }

    public function doWebPay() {
        global $_W, $_GPC;
        try {
            $type = safe_gpc_string($_GPC['type']);
            if (empty($type) || !in_array($type, array('wechat', 'ali'))) {
                iajax(-1, '支付类型错误！');
            }
            load()->library('sdk-module');
            $api = new \W7\Sdk\Module\Api($_W['setting']['server_setting']['app_id'], $_W['setting']['server_setting']['app_secret'], "1", V3_API_DOMAIN);
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
            $api = new \W7\Sdk\Module\Api($_W['setting']['server_setting']['app_id'], $_W['setting']['server_setting']['app_secret'], $account_type, V3_API_DOMAIN);
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

    public function payResult($params) {
        $paylog = pdo_get('core_paylog', array('uniontid' => $params['uniontid']));
        if (!empty($paylog['status'])) {
            pdo_update('demo_rgapi_paylog', array('status' => 1), array('no' => $paylog['tid']));
        }
        exit('success');
    }

    public function refundResult($params) {
        $paylog = pdo_get('core_refundlog', array('refund_uniontid' => $params['refund_uniontid']));
        if (!empty($paylog['status'])) {
            pdo_update('demo_rgapi_paylog', array('status' => 2), array('no' => $paylog['uniontid']));
        }
        exit('success');
    }

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
