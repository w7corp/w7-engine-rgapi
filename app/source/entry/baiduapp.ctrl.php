<?php
/**
 * 百度小程序入口
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */

defined('IN_IA') or exit('Access Denied');

$site = WeUtility::createModuleBaiduapp($entry['module']);
$method = 'doPage' . ucfirst($entry['do']);
if (!is_error($site)) {
    exit($site->$method());
}
exit();
