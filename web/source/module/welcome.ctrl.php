<?php
/**
 * 应用欢迎页
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');
load()->model('reply');
load()->model('miniapp');
load()->model('cache');

$dos = array('display', 'welcome_display', 'get_module_replies', 'get_module_covers', 'get_module_plugins');
$do = in_array($do, $dos) ? $do : 'display';

$module_name = safe_gpc_string($_GPC['module_name'] ?? $_GPC['m']);
$module = $_W['current_module'] = module_fetch($module_name);
if (empty($module)) {
    itoast('抱歉，你操作的模块不能被访问！');
}

if ('display' == $do) {
    $support = [];
    $support_num = [];
    foreach (module_support_type() as $key => $type) {
        if ($module[$key] == $type['support']) {
            $support[] = $type['type'];
            $support_num[] = $type['type_num'];
        }
    }
    if (empty($_W['uniacid'])) {
        itoast('', url('module/display/switch_uniacid', ['module_name' => $module_name]));
    }
    $link_accounts = [];
    if (!empty($_W['uniacid'])) {
        $module['welcome_display'] = false;
        // 模块默认入口
        $site = WeUtility::createModule($module_name);
        if (!is_error($site) && $site instanceof WeModule && method_exists($site, 'welcomeDisplay')) {
            $module['welcome_display'] = true;
        }
        $account_all_type = uni_account_type();
        $link_accounts = table('account')->where('type IN', $support_num)->getall();
        $first_account = [];
        foreach ($link_accounts as $key => &$item) {
            $item['type_sign'] = $account_all_type[$item['type']]['type_sign'];
            if (!in_array($item['type_sign'], $support)) {
                unset($link_accounts[$key]);
            }
            if ($item['uniacid'] == $_W['uniacid']) {
                $first_account = $item;
                unset($link_accounts[$key]);
            }
        }
        if (!empty($first_account)) {
            array_unshift($link_accounts, $first_account);
        }
    }
    template('module/welcome');
}

if ('welcome_display' == $do) {
    $site = WeUtility::createModule($module_name);
    if (!is_error($site)) {
        $method = 'welcomeDisplay';
        if (method_exists($site, $method)) {
            !defined('FRAME') && define('FRAME', 'module_welcome');
            $entries = module_entries($module_name, array('menu', 'home', 'profile', 'shortcut', 'cover', 'mine'));
            $site->$method($entries);
            exit;
        }
    }
}

if ('get_module_replies' == $do) {
    // 关键字
    $condition = "uniacid = :uniacid AND module != 'cover' AND module != 'userapi'";
    $condition .= ' AND `module` = :type';
    $params[':type'] = $module_name;
    $params[':uniacid'] = 0;
    $replies = reply_search($condition, $params);

    if (!empty($replies)) {
        foreach ($replies as &$item) {
            $condition = '`rid`=:rid';
            $params = array();
            $params[':rid'] = $item['id'];
            $item['keywords'] = reply_keywords_search($condition, $params);
            $item['allreply'] = reply_content_search($item['id']);
            $entries = module_entries($item['module'], array('rule'), $item['id']);

            if (!empty($entries)) {
                $item['options'] = $entries['rule'];
            }
            //若是模块，获取模块图片
            if (!in_array($item['module'], array('basic', 'news', 'images', 'voice', 'video', 'music', 'wxcard', 'reply'))) {
                $item['module_info'] = module_fetch($item['module']);
            }
        }
        unset($item);
    }
    iajax(0, $replies);
}

if ('get_module_covers' == $do) {
    // 封面链接入口
    $entries = module_entries($module_name);
    if (!empty($entries['cover'])) {
        $covers = $entries['cover'];
        $cover_eid = current($covers);
        $cover_eid = $cover_eid['eid'];
    }
    iajax(0, array('covers' => $covers, 'cover_eid' => $cover_eid));
}

if ('get_module_plugins' == $do) {
	if (!empty($module['from']) && 'cloud' != $module['from']) {
		iajax(0, []);
	}
	$plugins = module_plugin_list($module_name);
	if (is_error($plugins)) {
		iajax(-1, $plugins['message']);
	}

	iajax(0, $plugins);
}
