<?php

/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * 获取模块入口信息.
 */
defined('IN_IA') or exit('Access Denied');

$txt = safe_gpc_string($_GET['verify']);
$data = setting_load($txt);
exit($data[$txt]);
