<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.w7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 说明（以$w7_file_permission数组下第一个元素account为例）：
 * account  代表  设定/web/source/account文件夹下的权限（即代码中的 $controller 或 $_GPC['c']）
 * account数组下的元素：
 *    'default'       代表  进入此controller后在没有指定$action（即$_GPC['a']）的情况下，默认进入的文件
 *    'direct'        代表  无需任何权限，可以直接进入的权限
 *    'vice_founder'  代表  副创始人拥有的权限
 *    'owner'         代表  主管理员拥有的权限
 *    'manager'       代表  管理员拥有的权限
 *    'operator'      代表  操作员拥有的权限
 *    'clerk'         代表  店员拥有的权限
 * 权限中带星号'*'指拥有该文件夹下所有权限.
 */
$w7_file_permission = [];
$w7_file_permission = [
    'account' => [
        'default' => '',
        'direct' => [],
    ],
    'message' => [
        'default' => '',
        'direct' => [],
    ],
    'module' => [
        'default' => '',
        'direct' => [],
    ],
    'platform' => [
        'default' => '',
        'direct' => [],
    ],
    'site' => [
        'default' => '',
        'direct' => ['entry'],
    ],
    'system' => [
        'default' => '',
        'direct' => [],
    ],
    'user' => [
        'default' => '',
        'direct' => [
            'login',
        ],
    ],
    'utility' => [
        'default' => '',
        'direct' => ['visit', 'bindcall', 'mp-verify'],
    ],
];

return $w7_file_permission;
