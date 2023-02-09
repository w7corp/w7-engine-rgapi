<?php
/**
 * 初始化web端数据
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->web('common');
load()->web('template');
load()->func('file');
load()->func('tpl');
load()->model('cloud');
load()->model('user');
load()->model('attachment');
load()->classs('oauth2/oauth2client');
load()->model('system');

$session = !empty($_GPC['__session']) ? json_decode(authcode($_GPC['__session']), true) : '';
if (is_array($session)) {
    $user = user_single(array('uid' => $session['uid']));
    if (is_array($user) && $session['hash'] === $user['hash']) {
        $_W['uid'] = $user['uid'];
        $_W['username'] = $user['username'];
        $user['currentvisit'] = $user['lastvisit'];
        $user['currentip'] = $user['lastip'];
        $user['lastvisit'] = empty($session['lastvisit']) ? '' : $session['lastvisit'];
        $user['lastip'] = empty($session['lastip']) ? '--' : $session['lastip'];
        $_W['user'] = $user;
        $_W['isfounder'] = user_is_founder($_W['uid']);
        $_W['isadmin'] = user_is_founder($_W['uid'], true);
    } else {
        isetcookie('__session', '', -100);
    }
    unset($user);
}
unset($session);
if (getenv('LOCAL_DEVELOP')) {
    $_W['user'] = user_single(1);
    $_W['uid'] = $_W['user']['uid'];
    $_W['username'] = $_W['user']['username'];
    $_W['isfounder'] = $_W['isadmin'] = STATUS_ON;
}
$_W['uniacid'] = (int)igetcookie('__uniacid');

if (!empty($_W['uid'])) {
    $_W['role'] = ACCOUNT_MANAGE_NAME_FOUNDER;
}
$_W['template'] = '2.0';
$_W['token'] = token();
$_W['attachurl'] = attachment_set_attach_url();
