<?php
/**
 * 系统用户登录
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
class System extends OAuth2Client {
    private $calback_url;

    public function __construct($ak, $sk) {
        parent::__construct($ak, $sk);
        $this->stateParam['from'] = 'system';
    }

    public function showLoginUrl($calback_url = '') {
        return '';
    }

    public function user() {
        global $_GPC, $_W;
        $username = safe_gpc_string($_GPC['username']);
        $refused_login_limit = empty($_W['setting']['copyright']['refused_login_limit']) ? 0 : $_W['setting']['copyright']['refused_login_limit'];
        pdo_delete('users_failed_login', array('lastupdate <' => TIMESTAMP - $refused_login_limit * 60));
        $failed = pdo_get('users_failed_login', array('username' => $username));
        if (!empty($failed['count']) && $failed['count'] >= 5) {
            return error('-1', "输入密码错误次数超过5次，请在{$refused_login_limit}分钟后再登录");
        }
        if (!empty($_W['setting']['copyright']['verifycode']) && (int)$_GPC['agreement'] !== 1) {
            $verify = safe_gpc_string($_GPC['verify']);
            if (empty($verify)) {
                return error('-1', '请输入验证码');
            }
            $result = checkcaptcha($verify);
            if (empty($result)) {
                return error('-1', '输入验证码错误');
            }
        }
        if (empty($username)) {
            return error('-1', '请输入要登录的用户名');
        }
        $member['username'] = $username;
        $member['password'] = safe_gpc_html($_GPC['password']);
        $member['type'] = $this->user_type;
        if (empty($member['password'])) {
            return error('-1', '请输入密码');
        }

        return $member;
    }

    public function register() {
        global $_W, $_GPC;
        load()->model('user');
        $member = array();
        $profile = array();
        $member['username'] = safe_gpc_string($_GPC['username']);
        $member['owner_uid'] = intval($_GPC['owner_uid']);
        $member['password'] = safe_gpc_string($_GPC['password']);

        if (empty($member['username'])) {
            return error(-1, '必须输入用户名，格式为 3-15 位字符，可以包括汉字、字母（不区分大小写）、数字、下划线和句点。');
        }

        if (user_check(array('username' => $member['username']))) {
            return error(-1, '非常抱歉，此用户名已经被注册，你需要更换注册名称！');
        }

        if (!empty($_W['setting']['register']['code'])) {
            if (!checkcaptcha($_GPC['code'])) {
                return error(-1, '你输入的验证码不正确, 请重新输入.');
            }
        }
        if (istrlen($member['password']) < 8) {
            return error(-1, '必须输入密码，且密码长度不得低于8位。');
        }
        $register = array(
            'member' => $member,
            'profile' => $profile,
        );

        return parent::user_register($register);
    }

    public function login() {
        return $this->user();
    }

    public function bind() {
        return true;
    }

    public function unbind() {
        return true;
    }

    public function isbind() {
        return true;
    }
}
