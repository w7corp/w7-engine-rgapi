<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/framework/class/weixin.platform.class.php : v cbf1b98ef490 : 2015/09/18 07:28:57 : RenChao $.
 */
defined('IN_IA') or exit('Access Denied');

define('ACCOUNT_PLATFORM_API_ACCESSTOKEN', 'https://api.weixin.qq.com/cgi-bin/component/api_component_token');
define('ACCOUNT_PLATFORM_API_PREAUTHCODE', 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=');
define('ACCOUNT_PLATFORM_API_LOGIN', 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=%s&pre_auth_code=%s&redirect_uri=%s&auth_type=%s');
define('ACCOUNT_PLATFORM_API_QUERY_AUTH_INFO', 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=');
define('ACCOUNT_PLATFORM_API_ACCOUNT_INFO', 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=');
define('ACCOUNT_PLATFORM_API_REFRESH_AUTH_ACCESSTOKEN', 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=');
define('ACCOUNT_PLATFORM_API_OAUTH_CODE', 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&component_appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_base&state=%s#wechat_redirect');
define('ACCOUNT_PLATFORM_API_OAUTH_USERINFO', 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_userinfo&state=%s&component_appid=%s#wechat_redirect');
define('ACCOUNT_PLATFORM_API_OAUTH_INFO', 'https://api.weixin.qq.com/sns/oauth2/component/access_token?appid=%s&component_appid=%s&code=%s&grant_type=authorization_code&component_access_token=');
define('ACCOUNT_PLATFORM_API_LOGIN_ACCOUNT', 1); //公众号授权
define('ACCOUNT_PLATFORM_API_LOGIN_WXAPP', 2); //小程序授权
load()->classs('weixin.account');
load()->func('communication');

class WeixinPlatform extends WeixinAccount {
    //以下声明成public的,控制器中会调用，以防后续整理代码又改成protected
    public $appid;
    protected $appsecret;
    public $token;
    public $encodingaeskey;
    protected $refreshtoken;
    protected $menuFrame = 'account';
    protected $type = ACCOUNT_TYPE_OFFCIAL_AUTH;
    protected $typeName = '公众号';
    protected $typeSign = ACCOUNT_TYPE_SIGN;

    public function __construct($uniaccount = array()) {
        $setting = setting_load('platform');
        $this->appid = !empty($setting['platform']['appid']) ? $setting['platform']['appid'] : '';
        $this->appsecret = !empty($setting['platform']['appsecret']) ? $setting['platform']['appsecret'] : '';
        $this->token = !empty($setting['platform']['token']) ? $setting['platform']['token'] : '';
        $this->encodingaeskey = !empty($setting['platform']['encodingaeskey']) ? $setting['platform']['encodingaeskey'] : '';
        parent::__construct($uniaccount);
    }

    protected function getAccountInfo($uniacid) {
        if ('wx570bc396a51b8ff8' == $this->account['key']) {
            $this->account['key'] = $this->appid;
            $this->openPlatformTestCase();
        }
        return table('account')->getByUniacid($uniacid);
    }

    public function getComponentAccesstoken() {
        $accesstoken = cache_load(cache_system_key('account_component_assesstoken'));
        if (empty($accesstoken) || empty($accesstoken['value'])) {
            $ticket = empty($GLOBALS['_W']['setting']['account_ticket']) ? '' : $GLOBALS['_W']['setting']['account_ticket'];
            if (empty($ticket)) {
                return error(1, '缺少接入平台关键数据，等待微信开放平台推送数据，请十分钟后再试或是检查“授权事件接收URL”是否写错（index.php?c=account&amp;a=auth&amp;do=ticket地址中的&amp;符号容易被替换成&amp;amp;）');
            }
            $data = array(
                'component_appid' => $this->appid,
                'component_appsecret' => $this->appsecret,
                'component_verify_ticket' => $ticket,
            );
            $response = $this->request(ACCOUNT_PLATFORM_API_ACCESSTOKEN, $data);
            if (is_error($response)) {
                return $response;
            }
            $accesstoken = array(
                'value' => $response['component_access_token'],
            );
            $record_expire = intval($response['expires_in']) - 200;
            cache_write(cache_system_key('account_component_assesstoken'), $accesstoken, $record_expire);
        }

        return $accesstoken['value'];
    }

    public function getPreauthCode() {
        $component_accesstoken = $this->getComponentAccesstoken();
        if (is_error($component_accesstoken)) {
            return $component_accesstoken;
        }
        $data = array(
            'component_appid' => $this->appid,
        );
        $response = $this->request(ACCOUNT_PLATFORM_API_PREAUTHCODE . $component_accesstoken, $data);
        if (is_error($response)) {
            return $response;
        }

        return $response['pre_auth_code'];
    }

    public function getAuthInfo($code) {
        $component_accesstoken = $this->getComponentAccesstoken();
        if (is_error($component_accesstoken)) {
            return $component_accesstoken;
        }
        $post = array(
            'component_appid' => $this->appid,
            'authorization_code' => $code,
        );
        $response = $this->request(ACCOUNT_PLATFORM_API_QUERY_AUTH_INFO . $component_accesstoken, $post);
        if (is_error($response)) {
            return $response;
        }
        $this->setAuthRefreshToken($response['authorization_info']['authorizer_refresh_token']);

        return $response;
    }

    public function getAuthorizerInfo($appid = '') {
        $component_accesstoken = $this->getComponentAccesstoken();
        if (is_error($component_accesstoken)) {
            return $component_accesstoken;
        }
        $appid = !empty($appid) ? $appid : $this->account['app_id'];
        $post = array(
            'component_appid' => $this->appid,
            'authorizer_appid' => $appid,
        );

        return $this->request(ACCOUNT_PLATFORM_API_ACCOUNT_INFO . $component_accesstoken, $post);
    }
    
    //拉取所有已授权的帐号信息
    public function getAuthorizerList() {
        $token = $this->getComponentAccesstoken();
        if (is_error($token)) {
            return $token;
        }
        $data = array(
            'component_appid' => $this->appid,
            'offset' => 0,
            'count' => 500
        );
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_list?component_access_token={$token}";
        return $this->request($url, $data);
    }
    
    public function getAccessToken() {
        $cachekey = cache_system_key('account_auth_accesstoken', array('key' => $this->account['app_id']));
        $auth_accesstoken = cache_load($cachekey);
        if (empty($auth_accesstoken) || empty($auth_accesstoken['value'])) {
            $component_accesstoken = $this->getComponentAccesstoken();
            if (is_error($component_accesstoken)) {
                return $component_accesstoken;
            }
            $this->refreshtoken = $this->getAuthRefreshToken();
            $data = array(
                'component_appid' => $this->appid,
                'authorizer_appid' => $this->account['app_id'],
                'authorizer_refresh_token' => $this->refreshtoken,
            );
            $response = $this->request(ACCOUNT_PLATFORM_API_REFRESH_AUTH_ACCESSTOKEN . $component_accesstoken, $data);
            if (is_error($response)) {
                return $response;
            }
            if ($response['authorizer_refresh_token'] != $this->refreshtoken) {
                $this->setAuthRefreshToken($response['authorizer_refresh_token']);
            }
            $auth_accesstoken = array(
                'value' => $response['authorizer_access_token'],
            );
            $record_expire = intval($response['expires_in']) - 200;
            cache_write($cachekey, $auth_accesstoken, $record_expire);
        }

        return $auth_accesstoken['value'];
    }

    public function fetch_token() {
        return $this->getAccessToken();
    }

    public function getAuthLoginUrl($auth_type = ACCOUNT_PLATFORM_API_LOGIN_ACCOUNT, $if_console = false) {
        $allowed_type = array(ACCOUNT_PLATFORM_API_LOGIN_ACCOUNT, ACCOUNT_PLATFORM_API_LOGIN_WXAPP);
        $auth_type = in_array($auth_type, $allowed_type) ? $auth_type : ACCOUNT_PLATFORM_API_LOGIN_ACCOUNT;
        $preauthcode = $this->getPreauthCode();
        if (is_error($preauthcode)) {
            $authurl = "javascript:alert('{$preauthcode['message']}');";
        } else {
            $redirect = $GLOBALS['_W']['siteroot'] . 'web/index.php?c=account&a=auth&do=forward' . ($if_console ? '&if_console=1' : '');
            if (ACCOUNT_PLATFORM_API_LOGIN_WXAPP == $auth_type) {
                $redirect = $GLOBALS['_W']['siteroot'] . 'web/index.php?c=wxapp&a=auth&do=forward' . ($if_console ? '&if_console=1' : '');
            }
            $authurl = sprintf(
                ACCOUNT_PLATFORM_API_LOGIN,
                $this->appid,
                $preauthcode,
                urlencode($redirect),
                $auth_type
            );
        }

        return $authurl;
    }

    public function getOauthCodeUrl($callback, $state = '') {
        return sprintf(ACCOUNT_PLATFORM_API_OAUTH_CODE, $this->account['app_id'], $this->appid, $callback, $state);
    }

    public function getOauthUserInfoUrl($callback, $state = '', $extra = array()) {
        return sprintf(ACCOUNT_PLATFORM_API_OAUTH_USERINFO, $this->account['app_id'], $callback, $state, $this->appid);
    }

    public function getOauthInfo($code = '') {
        $component_accesstoken = $this->getComponentAccesstoken();
        if (is_error($component_accesstoken)) {
            return $component_accesstoken;
        }
        $apiurl = sprintf(ACCOUNT_PLATFORM_API_OAUTH_INFO . $component_accesstoken, $this->account['app_id'], $this->appid, $code);
        $response = $this->request($apiurl);
        if (is_error($response)) {
            return $response;
        }
        cache_write('account_oauth_refreshtoken' . $this->account['app_id'], $response['refresh_token']);

        return $response;
    }

    public function getJsApiTicket() {
        $cachekey = cache_system_key('jsticket', array('uniacid' => $this->account['uniacid']));
        $js_ticket = cache_load($cachekey);
        if (empty($js_ticket) || empty($js_ticket['value'])) {
            $access_token = $this->getAccessToken();
            if (is_error($access_token)) {
                return $access_token;
            }
            $apiurl = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$access_token}&type=jsapi";
            $response = $this->request($apiurl);
            if (is_error($response)) {
                return $response;
            }
            $js_ticket = array(
                'value' => $response['ticket'],
            );
            $record_expire = $response['expires_in'] - 200;
            cache_write($cachekey, $js_ticket, $record_expire);
        }
        $this->account['jsapi_ticket'] = $js_ticket;

        return $js_ticket['value'];
    }

    public function getJssdkConfig($url = '') {
        global $_W, $urls;
        $jsapiTicket = $this->getJsApiTicket();
        if (is_error($jsapiTicket)) {
            $jsapiTicket = $jsapiTicket['message'];
        }
        $nonceStr = random(16);
        $timestamp = TIMESTAMP;
        $url = empty($url) ? ($urls['scheme'] . '://' . $urls['host'] . $_SERVER['REQUEST_URI']) : $url;
        $string1 = "jsapi_ticket={$jsapiTicket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";
        $signature = sha1($string1);
        $config = array(
            'appId' => $this->account['app_id'],
            'nonceStr' => $nonceStr,
            'timestamp' => "$timestamp",
            'signature' => $signature,
        );
        if (DEVELOPMENT) {
            $config['url'] = $url;
            $config['string1'] = $string1;
            $config['name'] = $this->account['name'];
        }

        return $config;
    }

    public function openPlatformTestCase() {
        global $_GPC;
        $post = file_get_contents('php://input');
        WeUtility::logging('platform-test-message', $post);
        $encode_message = $this->xmlExtract($post);
        $message = aes_decode($encode_message['encrypt'], $this->encodingaeskey);
        $message = $this->parse($message);
        $response = array(
            'ToUserName' => $message['from'],
            'FromUserName' => $message['to'],
            'CreateTime' => TIMESTAMP,
            'MsgId' => TIMESTAMP,
            'MsgType' => 'text',
        );
        if ('TESTCOMPONENT_MSG_TYPE_TEXT' == $message['content']) {
            $response['Content'] = 'TESTCOMPONENT_MSG_TYPE_TEXT_callback';
        }
        if ('event' == $message['msgtype']) {
            $response['Content'] = $message['event'] . 'from_callback';
        }
        if (strexists($message['content'], 'QUERY_AUTH_CODE')) {
            list($sufixx, $authcode) = explode(':', $message['content']);
            $auth_info = $this->getAuthInfo($authcode);
            WeUtility::logging('platform-test-send-message', var_export($auth_info, true));
            $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $auth_info['authorization_info']['authorizer_access_token'];
            $data = array(
                'touser' => $message['from'],
                'msgtype' => 'text',
                'text' => array('content' => $authcode . '_from_api'),
            );
            $response = ihttp_request($url, urldecode(json_encode($data)));
            exit('');
        }
        $xml = array(
            'Nonce' => safe_gpc_string($_GPC['nonce']),
            'TimeStamp' => safe_gpc_string($_GPC['timestamp']),
            'Encrypt' => aes_encode(array2xml($response), $this->encodingaeskey, $this->appid),
        );
        $signature = array($xml['Encrypt'], $this->token, safe_gpc_string($_GPC['timestamp']), safe_gpc_string($_GPC['nonce']));
        sort($signature, SORT_STRING);
        $signature = implode($signature);
        $xml['MsgSignature'] = sha1($signature);
        exit(array2xml($xml));
    }
    
    //获取公众号/小程序所绑定的开放平台帐号
    public function openGet($appid = '') {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array(
            'appid' => !empty($appid) ? $appid : $this->account['app_id'],
        );
        $url = "https://api.weixin.qq.com/cgi-bin/open/get?access_token={$token}";
        return $this->request($url, $data);
    }
    
    //将公众号/小程序绑定到开放平台帐号下
    public function openBind($appid = '') {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array(
            'appid' => !empty($appid) ? $appid : $this->account['app_id'],
            'open_appid' => $this->appid,
        );
        $url = "https://api.weixin.qq.com/cgi-bin/open/bind?access_token={$token}";
        return $this->request($url, $data);
    }
    
    protected function request($url, $post = array()) {
        if (!empty($post)) {
            $response = ihttp_request($url, json_encode($post, JSON_UNESCAPED_UNICODE));
        } else {
            $response = ihttp_request($url);
        }
        $response = json_decode($response['content'], true);
        if (empty($response) || !empty($response['errcode'])) {
            return error($response['errcode'], $this->errorCode($response['errcode'], $response['errmsg']));
        }

        return $response;
    }

    protected function getAuthRefreshToken() {
        $auth_refresh_token = cache_load(cache_system_key('account_auth_refreshtoken', array('uniacid' => $this->account['uniacid'])));
        if (empty($auth_refresh_token)) {
            $auth_refresh_token = $this->account['auth_refresh_token'];
            cache_write(cache_system_key('account_auth_refreshtoken', array('uniacid' => $this->account['uniacid'])), $auth_refresh_token);
        }

        return $auth_refresh_token;
    }

    protected function setAuthRefreshToken($token) {
        $tablename = 'account_wechats';
        pdo_update($tablename, array('auth_refresh_token' => $token), array('uniacid' => $this->account['uniacid']));
        cache_write(cache_system_key('account_auth_refreshtoken', array('uniacid' => $this->account['uniacid'])), $token);
    }

    public function result($errno, $message = '', $data = '') {
        exit(json_encode(array(
            'errno' => $errno,
            'message' => $message,
            'data' => $data,
        )));
    }
}
