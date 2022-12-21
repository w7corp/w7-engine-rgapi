<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');
load()->app('common');
load()->app('template');
load()->model('mc');
load()->model('attachment');
load()->model('module');

$_W['uniacid'] = intval($_GPC['i']);
if (empty($_W['uniacid'])) {
    $_W['uniacid'] = empty($_GPC['weid']) ? 0 : intval($_GPC['weid']);
}
//放在uni_fetch之前(因为传的值是$_W['uniacid'],所以此判断效果一样)，否则会导致误删session
if (empty($_W['uniacid'])) {
    header('HTTP/1.1 404 Not Found');
    header("status: 404 Not Found");
    exit;
}
$_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);
if (is_error($_W['account'])) {
    message($_W['account']['message']);
}
$_W['acid'] = $_W['uniaccount']['acid'];

$_W['session_id'] = '';
if (isset($_GPC['state']) && !empty($_GPC['state']) && strexists($_GPC['state'], 'we7sid-')) {
    $pieces = explode('-', safe_gpc_string($_GPC['state']));
    $_W['session_id'] = $pieces[1];
    unset($pieces);
}
if (empty($_W['session_id'])) {
    $_W['session_id'] = $_COOKIE[session_name()];
}
if (empty($_W['session_id'])) {
    $_W['session_id'] = "{$_W['uniacid']}-" . random(20) ;
    $_W['session_id'] = md5($_W['session_id']);
    setcookie(session_name(), $_W['session_id'], 0, '/');
}
session_id($_W['session_id']);

load()->classs('wesession');
WeSession::start($_W['uniacid'], $_W['clientip']);
//兼容0.6的i和j的处理方式
if (!empty($_GPC['j'])) {
    $acid = intval($_GPC['j']);
    $_W['account'] = account_fetch($acid);
    if (is_error($_W['account'])) {
        $_W['account'] = account_fetch($_W['acid']);
    } else {
        $_W['acid'] = $acid;
    }
    $_SESSION['__acid'] = $_W['acid'];
    $_SESSION['__uniacid'] = $_W['uniacid'];
}
if (!empty($_SESSION['__acid']) && $_SESSION['__uniacid'] == $_W['uniacid']) {
    $_W['acid'] = intval($_SESSION['__acid']);
    $_W['account'] = uni_fetch($_W['uniacid']);
}
//加入query_string判断，安卓手机访问ico无uniacid导致误删sesion
if (strpos($_SERVER['QUERY_STRING'], 'favicon.ico') === false && ((!empty($_SESSION['acid']) && $_W['acid'] != $_SESSION['acid']) ||
        (!empty($_SESSION['uniacid']) && $_W['uniacid'] != $_SESSION['uniacid']))) {
    $keys = array_keys($_SESSION);
    foreach ($keys as $key) {
        unset($_SESSION[$key]);
    }
    unset($keys, $key);
}
$_SESSION['acid'] = $_W['acid'];
$_SESSION['uniacid'] = $_W['uniacid'];

if (!empty($_SESSION['openid'])) {
    $_W['openid'] = $_SESSION['openid'];
    $_W['fans'] = mc_fansinfo($_W['openid']);
    $_W['fans']['from_user'] = $_W['fans']['openid'] = $_W['openid'];
}
if (!empty($_SESSION['uid']) || (!empty($_W['fans']) && !empty($_W['fans']['uid']))) {
    $uid = intval($_SESSION['uid']);
    if (empty($uid)) {
        $uid = $_W['fans']['uid'];
    }
    _mc_login(array('uid' => $uid));
    unset($uid);
}
if (empty($_W['openid']) && !empty($_SESSION['oauth_openid'])) {
    $_W['openid'] = $_SESSION['oauth_openid'];
    $_W['fans'] = array(
        'openid' => $_SESSION['oauth_openid'],
        'from_user' => $_SESSION['oauth_openid'],
        'follow' => 0
    );
}

$_W['oauth_account'] = $_W['account']['oauth'] = array(
    'key' => $_W['account']['app_id'],
    'secret' => '',
    'acid' => $_W['acid'],
    'type' => $_W['account']['type'] ?? '',
    'level' => $_W['account']['level'] ?? 0,
    'support_oauthinfo' => $_W['account']->supportOauthInfo,
    'support_jssdk' => $_W['account']->supportJssdk,
);

$unisetting = uni_setting_load();

if ($controller != 'utility') {
    $_W['token'] = token();
}

if (!empty($_W['account']['oauth']) && $_W['account']['oauth']['support_oauthinfo'] && empty($_W['isajax']) &&
    (($_W['container'] == 'baidu' && $_W['account']->typeSign != 'account') || $_W['container'] != 'baidu')) {
    $_W['platform'] = empty($_W['platform']) ? '' : $_W['platform'];
    if (($_W['platform'] == 'account' && !$_GPC['logout'] && empty($_W['openid']) && ($controller != 'auth' || ($controller == 'auth' && !in_array($action, array('forward', 'oauth'))))) ||
        ($_W['platform'] == 'account' && !$_GPC['logout'] && empty($_SESSION['oauth_openid']) && ($controller != 'auth'))) {
        $state = 'we7sid-' . $_W['session_id'];
        if (empty($_SESSION['dest_url'])) {
            $_SESSION['dest_url'] = urlencode($_W['siteurl']);
        }
        $oauth_url = uni_account_oauth_host();
        $url = $oauth_url . "app/index.php?i={$_W['uniacid']}&c=auth&a=oauth&scope=snsapi_userinfo";
        $callback = urlencode($url);
        $oauth_account = WeAccount::create($_W['account']['oauth']);
        $forward = $oauth_account->getOauthUserInfoUrl($callback, $state);
        template('auth/wx');
        exit();
    }
}

if ($_W['platform'] == 'account' && $_W['account']->supportJssdk && $controller != 'utility') {
    if (!empty($unisetting['jsauth_acid'])) {
        $jsauth_acid = $unisetting['jsauth_acid'];
    } else {
        if ($_W['account']['level'] < ACCOUNT_SUBSCRIPTION_VERIFY && !empty($unisetting['oauth']['account'])) {
            $jsauth_acid = $unisetting['oauth']['account'];
        } else {
            $jsauth_acid = $_W['acid'];
        }
    }
    if (!empty($jsauth_acid)) {
        $account_api = WeAccount::create($jsauth_acid);
        if (!empty($account_api)) {
            $_W['account']['jssdkconfig'] = $account_api->getJssdkConfig();
            $_W['account']['jsauth_acid'] = $jsauth_acid;
        }
    }
    unset($jsauth_acid, $account_api);
}

$_W['attachurl'] = attachment_set_attach_url();
