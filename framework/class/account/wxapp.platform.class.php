<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/framework/class/weixin.platform.class.php : v cbf1b98ef490 : 2015/09/18 07:28:57 : RenChao $.
 */
defined('IN_IA') or exit('Access Denied');

load()->classs('weixin.platform');

class WxappPlatform extends WeixinPlatform {
    const JSCODEURL = 'https://api.weixin.qq.com/sns/component/jscode2session?appid=%s&js_code=%s&grant_type=authorization_code&component_appid=%s&component_access_token=%s';

    const FAST_REGISTER_WEAPP_CREATE = 'https://api.weixin.qq.com/cgi-bin/component/fastregisterweapp?action=create&component_access_token=%s';
    const FAST_REGISTER_WEAPP_SEARCH = 'https://api.weixin.qq.com/cgi-bin/component/fastregisterweapp?action=search&component_access_token=%s';

    //以下声明成public的,控制器中会调用，以防后续整理代码又改成protected
    public $appid;
    public $encodingaeskey;
    public $token;
    protected $refreshtoken;
    protected $tablename = 'account_wxapp';
    protected $menuFrame = 'wxapp';
    protected $type = ACCOUNT_TYPE_APP_AUTH;
    protected $typeName = '微信小程序';
    protected $typeSign = WXAPP_TYPE_SIGN;
    protected $supportVersion = STATUS_ON;

    public function __construct($uniaccount = array()) {
        $setting = setting_load('platform');
        $this->appid = $setting['platform']['appid'];
        $this->token = $setting['platform']['token'];
        $this->encodingaeskey = $setting['platform']['encodingaeskey'];
        parent::__construct($uniaccount);
    }

    protected function getAccountInfo($uniacid) {
        if ('wxd101a85aa106f53e' == $this->account['key']) {
            $this->account['key'] = $this->appid;
            $this->openPlatformTestCase();
        }
        return table('account')->getByUniacid($uniacid);
    }

    public function getOauthInfo($code = '') {
        $component_accesstoken = $this->getComponentAccesstoken();
        if (is_error($component_accesstoken)) {
            return $component_accesstoken;
        }
        $apiurl = sprintf(self::JSCODEURL, $this->account['key'], $code, $this->appid, $component_accesstoken);

        $response = $this->request($apiurl);
        if (is_error($response)) {
            return $response;
        }
        cache_write('account_oauth_refreshtoken' . $this->account['key'], $response['refresh_token']);

        return $response;
    }

    protected function setAuthRefreshToken($token) {
        $tablename = 'account_wxapp';
        pdo_update($tablename, array('auth_refresh_token' => $token), array('uniacid' => $this->account['uniacid']));
        cache_write(cache_system_key('account_auth_refreshtoken', array('uniacid' => $this->account['uniacid'])), $token);
    }

    /**
     * @param string $encryptData 待解密的数据
     * @param string $vi
     */
    public function pkcs7Encode($encrypt_data, $iv) {
        $key = base64_decode($_SESSION['session_key']);
        $result = aes_pkcs7_decode($encrypt_data, $key, $iv);
        if (is_error($result)) {
            return error(1, '解密失败');
        }
        $result = json_decode($result, true);
        if (empty($result)) {
            return error(1, '解密失败');
        }
        if ($result['watermark']['appid'] != $this->account['key']) {
            return error(1, '解密失败');
        }
        unset($result['watermark']);

        return $result;
    }

    public function result($errno, $message = '', $data = '') {
        exit(json_encode(array(
            'errno' => $errno,
            'message' => $message,
            'data' => $data,
        )));
    }

    public function getDailyVisitTrend($date) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/datacube/getweanalysisappiddailyvisittrend?access_token={$token}";

        $response = $this->requestApi($url, json_encode(array('begin_date' => $date, 'end_date' => $date)));
        if (is_error($response)) {
            return $response;
        }

        return $response['list'][0];
    }

    public function fastRegisterWxapp($data) {
        $component_accesstoken = $this->getComponentAccesstoken();
        if (is_error($component_accesstoken)) {
            return $component_accesstoken;
        }
        $apiurl = sprintf(self::FAST_REGISTER_WEAPP_CREATE, $component_accesstoken);
        $post = array(
            'name' => $data['name'],   // 企业名
            'code' => $data['code'], // 企业代码
            'code_type' => $data['code_type'], // 企业代码类型（1：统一社会信用代码， 2：组织机构代码，3：营业执照注册号）
            'legal_persona_wechat' => $data['legal_persona_wechat'], // 法人微信
            'legal_persona_name' => $data['legal_persona_name'], // 法人姓名
            'component_phone' => $data['component_phone'], //第三方联系电话
        );

        return $this->request($apiurl, $post);
    }

    public function fastRegisterWxappSearch($data) {
        $component_accesstoken = $this->getComponentAccesstoken();
        if (is_error($component_accesstoken)) {
            return $component_accesstoken;
        }
        $apiurl = sprintf(self::FAST_REGISTER_WEAPP_SEARCH, $component_accesstoken);
        $post = array(
            'name' => $data['name'],   // 企业名
            'legal_persona_wechat' => $data['legal_persona_wechat'], // 法人微信
            'legal_persona_name' => $data['legal_persona_name'], // 法人姓名
        );

        return $this->request($apiurl, $post);
    }

    //绑定微信用户为小程序体验者
    public function bindTester($wechatid) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array('wechatid' => $wechatid);
        $url = "https://api.weixin.qq.com/wxa/bind_tester?access_token={$token}";

        return $this->request($url, $data);
    }

    //获取小程序服务器域名
    public function getDomain() {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array(
            'action' => 'get',
        );
        $url = "https://api.weixin.qq.com/wxa/modify_domain?access_token={$token}";

        return $this->request($url, $data);
    }

    //操作授权接入小程序业务域名（仅供第三方代小程序调用）
    public function setWebViewDomain($data) {
        $webviewdomain = array();
        if ('get' == $data['action']) {
            $cachekey = cache_system_key('account_web_view_domain', array('uniacid' => $this->account['uniacid']));
            $webviewdomain = cache_load($cachekey);
        }
        if (empty($webviewdomain)) {
            $token = $this->getAccessToken();
            if (is_error($token)) {
                return $token;
            }
            $url = "https://api.weixin.qq.com/wxa/setwebviewdomain?access_token={$token}";
            $webviewdomain = $this->request($url, $data);
            if (is_error($webviewdomain)) {
                return error($webviewdomain['errno'], $this->errorCode($webviewdomain['errno']));
            }
            if ('get' == $data['action']) {
                $webviewdomain = $webviewdomain['webviewdomain'];
                cache_write($cachekey, $webviewdomain, CACHE_EXPIRE_LONG);
            }
        }
        return $webviewdomain;
    }

    //设置小程序服务器域名
    public function modifyDomain($domains) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array(
            'action' => 'set',
            'requestdomain' => $domains['requestdomain'],
            'wsrequestdomain' => $domains['wsrequestdomain'],
            'uploaddomain' => $domains['uploaddomain'],
            'downloaddomain' => $domains['downloaddomain'],
        );
        $url = "https://api.weixin.qq.com/wxa/modify_domain?access_token={$token}";

        return $this->request($url, $data);
    }

    //获取帐号基本信息
    public function getAccountBasicInfo() {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/account/getaccountbasicinfo?access_token={$token}";

        return $this->request($url);
    }

    //小程序名称设置及改名
    public function setNickname($name) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array('nick_name' => $name);
        $url = "https://api.weixin.qq.com/wxa/setnickname?access_token={$token}";

        return $this->request($url, $data);
    }

    //小程序改名审核状态查询
    public function queryNickname($audit_id) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array('audit_id' => $audit_id);
        $url = "https://api.weixin.qq.com/wxa/api_wxa_querynickname?access_token={$token}";

        return $this->request($url, $data);
    }

    //微信认证名称检测
    public function checkwxVerifyNickname($nick_name) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array('nick_name' => $nick_name);
        $url = "https://api.weixin.qq.com/cgi-bin/wxverify/checkwxverifynickname?access_token={$token}";

        return $this->request($url, $data);
    }

    //修改头像
    public function modifyHeadImage($path) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $upload_to_temporary = $this->uploadMedia($path);
        if (is_error($upload_to_temporary)) {
            return $upload_to_temporary;
        }
        $media_id = $upload_to_temporary['media_id'];
        $data = array(
            'head_img_media_id' => $media_id,
            'x1' => 0,
            'y1' => 0,
            'x2' => 1,
            'y2' => 1,
        );
        $url = "https://api.weixin.qq.com/cgi-bin/account/modifyheadimage?access_token={$token}";

        return $this->request($url, $data);
    }

    //修改功能介绍
    public function modifySignature($signature) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array('signature' => $signature);
        $url = "https://api.weixin.qq.com/cgi-bin/account/modifysignature?access_token={$token}";

        return $this->request($url, $data);
    }

    // 获取账号可以设置的所有类目
    public function getAllCategories() {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/wxopen/getallcategories?access_token={$token}";

        return $this->request($url);
    }

    //添加类目
    public function addCategory($category) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        if (!empty($category['certicates']) && !empty($category['certicates']['value'])) {
            $upload_to_temporary = $this->uploadMedia($category['certicates']['value']);
            if (is_error($upload_to_temporary)) {
                return $upload_to_temporary;
            }
            $category['certicates']['value'] = $upload_to_temporary['media_id'];
        }
        $data = array('categories' => $category);
        $url = "https://api.weixin.qq.com/cgi-bin/wxopen/addcategory?access_token={$token}";

        return $this->request($url, $data);
    }

    //删除类目
    public function deleteCategory($data) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/wxopen/deletecategory?access_token={$token}";

        return $this->request($url, $data);
    }

    //获取账号已经设置的所有类目
    public function getCategory() {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/wxopen/getcategory?access_token={$token}";

        return $this->request($url);
    }

    //修改类目
    public function modifyCategory($data) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/wxopen/modifycategory?access_token={$token}";

        return $this->request($url, $data);
    }

    //获取草稿箱内的所有临时代码草稿
    public function getTemplateDraftList() {
        $token = $this->getComponentAccesstoken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/wxa/gettemplatedraftlist?access_token={$token}";

        return $this->request($url);
    }

    //获取代码模版库中的所有小程序代码模版
    public function getTemplatelist() {
        $token = $this->getComponentAccesstoken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/wxa/gettemplatelist?access_token={$token}";

        return $this->request($url);
    }

    //将草稿箱的草稿选为小程序代码模版
    public function addToTemplate($draft_id) {
        $token = $this->getComponentAccesstoken();
        if (is_error($token)) {
            return $token;
        }
        $data = array('draft_id' => $draft_id);
        $url = "https://api.weixin.qq.com/wxa/addtotemplate?access_token={$token}";

        return $this->request($url, $data);
    }

    //删除指定小程序代码模版
    public function deleteTemplate($template_id) {
        $token = $this->getComponentAccesstoken();
        if (is_error($token)) {
            return $token;
        }
        $data = array('template_id' => $template_id);
        $url = "https://api.weixin.qq.com/wxa/deletetemplate?access_token={$token}";

        return $this->request($url, $data);
    }

    //为授权的小程序帐号上传小程序代码
    public function commit($template_id, $ext_json, $version, $desc = '') {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array(
            'template_id' => $template_id,
            'ext_json' => !empty($ext_json) ? json_encode($ext_json) : '',
            'user_version' => $version,
            'user_desc' => $desc,
        );
        $url = "https://api.weixin.qq.com/wxa/commit?access_token={$token}";

        return $this->request($url, $data);
    }

    //获取体验小程序的体验二维码
    public function getQrcode($path = '') {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/wxa/get_qrcode?access_token={$token}";
        if (!empty($path)) {
            $url .= '&path=' . urlencode($path);
        }

        return ihttp_request($url);
    }

    //获取授权小程序帐号已设置的类目
    public function getWxappCategory() {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/wxa/get_category?access_token={$token}";

        return $this->request($url);
    }

    //获取小程序的第三方提交代码的页面配置
    public function getWxappPage() {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/wxa/get_page?access_token={$token}";

        return $this->request($url);
    }

    //将第三方提交的代码包提交审核
    public function submitAudit($item_list = array()) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array('item_list' => $item_list);
        $url = "https://api.weixin.qq.com/wxa/submit_audit?access_token={$token}";

        return $this->request($url, $data);
    }

    //查询最新一次提交的审核状态
    public function getLatestAuditStatus() {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/wxa/get_latest_auditstatus?access_token={$token}";

        return $this->request($url);
    }
    
    //查询指定发布审核单的审核状态
    public function getAuditStatus($auditid) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array('auditid' => $auditid);
        $url = "https://api.weixin.qq.com/wxa/get_auditstatus?access_token={$token}";
        
        return $this->request($url, $data);
    }
    
    //发布已通过审核的小程序
    public function release() {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/wxa/release?access_token={$token}";
        $response = ihttp_request($url, '{}');
        $response = json_decode($response['content'], true);
        if (empty($response) || !empty($response['errcode'])) {
            return error($response['errcode'], $this->errorCode($response['errcode'], $response['errmsg']));
        }

        return $response;
    }

    //小程序审核撤回
    public function undoCodeAudit() {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/wxa/undocodeaudit?access_token={$token}";

        return $this->request($url);
    }
    //小程序版本回退
    public function revertCodeRelease() {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/wxa/revertcoderelease?access_token={$token}";
        return $this->request($url);
    }
}
