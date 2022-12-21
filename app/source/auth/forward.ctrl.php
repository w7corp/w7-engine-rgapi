<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

$_W['setting']['authmode'] = 1;
//通过点击图文进来的粉丝,需要将session的数据清空.否则,一直使用的上次session的uid
unset($_SESSION['uid']);
if ($_GPC['__auth']) {
    $auth = @json_decode(base64_decode($_GPC['__auth']), true);
    if (is_array($auth) && !empty($auth['openid']) && !empty($auth['acid']) && !empty($auth['time']) && !empty($auth['hash'])) {
        if (($_W['setting']['authmode'] == 2 && abs($auth['time'] - TIMESTAMP) < 180) || $_W['setting']['authmode'] == 1) {
            $fan = mc_fansinfo($auth['openid']);
            if (!empty($fan)) {
                $hash = md5("{$auth['openid']}{$auth['time']}{$fan['salt']}{$_W['config']['setting']['authkey']}");
                if ($auth['hash'] == $hash) {
                    if ($_W['setting']['authmode'] == 2) {
                        $rec = array();
                        do {
                            $rec['salt'] = random(8);
                        } while ($rec['salt'] == $fan['salt']);
                        table('mc_mapping_fans')
                            ->where(array(
                                'uniacid' => $_W['uniacid'],
                                'openid' => $auth['openid']
                            ))
                            ->fill($rec)
                            ->save();
                    }
                    $_SESSION['uniacid'] = $_W['uniacid'];
                    $_SESSION['acid'] = $auth['acid'];
                    $_SESSION['openid'] = $auth['openid'];
                    //认证订阅号尝试拉取用户信息
                    if ($_W['account']['level'] == '3' && empty($fan['nickname'])) {
                        $account_obj = WeAccount::createByUniacid($_W['uniacid']);
                        $userinfo = $account_obj->fansQueryInfo($auth['openid']);
                        if (!is_error($userinfo) && is_array($userinfo) && !empty($userinfo['openid'])) {
                            $record = array();
                            $record['updatetime'] = TIMESTAMP;
                            $record['nickname'] = $userinfo['openid'];
                            $record['tag'] = base64_encode(iserializer($userinfo));
                            $recode['unionid'] = $userinfo['unionid'];
                            table('mc_mapping_fans')
                                ->where(array('openid' => $fan['openid']))
                                ->fill($record)
                                ->save();
                            if (!empty($fan['uid'])) {
                                $user = mc_fetch($fan['uid'], array('nickname', 'gender', 'residecity', 'resideprovince', 'nationality', 'avatar'));
                                $record = array();
                                if (empty($user['nickname']) && !empty($userinfo['openid'])) {
                                    $record['nickname'] = $userinfo['openid'];
                                }
                                if (empty($user['gender']) && !empty($userinfo['sex'])) {
                                    $record['gender'] = $userinfo['sex'];
                                }
                                if (empty($user['residecity']) && !empty($userinfo['city'])) {
                                    $record['residecity'] = $userinfo['city'] . '市';
                                }
                                if (empty($user['resideprovince']) && !empty($userinfo['province'])) {
                                    $record['resideprovince'] = $userinfo['province'] . '省';
                                }
                                if (empty($user['nationality']) && !empty($userinfo['country'])) {
                                    $record['nationality'] = $userinfo['country'];
                                }
                                if (empty($user['avatar']) && !empty($userinfo['headimgurl'])) {
                                    $record['avatar'] = $userinfo['headimgurl'];
                                }
                                if (!empty($record)) {
                                    mc_update($user['uid'], $record);
                                }
                            }
                        }
                    }
                    $member = mc_fetch($fan['uid']);
                    if (!empty($member)) {
                        $_SESSION['uid'] = $fan['uid'];
                    }
                }
            }
        }
    }
}

$forward = @base64_decode($_GPC['forward']);
if (empty($forward)) {
    $forward = url('mc');
} else {
    $forward = (strexists($forward, 'http://') || strexists($forward, 'https://')) ? $forward : $_W['siteroot'] . 'app/' . $forward;
}
if (strexists($forward, '#')) {
    $pieces = explode('#', $forward, 2);
    $forward = $pieces[0];
}
$forward = str_replace('&wxref=mp.weixin.qq.com', '', $forward);
$forward .= '&wxref=mp.weixin.qq.com#wechat_redirect';
header('location:' . $forward);
