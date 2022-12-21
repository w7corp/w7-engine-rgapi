<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 用户注册
 * PS:密码字段不要加密
 * @param array $user 用户注册信息，需要的字段必须包括 username, password, remark
 * @param string $source 注册来源：mobile, system, qq, wechat, admin
 * @return int 成功返回新增的用户编号，失败返回 0
 */
function user_register($user) {
    if (empty($user) || !is_array($user)) {
        return 0;
    }
    if (isset($user['uid'])) {
        unset($user['uid']);
    }

    $user['salt'] = random(8);
    $user['lastip'] = '';
    $user['lastvisit'] = '';
    $result = pdo_insert('users', $user);
    if (!empty($result)) {
        $user['uid'] = pdo_insertid();
    }
    return intval($user['uid']);
}

/**
 * 检查用户是否存在，多个如果检查的参数包括多个字段，则必须满足所有参数条件符合才返回true
 * PS:密码字段不要加密，不能单独依靠密码查询
 * @param array $user 用户信息，需要的字段可以包括 uid, username, password, status
 * @return int 用户uid
 */
function user_check($user) {
    if (empty($user) || !is_array($user)) {
        return 0;
    }
    $where = ' WHERE 1 ';
    $params = array();
    if (!empty($user['uid'])) {
        $where .= ' AND `uid`=:uid';
        $params[':uid'] = intval($user['uid']);
    }
    if (!empty($user['username'])) {
        $where .= ' AND `username`=:username';
        $params[':username'] = $user['username'];
    }
    if (!empty($user['status'])) {
        $where .= " AND `status`=:status";
        $params[':status'] = intval($user['status']);
    }
    if (empty($params)) {
        return 0;
    }
    $sql = 'SELECT `uid`,`password`,`salt` FROM ' . tablename('users') . "$where LIMIT 1";
    $record = pdo_fetch($sql, $params);
    if (empty($record) || empty($record['password']) || empty($record['salt'])) {
        return 0;
    }
    if (!empty($user['password'])) {
        $password = user_hash($user['password'], $record['salt']);
        return $password == $record['password'] ? $record['uid'] : 0;
    }
    return $record['uid'];
}

/**
 * 判断是否是创始人
 * @param int $uid
 * @param boolean $only_main_founder 只判断只否是主创始人
 * @return boolean
 */
function user_is_founder($uid, $only_main_founder = false) {
    return true;
}

/**
 * 获取单条用户信息，如果查询参数多于一个字段，则查询满足所有字段的用户
 * PS:密码字段不要加密
 * @param array $user_or_uid 要查询的用户字段，可以包括  uid, username, password, status
 * @return array 完整的用户信息
 */
function user_single($user_or_uid) {
    $user = $user_or_uid;
    if (empty($user)) {
        return false;
    }
    if (is_numeric($user)) {
        $user = array('uid' => $user);
    }
    if (!is_array($user)) {
        return false;
    }
    $where = ' WHERE 1 ';
    $params = array();
    if (!empty($user['uid'])) {
        $where .= ' AND `uid`=:uid';
        $params[':uid'] = intval($user['uid']);
    }
    if (!empty($user['username'])) {
        $where .= ' AND `username`=:username';
        $params[':username'] = $user['username'];
    }
    if (!empty($user['openid'])) {
        $where .= ' AND `openid`=:openid';
        $params[':openid'] = $user['openid'];
    }
    if (empty($params)) {
        return false;
    }
    $sql = 'SELECT * FROM ' . tablename('users') . $where . ' LIMIT 1';

    $record = pdo_fetch($sql, $params);
    if (empty($record)) {
        return false;
    }
    $record['hash'] = md5($record['openid'] . $record['salt']);
    unset($record['salt']);
    return $record;
}

/**
 * 计算用户密码
 * @param string $passwordinput 输入字符串
 * @param string $salt 附加字符串
 * @return string
 */
function user_hash($passwordinput, $salt) {
    global $_W;
    $passwordinput = "{$passwordinput}-{$salt}-{$_W['config']['setting']['authkey']}";
    return sha1($passwordinput);
}

/**
 * 获取当前用户拥有的所有模块
 * @param $uid string 用户id
 * @return array 模块列表
 */
function user_modules($uid = 0) {
    return table('modules')->getall('name');
}
