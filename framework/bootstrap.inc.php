<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/framework/bootstrap.inc.php : v f5d0e9240317 : 2015/09/08 07:12:51 : yanghf $.
 */
define('IN_IA', true);
define('IA_ROOT', str_replace('\\', '/', dirname(dirname(__FILE__))));
define('STARTTIME', microtime());
define('MAGIC_QUOTES_GPC', (version_compare(PHP_VERSION, '7.4.0', '<') ? function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc() : 0) || @ini_get('magic_quotes_sybase'));
define('TIMESTAMP', time());

$configfile = IA_ROOT . '/data/config.php';
if (!file_exists($configfile)) {
    header('Content-Type: text/html; charset=utf-8');
    exit(('配置文件不存在或是不可读，请检查“data/config.php”文件或是<a href="./install.php"> 重新安装 </a>！'));
}
require $configfile;
$_W = $_GPC = array();
$_W['config'] = $config;

$allow_origin = array('https://user.w7.cc', 'https://m.w7.cc', 'https://console.w7.cc', 'http://console.w7.cc', 'http://user.w7.cc', 'http://m.w7.cc');
if (!empty($_W['config']['setting']['allow_origin']) && is_array($_W['config']['setting']['allow_origin'])) {
    $allow_origin = array_merge($allow_origin, $_W['config']['setting']['allow_origin']);
}
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allow_origin)) {
    header('Access-Control-Allow-Headers:Origin,X-Requested-With,Content-Type,Accept,Authorization,cancelload,X-W7-Oauthtoken,W7-Oauthtoken');
    header('Access-Control-Allow-Credentials:true');
    header('Access-Control-Allow-Method:POST,GET,OPTIONS');
    header('Access-Control-Allow-Origin:' . $_SERVER['HTTP_ORIGIN']);
}
if ('OPTIONS' == (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '')) {
    $vars = array();
    $vars['message'] = array('errno' => 0, 'message' => null);
    $vars['redirect'] = '';
    $vars['type'] = 'ajax';
    exit(json_encode($vars));
}
require IA_ROOT . '/framework/const.inc.php';
require IA_ROOT . '/framework/class/loader.class.php';
load()->func('global');
load()->func('compat');
load()->func('compat.biz');
load()->func('pdo');
load()->classs('account');
load()->model('cache');
load()->model('account');
load()->model('setting');
load()->model('module');
load()->library('agent');
load()->classs('db');
load()->func('communication');

define('CLIENT_IP', getip());

$_W['config']['db']['tablepre'] = !empty($_W['config']['db']['master']['tablepre']) ? $_W['config']['db']['master']['tablepre'] : $_W['config']['db']['tablepre'];
$_W['timestamp'] = TIMESTAMP;
$_W['charset'] = $_W['config']['setting']['charset'];
$_W['clientip'] = CLIENT_IP;

if (!empty($_W['config']['setting']['https']) && $_W['config']['setting']['https'] == '1') {
    $_W['ishttps'] = $_W['config']['setting']['https'];
} else {
    $_W['ishttps'] = !empty($_SERVER['SERVER_PORT']) && 443 == $_SERVER['SERVER_PORT'] ||
    !empty($_SERVER['HTTP_FROM_HTTPS']) && 'on' == strtolower($_SERVER['HTTP_FROM_HTTPS']) ||
    (!empty($_SERVER['HTTPS']) && 'off' != strtolower($_SERVER['HTTPS'])) ||
    !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' == strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) ||
    !empty($_SERVER['HTTP_X_CLIENT_SCHEME']) && 'https' == strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) || //阿里云判断方式
    !empty($_SERVER['HTTP_X_CLIENT_PROTO']) && 'https' == strtolower($_SERVER['HTTP_X_CLIENT_PROTO']) //腾讯云判断方式
        ? STATUS_ON : STATUS_OFF;
}
$_W['sitescheme'] = $_W['ishttps'] ? 'https://' : 'http://';
$_W['script_name'] = htmlspecialchars(scriptname());
$sitepath = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
$_W['siteroot'] = htmlspecialchars($_W['sitescheme'] . (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $sitepath);

if ('/' != substr($_W['siteroot'], -1)) {
    $_W['siteroot'] .= '/';
}
$urls = parse_url($_W['siteroot']);
$urls['path'] = empty($urls['path']) ? '' : $urls['path'];
$urls['path'] = str_replace(array('/web', '/app', '/payment/wechat', '/payment/alipay', '/api'), '', $urls['path']);
$urls['scheme'] = !empty($urls['scheme']) ? $urls['scheme'] : 'http';
$urls['host'] = !empty($urls['host']) ? $urls['host'] : '';
$_W['siteroot'] = $urls['scheme'] . '://' . $urls['host'] . ((!empty($urls['port']) && '80' != $urls['port']) ? ':' . $urls['port'] : '') . $urls['path'];

$_W['isajax'] = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 'xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']);
// 附件地址绝对路径
define('ATTACHMENT_ROOT', IA_ROOT . '/attachment/');
$pdo_if_start = (pdo()->getPDO() instanceof PDO);
if (!$pdo_if_start && file_exists(IA_ROOT . '/install.php') && !file_exists(ATTACHMENT_ROOT . '/install.lock')) {
    header('Location:' . $_W['siteroot'] . 'install.php');
    exit();
}

error_reporting(0);
define('DEVELOPMENT', $_W['config']['setting']['development'] == 1);
if (STATUS_ON == $_W['config']['setting']['development']) {
    $_W['config']['setting']['local_dev'] = STATUS_ON;
    ini_set('display_errors', '1');
    error_reporting(E_ALL ^ E_NOTICE);
} else {
    $_W['config']['setting']['local_dev'] = STATUS_ON;
}

if (!in_array($_W['config']['setting']['cache'], array('mysql', 'memcache', 'redis', 'memcached'))) {
    $_W['config']['setting']['cache'] = 'mysql';
}
load()->func('cache');

if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set($_W['config']['setting']['timezone']);
}
if (!empty($_W['config']['setting']['memory_limit']) && function_exists('ini_get') && function_exists('ini_set')) {
    if ($_W['config']['setting']['memory_limit'] != @ini_get('memory_limit')) {
        @ini_set('memory_limit', $_W['config']['setting']['memory_limit']);
    }
}

$_W['ispost'] = !empty($_SERVER['REQUEST_METHOD']) && 'POST' == strtoupper($_SERVER['REQUEST_METHOD']);

if (MAGIC_QUOTES_GPC) {
    $_GET = istripslashes($_GET);
    $_POST = istripslashes($_POST);
    $_COOKIE = istripslashes($_COOKIE);
}

$cplen = strlen($_W['config']['cookie']['pre']);
foreach ($_COOKIE as $key => $value) {
    if ($_W['config']['cookie']['pre'] == substr($key, 0, $cplen)) {
        $_GPC[substr($key, $cplen)] = $value;
    }
}
unset($cplen, $key, $value);

$_GPC = array_merge($_GET, $_GPC, $_POST);
$_GPC = ihtmlspecialchars($_GPC);

$_W['siteurl'] = $urls['scheme'] . '://' . $urls['host'] . ((!empty($urls['port']) && '80' != $urls['port']) ? ':' . $urls['port'] : '') . $_W['script_name'] . '?' . http_build_query($_GET, '', '&');

if (!$_W['isajax']) {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $__input = @json_decode($input, true);
        if (!empty($__input)) {
            $_GPC['__input'] = $__input;
            $_W['isajax'] = true;
        }
    }
    unset($input, $__input);
}
$_W['uniacid'] = $_W['uid'] = 0;

setting_load();
if (empty($_W['setting']['upload'])) {
    $_W['setting']['upload'] = array_merge($_W['config']['upload']);
}
define('IMS_FAMILY', 'v');
define('IMS_VERSION', !empty($_W['setting']['local_version']) ? $_W['setting']['local_version'] : '1.0.0');
define('IMS_RELEASE_DATE', '');
$_W['os'] = Agent::deviceType();
if (Agent::DEVICE_MOBILE == $_W['os']) {
    $_W['os'] = 'mobile';
} elseif (Agent::DEVICE_DESKTOP == $_W['os']) {
    $_W['os'] = 'windows';
} else {
    $_W['os'] = 'unknown';
}

$_W['container'] = Agent::browserType();
if (Agent::MICRO_MESSAGE_YES == Agent::isMicroMessage()) {
    $_W['container'] = 'wechat';
    if (Agent::MICRO_WXWORK_YES == Agent::isWxWork()) {
        $_W['container'] = 'workwechat';
    }
} elseif (Agent::BROWSER_TYPE_ANDROID == $_W['container']) {
    $_W['container'] = 'android';
} elseif (Agent::BROWSER_TYPE_IPAD == $_W['container']) {
    $_W['container'] = 'ipad';
} elseif (Agent::BROWSER_TYPE_IPHONE == $_W['container']) {
    $_W['container'] = 'iphone';
} elseif (Agent::BROWSER_TYPE_IPOD == $_W['container']) {
    $_W['container'] = 'ipod';
} else {
    $_W['container'] = 'unknown';
}

if ('wechat' == $_W['container'] || 'baidu' == $_W['container']) {
    $_W['platform'] = 'account';
} else {
    $_W['platform'] = '';
}

$controller = !empty($_GPC['c']) ? $_GPC['c'] : '';
$action = !empty($_GPC['a']) ? $_GPC['a'] : '';
$do = !empty($_GPC['do']) ? $_GPC['do'] : '';
if (strtoupper(php_sapi_name()) != 'CLI') {
    header('Content-Type: text/html; charset=' . $_W['charset']);
}
