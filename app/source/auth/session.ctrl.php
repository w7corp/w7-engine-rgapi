<?php
/**
 * 小程序身份获取
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */

defined('IN_IA') or exit('Access Denied');

load()->model('mc');

$dos = array('openid', 'userinfo', 'check');
$do = in_array($do, $dos) ? $do : 'openid';

$account_api = WeAccount::createByUniacid();

if ($do == 'openid') {
    /**
     * 用户可通过code码或是Openid来获取用户信息
     */
    $code = safe_gpc_string($_GPC['code']);
    $openid = safe_gpc_string($_GPC['openid']);
    
    if (empty($openid) && !empty($_W['openid'])) {
        $openid = $_W['openid'];
    }
    
    if (empty($_W['account']['oauth']) || (empty($code) && empty($openid))) {
        exit('通信错误，请在微信中重新发起请求');
    }

    $oauth = $account_api->getOauthInfo($code);
    if (!empty($oauth) && !is_error($oauth)) {
        $_SESSION['openid'] = $oauth['openid'];
        $_SESSION['unionid'] = $oauth['unionid'];
        $_SESSION['session_key'] = $oauth['session_key'];
        //更新Openid到mapping_fans表中
        $fans = mc_fansinfo($oauth['openid']);
        if (empty($fans)) {
            $record = array(
                'openid' => $oauth['openid'],
                'unionid' => $oauth['unionid'],
                'uid' => 0,
                'acid' => $_W['acid'],
                'uniacid' => $_W['uniacid'],
                'salt' => random(8),
                'updatetime' => TIMESTAMP,
                'nickname' => '',
                'follow' => '1',
                'followtime' => TIMESTAMP,
                'unfollowtime' => 0,
                'tag' => '',
                'user_from' => $_W['account']->typeSign == 'wxapp' ? 1 : 0,
            );

            $email = md5($oauth['openid']) . '@we7.cc';
            $email_exists_member = table('mc_members')
                ->where(array(
                    'email' => $email,
                    'uniacid' => $_W['uniacid']
                ))
                ->getcolumn('uid');
            if (!empty($email_exists_member)) {
                $uid = $email_exists_member;
            } else {
                $default_groupid = table('mc_groups')
                    ->where(array(
                        'uniacid' => $_W['uniacid'],
                        'isdefault' => 1
                    ))
                    ->getcolumn('groupid');
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'email' => $email,
                    'salt' => random(8),
                    'groupid' => $default_groupid,
                    'createtime' => TIMESTAMP,
                    'password' => md5($message['from'] . $data['salt'] . $_W['config']['setting']['authkey']),
                    'nickname' => '',
                    'avatar' => '',
                    'gender' => '',
                    'nationality' => '',
                    'resideprovince' => '',
                    'residecity' => '',
                );
                table('mc_members')->fill($data)->save();
                $uid = pdo_insertid();
            }
            $record['uid'] = $uid;
            $_SESSION['uid'] = $uid;
            table('mc_mapping_fans')->fill($record)->save();
        } else {
            $userinfo = $fans['tag'];
            $uid = $fans['uid'];
        }
        if (empty($userinfo)) {
            $userinfo = array(
                'openid' => $oauth['openid'],
            );
        }
        $_SESSION['userinfo'] = base64_encode(iserializer($userinfo));
        $account_api->result(0, '', array('sessionid' => $_W['session_id'], 'userinfo' => $fans, 'openid' => $oauth['openid']));
    } else {
        $account_api->result(1, $oauth['message']);
    }
} elseif ($do == 'userinfo') {
    $encrypt_data = $_GPC['encryptedData'];
    $iv = $_GPC['iv'];
    if (empty($iv) && empty($encrypt_data)) {
        if (empty($_SESSION['session_key']) || empty($_SESSION['openid'])) {
            $account_api->result(1, '请先登录');
        }
        $userinfo = json_decode(htmlspecialchars_decode($_GPC['userInfo']), true);// $_GPC 把原始数据转义了 使用 htmlspecialchars_decode 处理一下
        $userinfo['openId'] = $_SESSION['openid'];
        $userinfo['unionId'] = $_SESSION['unionid'];
    } else {
        if (empty($_SESSION['session_key']) || empty($encrypt_data) || empty($iv)) {
            $account_api->result(1, '请先登录');
        }
        $sign = sha1($_POST['rawData'] . $_SESSION['session_key']);
        if ($sign !== $_GPC['signature']) {
            $account_api->result(1, '签名错误');
        }
        $userinfo = $account_api->pkcs7Encode($encrypt_data, $iv);
    }
    $_SESSION['userinfo'] = base64_encode(iserializer($userinfo));

    $fans = mc_fansinfo($userinfo['openId']);
    $fans_update = array(
        'nickname' => $userinfo['nickName'],
        'unionid' => $userinfo['unionId'],
        'tag' => base64_encode(iserializer(array(
            'subscribe' => 1,
            'openid' => $userinfo['openId'],
            'nickname' => $userinfo['nickName'],
            'sex' => $userinfo['gender'],
            'language' => $userinfo['language'],
            'city' => $userinfo['city'],
            'province' => $userinfo['province'],
            'country' => $userinfo['country'],
            'headimgurl' => $userinfo['avatarUrl'],
        ))),
    );
    
    $member = mc_fetch($fans['uid']);
    if (empty($member)) {
        $default_groupid = table('mc_groups')
            ->where(array(
                'uniacid' => $_W['uniacid'],
                'isdefault' => 1
            ))
            ->getcolumn('groupid');
        $salt = random(8);
        $member = array(
            'uniacid' => $_W['uniacid'],
            'email' => md5($userinfo['openId']) . '@we7.cc',
            'salt' => $salt,
            'groupid' => $default_groupid,
            'createtime' => TIMESTAMP,
            'password' => md5($userinfo['openId'] . $salt . $_W['config']['setting']['authkey']),
            'nickname' => $userinfo['nickName'],
            'avatar' => $userinfo['avatarUrl'],
            'gender' => $userinfo['gender'],
            'nationality' => '',
            'resideprovince' => '',
            'residecity' => '',
        );
        table('mc_members')->fill($member)->save();
        $fans_update['uid'] = pdo_insertid();
    }
    table('mc_mapping_fans')->where(array('fanid' => $fans['fanid']))->fill($fans_update)->save();
    unset($member['password']);
    unset($member['salt']);
    $account_api->result(0, '', $member);
} elseif ($do == 'check') {
    if (!empty($_W['openid'])) {
        $account_api->result(0);
    } else {
        $account_api->result(1, 'session失效，请重新发起登录请求');
    }
}
