<?php

/**
 * 更新模板缓存
 * @return boolean
 */
function cache_build_template() {
    load()->func('file');
    rmdirs(IA_ROOT . '/data/tpl', true);
}

/**
 * 更新设置项缓存
 * @return mixed
 */
function cache_build_setting() {
    $setting = table('core_settings')->getall('key');
    if (is_array($setting)) {
        foreach ($setting as $k => $v) {
            $setting[$v['key']] = iunserializer($v['value']);
        }
        cache_write(cache_system_key('setting'), $setting);
    }
}

/**
 * 重建会员缓存
 * @param int uid 要重建缓存的会员uid
 */
function cache_build_memberinfo($uid) {
    $uid = intval($uid);
    cache_delete(cache_system_key('memberinfo', array('uid' => $uid)));
    return true;
}

/**
 * 更新会员个人信息字段
 * @return array
 */
function cache_build_users_struct() {
    $base_fields = array(
        'uniacid' => '同一公众号id',
        'groupid' => '分组id',
        'credit1' => '积分',
        'credit2' => '余额',
        'credit3' => '预留积分类型3',
        'credit4' => '预留积分类型4',
        'credit5' => '预留积分类型5',
        'credit6' => '预留积分类型6',
        'createtime' => '加入时间',
        'mobile' => '手机号码',
        'email' => '电子邮箱',
        'realname' => '真实姓名',
        'nickname' => '昵称',
        'avatar' => '头像',
        'qq' => 'QQ号',
        'gender' => '性别',
        'birth' => '生日',
        'constellation' => '星座',
        'zodiac' => '生肖',
        'telephone' => '固定电话',
        'idcard' => '证件号码',
        'studentid' => '学号',
        'grade' => '班级',
        'address' => '地址',
        'zipcode' => '邮编',
        'nationality' => '国籍',
        'reside' => '居住地',
        'graduateschool' => '毕业学校',
        'company' => '公司',
        'education' => '学历',
        'occupation' => '职业',
        'position' => '职位',
        'revenue' => '年收入',
        'affectivestatus' => '情感状态',
        'lookingfor' => ' 交友目的',
        'bloodtype' => '血型',
        'height' => '身高',
        'weight' => '体重',
        'alipay' => '支付宝帐号',
        'msn' => 'MSN',
        'taobao' => '阿里旺旺',
        'site' => '主页',
        'bio' => '自我介绍',
        'interest' => '兴趣爱好',
        'password' => '密码',
        'pay_password' => '支付密码',
    );
    cache_write(cache_system_key('userbasefields'), $base_fields);
    cache_write(cache_system_key('usersfields'), $base_fields);
}

function cache_build_frame_menu() {
    global $_W;
    load()->model('system');
    $system_menu = system_menu();
    if (!empty($system_menu) && is_array($system_menu)) {
        $system_displayoder = 1;
        foreach ($system_menu as $menu_name => $menu) {
            $system_menu[$menu_name]['is_system'] = 1;
            $system_menu[$menu_name]['is_display'] = 1;
            $system_menu[$menu_name]['displayorder'] = ++$system_displayoder;
        }
        $system_menu = iarray_sort($system_menu, 'displayorder', 'asc');
        cache_delete(cache_system_key('system_frame', array('uniacid' => $_W['uniacid'])));
        cache_write(cache_system_key('system_frame', array('uniacid' => $_W['uniacid'])), $system_menu);
        return $system_menu;
    }
}

function cache_build_module_subscribe_type() {
    global $_W;
    $modules = table('modules')->getByHasSubscribes();
    if (empty($modules)) {
        return array();
    }
    $subscribe = array();
    foreach ($modules as $module) {
        $module['subscribes'] = iunserializer($module['subscribes']);
        if (!empty($module['subscribes'])) {
            foreach ($module['subscribes'] as $event) {
                if ($event == 'text') {
                    continue;
                }
                $subscribe[$event][] = $module['name'];
            }
        }
    }

    $module_ban = !empty($_W['setting']['module_receive_ban']) ? $_W['setting']['module_receive_ban'] : array();
    foreach ($subscribe as $event => $module_group) {
        if (!empty($module_group)) {
            foreach ($module_group as $index => $module) {
                if (!empty($module_ban[$module])) {
                    unset($subscribe[$event][$index]);
                }
            }
        }
    }
    cache_write(cache_system_key('module_receive_enable'), $subscribe);
    return $subscribe;
}

/**
 * 更新模块信息
 */
function cache_build_module_info($module_name) {
    return cache_delete(cache_system_key('module_info', array('module_name' => $module_name)));
}

/**
 * @param int $length
 * @param boolean $direct_write
 * @return string
 */
function cache_random($length = 4, $direct_write = false) {
    $cachekey = cache_system_key('random');
    $cache = cache_load($cachekey);
    if ($cache && !$direct_write) {
        return $cache;
    }
    $result = random($length);
    cache_write($cachekey, $result, CACHE_EXPIRE_MIDDLE);
    return $result;
}

function cache_updatecache() {
    $account_ticket_cache = cache_read(cache_system_key('account_ticket'));
    //无论是哪种缓存方式，更新缓存时强制删除数据库中的值
    pdo_delete('core_cache');
    cache_clean();
    cache_write(cache_system_key('account_ticket'), $account_ticket_cache);

    cache_build_template();
    cache_build_users_struct();
    cache_build_setting();
    cache_build_module_subscribe_type();
    return true;
}
