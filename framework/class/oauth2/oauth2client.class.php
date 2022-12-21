<?php

/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
abstract class OAuth2Client {
    protected $ak;
    protected $sk;
    protected $login_type;
    protected $stateParam = array(
        'state' => '',
        'from' => '',
        'mode' => '',
    );

    public function __construct($ak, $sk) {
        $this->ak = $ak;
        $this->sk = $sk;
    }

    public function stateParam() {
        global $_W;
        $this->stateParam['state'] = $_W['token'];
        if (!empty($_W['user'])) {
            $this->stateParam['mode'] = 'bind';
        } else {
            $this->stateParam['mode'] = 'login';
        }

        return base64_encode(http_build_query($this->stateParam, '', '&'));
    }

    public function getLoginType($login_type) {
        $this->login_type = $login_type;
    }

    public static function supportLoginType() {
        return array('system', 'qq', 'wechat', 'mobile', 'console');
    }

    public static function supportThirdLoginType() {
        return array('qq', 'wechat');
    }

    public static function supportBindTypeInfo($type = '') {
        $data = array(
            'qq' => array(
                'type' => 'qq',
                'title' => 'QQ',
            ),
            'wechat' => array(
                'type' => 'wechat',
                'title' => '微信',
            ),
            'mobile' => array(
                'type' => 'mobile',
                'title' => '手机号',
            ),
            'console' => array(
                'type' => 'console',
                'title' => '控制台',
            ),
        );
        if (!empty($type)) {
            return $data[$type];
        } else {
            return $data;
        }
    }

    /**
     * 第三方登录后需要进行再次注册绑定的类型.
     *
     * @return array
     */
    public static function supportThirdLoginBindType() {
        return array('qq', 'wechat');
    }

    public static function supportThirdMode() {
        return array('bind', 'login');
    }

    public static function supportParams($state) {
        $state = urldecode($state);
        $param = array();
        if (!empty($state)) {
            $state = base64_decode($state);
            parse_str($state, $third_param);
            $modes = self::supportThirdMode();
            $types = self::supportThirdLoginType();

            if (in_array($third_param['mode'], $modes) && in_array($third_param['from'], $types)) {
                return $third_param;
            }
        }

        return $param;
    }

    public static function create($type, $appid = '', $appsecret = '') {
        $types = self::supportLoginType();
        if (in_array($type, $types)) {
            load()->classs('oauth2/' . $type);
            $type_name = ucfirst($type);
            $obj = new $type_name($appid, $appsecret);
            $obj->getLoginType($type);

            return $obj;
        }

        return null;
    }

    abstract public function showLoginUrl($calback_url = '');

    abstract public function user();

    abstract public function login();

    abstract public function bind();

    abstract public function unbind();

    abstract public function isbind();

    abstract public function register();

    public function user_register($register) {
        global $_W;
        load()->model('user');

        if (is_error($register)) {
            return $register;
        }
        $member = $register['member'];
        $profile = $register['profile'];

        $member['status'] = !empty($_W['setting']['register']['verify']) ? 1 : 2;
        $member['remark'] = '';
        $group = user_group_detail_info(intval($_W['setting']['register']['groupid']));

        $timelimit = intval($group['timelimit']);
        if ($timelimit > 0) {
            $member['endtime'] = strtotime($timelimit . ' days');
        }
        $member['starttime'] = TIMESTAMP;

        $user_id = user_register($member, $this->stateParam['from']);
        if ($user_id > 0) {
            unset($member['password']);
            $member['uid'] = $user_id;
            if (!empty($profile)) {
                $profile['uid'] = $user_id;
                $profile['createtime'] = TIMESTAMP;
                pdo_insert('users_profile', $profile);
            }

            $message = '注册成功';
            return array(
                'errno' => 0,
                'message' => $message,
                'uid' => $user_id,
            );
        }

        return error(-1, '增加用户失败，请稍候重试或联系网站管理员解决！');
    }
}
