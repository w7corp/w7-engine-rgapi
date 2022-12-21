<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');

if (empty($action)) {
    $action = 'site';
}

$eid = empty($_GPC['eid']) ? 0 : intval($_GPC['eid']);
if (!empty($eid)) {
    $entry = module_entry($eid);
} else {
    $entry = array(
        'module' => safe_gpc_string($_GPC['m']),
        'do' => safe_gpc_string($_GPC['do']),
        'state' => empty($_GPC['state']) ? '' : safe_gpc_string($_GPC['state']),
        'direct' => 0,
    );
}

if (empty($entry) || empty($entry['do'])) {
    message('非法访问.');
}

$_GPC['__entry'] = empty($entry['title']) ? '' : $entry['title'];
$_GPC['__state'] = $entry['state'];
$_GPC['state'] = $entry['state'];
$_GPC['m'] = $entry['module'];
$_GPC['do'] = $entry['do'];

$_W['current_module'] = module_fetch($entry['module']);
define('IN_MODULE', $entry['module']);
