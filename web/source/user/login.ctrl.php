<?php
/**
 * 用户登录
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

$accesstoken = cloud_oauth_accesstoken(safe_gpc_string($_GPC['code']));
if (empty($accesstoken) || is_error($accesstoken)) {
    $message = '授权用户登录获取accesstoken失败，请联系管理员处理。' . $accesstoken['message'];
    message($message, '', 'error');
}
$cloud_user_info = cloud_oauth_user($accesstoken);
if (is_error($cloud_user_info)) {
    message($cloud_user_info['message'], '', 'error');
}
$user_info = user_single(['openid' => $cloud_user_info['open_id']]);
if (empty($user_info) && $cloud_user_info['openid'] == $cloud_user_info['founder_openid']) {
    $user_save_result = user_register([
        'username' => $cloud_user_info['nickname'],
        'openid' => $cloud_user_info['open_id'],
        'starttime' => TIMESTAMP,
        'avatar' => $cloud_user_info['avatar'],
        'component_appid' => $cloud_user_info['component_appid'],
    ]);
    if (is_error($user_save_result)) {
        message($user_save_result['message'], '', 'info');
    }
    $user_info = user_single(['openid' => $cloud_user_info['open_id']]);
}
$update_data = array('lastvisit' => TIMESTAMP, 'lastip' => $_W['clientip']);
pdo_update('users', $update_data, array('uid' => $user_info['uid']));
$w7_user_token = authcode(json_encode(array(
    'uid' => $user_info['uid'],
    'hash' => $user_info['hash']
)), 'encode');
isetcookie('__session', $w7_user_token);
$url = !empty($_GPC['referer']) ? safe_gpc_url($_GPC['referer']) : $_W['siteroot'];
header('Location:' . $url);
exit;
