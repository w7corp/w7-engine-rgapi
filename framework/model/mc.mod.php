<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$
 */

/**
 * 更新会员个人信息.
 * 此函数不能更新用户的积分数量，更新积分数量使用 mc_credit_update
 * @param mixed $uid 会员id
 * @param array $fields 更新字段
 * @return mixed
 */
function mc_update($uid, $fields) {
    global $_W;
    if (empty($fields)) {
        return false;
    }
    //如果是$uid 是一个openid并且是新增用户，需要更新mc_mapping_fans对应的uid
    if (is_string($uid)) {
        $openid = $uid;
    }

    $uid = mc_openid2uid($uid);

    if (!empty($_W['weid'])) {
        $fields['weid'] = $_W['weid'];
    }
    $struct = array_keys(mc_fields());
    $struct[] = 'birthyear';
    $struct[] = 'birthmonth';
    $struct[] = 'birthday';
    $struct[] = 'resideprovince';
    $struct[] = 'residecity';
    $struct[] = 'residedist';
    $struct[] = 'groupid';
    $struct[] = 'salt';

    if (isset($fields['birth']) && !is_array($fields['birth'])) {
        $birth = explode('-', $fields['birth']);
        $fields['birth'] = array(
            'year' => $birth[0],
            'month' => $birth[1],
            'day' => $birth[2],
        );
    }
    if (!empty($fields['birth'])) {
        $fields['birthyear'] = $fields['birth']['year'];
        $fields['birthmonth'] = $fields['birth']['month'];
        $fields['birthday'] = $fields['birth']['day'];
    }
    if (isset($fields['reside'])) {
        $fields['resideprovince'] = $fields['reside']['province'];
        $fields['residecity'] = $fields['reside']['city'];
        $fields['residedist'] = $fields['reside']['district'];
    }
    unset($fields['reside'], $fields['birth']);
    //不允许修改的字段
    $unstruct = array('salt', 'credit1', 'credit2', 'credit3', 'credit4', 'credit5', 'credit6');
    $struct = array_diff($struct, $unstruct);
    foreach ($fields as $field => $value) {
        if (!in_array($field, $struct) || is_array($value)) {
            unset($fields[$field]);
        }
    }
    if (!empty($fields['avatar'])) {
        if (strexists($fields['avatar'], 'attachment/images/global/avatars/avatar_')) {
            $fields['avatar'] = str_replace($_W['attachurl'], '', $fields['avatar']);
        }
    }

    $member = table('mc_members')->getById($uid);
    if (!empty($fields['email'])) {
        $mc_members = table('mc_members');
        $mc_members->searchWithUniacid(mc_current_real_uniacid());
        $mc_members->searchWithoutUid($uid);
        $mc_members->searchWithEmail($fields['email']);
        $emailexists = $mc_members->getcolumn('email');
        if ($emailexists) {
            unset($fields['email']);
        }
    }
    if (!empty($fields['mobile'])) {
        $mc_members = table('mc_members');
        $mc_members->searchWithUniacid(mc_current_real_uniacid());
        $mc_members->searchWithoutUid($uid);
        $mc_members->searchWithEmail($fields['mobile']);
        $mobilexists = $mc_members->getcolumn('mobile');
        if ($mobilexists) {
            unset($fields['mobile']);
        }
    }
    if (empty($member)) {
        if (empty($fields['mobile']) && empty($fields['email'])) {
            return false;
        }
        $fields['uniacid'] = mc_current_real_uniacid();
        $fields['createtime'] = TIMESTAMP;
        pdo_insert('mc_members', $fields);
        $insert_id = pdo_insertid();
    } else {
        if (!empty($fields)) {
            pdo_update('mc_members', $fields, array('uid' => $uid, 'uniacid' => mc_current_real_uniacid()));
        }
    }

    if (!empty($openid) && empty($uid)) {
        table('mc_mapping_fans')->fill(array('uid' => $insert_id))->where(array('uniacid' => mc_current_real_uniacid(), 'openid' => $openid))->save();
    }
    cache_build_memberinfo($uid);
    return true;
}

/**
 * 获取一个或多个会员某个或多个字段的信息
 * @param mixed $uid 一个或多个会员 uid
 * @param array $fields 一个、多个或所有字段
 * @return array
 * */
function mc_fetch($uid, $fields = array()) {
    if (empty($uid)) {
        return array();
    }
    $struct = mc_fields();
    $struct = array_keys($struct);
    if (!empty($fields)) {
        foreach ($fields as $key => $field) {
            if (!in_array($field, $struct)) {
                unset($fields[$key]);
            }
            if ($field == 'birth') {
                $fields[] = 'birthyear';
                $fields[] = 'birthmonth';
                $fields[] = 'birthday';
            }
            if ($field == 'reside') {
                $fields[] = 'resideprovince';
                $fields[] = 'residecity';
                $fields[] = 'residedist';
            }
        }
        unset($fields['birth'], $fields['reside']);
    }
    $result = array();
    if (is_array($uid)) {
        foreach ($uid as $id) {
            $user_info = mc_fetch_one($id);
            if (!empty($user_info) && !empty($fields)) {
                foreach ($fields as $field) {
                    $result[$id][$field] = $user_info[$field];
                }
                $result[$id]['uid'] = $id;
            } else {
                $result[$id] = $user_info;
            }
        }
    } else {
        $user_info = mc_fetch_one($uid);
        if (!empty($user_info) && !empty($fields)) {
            foreach ($fields as $field) {
                $result[$field] = $user_info[$field];
            }
            $result['uid'] = $uid;
        } else {
            $result = $user_info;
        }
    }
    return $result;
}

/**
 * 获取一个会员所有信息
 * @param mixed $uid 会员 uid
 * @param int $uniacid 所属帐号uniacid
 * @return array
 * */
function mc_fetch_one($uid, $uniacid = 0) {
    global $_W;
    $uid = mc_openid2uid($uid);
    if (empty($uid)) {
        return array();
    }
    $cachekey = cache_system_key('memberinfo', array('uid' => $uid));
    $cache = cache_load($cachekey);

    if (!empty($cache)) {
        return $cache;
    }
    $params = array('uid' => $uid);
    $params['uniacid'] = intval($uniacid) > 0 ? intval($uniacid) : $_W['uniacid'];
    $result = pdo_get('mc_members', $params);
    if (!empty($result)) {
        $result['avatar'] = tomedia($result['avatar']);
        $result['credit1'] = floatval($result['credit1']);
        $result['credit2'] = floatval($result['credit2']);
        $result['credit3'] = floatval($result['credit3']);
        $result['credit4'] = floatval($result['credit4']);
        $result['credit5'] = floatval($result['credit5']);
        $result['credit6'] = floatval($result['credit6']);
    } else {
        $result = array();
    }
    cache_write($cachekey, $result);
    return $result;
}
/**
 * 获取粉丝信息
 * @param mixed $openidOruid 粉丝或会员ID
 * @param int $acid 子公众号ID
 * @param int $uniacid 主公众号ID
 * @return array
 */
function mc_fansinfo($openidOruid, $acid = 0, $uniacid = 0) {
    global $_W;
    if (empty($openidOruid)) {
        return array();
    }
    if (is_numeric($openidOruid)) {
        $openid = mc_uid2openid($openidOruid);
        if (empty($openid)) {
            return array();
        }
    } else {
        $openid = $openidOruid;
    }

    $mc_mapping_fans_table = table('mc_mapping_fans');
    if (!empty($uniacid)) {
        $mc_mapping_fans_table->searchWithUniacid($uniacid);
    }
    $mc_mapping_fans_table->searchWithOpenid($openid);
    $fan = $mc_mapping_fans_table->get();

    if (!empty($fan)) {
        $mc_fans_tag_table = table('mc_fans_tag');
        $tags_info = $mc_fans_tag_table->getByOpenid($openid);
        if (empty($tags_info)) {
            if (!empty($fan['tag']) && is_string($fan['tag'])) {
                if (is_base64($fan['tag'])) {
                    $fan['tag'] = @base64_decode($fan['tag']);
                }
                if (is_serialized($fan['tag'])) {
                    $fan['tag'] = @iunserializer($fan['tag']);
                }
                if (is_array($fan['tag']) && !empty($fan['tag']['headimgurl'])) {
                    $fan['tag']['avatar'] = tomedia($fan['tag']['headimgurl']);
                    unset($fan['tag']['headimgurl']);
                    if (empty($fan['nickname']) && !empty($fan['tag']['nickname'])) {
                        $fan['nickname'] = strip_emoji($fan['tag']['nickname']);
                    }
                    $fan['gender'] = $fan['sex'] = $fan['tag']['sex'];
                    $fan['avatar'] = $fan['headimgurl'] = $fan['tag']['avatar'];
                }
            } else {
                $fan['tag'] = array();
            }
        } else {
            $fan['tag'] = $tags_info;
            $fan['tag']['avatar'] = $tags_info['headimgurl'];

            if (empty($fan['nickname']) && !empty($fan['tag']['nickname'])) {
                $fan['nickname'] = strip_emoji($fan['tag']['nickname']);
            }
            $fan['gender'] = $fan['sex'] = $fan['tag']['sex'];
            $fan['avatar'] = $fan['headimgurl'] = $fan['tag']['avatar'];
        }
    }

    if (empty($fan) && $openid == $_W['openid'] && !empty($_SESSION['userinfo'])) {
        $fan['tag'] = iunserializer(base64_decode($_SESSION['userinfo']));
        $fan['uid'] = 0;
        $fan['openid'] = $fan['tag']['openid'];
        $fan['follow'] = 0;
        if (empty($fan['nickname']) && !empty($fan['tag']['nickname'])) {
            $fan['nickname'] = strip_emoji($fan['tag']['nickname']);
        }
        $fan['gender'] = $fan['sex'] = $fan['tag']['sex'];
        $fan['avatar'] = $fan['headimgurl'] = $fan['tag']['headimgurl'];
        $mc_oauth_fan = mc_oauth_fans($fan['openid']);
        if (!empty($mc_oauth_fan)) {
            $fan['uid'] = $mc_oauth_fan['uid'];
        }
    }
    return $fan;
}

/**
 * 无网页授权的公众号,通过借用授权   定位 openid 和 uid
 * @param string $openid 粉丝唯一标志
 * @param int $uniacid 子公众号ID
 * @return array
 */
function mc_oauth_fans($openid, $uniacid = 0) {
    $mc_oauth_fans_table = table('mc_oauth_fans');
    if (!empty($uniacid)) {
        $mc_oauth_fans_table->searchWithUniacid($uniacid);
    }
    $mc_oauth_fans_table->searchWithoAuthopenid($openid);
    $fan = $mc_oauth_fans_table->get();
    return $fan;
}

/**
 * 调用oauth用户授权获取资料并更新会员信息
 * @param int $acid 子公众号ID
 * @return mixed
 */
function mc_oauth_userinfo($acid = 0) {
    global $_W;
    if (isset($_SESSION['userinfo'])) {
        $userinfo = iunserializer(base64_decode($_SESSION['userinfo']));
        if (!empty($userinfo) || is_array($userinfo)) {
            return $userinfo;
        }
    }
    if ($_W['container'] != 'wechat') {
        return array();
    }
    //公众号不能授权直接判断小程序是否可以授权
    $result = mc_oauth_account_userinfo();
    if (is_error($result)) {
        load()->func('tpl');
        include template('mc/iswxapp', TEMPLATE_INCLUDEPATH);
        exit;
    }
    return $result;
}

function mc_oauth_account_userinfo($url = '') {
    global $_W;
    if ($_W['account']->typeSign != 'account') {
        error(-4, '该账号非公众号类型，不支持使用该函数！');
    }
    if (empty($_W['account']['oauth'])) {
        return error(-1, '未指定网页授权公众号, 无法获取用户信息.');
    }
    if (empty($_W['account']['oauth']['key'])) {
        return error(-2, '公众号未设置 appId 或 secret.');
    }
    if (intval($_W['account']['oauth']['level']) < 4 && !in_array($_W['account']['oauth']['level'], array(ACCOUNT_TYPE_APP_NORMAL, ACCOUNT_TYPE_APP_AUTH))) {
        return error(-3, '公众号非认证服务号, 无法获取用户信息.');
    }
    $state = 'we7sid-' . $_W['session_id'];
    $_SESSION['dest_url'] = urlencode($_W['siteurl']);
    if (!empty($url)) {
        $_SESSION['dest_url'] = urlencode($url);
    }
    $oauth_url = uni_account_oauth_host();
    $url = $oauth_url . "app/index.php?i={$_W['uniacid']}&c=auth&a=oauth&scope=userinfo";
    $callback = urlencode($url);

    $oauth_account = WeAccount::create($_W['account']['oauth']);
    $forward = $oauth_account->getOauthUserInfoUrl($callback, $state);
    header('Location: ' . $forward);
    exit;
}

/*
 * 如果某个模块需要获取用户的的一些信息(比如:真实姓名等),可使用函数【因生日.地区tpl函数修改,重新改造该函数】
 * 如果用户已经完善了(真实姓名),则返回用户的信息(真实姓名)。如果没有完善,则显示表单,让用户填写
 * @param int $uid 会员ID
 * @param array $fields 会员字段信息
 * @param string $pre 提示信息连接符
 * @return mixed
 */
function mc_require($uid, $fields, $pre = '') {
    global $_W, $_GPC;
    if (empty($fields) || !is_array($fields)) {
        return false;
    }
    $flipfields = array_flip($fields);
    //如果是生日或户籍,只要取其中一个字段,则返回其他两个
    if (in_array('birth', $fields) || in_array('birthyear', $fields) || in_array('birthmonth', $fields) || in_array('birthday', $fields)) {
        unset($flipfields['birthyear'], $flipfields['birthmonth'], $flipfields['birthday'], $flipfields['birth']);
        $flipfields['birthyear'] = 'birthyear';
        $flipfields['birthmonth'] = 'birthmonth';
        $flipfields['birthday'] = 'birthday';
    }
    if (in_array('reside', $fields) || in_array('resideprovince', $fields) || in_array('residecity', $fields) || in_array('residedist', $fields)) {
        unset($flipfields['residedist'], $flipfields['resideprovince'], $flipfields['residecity'], $flipfields['reside']);
        $flipfields['resideprovince'] = 'resideprovince';
        $flipfields['residecity'] = 'residecity';
        $flipfields['residedist'] = 'residedist';
    }
    $fields = array_keys($flipfields);
    if (!in_array('uniacid', $fields)) {
        $fields[] = 'uniacid';
    }
    if (!empty($pre)) {
        $pre .= '<br/>';
    }
    if (empty($uid)) {
        foreach ($fields as $field) {
            $profile[$field] = '';
        }
        $uniacid = $_W['uniacid'];
    } else {
        $profile = mc_fetch($uid, $fields);
        $uniacid = $profile['uniacid'];
    }
    $mc_member_fields = table('mc_member_fields');
    $mc_member_fields->searchWithUniacid($_W['uniacid']);
    $mc_member_fields->selectFields(array('b.field', 'b.id as fid', 'a.*'));
    $system_fields = $mc_member_fields->getAllFields();
    if (empty($system_fields)) {
        $system_fields = pdo_getall('profile_fields', array(), array('id', 'field', 'title'), '');
    }

    $titles = array();
    foreach ($system_fields as $field) {
        $titles[$field['field']] = $field['title'];
    }

    $message = '';
    $ks = array();
    foreach ($profile as $k => $v) {
        if (empty($v)) {
            $ks[] = $k;
            $message .= $system_fields[$k]['title'] . ', ';
        }
    }

    if (!empty($message)) {
        $title = '完善资料';
        if (checksubmit('submit')) {
            if (in_array('resideprovince', $fields)) {
                $_GPC['resideprovince'] = safe_gpc_string($_GPC['reside']['province']);
                $_GPC['residecity'] = safe_gpc_string($_GPC['reside']['city']);
                $_GPC['residedist'] = safe_gpc_string($_GPC['reside']['district']);
            }
            if (in_array('birthyear', $fields)) {
                $_GPC['birthyear'] = intval($_GPC['birth']['year']);
                $_GPC['birthmonth'] = intval($_GPC['birth']['month']);
                $_GPC['birthday'] = intval($_GPC['birth']['day']);
            }
            $record = array_elements($fields, safe_gpc_array($_GPC));
            if (isset($record['uniacid'])) {
                unset($record['uniacid']);
            }

            foreach ($record as $field => $value) {
                if ($field == 'gender') {
                    continue;
                }
                if (empty($value)) {
                    itoast('请填写完整所有资料.', referer(), 'error');
                }
            }
            if (empty($record['nickname']) && !empty($_W['fans']['nickname'])) {
                $record['nickname'] = $_W['fans']['nickname'];
            }
            if (empty($record['avatar']) && !empty($_W['fans']['tag']['avatar'])) {
                $record['avatar'] = $_W['fans']['tag']['avatar'];
            }
            $condition = " AND uid != {$uid} ";
            if (in_array('email', $fields)) {
                $mc_members = table('mc_members');
                $mc_members->searchWithUniacid(mc_current_real_uniacid());
                $mc_members->searchWithoutUid($uid);
                $mc_members->searchWithEmail(trim($record['email']));
                $emailexists = $mc_members->getcolumn('email');
                if ($emailexists) {
                    itoast('抱歉，您填写的手机号已经被使用，请更新。', 'refresh', 'error');
                }
            }
            if (in_array('mobile', $fields)) {
                $mc_members = table('mc_members');
                $mc_members->searchWithUniacid(mc_current_real_uniacid());
                $mc_members->searchWithoutUid($uid);
                $mc_members->searchWithEmail(trim($record['mobile']));
                $mobilexists = $mc_members->getcolumn('mobile');
                if ($mobilexists) {
                    itoast('抱歉，您填写的手机号已经被使用，请更新。', 'refresh', 'error');
                }
            }
            $insertuid = mc_update($uid, $record);
            if (empty($uid)) {
                pdo_update('mc_oauth_fans', array('uid' => $insertuid), array('oauth_openid' => $_W['openid']));
                pdo_update('mc_mapping_fans', array('uid' => $insertuid), array('openid' => $_W['openid']));
            }
            itoast('资料完善成功.', 'refresh', 'success');
        }
        load()->func('tpl');
        load()->model('activity');
        $filter = array();
        $filter['status'] = 1;
        $tokens = activity_token_owned($_W['member']['uid'], $filter);

        $setting = uni_setting($_W['uniacid'], array('creditnames', 'creditbehaviors'));
        $behavior = $setting['creditbehaviors'];
        $creditnames = $setting['creditnames'];
        $credits = mc_credit_fetch($_W['member']['uid'], '*');
        include template('mc/require', TEMPLATE_INCLUDEPATH);
        exit;
    }
    return $profile;
}

/**
 * 会员积分操作(记录所有的积分变动)
 * @param int $uid 会员ID
 * @param string $credittype 积分类型
 * @param mixed $creditval 积分数量，数量可以为正数或是负数
 * @param array $log 积分操作日志 索引数组,0=>操作管理员id, 1=>备注, 2=>模块标识 3=>店员id 4=>门店id 5=> 变更渠道(clerk_type 操作人类型,1: 线上操作 2: 系统后台(公众号管理员和操作员) 3: 店员)
 * @return boolean
 */
function mc_credit_update($uid, $credittype, $creditval = 0, $log = array()) {
    global $_W;
    $creditnames = uni_setting_load('creditnames');
    $creditnames = $creditnames['creditnames'];

    $credittype = trim($credittype);
    $credittypes = mc_credit_types();
    $clerk_types = array(
        '1' => '线上操作',
        '2' => '系统后台',
        '3' => '店员',
    );
    if (!in_array($credittype, $credittypes)) {
        return error('-1', "指定的用户积分类型 “{$credittype}”不存在.");
    }
    $creditval = floatval($creditval);
    if (empty($creditval)) {
        return true;
    }
    $value = pdo_getcolumn('mc_members', array('uid' => $uid), $credittype);
    if ($creditval > 0 || ($value + $creditval >= 0) || $credittype == 'credit6') {
        pdo_update('mc_members', array($credittype => $value + $creditval), array('uid' => $uid));
        cache_build_memberinfo($uid);
    } else {
        return error('-1', "积分类型为“{$credittype}”的积分不够，无法操作。");
    }
    //记录日志(如果没有$log参数或参数不合法,将视为用户自己修改积分);
    if (empty($log) || !is_array($log)) {
        load()->func('logging');
        if (!empty($GLOBALS['site']) && $GLOBALS['site'] instanceof WeModuleSite) {
            $log = array(
                $uid,
                $GLOBALS['site']->module['title'] . '模块内消费' . logging_implode($_GET),
                $GLOBALS['site']->module['name'],
                0,
            );
        } elseif (!empty($GLOBALS['_GPC']['m'])) {
            $modules = uni_modules();
            $log = array(
                $uid,
                $modules[$GLOBALS['_GPC']['m']]['title'] . '模块内消费' . logging_implode($_GET),
                $GLOBALS['_GPC']['m'],
                0,
            );
        } else {
            $log = array($uid, '未记录', 0, 0);
        }
    }
    if ($credittype == 'credit1') {
        $credittype_name = $creditnames['credit1']['title'];
    } elseif ($credittype == 'credit2') {
        $credittype_name = '元';
    }
    if (empty($log[1])) {
        if ($creditval > 0) {
            $log[1] = $clerk_types[$log[5]] . ': 添加' . $creditval . $credittype_name;
        } else {
            $log[1] = $clerk_types[$log[5]] . ': 减少' . -$creditval . $credittype_name;
        }
    }
    $clerk_type = intval($log[5]) ? intval($log[5]) : 1;
    $data = array(
        'uid' => $uid,
        'credittype' => $credittype,
        'uniacid' => $_W['uniacid'],
        'num' => $creditval,
        'createtime' => TIMESTAMP,
        'operator' => intval($log[0]),
        'module' => trim($log[2]),
        'clerk_id' => intval($log[3]),
        'store_id' => intval($log[4]),
        'clerk_type' => $clerk_type,
        'remark' => $log[1],
        'real_uniacid' => mc_current_real_uniacid()
    );
    pdo_insert('mc_credits_record', $data);
    return true;
}

/*
 * 会员积分|优惠券信息变更操作员
 * */
function mc_account_change_operator($clerk_type, $store_id, $clerk_id) {
    global $stores, $clerks, $_W;
    if (empty($stores) || empty($clerks)) {
        $clerks = pdo_getall('activity_clerks', array('uniacid' => $_W['uniacid']), array('id', 'name'), 'id');
        $stores = pdo_getall('activity_stores', array('uniacid' => $_W['uniacid']), array('id', 'business_name', 'branch_name'), 'id');
    }
    $data = array(
        'clerk_cn' => '',
        'store_cn' => '',
    );
    if ($clerk_type == 1) {
        $data['clerk_cn'] = '系统';
    } elseif ($clerk_type == 2) {
        $data['clerk_cn'] = pdo_getcolumn('users', array('uid' => $clerk_id), 'username');
    } elseif ($clerk_type == 3) {
        if (empty($clerk_id)) {
            $data['clerk_cn'] = '本人操作';
        } else {
            $data['clerk_cn'] = $clerks[$clerk_id]['name'];
        }
        $data['store_cn'] = $stores[$store_id]['business_name'] . ' ' . $stores[$store_id]['branch_name'];
    }
    if (empty($data['store_cn'])) {
        $data['store_cn'] = '暂无门店信息';
    }
    if (empty($data['clerk_cn'])) {
        $data['clerk_cn'] = '暂无操作员信息';
    }
    return $data;
}
/**
 * 获取会员积分
 * @param int $uid 会员ID
 * @param array $types 获取积分类型
 * @return array
 */
function mc_credit_fetch($uid, $types = array()) {
    if (empty($types) || $types == '*') {
        $select = array('credit1', 'credit2', 'credit3', 'credit4', 'credit5', 'credit6');
    } else {
        $struct = mc_credit_types();
        foreach ($types as $key => $type) {
            if (!in_array($type, $struct)) {
                unset($types[$key]);
            }
        }
        $select = $types;
    }
    return pdo_get('mc_members', array('uid' => $uid), $select);
}

/**
 * 获取会员所有积分类型
 * @return array
 */
function mc_credit_types() {
    static $struct = array('credit1', 'credit2', 'credit3', 'credit4', 'credit5', 'credit6');
    return $struct;
}

/**
 * 获取公众号会员组
 * @param int $uniacid 公众号ID
 * @return array
 */
function mc_groups($uniacid = 0) {
    global $_W;
    $uniacid = intval($uniacid);
    if (empty($uniacid)) {
        $uniacid = $_W['uniacid'];
    }
    return pdo_getall('mc_groups', array('uniacid' => $uniacid), array(), 'groupid', 'credit');
}

/**
 * 获取公众号粉丝标签列表
 * @return array
 */
function mc_fans_groups($force_update = false) {
    global $_W;
    $results = table('mc_fans_groups')->getByUniacid($_W['uniacid']);
    $results = empty($results['groups']) ? array() : $results['groups'];

    if (!empty($results) && !$force_update) {
        return $results;
    }
    $account_api = WeAccount::createByUniacid();
    $tags = $account_api->fansTagFetchAll();
    if (is_error($tags)) {
        $extend_buttons = array();
        if (61003 == $tags['errno']) {
            if (!empty($_W['setting']['platform']['authstate'])) {
                $account_platform = new WeixinPlatform();
                $authurl = $account_platform->getAuthLoginUrl();
            }
            $extend_buttons['cancel'] = array(
                'url' => $authurl,
                'class' => 'btn btn-default',
                'title' => '授权接入',
            );
        }
        itoast($tags['message'], '', 'error', $extend_buttons);
    }
    if (!empty($tags['tags'])) {
        $tags_tmp = array();
        foreach ($tags['tags'] as $da) {
            // '屏蔽组' 跳过
            if ($da['id'] == 1) {
                continue;
            }
            $tags_tmp[$da['id']] = $da;
        }
    }
    if (empty($results)) {
        $data = array('acid' => $_W['acid'], 'uniacid' => $_W['uniacid'], 'groups' => iserializer($tags_tmp));
        pdo_insert('mc_fans_groups', $data);
    } else {
        $data = array('groups' => iserializer($tags_tmp));
        pdo_update('mc_fans_groups', $data, array('uniacid' => $_W['uniacid']));
    }
    return $tags_tmp;
}

/**
 * 更新当前已登录会员信息
 * @param array $member 会员信息
 * @return boolean
 */
function _mc_login($member) {
    global $_W;
    if (!empty($member) && !empty($member['uid'])) {
        $member = pdo_get('mc_members', array('uid' => $member['uid'], 'uniacid' => $_W['uniacid']), array('uid', 'realname', 'mobile', 'email', 'groupid', 'credit1', 'credit2', 'credit6'));
        if (!empty($member) && (!empty($member['mobile']) || !empty($member['email']))) {
            $_W['member'] = $member;
            $_W['member']['groupname'] = $_W['uniaccount']['groups'][$member['groupid']]['title'];
            $_SESSION['uid'] = $member['uid'];
            mc_group_update();
            if (empty($_W['openid'])) {
                $fan = mc_fansinfo($member['uid']);
                if (!empty($fan)) {
                    $_SESSION['openid'] = $fan['openid'];
                    $_W['openid'] = $fan['openid'];
                    $_W['fans'] = $fan;
                    $_W['fans']['from_user'] = $_W['openid'];
                } else {
                    $_W['openid'] = $member['uid'];
                    $_W['fans'] = array(
                        'from_user' => $member['uid'],
                        'follow' => 0
                    );
                }
            }
            isetcookie('logout', '', -60000);
            return true;
        }
    }
    return false;
}

/**
 * 获取会员配置信息所有字段
 * @return array
 */
function mc_fields() {
    $fields = cache_load(cache_system_key('usersfields'));
    if (empty($fields)) {
        load()->model('cache');
        cache_build_users_struct();
        $fields = cache_load(cache_system_key('usersfields'));
    }
    return $fields;
}

/*
 * 获取公众号自定义的会员字段信息
 * $is_available 是否只获取开启的字段
 * */
function mc_acccount_fields($uniacid = 0, $is_available = true) {
    global $_W;
    $uniacid = !empty($uniacid) ? intval($uniacid) : $_W['uniacid'];
    $is_available = !empty($is_available) ? 1 : 0;

    $mc_member_fields = table('mc_member_fields');
    $mc_member_fields->searchWithUniacid($uniacid);
    $mc_member_fields->searchWithAvailable($is_available);
    $mc_member_fields->selectFields(array('a.title', 'b.field'));
    $data = $mc_member_fields->getAllFields();
    $fields = array();
    foreach ($data as $row) {
        $fields[$row['field']] = $row['title'];
    }
    return $fields;
}

/**
 * 用户积分赠送模块
 * @param int $touid 赠送积分用户uid
 * @param int $fromuid 赠送积分来源用户uid(系统模块填写-1)
 * @param array $handsel 赠送信息 array('module' => '', 'sign' => '', 'action' => '', 'credit_value' =>, 'credit_log' => );
 * @param string $uniacid 公众号ID 默认为$_W['uniacid']
 * @return mixed
 */
function mc_handsel($touid, $fromuid, $handsel, $uniacid = '') {
    global $_W;
    $touid = intval($touid);
    $fromuid = intval($fromuid);
    if (empty($uniacid)) {
        $uniacid = $_W['uniacid'];
    }
    $touid_exist = mc_fetch($touid, array('uniacid'));
    if (empty($touid_exist)) {
        return error(-1, '赠送积分用户不存在');
    }
    if (empty($handsel['module'])) {
        return error(-1, '没有填写模块名称');
    }
    if (empty($handsel['sign'])) {
        return error(-1, '没有填写赠送积分对象信息');
    }
    if (empty($handsel['action'])) {
        return error(-1, '没有填写赠送积分动作');
    }
    $credit_value = intval($handsel['credit_value']);

    $params = array('uniacid' => $uniacid, 'touid' => $touid, 'fromuid' => $fromuid, 'module' => $handsel['module'], 'sign' => $handsel['sign'], 'action' => $handsel['action']);
    $handsel_exists = pdo_get('mc_handsel', $params);
    if (!empty($handsel_exists)) {
        return error(-1, '已经赠送过积分,每个用户只能赠送一次');
    }
    $creditbehaviors = pdo_fetchcolumn('SELECT creditbehaviors FROM ' . tablename('uni_settings') . ' WHERE uniacid = :uniacid', array(':uniacid' => $uniacid));
    $creditbehaviors = iunserializer($creditbehaviors) ? iunserializer($creditbehaviors) : array();
    if (empty($creditbehaviors['activity'])) {
        return error(-1, '公众号没有配置积分行为参数');
    } else {
        $credittype = $creditbehaviors['activity'];
    }

    $data = array(
        'uniacid' => $uniacid,
        'touid' => $touid,
        'fromuid' => $fromuid,
        'module' => $handsel['module'],
        'sign' => $handsel['sign'],
        'action' => $handsel['action'],
        'credit_value' => $credit_value,
        'createtime' => TIMESTAMP
    );
    pdo_insert('mc_handsel', $data);
    $log = array($fromuid, $handsel['credit_log']);
    mc_credit_update($touid, $credittype, $credit_value, $log);
    return true;
}

/**
 * 将粉丝openid转换为会员uid
 * @param mixed $openid 粉丝Openid
 * @return mixed
 */
function mc_openid2uid($openid) {
    global $_W;
    if (is_numeric($openid)) {
        return $openid;
    }
    if (is_string($openid)) {
        $fans_info = pdo_get('mc_mapping_fans', array('uniacid' => mc_current_real_uniacid(), 'openid' => $openid), array('uid'));
        return !empty($fans_info) ? $fans_info['uid'] : false;
    }
    if (is_array($openid)) {
        $uids = array();
        foreach ($openid as $k => $v) {
            if (is_numeric($v)) {
                $uids[] = intval($v);
            } elseif (is_string($v)) {
                $fans[] = istripslashes(str_replace(' ', '', $v));
            }
        }
        if (!empty($fans)) {
            $fans = pdo_getall('mc_mapping_fans', array('uniacid' => mc_current_real_uniacid(), 'openid' => $fans), array('uid', 'openid'), 'uid');
            $fans = array_keys($fans);
            $uids = array_merge((array)$uids, $fans);
        }
        return $uids;
    }
    return false;
}

/**
    * 将会员uid转换为粉丝openid
    * @param mixed $uid 会员uid
    * @return mixed
*/
function mc_uid2openid($uid) {
    global $_W;
    if (is_numeric($uid)) {
        $fans_info = pdo_get('mc_mapping_fans', array('uniacid' => mc_current_real_uniacid(), 'uid' => $uid), 'openid');
        return !empty($fans_info['openid']) ? $fans_info['openid'] : false;
    }
    if (is_string($uid)) {
        $openid = trim($uid);
        $openid_exist = pdo_get('mc_mapping_fans', array('openid' => $openid));
        if (!empty($openid_exist)) {
            return $openid;
        } else {
            return false;
        }
    }
    if (is_array($uid)) {
        $openids = array();
        foreach ($uid as $key => $value) {
            if (is_string($value)) {
                $openids[] = $value;
            } elseif (is_numeric($value)) {
                $uids[] = $value;
            }
        }
        if (!empty($uids)) {
            $fans_info = pdo_getall('mc_mapping_fans', array('uniacid' => mc_current_real_uniacid(), 'uid' => $uids), array('uid', 'openid'), 'openid');
            $fans_info = array_keys($fans_info);
            $openids = array_merge($openids, $fans_info);
        }
        return $openids;
    }
    return false;
}
/*
 * 更新会员所在的会员组.
 * */
function mc_group_update($uid = 0) {
    global $_W;
    if (!$_W['uniaccount']['grouplevel']) {
        $_W['uniaccount']['grouplevel'] = pdo_getcolumn('uni_settings', array('uniacid' => $_W['uniacid']), 'grouplevel');
        if (empty($_W['uniaccount']['grouplevel'])) {
            return true;
        }
    }
    $uid = intval($uid);
    if ($uid <= 0) {
        $uid = $_W['member']['uid'];
        $user = $_W['member'];
        $user['openid'] = $_W['openid'];
    } else {
        $user = pdo_get('mc_members', array('uniacid' => $_W['uniacid'], 'uid' => $uid), array('uid', 'realname', 'credit1', 'credit6', 'groupid'));
        $user['openid'] = pdo_getcolumn('mc_mapping_fans', array('uniacid' => $_W['uniacid'], 'uid' => $uid), 'openid');
    }
    if (empty($user)) {
        return false;
    }
    $groupid = $user['groupid'];
    $credit = $user['credit1'] + $user['credit6'];
    $groups = mc_groups();
    if (empty($groups)) {
        return false;
    }
    $data = array();
    foreach ($groups as $group) {
        $data[$group['groupid']] = $group['credit'];
    }
    asort($data);
    if ($_W['uniaccount']['grouplevel'] == 1) {
        //根据积分多少变更
        foreach ($data as $k => $da) {
            if ($credit >= $da) {
                $groupid = $k;
            }
        }
    } else {
        //只升不降
        $now_group_credit = $data[$user['groupid']];
        if ($now_group_credit < $credit) {
            foreach ($data as $k => $da) {
                if ($credit >= $da) {
                    $groupid = $k;
                }
            }
        }
    }
    if ($groupid > 0 && $groupid != $user['groupid']) {
        pdo_update('mc_members', array('groupid' => $groupid), array('uniacid' => $_W['uniacid'], 'uid' => $uid));
        cache_build_memberinfo($uid);
        mc_notice_group($user['openid'], $_W['uniaccount']['groups'][$user['groupid']]['title'], $_W['uniaccount']['groups'][$groupid]['title']);
    }
    $user['groupid'] = $groupid;
    $_W['member']['groupid'] = $groupid;
    $_W['member']['groupname'] = $_W['uniaccount']['groups'][$groupid]['title'];
    return $user['groupid'];
}

function mc_notice_init() {
    global $_W;
    if (empty($_W['account'])) {
        $_W['account'] = uni_fetch($_W['uniacid']);
    }
    if (empty($_W['account'])) {
        return error(1, '创建公众号操作类失败');
    }
    if ($_W['account']['level'] < 3) {
        return error(1, '公众号没有经过认证，不能使用模板消息和客服消息');
    }
    $account = WeAccount::createByUniacid($_W['uniacid']);
    if (is_null($account)) {
        return error(1, '创建公众号操作对象失败');
    }
    $setting = uni_setting();
    $noticetpl = $setting['tplnotice'];
    $account->noticetpl = $noticetpl;
    return $account;
}

/*
 * 公用模板消息
 * $title 消息标题,eg:职位审核通知
 * $sender 发件人
 * $content 消息内容
 * $url 跳转链接
 * $remark 备注
 * */
function mc_notice_public($openid, $title, $sender, $content, $url = '', $remark = '') {
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }
    $data = array(
        'first' => array(
            'value' => $title,
            'color' => '#ff510'
        ),
        'keyword1' => array(
            'value' => $sender,
            'color' => '#ff510'
        ),
        'keyword2' => array(
            'value' => $content,
            'color' => '#ff510'
        ),
        'remark' => array(
            'value' => $remark,
            'color' => '#ff510'
        ),
    );
    $status = $account->sendTplNotice($openid, $account->noticetpl['public'], $data, $url);
    return $status;
}

/*
 * 会员余额充值微信模板消息和客服消息通知
 * $openid  粉丝openid
 * $uid	 会员uid 默认为当前会员
 * $num	 充值金额。单位（元）
 * $url	 跳转链接
 * $remark  操作备注
 * */
function mc_notice_recharge($openid, $uid = 0, $num = 0, $url = '', $remark = '') {
    global $_W;
    if (!$uid) {
        $uid = $_W['member']['uid'];
    }
    if (!$uid || !$num || empty($openid)) {
        return error(-1, '参数错误');
    }
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }
    $credit = mc_credit_fetch($uid);
    $time = date('Y-m-d H:i');
    if (empty($url)) {
        $url = murl('mc/bond/credits', array('credittype' => 'credit2', 'type' => 'record', 'period' => '1'), true, true);
    }
    if ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY && !empty($account->noticetpl['recharge']['tpl'])) {
        $data = array(
            'first' => array(
                'value' => "您好，您在{$time}进行会员余额充值，充值金额{$num}元，充值后余额为{$credit['credit2']}元",
                'color' => '#ff510'
            ),
            'accountType' => array(
                'value' => '会员UID',
                'color' => '#ff510'
            ),
            'account' => array(
                'value' => $uid,
                'color' => '#ff510'
            ),
            'amount' => array(
                'value' => $num . '元',
                'color' => '#ff510'
            ),
            'result' => array(
                'value' => '充值成功',
                'color' => '#ff510'
            ),
            'remark' => array(
                'value' => "{$remark}" ,
                'color' => '#ff510'
            ),
        );
        $status = $account->sendTplNotice($openid, $account->noticetpl['recharge']['tpl'], $data, $url);
    }
    if ($_W['account']['level'] == ACCOUNT_SUBSCRIPTION_VERIFY || is_error($status) || empty($account->noticetpl['recharge']['tpl'])) {
        $info = "【{$_W['account']['name']}】充值通知\n";
        $info .= "您在{$time}进行会员余额充值，充值金额【{$num}】元，充值后余额【{$credit['credit2']}】元。\n";
        $info .= !empty($remark) ? "备注：{$remark}\n\n" : '';
        $custom = array(
            'msgtype' => 'text',
            'text' => array('content' => urlencode($info)),
            'touser' => $openid,
        );
        $status = $account->sendCustomNotice($custom);
    }
    return $status;
}

/*
 * 会员余额变更通知
 * $openid		  粉丝openid
 * $uid			 会员uid 默认为当前会员
 * $credit2_num	 消费金额。单位（元）
 * $credit1_num	 获得积分
 * $store		   消费门店
 * $url			 跳转链接
 * $remark		  备注
 * */
function mc_notice_credit2($openid, $uid, $credit2_num, $credit1_num = 0, $store = '线下消费', $url = '', $remark = '谢谢惠顾，点击查看详情') {
    global $_W;
    if (!$uid) {
        $uid = $_W['member']['uid'];
    }
    if (!$uid || !$credit2_num || empty($openid)) {
        return error(-1, '参数错误');
    }
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }
    $credit = mc_credit_fetch($uid);
    $time = date('Y-m-d H:i');
    if (empty($url)) {
        $url = murl('mc/bond/credits', array('credittype' => 'credit2', 'type' => 'record', 'period' => '1'), true, true);
    }
    $credit_setting = uni_setting_load('creditnames');
    $credit1_title = empty($credit_setting['creditnames']['credit1']['title']) ? '积分' : $credit_setting['creditnames']['credit1']['title'];
    $credit2_title = empty($credit_setting['creditnames']['credit2']['title']) ? '余额' : $credit_setting['creditnames']['credit2']['title'];

    if ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY && !empty($account->noticetpl['credit2']['tpl'])) {
        $data = array(
            'first' => array(
                'value' => "您好，您在{$time}有{$credit2_title}消费",
                'color' => '#ff510'
            ),
            'keyword1' => array(
                'value' => abs($credit2_num) . '元',
                'color' => '#ff510'
            ),
            'keyword2' => array(
                'value' => floatval($credit1_num) . $credit1_title,
                'color' => '#ff510'
            ),
            'keyword3' => array(
                'value' => trim($store),
                'color' => '#ff510'
            ),
            'keyword4' => array(
                'value' => $credit['credit2'] . '元',
                'color' => '#ff510'
            ),
            'keyword5' => array(
                'value' => $credit['credit1'] . $credit1_title,
                'color' => '#ff510'
            ),
            'remark' => array(
                'value' => "{$remark}" ,
                'color' => '#ff510'
            ),
        );
        $status = $account->sendTplNotice($openid, $account->noticetpl['credit2']['tpl'], $data, $url);
    }
    if ($_W['account']['level'] == ACCOUNT_SUBSCRIPTION_VERIFY || is_error($status) || empty($account->noticetpl['credit2']['tpl'])) {
        $info = "【{$_W['account']['name']}】消费通知\n";
        $info .= "您在{$time}进行会员{$credit2_title}消费，消费金额【{$credit2_num}】元，获得{$credit1_title}【{$credit1_num}】,消费后余额【{$credit['credit2']}】元，消费后{$credit1_title}【{$credit['credit1']}】。\n";
        $info .= !empty($remark) ? "备注：{$remark}\n\n" : '';
        $custom = array(
            'msgtype' => 'text',
            'text' => array('content' => urlencode($info)),
            'touser' => $openid,
        );
        $status = $account->sendCustomNotice($custom);
    }
    return $status;
}

/*
 * 会员积分变更通知
 * $openid		  粉丝openid
 * $uid			 会员uid 默认为当前会员
 * $credit1_num	 积分数量。如果是消耗积分，使用负数即可。即：-1000
 * $tip			 积分变更原因
 * $url			 跳转链接
 * $remark		  备注
 * */
function mc_notice_credit1($openid, $uid, $credit1_num, $tip, $url = '', $remark = '谢谢惠顾，点击查看详情') {
    global $_W;
    if (!$uid) {
        $uid = $_W['member']['uid'];
    }
    if (!$uid || !$credit1_num || empty($tip)) {
        return error(-1, '参数错误');
    }
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }
    $credit = mc_credit_fetch($uid);
    $time = date('Y-m-d H:i');
    if (empty($url)) {
        $url = murl('mc/bond/credits', array('credittype' => 'credit1', 'type' => 'record', 'period' => '1'), true, true);
    }
    $credit1_num = floatval($credit1_num);
    $type = '消费';
    if ($credit1_num > 0) {
        $type = '到账';
    }
    $username = $_W['member']['realname'];
    if (empty($username)) {
        $username = $_W['member']['nickname'];
    }
    if (empty($username)) {
        $username = $uid;
    }
    $credit_setting = uni_setting_load('creditnames');
    $credit1_title = empty($credit_setting['creditnames']['credit1']['title']) ? '积分' : $credit_setting['creditnames']['credit1']['title'];

    if ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY && !empty($account->noticetpl['credit1']['tpl'])) {
        $data = array(
            'first' => array(
                'value' => "您好，您在{$time}有{$credit1_title}变更",
                'color' => '#ff510'
            ),
            'keyword1' => array(
                'value' => "原有{$credit1_title} : " . ($credit['credit1'] - $credit1_num),
                'color' => '#ff510'
            ),
            'keyword2' => array(
                'value' => "现有{$credit1_title} : " . $credit['credit1'],
                'color' => '#ff510'
            ),
            'keyword3' => array(
                'value' => "时间 : {$time} ",
                'color' => '#ff510'
            ),
            'remark' => array(
                'value' => "{$remark}" ,
                'color' => '#ff510'
            ),
        );
        $status = $account->sendTplNotice($openid, $account->noticetpl['credit1']['tpl'], $data, $url);
    }
    if ($_W['account']['level'] == ACCOUNT_SUBSCRIPTION_VERIFY || empty($account->noticetpl['credit1']['tpl']) || is_error($status)) {
        $info = "【{$_W['account']['name']}】{$credit1_title}变更通知\n";
        $info .= "您在{$time}有{$credit1_title}{$type}，{$type}{$credit1_title}【{$credit1_num}】，变更原因：【{$tip}】,消费后账户{$credit1_title}余额【{$credit['credit1']}】。\n";
        $info .= !empty($remark) ? "备注：{$remark}\n\n" : '';
        $custom = array(
            'msgtype' => 'text',
            'text' => array('content' => urlencode($info)),
            'touser' => $openid,
        );
        $status = $account->sendCustomNotice($custom);
    }
    return $status;
}

function mc_notice_group($openid, $old_group, $now_group, $url = '', $remark = '点击查看详情') {
    global $_W;
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }
    $time = date('Y-m-d H:i');
    if (empty($url)) {
        $url = murl('mc/home', array(), true, true);
    }
    if ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY && !empty($account->noticetpl['group']['tpl'])) {
        $data = array(
            'first' => array(
                'value' => "您好，您的会员组变更为{$now_group}",
                'color' => '#ff510'
            ),
            'grade1' => array(
                'value' => $old_group,
                'color' => '#ff510'
            ),
            'grade2' => array(
                'value' => $now_group,
                'color' => '#ff510'
            ),
            'time' => array(
                'value' => $time,
                'color' => '#ff510'
            ),
            'remark' => array(
                'value' => "{$remark}",
                'color' => '#ff510'
            ),
        );
        $status = $account->sendTplNotice($openid, $account->noticetpl['group']['tpl'], $data, $url);
    }
    if ($_W['account']['level'] == ACCOUNT_SUBSCRIPTION_VERIFY || is_error($status) || empty($account->noticetpl['group']['tpl'])) {
        $info = "【{$_W['account']['name']}】会员组变更通知\n";
        $info .= "您的会员等级在{$time}由{$old_group}变更为{$now_group}。\n";
        $info .= !empty($remark) ? "备注：{$remark}\n\n" : '';
        $custom = array(
            'msgtype' => 'text',
            'text' => array('content' => urlencode($info)),
            'touser' => $openid,
        );
        $status = $account->sendCustomNotice($custom);
    }
    return $status;
}

/*
 * 会员卡计次充值
 * $openid		  粉丝openid
 * $uid			 会员uid
 * $type			冲次项目（eg:理发计次）
 * $num			 本次充值次数
 * $total_num	   总剩余次数
 * $endtime		 服务截至日期
 * $remark		  备注
 * */
function mc_notice_nums_plus($openid, $type, $num, $total_num, $remark = '感谢您的支持，祝您生活愉快！') {
    global $_W;
    if (empty($num) || empty($total_num) || empty($type)) {
        return error(-1, '参数错误');
    }
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }
    $time = date('Y-m-d H:i');
    if ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY && !empty($account->noticetpl['nums_plus']['tpl'])) {
        $data = array(
            'first' => array(
                'value' => "您好，您的{$type}已充次成功",
                'color' => '#ff510'
            ),
            'keyword1' => array(
                'value' => $time,
                'color' => '#ff510'
            ),
            'keyword2' => array(
                'value' => $num . '次',
                'color' => '#ff510'
            ),
            'keyword3' => array(
                'value' => $total_num . '次',
                'color' => '#ff510'
            ),
            'keyword4' => array(
                'value' => '用完为止',
                'color' => '#ff510'
            ),
            'remark' => array(
                'value' => "{$remark}" ,
                'color' => '#ff510'
            ),
        );
        $status = $account->sendTplNotice($openid, $account->noticetpl['nums_plus']['tpl'], $data);
    }
    if ($_W['account']['level'] == ACCOUNT_SUBSCRIPTION_VERIFY || is_error($status) || empty($account->noticetpl['nums_plus']['tpl'])) {
        $info = "【{$_W['account']['name']}】-【{$type}】充值通知\n";
        $info .= "您的{$type}已充值成功，本次充次【{$num}】次，总剩余【{$total_num}】次。\n";
        $info .= !empty($remark) ? "备注：{$remark}\n\n" : '';
        $custom = array(
            'msgtype' => 'text',
            'text' => array('content' => urlencode($info)),
            'touser' => $openid,
        );
        $status = $account->sendCustomNotice($custom);
    }
    return $status;
}

/*
 * 会员卡计次消费
 * $openid		  粉丝openid
 * $uid			 会员uid
 * $card_id		 卡号
 * $type			消费项目（eg:理发计次）
 * $num			 剩余次数
 * $endtime		 服务截至日期
 * $remark		  备注
 * */
function mc_notice_nums_times($openid, $card_id, $type, $num, $remark = '感谢您对本店的支持，欢迎下次再来！') {
    global $_W;
    if (empty($num) || empty($type) || empty($card_id)) {
        return error(-1, '参数错误');
    }
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }
    $time = date('Y-m-d H:i');
    if ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY && !empty($account->noticetpl['nums_times']['tpl'])) {
        $data = array(
            'first' => array(
                'value' => "您好，您的{$type}已成功使用了【1】次。",
                'color' => '#ff510'
            ),
            'keyword1' => array(
                'value' => $card_id,
                'color' => '#ff510'
            ),
            'keyword2' => array(
                'value' => $time,
                'color' => '#ff510'
            ),
            'keyword3' => array(
                'value' => $num . '次',
                'color' => '#ff510'
            ),
            'keyword4' => array(
                'value' => '用完为止',
                'color' => '#ff510'
            ),
            'remark' => array(
                'value' => "{$remark}" ,
                'color' => '#ff510'
            ),
        );
        $status = $account->sendTplNotice($openid, $account->noticetpl['nums_times']['tpl'], $data);
    }
    if ($_W['account']['level'] == ACCOUNT_SUBSCRIPTION_VERIFY || is_error($status) || empty($account->noticetpl['nums_times']['tpl'])) {
        $info = "【{$_W['account']['name']}】-【{$type}】消费通知\n";
        $info .= "您的{$type}已成功使用了一次，总剩余【{$num}】次，消费时间【{$time}】。\n";
        $info .= !empty($remark) ? "备注：{$remark}\n\n" : '';
        $custom = array(
            'msgtype' => 'text',
            'text' => array('content' => urlencode($info)),
            'touser' => $openid,
        );
        $status = $account->sendCustomNotice($custom);
    }
    return $status;
}

/*
 * 会员卡计时充值
 * $openid		  粉丝openid
 * $uid			 会员uid
 * $card_id		 卡号
 * $type			消费项目（eg:理发计次）
 * $num			 剩余次数
 * $endtime		 服务截至日期
 * $remark		  备注
 * */
function mc_notice_times_plus($openid, $card_id, $type, $fee, $days, $endtime = '', $remark = '感谢您对本店的支持，欢迎下次再来！') {
    global $_W;
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }
    if ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY && empty($account->noticetpl['times_plus']['tpl'])) {
        $data = array(
            'first' => array(
                'value' => "您好，您的{$type}已续费成功。",
                'color' => '#ff510'
            ),
            'keynote1' => array(
                'value' => $type,
                'color' => '#ff510'
            ),
            'keynote2' => array(
                'value' => $card_id,
                'color' => '#ff510'
            ),
            'keynote3' => array(
                'value' => $fee . '元',
                'color' => '#ff510'
            ),
            'keynote4' => array(
                'value' => $days . '天',
                'color' => '#ff510'
            ),
            'keynote5' => array(
                'value' => $endtime,
                'color' => '#ff510'
            ),
            'remark' => array(
                'value' => "{$remark}" ,
                'color' => '#ff510'
            ),
        );
        $status = $account->sendTplNotice($openid, $account->noticetpl['times_plus']['tpl'], $data);
    }
    if ($_W['account']['level'] == ACCOUNT_SUBSCRIPTION_VERIFY || is_error($status) || empty($account->noticetpl['times_plus']['tpl'])) {
        $info = "【{$_W['account']['name']}】-【{$type}】续费通知\n";
        $info .= "您的{$type}已成功续费，续费时长【{$days}】天，续费金额【{$fee}】元，有效期至【{$endtime}】。\n";
        $info .= !empty($remark) ? "备注：{$remark}\n\n" : '';
        $custom = array(
            'msgtype' => 'text',
            'text' => array('content' => urlencode($info)),
            'touser' => $openid,
        );
        $status = $account->sendCustomNotice($custom);
    }
    return $status;
}

/*
 * 会员卡计时消费通知
 * $openid		粉丝openid
 * $title		服务说明（eg:您好，您有商品即将到期，请您注意。）
 * $type		服务项目（eg:游泳时长）
 * $endtime		服务有效期
 * $remark		备注
 * $card_sn		卡号
 * $use_time	消费时间
 * $has_time	剩余时长
 * */
function mc_notice_times_times($openid, $title, $type, $endtime = '', $remark = '请注意时间，防止服务失效！', $card_sn = '', $use_time = '', $has_time = '') {
    global $_W;
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }

    if ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY && !empty($account->noticetpl['times_times']['tpl'])) {
        $data = array(
            'first' => array('value' => $title, 'color' => '#ff510'), //first
            'keyword1' => array('value' => $card_sn, 'color' => '#ff510'), //卡号
            'keyword2' => array('value' => $use_time, 'color' => '#ff510'), //消费时间
            'keyword3' => array('value' => $has_time, 'color' => '#ff510'), //剩余时长
            'keyword4' => array('value' => $endtime, 'color' => '#ff510'), //有效期
            'remark' => array('value' => "{$remark}" , 'color' => '#ff510'), //remark
        );
        $status = $account->sendTplNotice($openid, $account->noticetpl['times_times']['tpl'], $data);
    }
    if ($_W['account']['level'] == ACCOUNT_SUBSCRIPTION_VERIFY || is_error($status) || empty($account->noticetpl['times_times']['tpl'])) {
        $info = "【{$_W['account']['name']}】-【{$type}】服务到期通知\n";
        $info .= "您的{$type}即将到期，有效期至【{$endtime}】。\n";
        $info .= !empty($remark) ? "备注：{$remark}\n\n" : '';
        $custom = array(
            'msgtype' => 'text',
            'text' => array('content' => urlencode($info)),
            'touser' => $openid,
        );
        $status = $account->sendCustomNotice($custom);
    }
    return $status;
}

/**
 * 订单支付成功通知
 * @param $openid  接收用户的openid
 * @param $username  用户名
 * @param $order_sn	订单号
 * @param $money 订单金额
 * @param $goods_info  商品信息
 * @param string $title  通知摘要
 * @param string $remark  通知备注
 * @param string $url  消息跳转链接
 * @return array|bool
 */
function mc_notice_pay_success($openid, $username, $order_sn, $money, $goods_info, $title = '尊敬的客户，您的订单已支付成功', $remark = '', $url = '') {
    global $_W;
    $money = sprintf("%.2f", $money);
    if (empty($money) || empty($openid)) {
        return error(-1, '参数错误');
    }
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }
    if ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY && !empty($account->noticetpl['pay_success']['tpl'])) {
        $data = array(
            'first' => array(
                'value' => $title,
                'color' => '#ff510'
            ),
            'keyword1' => array(
                'value' => $username,
                'color' => '#ff510'
            ),
            'keyword2' => array(
                'value' => $order_sn,
                'color' => '#ff510'
            ),
            'keyword3' => array(
                'value' => $money . '元',
                'color' => '#ff510'
            ),
            'keyword4' => array(
                'value' => $goods_info,
                'color' => '#ff510'
            ),
            'remark' => array(
                'value' => $remark ,
                'color' => '#ff510'
            ),
        );
        $status = $account->sendTplNotice($openid, $account->noticetpl['pay_success']['tpl'], $data, $url);
    }
    if ($_W['account']['level'] == ACCOUNT_SUBSCRIPTION_VERIFY || is_error($status) || empty($account->noticetpl['pay_success']['tpl'])) {
        $info = "【{$_W['account']['name']}】付款成功通知\n";
        $info .= "您编号为{$order_sn}的订单已成功支付{$money}。\n";
        $info .= "商品信息:{$goods_info}。\n";
        $info .= !empty($remark) ? "备注：{$remark}\n\n" : '';
        $custom = array(
            'msgtype' => 'text',
            'text' => array('content' => urlencode($info)),
            'touser' => $openid,
        );
        $status = $account->sendCustomNotice($custom);
    }
    return $status;
}

/*
 * 会员消费通知
 * */
function mc_notice_consume($openid, $title, $content, $url = '') {
    global $_W;
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }
    if ($_W['account']['level'] == 4) {
        mc_notice_credit2($openid, $content['uid'], $content['credit2_num'], $content['credit1_num'], $content['store'], '', $content['remark']);
    }
    if ($_W['account']['level'] == 3) {
        mc_notice_custom_text($openid, $title, $content);
    }
    return true;
}
/*
 * 发送文本类型的客服消息
 * */
function mc_notice_custom_text($openid, $title, $info) {
    global $_W;
    $account = mc_notice_init();
    if (is_error($account)) {
        return error(-1, $account['message']);
    }
    $custom = array(
        'msgtype' => 'text',
        'text' => array('content' => urlencode($title . '\n' . $info)),
        'touser' => $openid,
    );
    $status = $account->sendCustomNotice($custom);
    return $status;
}

/**
 * 初始化粉丝信息，新建或是更新
 * @param string $openid 初始化粉丝openid
 * @param boolean $force_init_member 是否强制初始化会员
 * @return boolean
 */
function mc_init_fans_info($openid, $force_init_member = false) {
    global $_W;
    static $account_api;
    if (empty($account_api)) {
        $account_api = WeAccount::createByUniacid();
    }
    if (is_array($openid)) {
        $fans_list = $account_api->fansBatchQueryInfo($openid);
    } else {
        $fans_list = $account_api->fansQueryInfo($openid);
    }
    if (empty($fans_list) || is_error($fans_list)) {
        //未认证号没有获取用户信息权限，此处直接插入一个Openid空记录
        if ($fans_list['errno'] == '48001') {
            $fans_list = array(
                'openid' => $openid,
                'subscribe_time' => TIMESTAMP,
                'subscribe' => 1,
            );
        } else {
            return true;
        }
    }
    if (!is_array($openid)) {
        $fans_list = array($fans_list);
    }
    foreach ($fans_list as $fans) {
        $fans_mapping = mc_fansinfo($fans['openid']);
        if (empty($fans['subscribe']) && empty($fans_mapping)) {
            pdo_update('mc_mapping_fans', array('follow' => 0, 'unfollowtime' => TIMESTAMP), array('openid' => $fans['openid']));
            continue;
        }
        if (!empty($fans_mapping) && $fans['openid'] == $fans_mapping['openid']) {
            $fans = array_merge($fans_mapping['tag'], $fans);
        }
        unset($fans['remark'], $fans['subscribe_scene'], $fans['qr_scene'], $fans['qr_scene_str']);
        $fans_update_info = array(
            'openid' => $fans['openid'],
            'acid' => $_W['acid'],
            'uniacid' => $_W['uniacid'],
            'updatetime' => TIMESTAMP,
            'followtime' => $fans['subscribe_time'],
            'follow' => $fans['subscribe'],
            'nickname' => $fans['openid'],
            'tag' => '',
            'unionid' => $fans['unionid'],
            'groupid' => !empty($fans['tagid_list']) ? (',' . join(',', $fans['tagid_list']) . ',') : '',
            'user_from' => $_W['account']->typeSign == 'wxapp' ? 1 : 0,
        );
        if (empty($fans_update_info['groupid'])) {
            unset($fans_update_info['groupid']);
        }
        if ($force_init_member) {
            $member_update_info = array(
                'uniacid' => $_W['uniacid'],
                'nickname' => $fans_update_info['nickname'],
                'avatar' => '',
                'gender' => $fans['sex'],
                'nationality' => $fans['country'],
                'resideprovince' => $fans['province'] . '省',
                'residecity' => $fans['city'] . '市',
            );

            if (empty($fans_mapping['uid'])) {
                $email = md5($fans['openid']) . '@we7.cc';
                $email_exists_member = pdo_getcolumn('mc_members', array('email' => $email, 'uniacid' => $_W['uniacid']), 'uid');
                if (!empty($email_exists_member)) {
                    $uid = $email_exists_member;
                } else {
                    $member_update_info['groupid'] = pdo_getcolumn('mc_groups', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), 'groupid');
                    $member_update_info['salt'] = random(8);
                    $member_update_info['password'] = md5($fans['openid'] . $member_update_info['salt'] . $_W['config']['setting']['authkey']);
                    $member_update_info['email'] = $email;
                    $member_update_info['createtime'] = TIMESTAMP;

                    pdo_insert('mc_members', $member_update_info);
                    $uid = pdo_insertid();
                }
                $fans_update_info['uid'] = $uid;
            } else {
                $fans_update_info['uid'] = $fans_mapping['uid'];
                pdo_update('mc_members', $member_update_info, array('uid' => $fans_mapping['uid']));
                cache_delete(cache_system_key('memberinfo', array('uid' => $fans_mapping['uid'])));
            }
        }

        if (!empty($fans_mapping)) {
            pdo_update('mc_mapping_fans', $fans_update_info, array('fanid' => $fans_mapping['fanid']));
            pdo_update('mc_fans_tag', array('fanid' => $fans_mapping['fanid']), array('openid' => $fans_mapping['openid']));
        } else {
            $fans_update_info['salt'] = random(8);
            $fans_update_info['unfollowtime'] = 0;
            $fans_update_info['followtime'] = TIMESTAMP;
            pdo_insert('mc_mapping_fans', $fans_update_info);
            $fans_mapping['fanid'] = pdo_insertid();
        }
        if (!empty($fans['tagid_list'])) {
            $groupid = $fans['tagid_list'];
            @sort($groupid, SORT_NATURAL);
            mc_insert_fanstag_mapping($fans_mapping['fanid'], $groupid);
        }

        $mc_fans_tag_table = table('mc_fans_tag');
        $mc_fans_tag_fields = mc_fans_tag_fields();
        $fans_tag_update_info = array();
        foreach ($fans as $fans_field_key => $fans_field_info) {
            if (in_array($fans_field_key, array_keys($mc_fans_tag_fields))) {
                $fans_tag_update_info[$fans_field_key] = $fans_field_info;
            }
            $fans_tag_update_info['tagid_list'] = !empty($fans_tag_update_info['tagid_list']) ? iserializer($fans_tag_update_info['tagid_list']) : '';
        }

        $fans_tag_exists = $mc_fans_tag_table->getByOpenid($fans_tag_update_info['openid']);
        if (!empty($fans_tag_exists)) {
            pdo_update('mc_fans_tag', $fans_tag_update_info, array('openid' => $fans_tag_update_info['openid']));
        } else {
            pdo_insert('mc_fans_tag', $fans_tag_update_info);
        }
    }
    if (is_string($openid) && !empty($fans_update_info)) {
        return $fans_update_info;
    } else {
        return true;
    }
}

/**
 * 更新粉丝标签，删除多余的，添加新增的
 * @param int $fanid 粉丝ID
 * @param array $groupid_list 粉丝标签
 * @return boolean
 */
function mc_insert_fanstag_mapping($fanid, $groupid_list) {
    if (empty($groupid_list)) {
        return true;
    }

    foreach ($groupid_list as $groupid) {
        $record_mapping = array(
            'fanid' => $fanid,
            'tagid' => $groupid
        );
        $isfound = pdo_getcolumn('mc_fans_tag_mapping', $record_mapping, 'id');
        if (empty($isfound)) {
            pdo_insert('mc_fans_tag_mapping', $record_mapping);
        }
    }
    pdo_delete('mc_fans_tag_mapping', array('fanid' => $fanid, 'tagid !=' => $groupid_list));
    return true;
}
/**
 * 会员卡个人资料设置是否显示
 * @param 	personal_info, contact_method, education_info, jobedit
 * @return 	boolean
 */
function mc_card_settings_hide($item = '') {
    $mcFields = mc_acccount_fields();
    if ($item == 'personal_info') {
        if (empty($mcFields['idcard']) && empty($mcFields['height']) && empty($mcFields['weight']) && empty($mcFields['bloodtype']) && empty($mcFields['zodiac']) && empty($mcFields['constellation']) && empty($mcFields['site']) && empty($mcFields['affectivestatus']) && empty($mcFields['lookingfor']) && empty($mcFields['bio']) && empty($mcFields['interest'])) {
            return true;
        }
    } elseif ($item == 'contact_method') {
        if (empty($mcFields['telephone']) && empty($mcFields['qq']) && empty($mcFields['msn']) && empty($mcFields['taobao']) && empty($mcFields['alipay'])) {
            return true;
        }
    } elseif ($item == 'education_info') {
        if (empty($mcFields['education']) && empty($mcFields['graduateschool']) && empty($mcFields['studentid'])) {
            return true;
        }
    } elseif ($item == 'jobedit') {
        if (empty($mcFields['company']) && empty($mcFields['occupation']) && empty($mcFields['position']) && empty($mcFields['revenue'])) {
            return true;
        }
    } elseif (empty($item)) {
        if (empty($mcFields['idcard']) && empty($mcFields['height']) && empty($mcFields['weight'])
        && empty($mcFields['bloodtype']) && empty($mcFields['zodiac']) && empty($mcFields['constellation'])
        && empty($mcFields['site']) && empty($mcFields['affectivestatus']) && empty($mcFields['lookingfor'])
        && empty($mcFields['bio']) && empty($mcFields['interest']) && empty($mcFields['telephone'])
        && empty($mcFields['qq']) && empty($mcFields['msn']) && empty($mcFields['taobao'])
        && empty($mcFields['alipay']) && empty($mcFields['education']) && empty($mcFields['graduateschool'])
        && empty($mcFields['studentid']) && empty($mcFields['company']) && empty($mcFields['occupation'])
        && empty($mcFields['position']) && empty($mcFields['revenue']) && empty($mcFields['avatar'])
        && empty($mcFields['nickname']) && empty($mcFields['realname']) && empty($mcFields['gender'])
        && empty($mcFields['birthyear']) && empty($mcFields['resideprovince'])) {
            return true;
        }
    }
    return false;
}

/**
 * 用户消费返积分
 * @param 	string 		$openid 		粉丝openid字段
 * @param 	string 		$card_fee 		core_paylog表card_fee 使用卡券后的价格
 * @param 	string 		$storeid 		消费门店id
 * @param 	string 		$modulename 		模块标识
 */
function mc_card_grant_credit($openid, $card_fee, $storeid = 0, $modulename = '') {
    global $_W;
    $setting = uni_setting($_W['uniacid'], array('creditbehaviors'));
    load()->model('card');
    $recharges_set = card_params_setting('cardRecharge');
    $card_settings = card_setting();
    $grant_rate = $card_settings['grant_rate'];
    $grant_rate_switch = intval($recharges_set['params']['grant_rate_switch']);
    $grant_credit1_enable = false;
    if (!empty($grant_rate)) {
        if (empty($recharges_set['params']['recharge_type'])) {
            $grant_credit1_enable = true;
        } else {
            if ($grant_rate_switch == '1') {
                $grant_credit1_enable = true;
            }
        }
    }
    if (!empty($grant_credit1_enable)) {
        $num = $card_fee * $grant_rate;
        $tip = "用户消费{$card_fee}元，支付金额{$card_fee}，积分赠送比率为:【1：{$grant_rate}】,共赠送【{$num}】积分";
        mc_credit_update($openid, 'credit1', $num, array('0', $tip, $modulename, 0, $storeid, 3));
        return error(0, $num);
    } else {
        return error(-1, '');
    }
}

/**
 * 由于小程序可能会绑定某个公众号，此函数获取真正的当前的Uniacid
 */
function mc_current_real_uniacid() {
    global $_W;
    return $_W['uniacid'];
}

function mc_fans_tag_fields() {
    return array(
        'subscribe' => '用户是否订阅该公众号标识，值为0时，代表此用户没有关注该公众号，拉取不到其余信息',
        'openid' => '用户的标识，对当前公众号唯一',
        'nickname' => '用户的昵称',
        'sex' => '用户的性别，值为1时是男性，值为2时是女性，值为0时是未知',
        'city' => '用户所在城市',
        'country' => '用户所在国家',
        'province' => '用户所在省份',
        'language' => '用户的语言，简体中文为zh_CN',
        'headimgurl' => '用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效',
        'subscribe_time' => '用户关注时间，为时间戳。如果用户曾多次关注，则取最后关注时间',
        'unionid' => '只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段',
        'remark' => '公众号运营者对粉丝的备注，公众号运营者可在微信公众平台用户管理界面对粉丝添加备注',
        'groupid' => '用户所在的分组ID（暂时兼容用户分组旧接口）',
        'tagid_list' => '用户被打上的标签ID列表',
        'subscribe_scene' => '返回用户关注的渠道来源，ADD_SCENE_SEARCH 公众号搜索，ADD_SCENE_ACCOUNT_MIGRATION 公众号迁移，ADD_SCENE_PROFILE_CARD 名片分享，ADD_SCENE_QR_CODE 扫描二维码，ADD_SCENEPROFILE LINK 图文页内名称点击，ADD_SCENE_PROFILE_ITEM 图文页右上角菜单，ADD_SCENE_PAID 支付后关注，ADD_SCENE_OTHERS 其他',
        'qr_scene' => '二维码扫码场景（开发者自定义）',
        'qr_scene_str' => '二维码扫码场景描述（开发者自定义）',
    );
}

/**
 * 得到符合导出格式的会员信息字符串
 * @param unknown $members 会员数组
 * @param unknown $header  导出头
 * @param unknown $type 类型
 * @return string 处理后的会员信息字符串
 */
function mc_member_export_parse($members, $header = array(), $type = 'userinfo') {
    if (empty($members)) {
        return false;
    }
    if ('userinfo' == $type) {
        $groups = mc_groups();
    }
    $keys = array_keys($header);
    $html = "\xEF\xBB\xBF";
    foreach ($header as $li) {
        $html .= $li . "\t ,";
    }
    $html .= "\n";
    $count = count($members);
    $pagesize = ceil($count / 5000);
    for ($j = 1; $j <= $pagesize; $j++) {
        $list = array_slice($members, ($j - 1) * 5000, 5000);
        if (!empty($list)) {
            $size = ceil(count($list) / 500);
            for ($i = 0; $i < $size; $i++) {
                $buffer = array_slice($list, $i * 500, 500);
                $user = array();
                foreach ($buffer as $row) {
                    if ('userinfo' == $type) {
                        if (strexists($row['email'], 'we7.cc')) {
                            $row['email'] = '';
                        }
                        $row['groupid'] = empty($row['groupid']) ? '' : $groups[$row['groupid']]['title'];
                    } elseif ('creditinfo' == $type) {
                        $row['credittype'] = $row['credittype'] == 'credit1' ? '积分' : '余额';
                    }
                    $row['createtime'] = empty($row['createtime']) ? '' : date('Y-m-d H:i:s', $row['createtime']);
                    foreach ($keys as $key) {
                        $data[] = $row[$key];
                    }
                    $user[] = implode("\t ,", $data) . "\t ,";
                    unset($data);
                }
                $html .= implode("\n", $user) . "\n";
            }
        }
    }
    return $html;
}

/**
 * 会员字段调整
 * @param $profile 字段信息数组
 * @return array(); 格式化后的数组
 */
function mc_parse_profile($profile) {
    global $_W;
    if (empty($profile)) {
        return array();
    }
    if (!empty($profile['avatar'])) {
        $profile['avatar'] = tomedia($profile['avatar']);
    } else {
        $profile['avatar'] = './resource/images/nopic.jpg';
    }
    $profile['avatarUrl'] = $profile['avatar'];
    $profile['birth'] = array(
        'year' => $profile['birthyear'],
        'month' => $profile['birthmonth'],
        'day' => $profile['birthday'],
    );
    $profile['reside'] = array(
        'province' => empty($profile['resideprovince']) ? '' : $profile['resideprovince'],
        'city' => empty($profile['city']) ? '' : $profile['city'],
        'district' => empty($profile['dist']) ? '' : $profile['dist']
    );
    //邮箱无效不显示。
    if (empty($profile['email']) || (!empty($profile['email']) && substr($profile['email'], -6) == 'we7.cc' && strlen($profile['email']) == 39)) {
        $profile['email'] = '';
    }
    return $profile;
}
