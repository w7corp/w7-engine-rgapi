<?php
/**
 * $sn: pro/framework/function/compat.biz.func.php : v 1c53ee809f76 : 2015/04/23 02:12:39 : RenChao $
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

if (isset($_W['uniacid'])) {
    $_W['weid'] = $_W['uniacid'];
}
if (isset($_W['openid'])) {
    $_W['fans']['from_user'] = $_W['openid'];
}
if (isset($_W['member']['uid'])) {
    if (empty($_W['fans']['from_user'])) {
        $_W['fans']['from_user'] = $_W['member']['uid'];
    }
}

/*
 * 获取一个或多个微擎用户某个或多个字段的信息.
 * @param number|array $uid 一个或多个用户 uid
 * @param array $fields 一个、多个或所有字段
 * @return array array('uid1'=>array()) or $user =array()
 */
if (!function_exists('fans_search')) {
    function fans_search($user, $fields = array()) {
        global $_W;
        load()->model('mc');
        $uid = intval($user);
        if (empty($uid)) {
            $uid = pdo_fetchcolumn('SELECT uid FROM ' . tablename('mc_mapping_fans') . ' WHERE openid = :openid AND uniacid = :uniacid', array(':openid' => $user, ':uniacid' => $_W['uniacid']));
            if (empty($uid)) {
                return array(); //得到UID
            }
        }

        return mc_fetch($uid, $fields);
    }
}

if (!function_exists('fans_fields')) {
    function fans_fields() {
        load()->model('mc');

        return mc_fields();
    }
}

if (!function_exists('fans_update')) {
    function fans_update($user, $fields) {
        global $_W;
        load()->model('mc');
        $uid = intval($user);
        if (empty($uid)) {
            $uid = pdo_fetchcolumn('SELECT uid FROM ' . tablename('mc_mapping_fans') . ' WHERE openid = :openid AND uniacid = :uniacid', array(':openid' => $user, ':uniacid' => $_W['uniacid']));
            if (empty($uid)) {
                return false; //得到UID
            }
        }

        return mc_update($uid, $fields);
    }
}

if (!function_exists('create_url')) {
    function create_url($segment = '', $params = array(), $noredirect = false) {
        return url($segment, $params, $noredirect);
    }
}

if (!function_exists('toimage')) {
    function toimage($src) {
        return tomedia($src);
    }
}

if (!function_exists('uni_setting')) {
    function uni_setting($uniacid = 0, $fields = '*', $force_update = false) {
        global $_W;
        load()->model('account');
        if ('*' == $fields) {
            $fields = '';
        }

        return uni_setting_load($fields, $uniacid);
    }
}
if (!function_exists('uni_user_permission')) {
    function uni_user_permission($type = 'system') {
        return array('account*', 'wxapp*', 'phoneapp*');
    }
}
if (!function_exists('uni_permission')) {
    function uni_permission($uid = 0, $uniacid = 0) {
        return ACCOUNT_MANAGE_NAME_FOUNDER;
    }
}
if (!function_exists('uni_user_permission_exist')) {
    function uni_user_permission_exist($uid = 0, $uniacid = 0) {
        return true;
    }
}
if (!function_exists('uni_user_permission_check')) {
    function uni_user_permission_check($permission_name = '', $show_message = true, $action = '') {
        return true;
    }
}
if (!function_exists('permission_check_account_user')) {
    function permission_check_account_user($permission_name, $show_message = true, $action = '') {
        return true;
    }
}
if (!function_exists('user_is_vice_founder')) {
    function user_is_vice_founder($uid = 0) {
        return false;
    }
}
if (!defined('CACHE_KEY_MODULE_SETTING')) {
    //模块配置信息
    define('CACHE_KEY_MODULE_SETTING', 'module_setting:%s:%s');
}
if (!function_exists('uni_accounts')) {
    //获取当前公号的所有子公众号
    function uni_accounts($uniacid = 0) {
        global $_W;
        $uniacid = empty($uniacid) ? $_W['uniacid'] : intval($uniacid);
        $account_info = pdo_get('account', array('uniacid' => $uniacid));
        if (!empty($account_info)) {
            $account_tablename = uni_account_type($account_info['type']);
            $account_tablename = $account_tablename['table_name'];
            $accounts = pdo_fetchall("SELECT w.*, a.type, a.isconnect FROM " . tablename('account') . " a INNER JOIN " . tablename($account_tablename) . " w USING(acid) WHERE a.uniacid = :uniacid AND a.isdeleted <> 1 ORDER BY a.acid ASC", array(':uniacid' => $uniacid), 'acid');
        }
        return !empty($accounts) ? $accounts : array();
    }
}
