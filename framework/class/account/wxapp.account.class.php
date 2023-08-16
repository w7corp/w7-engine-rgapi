<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
load()->func('communication');

class WxappAccount extends WeAccount {
    protected $menuFrame = 'wxapp';
    protected $type = ACCOUNT_TYPE_APP_NORMAL;
    protected $typeName = '微信小程序';
    protected $typeSign = WXAPP_TYPE_SIGN;
    protected $supportVersion = STATUS_ON;

    protected function getAccountInfo($uniacid) {
        return table('account')->getByUniacid($uniacid);
    }

    public function getOauthInfo($code = '') {
        global $_W, $_GPC;
        if (!empty($_GPC['code'])) {
            $code = $_GPC['code'];
        }
        try {
            load()->library('sdk-module');
            $api = new \W7\Sdk\Module\Api(getenv('APP_ID'), getenv('APP_SECRET'), $_W['setting']['server_setting']['app_id'], 2, V3_API_DOMAIN);
            return $api->app()->jsCode2Session($code);
        } catch (\W7\Sdk\Module\Exceptions\ApiException $e) {
            return error(-1, '获取微信小程序授权失败, 请稍后重试！错误详情: ' . $e->getResponse()->getBody()->getContents());
        }
    }

    public function getOauthCodeUrl($callback, $state = '') {
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->account['app_id']}&redirect_uri={$callback}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";
    }

    public function getOauthUserInfoUrl($callback, $state = '') {
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->account['app_id']}&redirect_uri={$callback}&response_type=code&scope=snsapi_userinfo&state={$state}#wechat_redirect";
    }

    /**
     * 微擎系统对来自微信公众平台请求的安全校验.
     *
     * @see WeAccount::checkSign()
     */
    public function checkSign() {
        $token = $this->account['token'];
        $signkey = array($token, $_GET['timestamp'], $_GET['nonce']);
        sort($signkey, SORT_STRING);
        $signString = implode($signkey);
        $signString = sha1($signString);

        return $signString == $_GET['signature'];
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

    public function getAccessToken() {
        global $_W;
        $cachekey = cache_system_key('accesstoken_key', array('key' => $this->account['key']));
        $cache = cache_load($cachekey);
        if (!empty($cache) && !empty($cache['token'])) {
            $this->account['access_token'] = $cache;
            return $cache['token'];
        }
        if (getenv('LOCAL_DEVELOP')) {
            if (empty($this->account['app_id']) || empty($this->account['app_secret'])) {
                return error('-1', '未填写小程序的 appid 或 appsecret！');
            }
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->account['app_id']}&secret={$this->account['app_secret']}";
            $token = $this->requestApi($url);
        } else {
            if (empty($_W['setting']['server_setting']['app_id'])) {
                return error('-1', '请先到系统功能下进行“一键授权关联”。');
            }
            try {
                load()->library('sdk-module');
                $api = new \W7\Sdk\Module\Api(getenv('APP_ID'), getenv('APP_SECRET'), $_W['setting']['server_setting']['app_id'], "2", V3_API_DOMAIN);
                $token = $api->app()->getAccessToken()->toArray();
            } catch (\W7\Sdk\Module\Exceptions\ApiException $e) {
                return error(-1, '获取微信小程序授权失败, 请稍后重试！错误详情: ' . $e->getResponse()->getBody()->getContents());
            }
        }

        if (!empty($token['errcode']) && '40164' == $token['errcode']) {
            return error(-1, $this->errorCode($token['errcode'], $token['errmsg']));
        }
        if (empty($token) || !is_array($token) || empty($token['access_token']) || empty($token['expires_in'])) {
            return error('-1', '获取微信公众号授权失败！错误代码:' . $token['errcode'] . '，错误信息:' . $this->errorCode($token['errcode']));
        }
        $record = array(
            'token' => $token['access_token'],
        );
        $record_expire = intval($token['expires_in']) - 200;
        $this->account['access_token'] = $record;
        cache_write($cachekey, $record, $record_expire);

        return $record['token'];
    }

    public function getJssdkConfig($url = '') {
        return array();
    }

    /**
     * 获取永久二维码
     *
     * @param unknown $path
     * @param string  $width
     * @param array   $option
     */
    public function getCodeLimit($path, $width = '430', $option = array()) {
        if (!preg_match('/[0-9a-zA-Z\&\/\:\=\?\-\.\_\~\@]{1,128}/', $path)) {
            return error(1, '路径值不合法');
        }
        $access_token = $this->getAccessToken();
        if (is_error($access_token)) {
            return $access_token;
        }
        $data = array(
            'path' => $path,
            'width' => intval($width),
        );
        if (!empty($option['auto_color'])) {
            $data['auto_color'] = intval($option['auto_color']);
        }
        if (!empty($option['line_color'])) {
            $data['line_color'] = array(
                'r' => $option['line_color']['r'],
                'g' => $option['line_color']['g'],
                'b' => $option['line_color']['b'],
            );
            $data['auto_color'] = false;
        }
        $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=' . $access_token;
        $response = $this->requestApi($url, json_encode($data));
        if (is_error($response)) {
            return $response;
        }

        return $response['content'];
    }

    public function getCodeUnlimit($scene, $page = '', $width = '430', $option = array()) {
        if (!preg_match('/[0-9a-zA-Z\!\#\$\&\'\(\)\*\+\,\/\:\;\=\?\@\-\.\_\~]{1,32}/', $scene)) {
            return error(1, '场景值不合法');
        }
        $access_token = $this->getAccessToken();
        if (is_error($access_token)) {
            return $access_token;
        }
        $data = array(
            'scene' => $scene,
            'width' => intval($width),
        );
        if (!empty($page)) {
            $data['page'] = $page;
        }
        if (!empty($option['auto_color'])) {
            $data['auto_color'] = intval($option['auto_color']);
        }

        if (!empty($option['line_color'])) {
            $data['line_color'] = array(
                'r' => $option['line_color']['r'],
                'g' => $option['line_color']['g'],
                'b' => $option['line_color']['b'],
            );
            $data['auto_color'] = false;
        }
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $access_token;
        $response = $this->requestApi($url, json_encode($data));
        if (is_error($response)) {
            return $response;
        }

        return $response['content'];
    }

    public function getQrcode() {
    }

    protected function requestApi($url, $post = '', $extra = []) {
        $response = ihttp_request($url, $post, $extra);
        $result = @json_decode($response['content'], true);
        if (is_error($response)) {
            return error($result['errcode'], "访问公众平台接口失败, 错误详情: {$this->errorCode($result['errcode'])}");
        }
        if (empty($result)) {
            return $response;
        } elseif (!empty($result['errcode'])) {
            return error($result['errcode'], "访问公众平台接口失败, 错误: {$result['errmsg']},错误详情：{$this->errorCode($result['errcode'])}");
        }

        return $result;
    }

    /**
     * 消息加密错误码信息
     * @param $code
     * @return string
     */
    private function encryptErrorCode($code) {
        $errors = array(
            '40001' => '签名验证错误',
            '40002' => 'xml解析失败',
            '40003' => 'sha加密生成签名失败',
            '40004' => 'encodingAesKey 非法',
            '40005' => 'appid 校验错误',
            '40006' => 'aes 加密失败',
            '40007' => 'aes 解密失败',
            '40008' => '解密后得到的buffer非法',
            '40009' => 'base64加密失败',
            '40010' => 'base64解密失败',
            '40011' => '生成xml失败',
        );
        if ($errors[$code]) {
            return $errors[$code];
        } else {
            return '未知错误';
        }
    }

    /**
     * 验证签名是否合法
     * @param $encrypt_msg
     * @return bool
     */
    public function checkSignature($encrypt_msg) {
        $str = $this->buildSignature($encrypt_msg);

        return $str == $_GET['msg_signature'];
    }

    /**
     * 生成签名
     * @param $encrypt_msg
     * @return string
     */
    public function buildSignature($encrypt_msg) {
        $token = $this->account['token'];
        $array = array($encrypt_msg, $token, $_GET['timestamp'], $_GET['nonce']);
        sort($array, SORT_STRING);
        $str = implode($array);
        $str = sha1($str);

        return $str;
    }

    /**
     * 生成签名并对消息进行加密
     * @param $text
     * @return array
     */
    public function encryptMsg($text) {
        $token = $this->account['token'];
        $encodingaeskey = $this->account['encodingaeskey'];
        $appid = $this->account['app_id'];

        $key = base64_decode($encodingaeskey . '=');
        $text = random(16) . pack('N', strlen($text)) . $text . $appid;
        $iv = substr($key, 0, 16);
        $block_size = 32;
        $text_length = strlen($text);
        $amount_to_pad = $block_size - ($text_length % $block_size);
        if (0 == $amount_to_pad) {
            $amount_to_pad = $block_size;
        }
        $pad_chr = chr($amount_to_pad);
        $tmp = '';
        for ($index = 0; $index < $amount_to_pad; ++$index) {
            $tmp .= $pad_chr;
        }
        $text = $text . $tmp;

        $encrypted = openssl_encrypt($text, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        $encrypt_msg = base64_encode($encrypted);
        $signature = $this->buildSignature($encrypt_msg);

        return array($signature, $encrypt_msg);
    }

    /**
     * 生成加密后xml
     * @param $data
     * @return string
     */
    public function xmlDetract($data) {
        $xml['Encrypt'] = $data[1];
        $xml['MsgSignature'] = $data[0];
        $xml['TimeStamp'] = $_GET['timestamp'];
        $xml['Nonce'] = $_GET['nonce'];

        return array2xml($xml);
    }

    /**
     * 从xml中提取密文
     * @param $message
     * @return array
     */
    public function xmlExtract($message) {
        $packet = array();
        if (!empty($message)) {
            $obj = isimplexml_load_string($message, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($obj instanceof SimpleXMLElement) {
                $packet['encrypt'] = strval($obj->Encrypt);
                $packet['to'] = strval($obj->ToUserName);
            }
        }
        if (!empty($packet['encrypt'])) {
            return $packet;
        } else {
            return error(-1, "微信公众平台返回接口错误. \n错误代码为: 40002 \n,错误描述为: " . $this->encryptErrorCode('40002'));
        }
    }

    /**
     * 检验签名并对消息进行解密
     * @param $postData
     * @return array|false|string
     */
    public function decryptMsg($postData) {
        $token = $this->account['token'];
        $encodingaeskey = $this->account['encodingaeskey'];
        $appid = $this->account['app_id'];
        $key = base64_decode($encodingaeskey . '=');

        if (43 != strlen($encodingaeskey)) {
            return error(-1, "微信公众平台返回接口错误. \n错误代码为: 40004 \n,错误描述为: " . $this->encryptErrorCode('40004'));
        }
        $packet = $this->xmlExtract($postData);
        if (is_error($packet)) {
            return error(-1, $packet['message']);
        }
        $istrue = $this->checkSignature($packet['encrypt']);
        if (!$istrue) {
            return error(-1, "微信公众平台返回接口错误. \n错误代码为: 40001 \n,错误描述为: " . $this->encryptErrorCode('40001'));
        }
        $ciphertext_dec = base64_decode($packet['encrypt']);
        $iv = substr($key, 0, 16);
        $decrypted = openssl_decrypt($ciphertext_dec, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

        $pad = ord(substr($decrypted, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }
        $result = substr($decrypted, 0, (strlen($decrypted) - $pad));
        if (strlen($result) < 16) {
            return '';
        }
        $content = substr($result, 16, strlen($result));
        $len_list = unpack('N', substr($content, 0, 4));
        $xml_len = $len_list[1];
        $xml_content = substr($content, 4, $xml_len);
        $from_appid = substr($content, $xml_len + 4);
        if ($from_appid != $appid) {
            return error(-1, "微信公众平台返回接口错误. \n错误代码为: 40005 \n,错误描述为: " . $this->encryptErrorCode('40005'));
        }

        return $xml_content;
    }

    public function result($errno, $message = '', $data = '') {
        exit(json_encode(array(
            'errno' => $errno,
            'message' => $message,
            'data' => $data,
        )));
    }

    public function getDailyVisitTrend($date) {
        global $_W;
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/datacube/getweanalysisappiddailyvisittrend?access_token={$token}";
        $data = array(
            'begin_date' => $date,
            'end_date' => $date,
        );

        $response = $this->requestApi($url, json_encode($data));
        if (is_error($response)) {
            return $response;
        }

        return $response['list'][0];
    }
}
