<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');
load()->func('communication');

$code = safe_gpc_string($_GPC['code']);
$scope = safe_gpc_string($_GPC['scope']);
$oauth_type = safe_gpc_int($_GPC['oauth_type']);
if (!empty($_SESSION['pay_params'])) {
    //借用微信支付或服务商支付，授权公众号信息改成借用公众号信息
    $setting = uni_setting($_W['uniacid'], array('payment'));
    $uniacid = !empty($setting['payment']['wechat']['service']) ? $setting['payment']['wechat']['service'] : $setting['payment']['wechat']['borrow'];
    $acid = table('uni_account')
        ->where(array('uniacid' => $uniacid))
        ->getcolumn('default_acid');
    $setting = account_fetch($acid);
    $_W['account']['oauth'] = array(
        'key' => $setting['key'],
        'secret' => $setting['secret'],
        'type' => $setting['type'],
        'level' => $setting['level'],
        'acid' => $setting['acid'],
    );
}
if (empty($_W['account']['oauth']) || empty($code)) {
    exit('通信错误，请在微信中重新发起请求');
}
$oauth_account = WeAccount::create($_W['account']['oauth']);
$oauth = $oauth_account->getOauthInfo($code);

if (is_error($oauth) || empty($oauth['openid'])) {
    $state = '';
    if (isset($_GPC['state']) && !empty($_GPC['state']) && strexists($_GPC['state'], 'we7sid-')) {
        $state = safe_gpc_string($_GPC['state']);
    }
	$url = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=auth&a=oauth&scope=snsapi_base&oauth_type={$oauth_type}";
    $callback = urlencode($url);
    $forward = $oauth_account->getOauthCodeUrl($callback, $state);
    header('Location: ' . $forward);
    exit;
}

$forward = urldecode($_SESSION['dest_url']);
$forward = strexists($forward, 'i=') ? $forward : "{$forward}&i={$_W['uniacid']}";

if ('snsapi_base' == $scope && (empty($oauth_type) || OAUTH_TYPE_SYSTEM == $oauth_type)) {
	$fan = mc_fansinfo($oauth['openid'], 0, $_W['uniacid']);
	if (empty($fan)) {
		header('Location: ' . $forward . '&scope=snsapi_userinfo');
		exit;
	}
}

//部分开发者链接内有‘&wxref=mp.weixin.qq.com’，而没有‘#wechat_redirect’会导致判断错误，故不能直接判断‘&wxref=mp.weixin.qq.com#wechat_redirect’
if (strpos($forward, '&wxref=mp.weixin.qq.com')) {
    //部分开发者链接形如： i=1&c=enrey&do=detail&wxref=mp.weixin.qq.com&m=we7_mall&id=2,此时使用strstr会丢失&wxref=mp.weixin.qq.com后的值
    $forward = str_replace('&wxref=mp.weixin.qq.com', '', $forward) . '&wxref=mp.weixin.qq.com#wechat_redirect';
} else {
    $forward .= '&wxref=mp.weixin.qq.com#wechat_redirect';
}
if (!empty($oauth['is_snapshotuser'])) {
    header('Location: ' . $forward);
    exit;
}

if (!empty($_SESSION['pay_params'])) {
    if (!empty($oauth['openid'])) {
        header("Location: " . url('mc/cash/wechat', array('payopenid' => $oauth['openid'], 'params' => $_SESSION['pay_params'])));
        exit;
    } else {
        message('非法访问.');
    }
}
$_SESSION['oauth_openid'] = $oauth['openid'];
$_SESSION['oauth_acid'] = $_W['account']['oauth']['acid'];

if (intval($_W['account']['level']) == ACCOUNT_SERVICE_VERIFY) {
    $fan = mc_fansinfo($oauth['openid'], 0, $_W['uniacid']);
    if (!empty($fan)) {
        $_SESSION['openid'] = $oauth['openid'];
        if (empty($_SESSION['uid'])) {
            if (!empty($fan['uid'])) {
                $member = mc_fetch($fan['uid'], array('uid'));
                if (!empty($member) && $member['uniacid'] == $_W['uniacid']) {
                    $_SESSION['uid'] = $member['uid'];
                }
            }
        }
    } else {
        $accObj = WeAccount::createByUniacid($_W['uniacid']);
        $userinfo = $accObj->fansQueryInfo($oauth['openid']);

        if (!is_error($userinfo) && !empty($userinfo) && !empty($userinfo['subscribe'])) {
            $userinfo['nickname'] = $userinfo['openid'];
            $_SESSION['userinfo'] = base64_encode(iserializer($userinfo));
            $record = array(
                'openid' => $userinfo['openid'],
                'uid' => 0,
                'acid' => $_W['acid'],
                'uniacid' => $_W['uniacid'],
                'salt' => random(8),
                'updatetime' => TIMESTAMP,
                'nickname' => $userinfo['openid'],
                'follow' => $userinfo['subscribe'],
                'followtime' => $userinfo['subscribe_time'],
                'unfollowtime' => 0,
                'unionid' => $userinfo['unionid'],
                'tag' => base64_encode(iserializer($userinfo)),
                'user_from' => $_W['account']->typeSign == 'wxapp' ? 1 : 0,
            );

            if (!isset($unisetting['passport']) || empty($unisetting['passport']['focusreg'])) {
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
                        'nickname' => $userinfo['openid'],
                        'avatar' => '',
                        'gender' => $userinfo['sex'],
                        'nationality' => '',
                        'resideprovince' => '',
                        'residecity' => '',
                    );
                    table('mc_members')->fill($data)->save();
                    $uid = pdo_insertid();
                }
                $record['uid'] = $uid;
                $_SESSION['uid'] = $uid;
            }
            table('mc_mapping_fans')->fill($record)->save();
            $fanid = pdo_insertid();
            $mc_fans_tag_table = table('mc_fans_tag');
            $mc_fans_tag_fields = mc_fans_tag_fields();
            $fans_tag_update_info = array();
            foreach ($userinfo as $fans_field_key => $fans_field_info) {
                if (in_array($fans_field_key, array_keys($mc_fans_tag_fields))) {
                    $fans_tag_update_info[$fans_field_key] = $fans_field_info;
                }
            }
            $fans_tag_update_info['tagid_list'] = iserializer($fans_tag_update_info['tagid_list']);
            $fans_tag_update_info['uniacid'] = $_W['uniacid'];
            $fans_tag_update_info['fanid'] = $fanid;
            $fans_tag_exists = $mc_fans_tag_table->getByOpenid($fans_tag_update_info['openid']);
            if (!empty($fans_tag_exists)) {
                table('mc_fans_tag')
                    ->where(array('openid' => $fans_tag_update_info['openid']))
                    ->fill($fans_tag_update_info)
                    ->save();
            } else {
                table('mc_fans_tag')->fill($fans_tag_update_info)->save();
            }
        } else {
            $record = array(
                'openid' => $oauth['openid'],
                'nickname' => '',
                'subscribe' => '0',
                'subscribe_time' => '',
                'headimgurl' => '',
            );
        }
        $_SESSION['openid'] = $oauth['openid'];
        $_W['fans'] = $record;
        $_W['fans']['from_user'] = $record['openid'];
    }
}
if (intval($_W['account']['level']) != ACCOUNT_SERVICE_VERIFY) {
    $mc_oauth_fan = mc_oauth_fans($oauth['openid'], $_W['uniacid']);
    if (empty($mc_oauth_fan)) {
        $data = array(
            'uniacid' => $_W['uniacid'],
            'oauth_openid' => $oauth['openid'],
            'uid' => intval($_SESSION['uid']),
            'openid' => $_SESSION['openid']
        );
        table('mc_oauth_fans')->fill($data)->save();
    }
    //如果包含Unionid，则直接查原始openid
    if (!empty($oauth['unionid'])) {
        $fan = table('mc_mapping_fans')
            ->searchWithUnionid($oauth['unionid'])
            ->searchWithUniacid($_W['uniacid'])
            ->get();
        if (!empty($fan)) {
            if (!empty($fan['uid'])) {
                $_SESSION['uid'] = intval($fan['uid']);
            }
            if (!empty($fan['openid'])) {
                $_SESSION['openid'] = strval($fan['openid']);
            }
        }
    } else {
        if (!empty($mc_oauth_fan)) {
            if (empty($_SESSION['uid']) && !empty($mc_oauth_fan['uid'])) {
                $_SESSION['uid'] = intval($mc_oauth_fan['uid']);
            }
            if (empty($_SESSION['openid']) && !empty($mc_oauth_fan['openid'])) {
                $_SESSION['openid'] = strval($mc_oauth_fan['openid']);
            }
        }
    }
}
if ($scope == 'userinfo' || $scope == 'snsapi_userinfo') {
    $userinfo = $oauth_account->getOauthUserInfo($oauth['access_token'], $oauth['openid']);
    if (!is_error($userinfo)) {
        $_W['fans']['nickname'] = $userinfo['nickname'] = stripcslashes($userinfo['nickname']);
        $_W['fans']['headimgurl'] = $userinfo['avatar'] = $userinfo['headimgurl'];
        $_W['fans']['userinfo'] = $_SESSION['userinfo'] = base64_encode(iserializer($userinfo));
        $fan = table('mc_mapping_fans')->searchWithOpenid($oauth['openid'])->searchWithUniacid($_W['uniacid'])->get();
        if (!empty($fan)) {
            $record = array();
            $record['updatetime'] = TIMESTAMP;
            $record['nickname'] = stripslashes($userinfo['nickname']);
            $record['tag'] = base64_encode(iserializer($userinfo));
            if (empty($fan['unionid'])) {
                $record['unionid'] = !empty($userinfo['unionid']) ? $userinfo['unionid'] : '';
            }
            table('mc_mapping_fans')
                ->where(array(
                    'openid' => $fan['openid'],
                    'uniacid' => $_W['uniacid']
                ))
                ->fill($record)
                ->save();
            if (!empty($fan['uid']) || !empty($_SESSION['uid'])) {
                $uid = $fan['uid'];
                if (empty($uid)) {
                    $uid = $_SESSION['uid'];
                }
                $user = mc_fetch($uid, array('nickname', 'gender', 'residecity', 'resideprovince', 'nationality', 'avatar'));
                $record = array();
                $record['nickname'] = stripslashes($userinfo['nickname']);
                $record['gender'] = $userinfo['sex'];
                $record['residecity'] = $userinfo['city'] . '市';
                $record['resideprovince'] = $userinfo['province'] . '省';
                $record['nationality'] = $userinfo['country'];
                $record['avatar'] = $userinfo['headimgurl'];
                if (!empty($record)) {
                    mc_update($user['uid'], $record);
                }
            }
            $fanid = $fan['fanid'];
        } else {
            $record = array(
                'openid' => $oauth['openid'],
                'uid' => 0,
                'acid' => $_W['acid'],
                'uniacid' => $_W['uniacid'],
                'salt' => random(8),
                'updatetime' => TIMESTAMP,
                'nickname' => $userinfo['nickname'],
                'follow' => 0,
                'followtime' => 0,
                'unfollowtime' => 0,
                'tag' => base64_encode(iserializer($userinfo)),
                'unionid' => !empty($userinfo['unionid']) ? $userinfo['unionid'] : '',
                'user_from' => $_W['account']->typeSign == 'wxapp' ? 1 : 0,
            );

            if (!isset($unisetting['passport']) || empty($unisetting['passport']['focusreg'])) {
                $default_groupid = table('mc_groups')
                    ->where(array(
                        'uniacid' => $_W['uniacid'],
                        'isdefault' => 1
                    ))
                    ->getcolumn('groupid');
                $data = array(
                    'uniacid' => $_W['uniacid'],
                    'email' => md5($oauth['openid']) . '@we7.cc',
                    'salt' => random(8),
                    'groupid' => $default_groupid,
                    'createtime' => TIMESTAMP,
                    'password' => md5($message['from'] . $data['salt'] . $_W['config']['setting']['authkey']),
                    'nickname' => $userinfo['nickname'],
                    'avatar' => $userinfo['headimgurl'],
                    'gender' => $userinfo['sex'],
                    'nationality' => $userinfo['country'],
                    'resideprovince' => $userinfo['province'] . '省',
                    'residecity' => $userinfo['city'] . '市',
                );
                table('mc_members')
                    ->fill($data)
                    ->save();
                $uid = pdo_insertid();
                $record['uid'] = $uid;
                $_SESSION['uid'] = $uid;
            }
            table('mc_mapping_fans')->fill($record)->save();
            $fanid = pdo_insertid();
        }
        $mc_fans_tag_table = table('mc_fans_tag');
        $fans_tag_update_info = array();
        $fans_tag_update_info['openid'] = $userinfo['openid'];
        $fans_tag_update_info['nickname'] = $userinfo['nickname'];
        $fans_tag_update_info['headimgurl'] = $userinfo['headimgurl'];
        $fans_tag_update_info['uniacid'] = $_W['uniacid'];
        $fans_tag_update_info['fanid'] = $fanid;
        $fans_tag_exists = $mc_fans_tag_table->getByOpenid($fans_tag_update_info['openid']);
        if (!empty($fans_tag_exists)) {
            table('mc_fans_tag')
                ->where(array('openid' => $fans_tag_update_info['openid']))
                ->fill($fans_tag_update_info)
                ->save();
        } else {
            table('mc_fans_tag')->fill($fans_tag_update_info)->save();
        }
    } else {
        message('微信授权获取用户信息失败,错误信息为: ' . $response['message']);
    }
}

header('Location: ' . $forward);
exit;
