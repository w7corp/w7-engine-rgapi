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

    public function doWebPay() {
        $data = pdo_getall('demo_rgapi_paylog', array(), '', '', 'id DESC');
        foreach ($data as $key => &$value) {
            $value['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
        }
        include $this->template('pay');
    }

    public function doWebWechatPay() {
        global $_W;
        try {
            load()->library('sdk-module');
            $api = new \W7\Sdk\Module\Api($_W['setting']['server_setting']['app_id'], $_W['setting']['server_setting']['app_secret'], "1");
            $pay = $api->wechatPay($_W['siteroot'] . 'payment/wechat/notify.php');
            $out_trade_no = 'demo' . date('YmdHis', time()) . time() . rand(1111, 9999);
            $data = $pay->payTransactionsNative("测试支付", $out_trade_no, 1, array('attach' => json_encode(array('uniacid' => $_W['uniacid']))))->toArray();
            if (empty($data['code_url'])) {
                iajax(-1, '支付失败！');
            }
            $log = array(
                'type' => 'wechat',
                'uniacid' => $_W['uniacid'],
                'acid' => $_W['acid'],
                'openid' => $_W['member']['uid'],
                'module' => 'demo_rgapi',
                'uniontid' => $out_trade_no,
                'tid' => $out_trade_no,
                'fee' => 0.01,
                'card_fee' => 0.01,
                'status' => '0',
                'is_usecard' => '0',
            );
            pdo_insert('core_paylog', $log);
            $insert = array(
                'no' => $out_trade_no,
                'code' => $data['code_url'],
                'status' => 0,
                'createtime' => TIMESTAMP,
                'updatetime' => TIMESTAMP,
                'uid' => $_W['uid'],
                'uniacid' => $_W['uniacid'],
            );
            pdo_insert('demo_rgapi_paylog', $insert);
            iajax(0, array('code' => $data['code_url'], 'no' => $out_trade_no));
        } catch (Exception $e) {
            iajax(-1, '支付失败！错误详情: ' . $e->getMessage());
        }
    }

    public function doWebWechatRefund() {
        global $_W, $_GPC;
        try {
            load()->library('sdk-module');
            $api = new \W7\Sdk\Module\Api($_W['setting']['server_setting']['app_id'], $_W['setting']['server_setting']['app_secret'], "1");
            $pay = $api->wechatPay($_W['siteroot'] . 'payment/wechat/refund.php');
            $out_trade_no = safe_gpc_string($_GPC['__input']['no']);
            $data = $pay->refund($out_trade_no, 1, 1, '', $out_trade_no)->toArray();
            if (!empty($data['status']) && 'SUCCESS' == $data['status']) {
                iajax(0, '已申请退款!');
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
            iajax(0, '已发起退款申请，预计1分钟后退款成功！');
        } catch (Exception $e) {
            iajax(-1, '退款失败！错误详情: ' . $e->getMessage());
        }
    }

    public function payResult($params) {
        $paylog = pdo_get('core_paylog', array('uniontid' => $params['uniontid']));
        if (!empty($paylog['status'])) {
            pdo_update('demo_rgapi_paylog', array('status' => 1), array('no' => $params['uniontid']));
        }
        exit('success');
    }

    public function refundResult($params) {
        $paylog = pdo_get('core_refundlog', array('refund_uniontid' => $params['refund_uniontid']));
        if (!empty($paylog['status'])) {
            pdo_update('demo_rgapi_paylog', array('status' => 2), array('no' => $params['uniontid']));
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
