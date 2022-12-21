<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');
load()->model('site');
$multiid = empty($_GPC['t']) ? 0 : intval($_GPC['t']);
if (empty($multiid)) {
    load()->model('account');
    $setting = uni_setting($_W['uniacid'], array('default_site'));
    $multiid = $setting['default_site'];
}
$title = $_W['page']['title'];
$navs = site_app_navs('home', $multiid);
//设置分享信息
$share_tmp = table('cover_reply')
    ->where(array(
        'uniacid' => $_W['uniacid'],
        'multiid' => $multiid,
        'module' => 'site'
    ))
    ->select(array('title', 'description', 'thumb'))
    ->get();
$_share['imgUrl'] = !empty($share_tmp['thumb']) ? tomedia($share_tmp['thumb']) : '';
$_share['desc'] = !empty($share_tmp['description']) ? $share_tmp['description'] : '';
$_share['title'] = !empty($share_tmp['title']) ? $share_tmp['title'] : '';
$category_list = table('site_category')
    ->where(array(
        'uniacid' => $_W['uniacid'],
        'multiid' => $multiid
    ))
    ->getall('id');
if (!empty($multiid)) {
    isetcookie('__multiid', $multiid);
}
template('home/home');
