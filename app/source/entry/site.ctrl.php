<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */

defined('IN_IA') or exit('Access Denied');

$site = WeUtility::createModuleSite($entry['module']);
if (!is_error($site)) {
    $do_function = $site instanceof WeModuleSite ? 'doMobile' : 'doPage';
    $method = $do_function . ucfirst($entry['do']);
    exit($site->$method());
}
exit();
