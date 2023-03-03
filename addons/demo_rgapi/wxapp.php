<?php
/**
 * @author 微擎团队
 * @url
 */
class Demo_rgapiModuleWxapp extends WeModuleWxapp {
    const TABLE = 'demo_rgapi_riji';

    private $gpc;
    private $w;
    private $uid; // 用户ID

    public function __construct() {
        global $_W;
        global $_GPC;
        $this->gpc = $_GPC;
        $this->w = $_W;
        $this->uid = $_W['openid'];
        $this->uniacid = $_W['uniacid'];
    }

    public function get($key, $default = null) {
        $this->isLogin();
        return isset($this->gpc[$key]) ? $this->gpc[$key] : $default;
    }

    /**
     * 显示数据
     * 接口一个名为"index"的接口
     * 响应json串.
     */
    public function doPageIndex() {
        $this->isLogin();
        $this->result(0, '', array('hello' => 'word'));
    }

    /**
     *  日记列表
     */
    public function doPageList() {
        $this->isLogin();
        $data = pdo_getall(self::TABLE, array('uniacid' => $this->uniacid, 'uid' => $this->uid), '', 'orderBy createtime desc');
        $this->result(0, '日记列表', $data);
    }

    /**
     *  获取单条日记
     */
    public function doPageShow() {
        $this->isLogin();
        $id = intval($this->get('id', 0));
        $data = pdo_get(self::TABLE, array('id' => $id, 'uid' => $this->uid, 'uniacid' => $this->uniacid));
        $this->result(0, '获取单条日记', $data);
    }

    /**
     *  修改单条日记
     */
    public function doPageEdit() {
        $this->isLogin();
        $id = intval($this->get('id', 0));
        $title = $this->get('title');
        $content = $this->get('content');
        $data = pdo_update(self::TABLE, array('title' => $title, 'content' => $content), array('id' => $id, 'uid' => $this->uid, 'uniacid' => $this->uniacid));
        $this->result(0, '编辑单条日记', $data ? 1 : 0);
    }

    /**
     *  添加日记
     */
    public function doPageAdd() {
        $this->isLogin();
        $title = $this->get('title', '');
        $content = $this->get('content');
        $image = $this->get('image', '');

        $insert = pdo_insert(self::TABLE, array('title' => $title, 'image' => $image,
            'content' => $content, 'createtime' => TIMESTAMP, 'updatetime' => TIMESTAMP,
            'uid' => $this->uid, 'uniacid' => $this->uniacid));

        if ($insert) {
            $this->result(0, 'message', $insert);
            return;
        }
        $this->result(0, '添加失败');
    }

    /**
     *  删除日记
     */
    public function doPageDel() {
        $this->isLogin();
        $result = pdo_delete(self::TABLE, array('id' => intval($this->get('id')),
            'uid' => $this->uid, 'uniacid' => $this->uniacid));
        $this->result(0, '', $result ? 1 : 0);
    }

    /**
     * 微信支付
     */
    public function doPagePay() {
        $this->isLogin();
        $out_trade_no = 'wechat' . date('YmdHis', time()) . time() . rand(11, 99);
        $order = array(
            'tid' => $out_trade_no,
            'user' => $this->w['openid'],
            'fee' => 0.01,
            'title' => '测试支付',
        );
        $pay_params = $this->pay($order);
        if (is_error($pay_params)) {
            return $this->result(1, $pay_params['message']);
        }
        $insert = array(
            'no' => $out_trade_no,
            'code' => '',
            'status' => 0,
            'type' => 4,
            'createtime' => TIMESTAMP,
            'updatetime' => TIMESTAMP,
            'uid' => $this->w['uid'],
            'uniacid' => $this->w['uniacid'],
        );
        pdo_insert('demo_rgapi_paylog', $insert);
        return $this->result(0, '', $pay_params);
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

    public function isLogin() {
        if (empty($this->uid)) {
            $this->result(41009, '请先登录');
        }
    }
}
