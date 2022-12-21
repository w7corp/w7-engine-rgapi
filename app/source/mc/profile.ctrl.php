<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');
load()->model('site');
load()->func('tpl');

$title = $_W['account']['name'] . '微站';
$dos = array('index', 'editprofile', 'personal_info', 'contact_method', 'education_info', 'jobedit', 'avatar', 'address', 'addressadd');
$do = in_array($do, $dos) ? $do : 'index';
$navs = site_app_navs('profile');
$operate = empty($_GPC['operate']) ? '' : safe_gpc_belong($_GPC['operate'], array('index', 'personal_info', 'contact_method', 'education_info', 'jobedit'), 'index');

if (empty($_W['member']['uid'])) {
    message('请先登录!', url('auth/login', array('i' => $_W['uniacid'])), 'error');
}

$profile = mc_fetch($_W['member']['uid']);
if (!empty($profile)) {
    if (empty($profile['email']) || (!empty($profile['email']) && substr($profile['email'], -6) == 'we7.cc' && strlen($profile['email']) == 39)) {
        $profile['email'] = '';
        $profile['email_effective'] = 1;
    }
}
//如果有openid,获取从公众平台同步的用户信息
if (!empty($_W['openid'])) {
    $map_fans = table('mc_mapping_fans')
        ->where(array(
            'uniacid' => $_W['uniacid'],
            'openid' => $_W['openid']
        ))
        ->getcolumn('tag');
    if (!empty($map_fans)) {
        if (is_base64($map_fans)) {
            $map_fans = base64_decode($map_fans);
        }
        if (is_serialized($map_fans)) {
            $map_fans = iunserializer($map_fans);
        }
        if (!empty($map_fans) && is_array($map_fans)) {
            //如果用户的资料中有这些信息,以用户的信息为准
            empty($profile['nickname']) ? ($data['nickname'] = strip_emoji($map_fans['nickname'])) : '';
            empty($profile['gender']) ? ($data['gender'] = $map_fans['sex']) : '';
            empty($profile['residecity']) ? ($data['residecity'] = ($map_fans['city']) ? $map_fans['city'] . '市' : '') : '';
            empty($profile['resideprovince']) ? ($data['resideprovince'] = ($map_fans['province']) ? $map_fans['province'] . '省' : '') : '';
            empty($profile['nationality']) ? ($data['nationality'] = $map_fans['country']) : '';
            empty($profile['avatar']) ? ($data['avatar'] = $map_fans['headimgurl']) : '';
            if (!empty($data)) {
                mc_update($_W['member']['uid'], $data);
            }
        }
    }
}

// 会员启用字段
$mcFields = table('mc_member_fields')
    ->searchWithProfileFields()
    ->select(array('mf.*', 'pf.field'))
    ->where(array(
        'mf.uniacid' => $_W['uniacid'],
        'mf.available' => 1
    ))
    ->getall('field');
$personal_info_hide = mc_card_settings_hide('personal_info');
$contact_method_hide = mc_card_settings_hide('contact_method');
$education_info_hide = mc_card_settings_hide('education_info');
$jobedit_hide = mc_card_settings_hide('jobedit');

if ($do == 'editprofile') {
    if ($_W['isajax'] && $_W['ispost']) {
        if ($operate == 'index') {
            $data = array(
                'nickname' => empty($_GPC['nickname']) ? '' : safe_gpc_string($_GPC['nickname']),
                'realname' => empty($_GPC['realname']) ? '' : safe_gpc_string($_GPC['realname']),
                'birth' => array(
                    'year' => empty($_GPC['birth']['year']) ? 0 : safe_gpc_int($_GPC['birth']['year']),
                    'month' => empty($_GPC['birth']['month']) ? 0 : safe_gpc_int($_GPC['birth']['month']),
                    'day' => empty($_GPC['birth']['day']) ? 0 : safe_gpc_int($_GPC['birth']['day'])
                ),
                'gender' => empty($_GPC['gender']) ? 0 : safe_gpc_int($_GPC['gender']),
            );
        }
        if ($operate == 'personal_info') {
            $data = array(
                'idcard' => empty($_GPC['idcard']) ? '' : safe_gpc_string($_GPC['idcard']),
                'height' => empty($_GPC['height']) ? 0 : safe_gpc_int($_GPC['height']),
                'weight' => empty($_GPC['weight']) ? 0 : safe_gpc_int($_GPC['weight']),
                'bloodtype' => empty($_GPC['bloodtype']) ? '' : safe_gpc_string($_GPC['bloodtype']),
                'zodiac' => empty($_GPC['zodiac']) ? '' : safe_gpc_string($_GPC['zodiac']),
                'constellation' => empty($_GPC['constellation']) ? '' : safe_gpc_string($_GPC['constellation']),
                'site' => empty($_GPC['site']) ? '' : safe_gpc_string($_GPC['site']),
                'bio' => empty($_GPC['bio']) ? '' : safe_gpc_string($_GPC['bio']),
                'affectivestatus' => empty($_GPC['affectivestatus']) ? '' : safe_gpc_string($_GPC['affectivestatus']),
                'lookingfor' => empty($_GPC['lookingfor']) ? '' : safe_gpc_string($_GPC['lookingfor']),
                'interest' => empty($_GPC['interest']) ? '' : safe_gpc_string($_GPC['interest']),
            );
        }
        if ($operate == 'contact_method') {
            $data = array(
                'telephone' => empty($_GPC['telephone']) ? '' : safe_gpc_string($_GPC['telephone']),
                'qq' => empty($_GPC['qq']) ? '' : safe_gpc_string($_GPC['qq']),
                'msn' => empty($_GPC['msn']) ? '' : safe_gpc_string($_GPC['msn']),
                'taobao' => empty($_GPC['taobao']) ? '' : safe_gpc_string($_GPC['taobao']),
                'alipay' => empty($_GPC['alipay']) ? '' : safe_gpc_string($_GPC['alipay']),
            );
        }
        if ($operate == 'education_info') {
            $data = array(
                'education' => empty($_GPC['education']) ? '' : safe_gpc_string($_GPC['education']),
                'graduateschool' => empty($_GPC['graduateschool']) ? '' : safe_gpc_string($_GPC['graduateschool']),
                'studentid' => empty($_GPC['studentid']) ? '' : safe_gpc_string($_GPC['studentid']),
            );
        }
        if ($operate == 'jobedit') {
            $data = array(
                'company' => empty($_GPC['company']) ? '' : safe_gpc_string($_GPC['company']),
                'occupation' => empty($_GPC['occupation']) ? '' : safe_gpc_string($_GPC['occupation']),
                'position' => empty($_GPC['position']) ? '' : safe_gpc_string($_GPC['position']),
                'revenue' => empty($_GPC['revenue']) ? '' : safe_gpc_string($_GPC['revenue']),
            );
        }
        $result = mc_update($_W['member']['uid'], $data);
        if ($result) {
            message('更新资料成功！', referer(), 'success');
        } else {
            message('更新资料失败！', referer(), 'error');
        }
    }
}
if ($do == 'avatar') {
    $avatar = array('avatar' => safe_gpc_url($_GPC['avatar']));
    if (mc_update($_W['member']['uid'], $avatar)) {
        message('头像设置成功！', referer(), 'success');
    }
}
/*收货地址*/
if ($do == 'address') {
    $address_id = empty($_GPC['id']) ? 0 : intval($_GPC['id']);
    $_GPC['op'] = empty($_GPC['op']) ? '' : $_GPC['op'];
    if ($_GPC['op'] == 'default') {
        pdo_update('mc_member_address', array('isdefault' => 0), array('uniacid' => $_W['uniacid'], 'uid' => $_W['member']['uid']));
        pdo_update('mc_member_address', array('isdefault' => 1), array('id' => $address_id, 'uniacid' => $_W['uniacid']));
        mc_update($_W['member']['uid'], array('address' => safe_gpc_string($_GPC['address'])));
    }
    if ($_GPC['op'] == 'delete') {
        if (!empty($profile) && !empty($_W['openid'])) {
            pdo_delete('mc_member_address', array('id' => $address_id, 'uid' => $_W['member']['uid'], 'uniacid' => $_W['uniacid']));
        }
    }
    $where = ' WHERE 1';
    $params = array(':uniacid' => $_W['uniacid'], ':uid' => $_W['member']['uid']);
    if (!empty($_GPC['addid'])) {
        $where .= ' AND `id` = :id';
        $params[':id'] = intval($_GPC['addid']);
    }
    $where .= ' AND `uniacid` = :uniacid AND `uid` = :uid';
    $sql = 'SELECT * FROM ' . tablename('mc_member_address') . $where;
    if (empty($params[':id'])) {
        $psize = 10;
        $pindex = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
        $sql .= ' LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
        $addresses = pdo_fetchall($sql, $params);
        $sql = 'SELECT COUNT(*) FROM ' . tablename('mc_member_address') . $where;
        $total = pdo_fetchcolumn($sql, $params);
        $pager = pagination($total, $pindex, $psize);
    } else {
        $address = pdo_fetch($sql, $params);
    }
}
/*添加或编辑地址*/
if ($do == 'addressadd') {
    $addid = empty($_GPC['addid']) ? 0 : intval($_GPC['addid']);
    if ($_W['isajax'] && $_W['ispost']) {
        $post = safe_gpc_array($_GPC['address']);
        if (empty($post['username'])) {
            message('请输入您的姓名', referer(), 'error');
        }
        if (empty($post['mobile'])) {
            message('请输入您的手机号', referer(), 'error');
        }
        if (empty($post['zipcode'])) {
            message('请输入您的邮政编码', referer(), 'error');
        }
        if (empty($post['province'])) {
            message('请输入您的所在省', referer(), 'error');
        }
        if (empty($post['city'])) {
            message('请输入您的所在市', referer(), 'error');
        }
        if (empty($post['address'])) {
            message('请输入您的详细地址', referer(), 'error');
        }
        $address = array(
            'username' => $post['username'],
            'mobile' => $post['mobile'],
            'zipcode' => $post['zipcode'],
            'province' => $post['province'],
            'city' => $post['city'],
            'district' => empty($post['district']) ? '' : $post['district'],
            'address' => $post['address'],
        );
        $address_data = table('mc_member_address')
            ->where(array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid']
            ))
            ->get();
        if (empty($address_data)) {
            $address['isdefault'] = 1;
        }
        if (!empty($addid)) {
            if (table('mc_member_address')
                ->where(array(
                    'id' => $addid,
                    'uniacid' => $_W['uniacid'],
                    'uid' => $_W['member']['uid']
                ))
                ->fill($address)
                ->save()) {
                message('修改收货地址成功', url('mc/profile/address'), 'success');
            } else {
                message('修改收货地址失败，请稍后重试', url('mc/profile/address'), 'error');
            }
        } else {
            $address['uniacid'] = $_W['uniacid'];
            $address['uid'] = $_W['member']['uid'];
            if (table('mc_member_address')->fill($address)->save()) {
                $adres = table('mc_member_address')
                    ->where(array(
                        'uniacid' => $_W['uniacid'],
                        'uid' => $_W['member']['uid'],
                        'isdefault' => 1
                    ))
                    ->get();
                if (!empty($adres)) {
                    $adres['address'] = $adres['province'] . $adres['city'] . $adres['district'] . $adres['address'];
                    mc_update($_W['member']['uid'], array('address' => $adres['address']));
                }
                message('地址添加成功', url('mc/profile/address'), 'success');
            }
        }
    }
    if (!empty($addid)) {
        $address = pdo_get('mc_member_address', array('id' => $addid, 'uid' => $_W['member']['uid'], 'uniacid' => $_W['uniacid']));
    }
}
template('mc/profile');
