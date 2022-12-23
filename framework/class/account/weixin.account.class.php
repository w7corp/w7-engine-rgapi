<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$.
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 微信平台公众号业务操作类.
 */
class WeixinAccount extends WeAccount {
    protected $menuFrame = 'account';
    protected $type = ACCOUNT_TYPE_OFFCIAL_NORMAL;
    protected $typeName = '公众号';
    protected $typeSign = ACCOUNT_TYPE_SIGN;
    protected $supportVersion = STATUS_OFF;
    /**
     * 与公众平台访问的接口地址
     * @var array<pre>
     *                 // 返回值结构
     *                 array(
     *                 &nbsp;'barcode' => array(
     *                 &nbsp;&nbsp;'post'	=> 修改提交地址,
     *                 &nbsp;&nbsp;'display' => 显示请求地址,
     *                 &nbsp;)
     *                 )
     *                 </pre>
     */
    public $types = array(
        'view', 'click', 'scancode_push',
        'scancode_waitmsg', 'pic_sysphoto', 'pic_photo_or_album',
        'pic_weixin', 'location_select', 'media_id', 'view_limited',
    );

    protected function getAccountInfo($uniacid) {
        return table('account')->getByUniacid($uniacid);
    }

    /**
     * 微擎系统对来自微信公众平台请求的安全校验.
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
     * 验证签名是否合法.
     * @param string $encrypt_msg
     * @return boolean
     */
    public function checkSignature($encrypt_msg) {
        $str = $this->buildSignature($encrypt_msg);
        return $str == $_GET['msg_signature'];
    }

    public function local_checkSignature($packet) {
        $token = $this->account['token'];
        $array = array($packet['Encrypt'], $token, $packet['TimeStamp'], $packet['Nonce']);
        sort($array, SORT_STRING);
        $str = implode($array);
        $str = sha1($str);

        return $str == $packet['MsgSignature'];
    }

    /**
     * 该函数只是在模拟测试的时候使用.
     * @param array $postData
     * @return array|string error/string
     */
    public function local_decryptMsg($postData) {
        $token = $this->account['token'];
        $encodingaeskey = $this->account['encodingaeskey'];
        $appid = $this->account['app_id'];

        if (43 != strlen($encodingaeskey)) {
            return error(-1, "微信公众平台返回接口错误. \n错误代码为: 40004 \n,错误描述为: " . $this->encryptErrorCode('40004'));
        }
        $key = base64_decode($encodingaeskey . '=');
        //提取密文
        $packet = $this->local_xmlExtract($postData);
        if (is_error($packet)) {
            return error(-1, $packet['message']);
        }
        //检验签名
        $istrue = $this->local_checkSignature($packet);
        if (!$istrue) {
            return error(-1, "微信公众平台返回接口错误. \n错误代码为: 40001 \n,错误描述为: " . $this->encryptErrorCode('40001'));
        }
        //对消息进行解密
        $ciphertext_dec = base64_decode($packet['Encrypt']);
        $iv = substr($key, 0, 16);
        $decrypted = openssl_decrypt($ciphertext_dec, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        $block_size = 32;

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

    /**
     * 生成签名.
     * @param string $encrypt_msg
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
     * 生成签名并对消息进行加密.
     * @param string $text
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
        //计算需要填充的位数
        $amount_to_pad = $block_size - ($text_length % $block_size);
        if (0 == $amount_to_pad) {
            $amount_to_pad = $block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = '';
        for ($index = 0; $index < $amount_to_pad; ++$index) {
            $tmp .= $pad_chr;
        }
        $text = $text . $tmp;

        //php7不支持mcrypt,换成openssl
        $encrypted = openssl_encrypt($text, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        //加密后的消息
        $encrypt_msg = base64_encode($encrypted);
        //生成的签名
        $signature = $this->buildSignature($encrypt_msg);

        return array($signature, $encrypt_msg);
    }

    /**
     * 检验签名并对消息进行解密.
     * @param array $postData
     * @return error 或 string
     */
    public function decryptMsg($postData) {
        $token = $this->account['token'];
        $encodingaeskey = $this->account['encodingaeskey'];
        $appid = $this->account['app_id'];
        $key = base64_decode($encodingaeskey . '=');

        if (43 != strlen($encodingaeskey)) {
            return error(-1, "微信公众平台返回接口错误. \n错误代码为: 40004 \n,错误描述为: " . $this->encryptErrorCode('40004'));
        }
        //提取密文
        $packet = $this->xmlExtract($postData);
        if (is_error($packet)) {
            return error(-1, $packet['message']);
        }
        //检验签名
        $istrue = $this->checkSignature($packet['encrypt']);
        if (!$istrue) {
            return error(-1, "微信公众平台返回接口错误. \n错误代码为: 40001 \n,错误描述为: " . $this->encryptErrorCode('40001'));
        }
        //对消息进行解密
        $ciphertext_dec = base64_decode($packet['encrypt']);
        $iv = substr($key, 0, 16);
        //php7不支持mcrypt,换成openssl
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

    /**
     * 生成加密后xml.
     * @param array $data
     * @return string xml
     */
    public function xmlDetract($data) {
        //生成xml
        $xml['Encrypt'] = $data[1];
        $xml['MsgSignature'] = $data[0];
        $xml['TimeStamp'] = $_GET['timestamp'];
        $xml['Nonce'] = $_GET['nonce'];

        return array2xml($xml);
    }

    /**
     * 从xml中提取密文.
     * @param string $message
     * @return array error/array
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

    public function local_xmlExtract($message) {
        $packet = array();
        if (!empty($message)) {
            $obj = isimplexml_load_string($message, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($obj instanceof SimpleXMLElement) {
                $packet['Encrypt'] = strval($obj->Encrypt);
                $packet['MsgSignature'] = strval($obj->MsgSignature);
                $packet['TimeStamp'] = strval($obj->TimeStamp);
                $packet['Nonce'] = strval($obj->Nonce);
            }
        }
        if (!empty($packet)) {
            return $packet;
        } else {
            return error(-1, "微信公众平台返回接口错误. \n错误代码为: 40002 \n,错误描述为: " . $this->encryptErrorCode('40002'));
        }
    }

    public function fansQueryInfo($uniid, $isOpen = true) {
        if ($isOpen) {
            $openid = $uniid;
        } else {
            exit('error');
        }
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$token}&openid={$openid}&lang=zh_CN";
        $response = ihttp_get($url);
        if (is_error($response)) {
            return error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
        }
        preg_match('/city":"(.*)","province":"(.*)","country":"(.*)"/U', $response['content'], $reg_arr);
        $reg_arr[1] = empty($reg_arr[1]) ? '' : $reg_arr[1];
        $reg_arr[2] = empty($reg_arr[2]) ? '' : $reg_arr[2];
        $reg_arr[3] = empty($reg_arr[3]) ? '' : $reg_arr[3];
        $city = htmlentities(bin2hex($reg_arr[1]));
        $province = htmlentities(bin2hex($reg_arr[2]));
        $country = htmlentities(bin2hex($reg_arr[3]));
        $response['content'] = str_replace('"city":"' . $reg_arr[1] . '","province":"' . $reg_arr[2] . '","country":"' . $reg_arr[3] . '"', '"city":"' . $city . '","province":"' . $province . '","country":"' . $country . '"', $response['content']);
        $result = @json_decode($response['content'], true);
        $result['city'] = empty($result['city']) ? '' : hex2bin(html_entity_decode($result['city']));
        $result['province'] = empty($result['province']) ? '' : hex2bin(html_entity_decode($result['province']));
        $result['country'] = empty($result['country']) ? '' : hex2bin(html_entity_decode($result['country']));
        $result['headimgurl'] = empty($result['headimgurl']) ? '' : str_replace('http:', 'https:', $result['headimgurl']);
        unset($result['remark'], $result['subscribe_scene'], $result['qr_scene'], $result['qr_scene_str']);
        if (empty($result)) {
            return error(-1, "接口调用失败, 元数据: {$response['meta']}");
        } elseif (!empty($result['errcode'])) {
            return error($result['errcode'], "访问微信接口错误, 错误代码: {$result['errcode']}, 错误信息: {$result['errmsg']},错误详情：{$this->errorCode($result['errcode'])}");
        }

        return $result;
    }

    /*
     * 批量获取粉丝信息（每次最多支持100个）
     * $data = array(
     *	'openid' => '**********',
     *	'openid' => '**********',
     * );
     */
    public function fansBatchQueryInfo($data) {
        if (empty($data)) {
            return error(-1, '粉丝openid错误');
        }
        foreach ($data as $da) {
            $post[] = array(
                'openid' => trim($da),
                'lang' => 'zh_CN',
            );
        }
        $data = array();
        $data['user_list'] = $post;
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token={$token}";
        $response = ihttp_post($url, json_encode($data));
        if (is_error($response)) {
            return error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
        }
        $result = @json_decode($response['content'], true);
        if (empty($result)) {
            return error(-1, "接口调用失败, 元数据: {$response['meta']}");
        } elseif (!empty($result['errcode'])) {
            return error(-1, "访问微信接口错误, 错误代码: {$result['errcode']}, 错误信息: {$result['errmsg']},错误详情：{$this->errorCode($result['errcode'])}");
        }

        return $result['user_info_list'];
    }

    public function fansAll($startopenid = '') {
        global $_GPC;
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token=' . $token;
        if (!empty($_GPC['next_openid'])) {
            $startopenid = safe_gpc_string($_GPC['next_openid']);
        }
        if (!empty($startopenid)) {
            $url .= '&next_openid=' . $startopenid;
        }
        $response = ihttp_get($url);
        if (is_error($response)) {
            return error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
        }
        $result = @json_decode($response['content'], true);
        if (empty($result)) {
            return error(-1, "接口调用失败, 元数据: {$response['meta']}");
        } elseif (!empty($result['errcode'])) {
            return error(-1, "访问公众平台接口失败, 错误: {$result['errmsg']},错误详情：{$this->errorCode($result['errcode'])}");
        }
        $return = array();
        $return['total'] = $result['total'];
        $return['fans'] = $result['data']['openid'];
        $return['next'] = $result['next_openid'];

        return $return;
    }

    public function barCodeCreateFixed($barcode) {
        if ('QR_LIMIT_SCENE' == $barcode['action_name'] && empty($barcode['action_info']['scene']['scene_id'])) {
            return error('1', '场景值错误');
        }
        if ('QR_LIMIT_STR_SCENE' == $barcode['action_name'] && empty($barcode['action_info']['scene']['scene_str'])) {
            return error('1', '场景字符串错误');
        }
        $token = $this->getAccessToken();
        $response = ihttp_request('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $token, json_encode($barcode));
        if (is_error($response)) {
            return $response;
        }
        $content = @json_decode($response['content'], true);
        if (empty($content)) {
            return error(-1, "接口调用失败, 元数据: {$response['meta']}");
        }
        if (!empty($content['errcode'])) {
            return error(-1, "访问微信接口错误, 错误代码: {$content['errcode']}, 错误信息: {$content['errmsg']},错误详情：{$this->errorCode($content['errcode'])}");
        }

        return $content;
    }

    //消息加密错误码信息
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
     * 获取当前公众号的AccessToken.
     * @return error | string accesstoken值
     */
    public function getAccessToken() {
        global $_W;
        $cachekey = cache_system_key('accesstoken', array('uniacid' => $this->uniacid));
        $cache = cache_load($cachekey);
        if (!empty($cache) && !empty($cache['token'])) {
            $this->account['access_token'] = $cache;
            return $cache['token'];
        }
        if (empty($_W['setting']['server_setting']['app_id']) || empty($_W['setting']['server_setting']['app_secret'])) {
            return error('-1', '请先到系统功能下配置app_id和app_secret。');
        }
        try {
            load()->library('sdk-module');
            $api = new \W7\Sdk\Module\Api($_W['setting']['server_setting']['app_id'], $_W['setting']['server_setting']['app_secret'], "1");
            $token = $api->app()->getAccessToken()->toArray();
        } catch (Exception $e) {
            return error(-1, '获取微信公众号授权失败, 请稍后重试！错误详情: ' . $e->getMessage());
        }
        if (!empty($token['errcode']) && '40164' == $token['errcode']) {
            return error(-1, $this->errorCode($token['errcode'], $token['errmsg']));
        }
        if (empty($token) || !is_array($token) || empty($token['access_token']) || empty($token['expires_in'])) {
            return error('-1', '获取微信公众号授权失败！错误代码:' . $token['errcode'] . '，错误信息:' . $this->errorCode($token['errcode']));
        }
        $record = array();
        $record['token'] = $token['access_token'];
        $record_expire = $token['expires_in'] - 200;
        $this->account['access_token'] = $record;
        cache_write($cachekey, $record, $record_expire);

        return $record['token'];
    }

    public function getVailableAccessToken() {
        $accounts = pdo_fetchall('SELECT `key`, `secret`, `acid` FROM ' . tablename('account_wechats') . ' WHERE uniacid = :uniacid ORDER BY `level` DESC ', array(':uniacid' => $GLOBALS['_W']['uniacid']));
        if (empty($accounts)) {
            return error(-1, 'no permission');
        }
        foreach ($accounts as $account) {
            if (empty($account['key']) || empty($account['secret'])) {
                continue;
            }
            $acid = $account['acid'];
            break;
        }
        $account = WeAccount::create($acid);

        return $account->getAccessToken();
    }

    public function fetch_token() {
        return $this->getAccessToken();
    }

    /**
     * 获取 jsapi_ticket.
     * @return array
     */
    public function getJsApiTicket() {
        $cachekey = cache_system_key('jsticket', array('uniacid' => $this->account['uniacid']));
        $cache = cache_load($cachekey);
        if (!empty($cache) && !empty($cache['ticket'])) {
            return $cache['ticket'];
        }
        $access_token = $this->getAccessToken();
        if (is_error($access_token)) {
            return $access_token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$access_token}&type=jsapi";
        $content = ihttp_get($url);
        if (is_error($content)) {
            return error(-1, '调用接口获取微信公众号 jsapi_ticket 失败, 错误信息: ' . $this->errorCode($content['message']));
        }
        $result = @json_decode($content['content'], true);
        if (empty($result) || 0 != intval(($result['errcode'])) || 'ok' != $result['errmsg']) {
            return error(-1, '获取微信公众号 jsapi_ticket 结果错误, 错误信息: ' . $this->errorCode($result['errcode'], $result['errmsg']));
        }
        $record = array();
        $record['ticket'] = $result['ticket'];
        $record_expire = $result['expires_in'] - 200;
        $this->account['jsapi_ticket'] = $record;
        cache_write($cachekey, $record, $record_expire);

        return $record['ticket'];
    }

    /**
     * 获取 jssdk config.
     * @return array
     */
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
            'appId' => $this->account['key'],
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

    /**
     * 获取粉丝标签列表.
     * @return array $result
     * 返回结果，格式如下:
     * 		array(
     *			'tags' => array(
     *				0 => array('id' => 1, 'name' => '微擎1', 'count' => '标签下的粉丝数'),
     *				1 => array('id' => 2, 'name' => '微擎2', 'count' => '标签下的粉丝数'),
     *				....
     *			)
     * 		)
     */
    public function fansTagFetchAll() {
        $cachekey = cache_system_key('account_tags', array('uniacid' => $this->account['uniacid']));
        $cache = cache_load($cachekey);
        if (!empty($cache['tags']) && $cache['expire'] > TIMESTAMP) {
            return $cache['tags'];
        }
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/tags/get?access_token={$token}";
        $tags = $this->requestApi($url);
        if (!is_error($tags)) {
            cache_write($cachekey, array('tags' => $tags, 'expire' => TIMESTAMP + 3600), 3400);
        }
        return $tags;
    }

    /*发送客服消息*/
    public function sendCustomNotice($data) {
        if (empty($data)) {
            return error(-1, '参数错误');
        }
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$token}";
        $response = ihttp_request($url, urldecode(json_encode($data)));
        if (is_error($response)) {
            return error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
        }
        $result = @json_decode($response['content'], true);
        if (empty($result)) {
            return error(-1, "接口调用失败, 元数据: {$response['meta']}");
        } elseif (!empty($result['errcode'])) {
            return error(-1, "访问微信接口错误, 错误代码: {$result['errcode']}, 错误信息: {$result['errmsg']},错误详情：{$this->errorCode($result['errcode'])}");
        }

        return true;
    }

    /**
     * 发送模板消息.
     *  @param string $touser 粉丝openid
     *  @param string $tpl_id_short 模板id
     *  @param array $postdata 根据模板规则完善消息
     *  @param string $url 详情页链接
     *  @param array $miniprogram 跳小程序所需数据，不需跳小程序可不用传该数据，暂不支持小游戏
     *  @param string $miniprogram['appid'] 所需跳转到的小程序appid（该小程序appid必须与发模板消息的公众号是绑定关联关系
     *  @param string $miniprogram['pagepath'] 所需跳转到小程序的具体页面路径，支持带参数,（示例index?foo=bar）
     */
    public function sendTplNotice($touser, $template_id, $postdata, $url = '', $topcolor = '#FF683F', $miniprogram = array('appid' => '', 'pagepath' => '')) {
        if (empty($this->account['key']) || ACCOUNT_SERVICE_VERIFY != $this->account['level']) {
            return error(-1, '你的公众号没有发送模板消息的权限');
        }
        if (empty($touser)) {
            return error(-1, '参数错误,粉丝openid不能为空');
        }
        if (empty($template_id)) {
            return error(-1, '参数错误,模板标示不能为空');
        }
        if (empty($postdata) || !is_array($postdata)) {
            return error(-1, '参数错误,请根据模板规则完善消息内容');
        }
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $data = array();
        if (!empty($miniprogram['appid']) && !empty($miniprogram['pagepath'])) {
            $data['miniprogram'] = $miniprogram;
        }
        $data['touser'] = $touser;
        $data['template_id'] = trim($template_id);
        $data['url'] = trim($url);
        $data['topcolor'] = trim($topcolor);
        $data['data'] = $postdata;
        $data = json_encode($data);
        $post_url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$token}";
        $response = ihttp_request($post_url, $data);
        if (is_error($response)) {
            return error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
        }
        $result = @json_decode($response['content'], true);
        if (empty($result)) {
            return error(-1, "接口调用失败, 元数据: {$response['meta']}");
        } elseif (!empty($result['errcode'])) {
            return error(-1, "访问微信接口错误, 错误代码: {$result['errcode']}, 错误信息: {$result['errmsg']},信息详情：{$this->errorCode($result['errcode'])}");
        }

        return true;
    }

    /*上传临时素材接口
     * 类型：图片，语音
     * $path => 文件地址
     * $type => 文件类型
    */
    public function uploadMedia($path, $type = 'image') {
        if (empty($path)) {
            return error(-1, '参数错误');
        }
        if (in_array(substr(ltrim($path, '/'), 0, 6), array('images', 'videos', 'audios', 'thumb'))) {
            $path = ATTACHMENT_ROOT . ltrim($path, '/');
        }
        if (!file_exists($path)) {
            return error(1, '文件不存在');
        }
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$token}&type={$type}";
        $data = array(
            'media' => '@' . $path,
        );

        return $this->requestApi($url, $data);
    }

    /**
     * 上传永久素材.
     * @param string $path 文件物理路径
     * @param string $type 素材类型 image, voice, video, thumb
     */
    public function uploadMediaFixed($path, $type = 'images') {
        global $_W;
        if (empty($path)) {
            return error(-1, '参数错误');
        }
        if (in_array(substr(ltrim($path, '/'), 0, 6), array('images', 'videos', 'audios', 'thumb', 'voices'))) {
            $path = ATTACHMENT_ROOT . ltrim($path, '/');
        }
        if (!file_exists($path)) {
            return error(1, '文件不存在');
        }
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$token}&type={$type}";
        $data = array(
            'media' => '@' . $path,
        );

        if ('videos' == $type) {
            $video_filename = ltrim($path, ATTACHMENT_ROOT);
            $material = $material = pdo_get('core_attachment', array('uniacid' => $_W['uniacid'], 'attachment' => $video_filename));
        }
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $description = array(
            'title' => 'videos' == $type ? $material['filename'] : $filename,
            'introduction' => $filename,
        );
        $data['description'] = urldecode(json_encode($description));

        return $this->requestApi($url, $data);
    }

    /**
     * 上传图文消息内的图片获取URL.
     * @param array $data 图片信息
     */
    public function uploadNewsThumb($thumb) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        if (!file_exists($thumb)) {
            return error(1, '文件不存在');
        }
        $data = array(
            'media' => '@' . $thumb,
        );
        $url = "https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token={$token}";
        $response = $this->requestApi($url, $data);
        if (is_error($response)) {
            return $response;
        } else {
            return $response['url'];
        }
    }

    public function uploadVideoFixed($title, $description, $path) {
        if (empty($path) || empty($title) || empty($description)) {
            return error(-1, '参数错误');
        }
        if (in_array(substr(ltrim($path, '/'), 0, 6), array('images', 'videos', 'audios'))) {
            $path = ATTACHMENT_ROOT . ltrim($path, '/');
        }
        if (!file_exists($path)) {
            return error(1, '文件不存在');
        }
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$token}&type=videos";
        $data = array(
            'media' => '@' . $path,
            'description' => stripslashes(ijson_encode(array('title' => $title, 'introduction' => $description), JSON_UNESCAPED_UNICODE)),
        );

        return $this->requestApi($url, $data);
    }

    /*上传视频素材接口*/
    public function uploadVideo($data) {
        if (empty($data)) {
            return error(-1, '参数错误');
        }
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://file.api.weixin.qq.com/cgi-bin/media/uploadvideo?access_token={$token}";
        $response = ihttp_request($url, urldecode(json_encode($data)));
        if (is_error($response)) {
            return error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
        }
        $result = @json_decode($response['content'], true);
        if (empty($result)) {
            return error(-1, "接口调用失败, 元数据: {$response['meta']}");
        } elseif (!empty($result['errcode'])) {
            return error(-1, "访问微信接口错误, 错误代码: {$result['errcode']}, 错误信息: {$result['errmsg']}, 错误详情：{$this->errorCode($result['errcode'])}");
        }

        return $result;
    }

    /*上传图文素材接口*/
    public function uploadNews($data) {
        if (empty($data)) {
            return error(-1, '参数错误');
        }
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token={$token}";
        $response = ihttp_request($url, urldecode(json_encode($data)));
        if (is_error($response)) {
            return error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
        }
        $result = @json_decode($response['content'], true);
        if (empty($result)) {
            return error(-1, "接口调用失败, 元数据: {$response['meta']}");
        } elseif (!empty($result['errcode'])) {
            return error(-1, "访问微信接口错误, 错误代码: {$result['errcode']}, 错误信息: {$result['errmsg']},错误详情：{$this->errorCode($result['errcode'])}");
        }

        return $result;
    }

    /*
     * 获取微信素材（只能拉取永久素材）
     * $type => 素材类型（image, video, voice, news）
     * $count => 每次拉取数量（值在1-20之间）
     * */
    public function batchGetMaterial($type = 'news', $offset = 0, $count = 20) {
        global $_W;
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=' . $token;
        $data = array(
            'type' => $type,
            'offset' => intval($offset),
            'count' => $count,
        );

        return $this->requestApi($url, json_encode($data));
    }

    /**
     * 获取永久素材.
     * @param string $media_id 素材ID
     * @param string $savefile 是否保存为文件
     * @return 保存为文件时，返回文件路径否则返回文件二进制内容或是图文数组
     */
    public function getMaterial($media_id, $savefile = true) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=' . $token;
        $data = array(
            'media_id' => trim($media_id),
        );
        $response = ihttp_request($url, json_encode($data));
        if (is_error($response)) {
            return error(-1, "访问公平台接口失败, 错误: {$response['message']}");
        }
        $result = @json_decode($response['content'], true);
        if (!empty($result['errcode'])) {
            return error(-1, "访问公众平台接口失败, 错误: {$result['errmsg']},错误详情：{$this->errorCode($result['errcode'])}");
        }
        if (empty($response['headers']['Content-disposition'])) {
            $response = json_decode($response['content'], true);
            if (!empty($response['down_url'])) {
                if (empty($savefile)) {
                    return $response;
                }
                $response = ihttp_get($response['down_url']);
                //微信端大小写不兼容
                $response['headers']['Content-disposition'] = $response['headers']['Content-Disposition'];
            } elseif (!empty($response['news_item'])) {
                return $response;
            }
        }
        if ($savefile && !empty($response['headers']['Content-disposition']) && strexists($response['headers']['Content-disposition'], 'filename=')) {
            global $_W;
            preg_match('/filename=\"?([^"]*)/', $response['headers']['Content-disposition'], $match);
            $pathinfo = pathinfo($match[1]);
            $filename = $_W['uniacid'] . '/' . date('Y/m/');
            if (in_array(strtolower($pathinfo['extension']), array('mp4'))) {
                $filename = 'videos/' . $filename;
            } elseif (in_array(strtolower($pathinfo['extension']), array('amr', 'mp3', 'wma', 'wmv'))) {
                $filename = 'audios/' . $filename;
            } else {
                $filename = 'images/' . $filename;
            }
            $filename .= file_random_name($filename, $pathinfo['extension']);
            load()->func('file');
            file_write($filename, $response['content']);
            file_remote_upload($filename);

            return $filename;
        } else {
            return $response['content'];
        }

        return $result;
    }

    /**
     * 下载临时素材.
     * @param $mediaid 素材ID
     * @param $savefile 是否保存为文件
     * @return 保存为文件时，返回文件路径否则返回文件二进制内容
     */
    public function downloadMedia($media_id, $savefile = true) {
        $mediatypes = array('image', 'voice', 'thumb');
        $media_id = is_array($media_id) ? $media_id['media_id'] : $media_id;
        if (empty($media_id)) {
            return error(-1, '微信下载媒体资源参数错误');
        }

        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token={$token}&media_id={$media_id}";
        $response = ihttp_get($url);

        if (empty($response['headers']['Content-disposition'])) {
            $response = json_decode($response['content'], true);
            if (!empty($response['video_url'])) {
                $response = ihttp_get($response['video_url']);
                //微信端大小写不兼容
                $response['headers']['Content-disposition'] = $response['headers']['Content-Disposition'];
            }
        }
        if ($savefile && !empty($response['headers']['Content-disposition']) && strexists($response['headers']['Content-disposition'], 'filename=')) {
            global $_W;
            preg_match('/filename=\"?([^"]*)/', $response['headers']['Content-disposition'], $match);
            $filename = $_W['uniacid'] . '/' . date('Y/m/') . $match[1];
            $pathinfo = pathinfo($filename);
            if (in_array(strtolower($pathinfo['extension']), array('mp4'))) {
                $filename = 'videos/' . $filename;
            } elseif (in_array(strtolower($pathinfo['extension']), array('amr', 'mp3', 'wma', 'wmv'))) {
                $filename = 'audios/' . $filename;
            } else {
                $filename = 'images/' . $filename;
            }
            load()->func('file');
            file_write($filename, $response['content']);
            file_remote_upload($filename);

            return $filename;
        } else {
            return $response['content'];
        }
    }

    /**
     * 修改微商城订单状态
     * @param array $send
     * @return array 提示信息
     */
    public function changeOrderStatus($send) {
        if (empty($send)) {
            return error(-1, '参数错误');
        }
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $sendapi = 'https://api.weixin.qq.com/pay/delivernotify?access_token=' . $token;
        $response = ihttp_request($sendapi, json_encode($send));
        $response = json_decode($response['content'], true);
        if (empty($response)) {
            return error(-1, '发货失败，请检查您的公众号权限或是公众号AppId和公众号AppSecret！');
        }
        if (!empty($response['errcode'])) {
            return error(-1, $response['errmsg']);
        }

        return $response;
    }

    public function getOauthUserInfo($accesstoken, $openid) {
        $apiurl = "https://api.weixin.qq.com/sns/userinfo?access_token={$accesstoken}&openid={$openid}&lang=zh_CN";
        $response = $this->requestApi($apiurl);
        unset($response['remark'], $response['subscribe_scene'], $response['qr_scene'], $response['qr_scene_str']);

        return $response;
    }

    public function getOauthInfo($code = '') {
        global $_W, $_GPC;
        if (!empty($_GPC['code'])) {
            $code = $_GPC['code'];
        }
        if (empty($code)) {
            $oauth_url = uni_account_oauth_host();
            $url = $oauth_url . "app/index.php?{$_SERVER['QUERY_STRING']}";
            $forward = $this->getOauthCodeUrl(urlencode($url));
            header('Location: ' . $forward);
            exit;
        }
        $url = $this->sdkUriPre . "/sns/oauth2/access_token?code={$code}&grant_type=authorization_code";
        return $this->requestApi($url, '', ['token' => md5($_W['setting']['server_setting']['app_id'] . $_W['setting']['server_setting']['app_secret'])]);
    }

    public function getOauthAccessToken() {
        $cachekey = cache_system_key('oauthaccesstoken', array('acid' => $this->account['acid']));
        $cache = cache_load($cachekey);
        if (!empty($cache) && !empty($cache['token'])) {
            return $cache['token'];
        }
        $token = $this->getOauthInfo();
        if (is_error($token)) {
            return error(1);
        }
        $record = array();
        $record['token'] = $token['access_token'];
        $record_expire = $token['expires_in'] - 200;
        cache_write($cachekey, $record, $record_expire);

        return $token['access_token'];
    }

    /**
     * 获取共享收货地址JS调用的配置信息.
     * @return error | array 配置信息
     */
    public function getShareAddressConfig() {
        global $_W;
        static $current_url;
        if (empty($current_url)) {
            $current_url = $_W['siteurl'];
        }
        $token = $this->getOauthAccessToken();
        if (is_error($token)) {
            return false;
        }
        $package = array(
            'appid' => $this->account['key'],
            'url' => $current_url,
            'timestamp' => strval(TIMESTAMP),
            'noncestr' => strval(random(8, true)),
            'accesstoken' => $token,
        );
        ksort($package, SORT_STRING);
        $signstring = array();
        foreach ($package as $k => $v) {
            $signstring[] = "{$k}={$v}";
        }
        $signstring = strtolower(sha1(trim(implode('&', $signstring))));
        $shareaddress_config = array(
            'appId' => $this->account['key'],
            'scope' => 'jsapi_address',
            'signType' => 'sha1',
            'addrSign' => $signstring,
            'timeStamp' => $package['timestamp'],
            'nonceStr' => $package['noncestr'],
        );

        return $shareaddress_config;
    }

    public function getOauthCodeUrl($callback, $state = '') {
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->account['key']}&redirect_uri={$callback}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";
    }

    public function getOauthUserInfoUrl($callback, $state = '', $extra = array()) {
        $url_param = '';
        if (!empty($extra['forcePopup'])) {
            $url_param .= '&forcePopup=true';
        }
        if (!empty($extra['forceSnapShot'])) {
            $url_param .= '&forceSnapShot=true';
        }
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->account['key']}&redirect_uri={$callback}&response_type=code&scope=snsapi_userinfo&state={$state}{$url_param}#wechat_redirect";
    }

    /**
     * 查看指定文章的评论数据.
     * @param $msg_data_id 群发返回的msg_data_id
     * @param $index 多图文时，用来指定第几篇图文，从0开始，不带默认返回该msg_data_id的第一篇图文
     * @param int $type  type=0 普通评论&精选评论 type=1 普通评论 type=2 精选评论
     * @param int $begin 起始位置
     * @param int $count 获取数目（>=50会被拒绝）
     * @return array|mixed
     */
    public function getComment($msg_data_id, $index, $type = 0, $begin = 0, $count = 50) {
        $token = $this->getAccessToken();
        if (is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/comment/list?access_token={$token}";
        $data = array(
            'msg_data_id' => $msg_data_id,
            'index' => $index,
            'begin' => $begin,
            'count' => $count,
            'type' => $type,
        );

        return $this->requestApi($url, json_encode($data));
    }

    protected function requestApi($url, $post = '', $extra = []) {
        $response = ihttp_request($url, $post, $extra);

        $result = @json_decode($response['content'], true);
        if (is_error($response)) {
            if (empty($result)) {
                return error(-1, "接口调用失败, 元数据: {$response['message']}");
            }
            return error($result['errcode'], "访问公众平台接口失败, 错误详情: {$this->errorCode($result['errcode'])}");
        }
        if (empty($result)) {
            return error(-1, "接口调用失败, 元数据: {$response['meta']}");
        } elseif (!empty($result['errcode'])) {
            return error($result['errcode'], "访问公众平台接口失败, 错误: {$result['errmsg']},错误详情：{$this->errorCode($result['errcode'])}");
        }

        return $result;
    }
}
