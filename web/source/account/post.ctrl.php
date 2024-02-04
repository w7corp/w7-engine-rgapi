<?php
/**
 * 管理公众号
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');
load()->model('cloud');
load()->model('cache');
load()->classs('weixin.platform');
load()->model('utility');
load()->func('file');
$uniacid = intval($_GPC['uniacid']);
$account = uni_fetch($uniacid);
if (!$account) {
    if ($_W['isajax']) {
        iajax(-1, '无效的uniacid');
    }
    itoast('无效的uniacid');
}
$acid = $account['acid']; //强制使用默认的acid
$dos = array('base');
$do = in_array($do, $dos) ? $do : 'base';

if ('base' == $do) {
    if ($_W['ispost'] && $_W['isajax']) {
        if (!empty($_GPC['type'])) {
            $type = safe_gpc_string($_GPC['type']);
        } else {
            iajax(-1, '参数错误！', '');
        }
        $request_data = safe_gpc_string($_GPC['request_data']);
        switch ($type) {
            case 'headimgsrc':
                $imgsrc = safe_gpc_path($_GPC['request_data']);
                if (!file_is_image($imgsrc)) {
                    iajax(1, '不是一个有效图片！', '');
                }
                $data = array('logo' => $imgsrc);
                break;
            case 'name':
                $check_uniacname = pdo_get('account', ['name' => $request_data, 'type' => $account['type']]);
                if (!empty($check_uniacname)) {
                    iajax(1, "该名称'{$request_data}'已经存在");
                }
                $data = array('name' => $request_data);
                break;
            case 'account':
                $data = array('account' => $request_data); break;
            case 'level':
                $data = array('level' => intval($request_data)); break;
            case 'appid':
                $data = array('app_id' => $request_data); break;
            case 'secret':
                if ($account['secret'] == $request_data) {
                    iajax(0, '修改成功！', referer());
                }
                $data = array('app_secret' => $request_data); break;
            case 'token':
                $data = array('token' => $request_data);
                break;
            case 'encodingaeskey':
                $data = array('encodingaeskey' => $request_data);
                break;
        }
        $result = pdo_update('account', $data, array('uniacid' => $uniacid));
        if ($result) {
            cache_delete(cache_system_key('uniaccount', array('uniacid' => $uniacid)));
            cache_delete(cache_system_key('accesstoken', array('uniacid' => $uniacid)));
            iajax(0, '修改成功！', referer());
        } else {
            iajax(1, '修改失败！', '');
        }
    }

    if (!$_W['isadmin']) {
        $owner_id = pdo_getcolumn('uni_account_users', array('uniacid' => $uniacid, 'role' => 'owner'), 'uid');
        $user_endtime = user_end_time($owner_id);
    }
    $authurl = ['errno' => 1, 'url' => '微信开放平台 appid 链接不成功，请检查配置后再试'];
    if ($_W['setting']['platform']['authstate']) {
        $account_platform = new WeixinPlatform();
        $preauthcode = $account_platform->getPreauthCode();
        if (is_error($preauthcode)) {
            if (40013 == $preauthcode['errno']) {
                $url = '微信开放平台 appid 链接不成功，请检查修改后再试' . "<a href='" . url('system/platform') . "' style='color:#3296fa'>去设置</a>";
            } else {
                $url = "{$preauthcode['message']}";
            }

            $authurl['url'] = $url;
        } else {
            $authurl_type = in_array($account['type'], array(4, 7)) ? ACCOUNT_PLATFORM_API_LOGIN_WXAPP : ACCOUNT_PLATFORM_API_LOGIN_ACCOUNT;
            $callurl = $authurl_type == ACCOUNT_PLATFORM_API_LOGIN_WXAPP ? 'wxapp/auth/forward' : 'account/auth/forward';
            $authurl = array(
                'errno' => 0,
                'url' => sprintf(ACCOUNT_PLATFORM_API_LOGIN, $account_platform->appid, $preauthcode, urlencode(url($callurl, array(), true)), $authurl_type),
            );
        }
    }
    $account['authurl'] = $authurl;
    $account['createtime'] = date('Y-m-d H:i:s', $account['createtime']);
    $account['start'] = date('Y-m-d', $account['starttime']);
    $uni_setting = (array) uni_setting_load(array('statistics', 'attachment_limit', 'attachment_size'), $uniacid);

    $attachment_limit = intval($uni_setting['attachment_limit']);
    if (0 == $attachment_limit) {
        $upload = setting_load('upload');
        $attachment_limit = empty($upload['upload']['attachment_limit']) ? 0 : intval($upload['upload']['attachment_limit']);
    }
    if ($attachment_limit <= 0) {
        $attachment_limit = -1;
    }
    $account['switchurl_full'] = $_W['siteroot'] . 'web/' . ltrim($account['switchurl'], './');
    $account['headimgsrc'] = $account['logo'];
    $account['qrcodeimgsrc'] = $account['qrcode'];
    $account['siteurl'] = $account['type_sign'] != WXAPP_TYPE_SIGN ? rtrim($_W['siteroot'], '/') : rtrim(str_replace('http://', 'https://', $_W['siteroot']), '/');
    $account['socketurl'] = str_replace('https://', 'wss://', $account['siteurl']);
    $account['udpurl'] = str_replace('https://', 'udp://', $account['siteurl']);
    $account['service_url'] = $account['siteurl'] . '/api.php?id=' . $account['acid'];
    $account['type_class'] = $account_all_type_sign[$account['type_sign']]['icon'];
    $account['support_version'] = $account->supportVersion;
    $uniaccount = pdo_get('uni_account', array('uniacid' => $uniacid));
    template('account/manage-base');
}
