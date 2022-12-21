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

    private function requestApi($url, $post = '', $extra = []) {
        $response = ihttp_request($url, $post, $extra);

        $result = @json_decode($response['content'], true);
        if (is_error($response)) {
            if (empty($result)) {
                return error(-1, "接口调用失败, 元数据: {$response['message']}");
            }
            return error($result['errcode'], "访问公众平台接口失败, 错误详情: ". $result['errcode']);
        }
        if (empty($result)) {
            return error(-1, "接口调用失败, 元数据: {$response['meta']}");
        } elseif (!empty($result['errcode'])) {
            return error($result['errcode'], "访问公众平台接口失败, 错误: {$result['errmsg']},错误详情：" . $result['errcode']);
        }

        return $result;
    }
}
