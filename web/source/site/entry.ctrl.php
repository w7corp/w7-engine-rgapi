<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');
load()->model('extension');

$eid = !empty($_GPC['eid']) ? intval($_GPC['eid']) : 0;
if (!empty($eid)) {
    $entry = module_entry($eid);
} else {
    $entry_module_name = safe_gpc_string($_GPC['module_name'] ?? '') ?: safe_gpc_string($_GPC['m'] ?? '');
    $entry = table('modules_bindings')
        ->where(array(
            'module' => $entry_module_name,
            'do' => safe_gpc_string($_GPC['do'])
        ))
        ->get();
    if (empty($entry)) {
        $entry = array(
            'module' => $entry_module_name,
            'do' => safe_gpc_string($_GPC['do']),
            'state' => !empty($_GPC['state']) ? safe_gpc_string($_GPC['state']) : '',
            'direct' => !empty($_GPC['direct']) ? safe_gpc_string($_GPC['direct']) : 0,
        );
    }
}
if (empty($entry) || empty($entry['do'])) {
    itoast('非法访问.', '', '');
}

$module = module_fetch($entry['module']);

if (empty($module)) {
    itoast("访问非法, 没有操作权限. (module: {$entry['module']})", '', '');
}

if (!$entry['direct']) {
    checklogin();
    $referer = (url_params(referer()));
    if (empty($_W['isajax']) && empty($_W['ispost']) && empty($_GPC['version_id']) && !empty($referer['version_id']) && intval($referer['version_id']) > 0 &&
        ('wxapp' == $referer['c'] ||
        'site' == $referer['c'] && in_array($referer['a'], array('entry', 'nav')) ||
        'home' == $referer['c'] && 'welcome' == $referer['a'] ||
        'module' == $referer['c'] && in_array($referer['a'], array('manage-account', 'permission')))) {
        itoast('', $_W['siteurl'] . '&version_id=' . $referer['version_id']);
    }

    $_W['page']['title'] = !empty($entry['title']) ? $entry['title'] : '';
}

$_GPC['__entry'] = !empty($entry['title']) ? $entry['title'] : '';
$_GPC['__state'] = $entry['state'];
$_GPC['state'] = $entry['state'];
$_GPC['m'] = $entry['module'];
$_GPC['do'] = $entry['do'];

$_W['current_module'] = $module;

$site = WeUtility::createModuleSite($entry['module']);

if (!empty($_W['uniacid'])) {
    $_W['uniaccount'] = $_W['account'] = uni_fetch();
}
define('IN_MODULE', $entry['module']);
if (!is_error($site)) {
    $method = 'doWeb' . ucfirst($entry['do']);
    exit($site->$method());
}
itoast("访问的方法 {$method} 不存在.", referer(), 'error');
