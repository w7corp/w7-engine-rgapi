<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.w7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');
global $_W;

$w7_system_menu = array();

$w7_system_menu['system'] = array(
    'title' => '系统功能',
    'icon' => 'wi wi-setting',
    'dimension' => 2,
    'url' => url('system/base-info/display'),
    'section' => array(),
);

$w7_system_menu['site'] = array(
    'title' => '站点设置',
    'icon' => 'wi wi-system-site',
    'dimension' => 2,
    'url' => url('system/setting/basic'),
    'section' => array(),
);
$w7_system_menu['account'] = array(
    'title' => '公众号',
    'icon' => 'wi wi-white-collar',
    'dimension' => 3,
    'url' => url('module/welcome/display'),
    'section' => array(),
);
return $w7_system_menu;
