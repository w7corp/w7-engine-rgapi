<?php
/**
 * 自动回复
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
load()->model('reply');
load()->model('module');

$dos = array('display', 'post', 'delete', 'change_status', 'change_keyword_status');
$do = in_array($do, $dos) ? $do : 'display';

$m = empty($_GPC['module_name']) ? empty($_GPC['m']) ? '' : safe_gpc_string($_GPC['m']) : safe_gpc_string($_GPC['module_name']);
$m = empty($m) ? 'keyword' : $m;
$_W['current_module'] = empty($modules[$m]) ? [] : $modules[$m];
define('IN_MODULE', $m);

if (empty($m)) {
    itoast('错误访问.', '', '');
}
if ('special' == $m) {
    $mtypes = array(
        'image' => '图片消息',
        'voice' => '语音消息',
        'video' => '视频消息',
        'shortvideo' => '小视频消息',
        'location' => '位置消息',
        'trace' => '上报地理位置',
        'link' => '链接消息',
        'merchant_order' => '微小店消息',
        'ShakearoundUserShake' => '摇一摇:开始摇一摇消息',
        'ShakearoundLotteryBind' => '摇一摇:摇到了红包消息',
        'WifiConnected' => 'Wifi连接成功消息',
        'qr' => '二维码',
        'wxapp' => '微信小程序',
    );
}
if ('display' == $do) {
    if ('keyword' == $m) {
        $pindex = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
        $psize = 8;
        $cids = $parentcates = $list = array();
        $condition = "uniacid = :uniacid AND module != 'cover' AND module != 'userapi'";
        $params = array();
        $params[':uniacid'] = $_W['uniacid'];
        if (isset($_GPC['type']) && !empty($_GPC['type'])) {
            $type = safe_gpc_string($_GPC['type']);
            if ('apply' == $type) {
                $condition .= " AND module NOT IN ('basic', 'news', 'images', 'voice', 'video', 'music', 'wxcard', 'reply')";
            } else {
                if (!in_array($type, array('basic', 'news', 'images', 'voice', 'video', 'music', 'wxcard'))) {
                    itoast('非法语句！', referer(), 'error');
                }
                $condition .= " AND (FIND_IN_SET(:type, `containtype`) OR module = :type)";
                $params[':type'] = $type;
            }
        }
        $condition .= ' AND `module` = :type';
        $params[':type'] = $m;
        if (!empty($_GPC['keyword'])) {
            if ('keyword' == $_GPC['search_type']) {
                $rule_keyword_rid_list = pdo_getall('rule_keyword', array('content LIKE' => "%{$_GPC['keyword']}%"), array('rid'), 'rid', array('id DESC'));
                if (!empty($rule_keyword_rid_list)) {
                    $rids = implode(',', array_map(function ($item) {
                        return intval($item);
                    }, array_keys($rule_keyword_rid_list)));
                    $condition .= ' AND id IN (' . $rids . ')';
                }
            } else {
                $condition .= ' AND `name` LIKE :keyword';
                $params[':keyword'] = "%{$_GPC['keyword']}%";
            }
        }
        if (!empty($_GPC['keyword']) && 'keyword' == $_GPC['search_type'] && empty($rule_keyword_rid_list)) {
            $replies = array();
            $pager = '';
        } else {
            $replies = reply_search($condition, $params, $pindex, $psize, $total);
            $pager = pagination($total, $pindex, $psize);
            if (!empty($replies)) {
                foreach ($replies as &$item) {
                    $condition = '`rid`=:rid';
                    $params = array();
                    $params[':rid'] = $item['id'];
                    $item['keywords'] = reply_keywords_search($condition, $params);
                    $item['allreply'] = reply_content_search($item['id']);
                    $entries = module_entries($item['module'], array('rule'), $item['id']);
                    if (!empty($entries)) {
                        $item['options'] = $entries['rule'];
                    }
                    //若是模块，获取模块图片
                    if (!in_array($item['module'], array('basic', 'news', 'images', 'voice', 'video', 'music', 'wxcard', 'reply'))) {
                        $item['module_info'] = module_fetch($item['module']);
                    }
                }
                unset($item);
            }
        }
        $entries = module_entries($m, array('rule'));
    }
    if ('special' == $m) {
        $setting = uni_setting_load('default_message', $_W['uniacid']);
        $setting = $setting['default_message'] ? $setting['default_message'] : array();
        if (!empty($setting)) {
            foreach ($setting as $key => $item) {
                if (!empty($item['module'])) {
                    $setting[$key]['module'] = explode(',', $item['module']);
                }
            }
        }
        $module = uni_modules();
    }
    if ('default' == $m || 'welcome' == $m) {
        $setting = uni_setting($_W['uniacid'], array($m));
        if (!empty($setting[$m])) {
            $rule_keyword_id = pdo_getcolumn('rule_keyword', array('uniacid' => $_W['uniacid'], 'content' => $setting[$m]), 'rid');
            //触发的关键字，module_build_form()函数使用，因为一个规则可能对应多个关键字
            $setting_keyword = $setting[$m];
        }
    }
    if ('service' == $m) {
        $service_list = reply_getall_common_service();
    }
    if ('userapi' == $m) {
        $pindex = empty($_GPC['page']) ? 0 : intval($_GPC['page']);
        $psize = 8;

        $condition = 'uniacid = :uniacid AND `module`=:module';
        $params = array();
        $params[':uniacid'] = $_W['uniacid'];
        $params[':module'] = 'userapi';
        if (!empty($_GPC['keyword'])) {
            if ('keyword' == $_GPC['search_type']) {
                $rule_keyword_rid_list = pdo_getall('rule_keyword', array('content LIKE' => "%{$_GPC['keyword']}%"), array('rid'), 'rid', array('id DESC'));
                if (!empty($rule_keyword_rid_list)) {
                    $rids = implode(',', array_map(function ($item) {
                        return intval($item);
                    }, array_keys($rule_keyword_rid_list)));
                    $condition .= ' AND id IN (' . $rids . ')';
                }
            } else {
                $condition .= ' AND `name` LIKE :keyword';
                $params[':keyword'] = "%{$_GPC['keyword']}%";
            }
        }
        if (!empty($_GPC['keyword']) && 'keyword' == $_GPC['search_type'] && empty($rule_keyword_rid_list)) {
            $replies = array();
            $pager = '';
        } else {
            $replies = reply_search($condition, $params, $pindex, $psize, $total);
            $pager = pagination($total, $pindex, $psize);
            if (!empty($replies)) {
                foreach ($replies as &$item) {
                    $condition = '`rid`=:rid';
                    $params = array();
                    $params[':rid'] = $item['id'];
                    $item['keywords'] = reply_keywords_search($condition, $params);
                }
                unset($item);
            }
        }
    }
    template('platform/reply');
}
if ('post' == $do) {
    if ('keyword' == $m || 'userapi' == $m) {
        $module['title'] = '关键字自动回复';
        if ($_W['isajax'] && $_W['ispost']) {
            $keyword = safe_gpc_string($_GPC['keyword']);
            $sensitive_word = detect_sensitive_word($keyword);
            if (!empty($sensitive_word)) {
                iajax(-2, '含有敏感词:' . $sensitive_word);
            }
            $keyword = preg_replace('/，/', ',', $keyword);
            $keyword_arr = explode(',', $keyword);
            $result = pdo_getall('rule_keyword', array('uniacid' => $_W['uniacid'], 'content IN' => $keyword_arr), array('rid'));
            if (!empty($result)) {
                $keywords = array();
                foreach ($result as $reply) {
                    $keywords[] = intval($reply['rid']);
                }
                $rules = table('rule')->select(['id', 'name'])->where('id IN', $keywords)->getall();
                iajax(-1, $rules, '');
            }
            iajax(0, '');
        }
        $rid = empty($_GPC['rid']) ? 0 : intval($_GPC['rid']);
        if (!empty($rid)) {
            $reply = reply_single($rid);
            if (empty($reply) || $reply['uniacid'] != $_W['uniacid']) {
                itoast('抱歉，您操作的规则不在存或是已经被删除！', url('platform/reply', array('module_name' => $m)), 'error');
            }
            if (!empty($reply['keywords'])) {
                foreach ($reply['keywords'] as &$keyword) {
                    $keyword = array_elements(array('type', 'content'), $keyword);
                }
                unset($keyword);
            }
        }
        if (checksubmit('submit')) {
            $keywords = @json_decode(safe_gpc_html(htmlspecialchars_decode($_GPC['keywords'], ENT_QUOTES)), true);

            if (empty($keywords)) {
                itoast('必须填写有效的触发关键字.');
            }

            $rulename = safe_gpc_string($_GPC['rulename']);
            $rulename_sensitive_word = detect_sensitive_word($rulename);
            if (!empty($rulename_sensitive_word)) {
                itoast('规则名称含有敏感词:' . $rulename_sensitive_word);
            }
            $containtype = '';

            foreach ($_GPC['reply'] as $replykey => $replyval) {
                switch ($replykey) {
                    case 'reply_basic':
                        $replyval = safe_gpc_html($replyval);
                        $replyval_sensitive_word = detect_sensitive_word($replyval);
                        if (!empty($replyval_sensitive_word)) {
                            itoast('回复内容含有敏感词:' . $replyval_sensitive_word);
                        }
                        break;
                    case 'reply_news':
                    case 'reply_image':
                    case 'reply_music':
                    case 'reply_voice':
                    case 'reply_video':
                    case 'reply_wxapp':
                        $replyval = safe_gpc_html($replyval);
                        break;
                    default:
                        $replyval = safe_gpc_string($replyval);
                        break;
                }
                if (!empty($replyval)) {
                    $type = substr($replykey, 6);
                    $containtype .= 'image' == $type ? 'images' : $type . ',';
                }
            }
            $rule = array(
                'uniacid' => $_W['uniacid'],
                'name' => $rulename,
                'module' => 'keyword' == $m ? 'reply' : $m,
                'containtype' => $containtype,
                'status' => 'true' == safe_gpc_string($_GPC['status']) ? 1 : 0,
                'displayorder' => intval($_GPC['displayorder_rule']),
            );
            if (1 == intval($_GPC['istop'])) {
                $rule['displayorder'] = 255;
            } else {
                $rule['displayorder'] = range_limit($rule['displayorder'], 0, 254);
            }

            if ('userapi' == $m) {
                $module = WeUtility::createModule('userapi');
            } else {
                $module = WeUtility::createModule('core');
            }
            $msg = $module->fieldsFormValidate();

            $module_info = module_fetch($m);
            if (!empty($module_info) && empty($module_info['issystem'])) {
                $user_module = WeUtility::createModule($m);
                if (empty($user_module)) {
                    itoast('抱歉，模块不存在请重新选择其它模块！', '', '');
                }
                $user_module_error_msg = $user_module->fieldsFormValidate();
            }
            if ((is_string($msg) && '' != trim($msg)) || (is_string($user_module_error_msg) && '' != trim($user_module_error_msg))) {
                itoast($msg . $user_module_error_msg, '', '');
            }
            if (!empty($rid)) {
                $result = pdo_update('rule', $rule, array('id' => $rid, 'uniacid' => $_W['uniacid']));
            } else {
                $result = pdo_insert('rule', $rule);
                $rid = pdo_insertid();
            }

            if (!empty($rid)) {
                pdo_delete('rule_keyword', array('rid' => $rid, 'uniacid' => $_W['uniacid']));
                $rowtpl = array(
                    'rid' => $rid,
                    'uniacid' => $_W['uniacid'],
                    'module' => 'keyword' == $m ? 'reply' : $m,
                    'status' => $rule['status'],
                    'displayorder' => $rule['displayorder'],
                );
                foreach ($keywords as $kw) {
                    $krow = $rowtpl;
                    $krow['type'] = range_limit($kw['type'], 1, 4);
                    $krow['content'] = htmlspecialchars($kw['content']);
                    pdo_insert('rule_keyword', $krow);
                }
                $kid = pdo_insertid();
                $module->fieldsFormSubmit($rid);
                if (!empty($module_info) && empty($module_info['issystem'])) {
                    $user_module->fieldsFormSubmit($rid);
                }
                itoast('回复规则保存成功！', url('platform/reply', array('module_name' => $m)), 'success');
            } else {
                itoast('回复规则保存失败, 请联系网站管理员！', url('platform/reply', array('module_name' => $m)), 'error');
            }
        }
        template('platform/reply-post');
    }
    if ('special' == $m) {
        $type = safe_gpc_string($_GPC['type']);
        $setting = uni_setting_load('default_message', $_W['uniacid']);
        $setting = $setting['default_message'] ? $setting['default_message'] : array();
        if (checksubmit('submit')) {
            $rule_id = intval(trim(htmlspecialchars_decode($_GPC['reply']['reply_keyword']), '"'));
            $module = trim(safe_gpc_string(htmlspecialchars_decode($_GPC['reply']['reply_module'])), '"');
            if ((empty($rule_id) && empty($module)) || '0' === $_GPC['status']) {
                $setting[$type] = array('type' => '', 'module' => $module, 'keyword' => $rule_id);
                uni_setting_save('default_message', $setting);
                itoast('关闭成功', url('platform/reply', array('module_name' => 'special')), 'success');
            }
            $reply_type = empty($rule_id) ? 'module' : 'keyword';
            $reply_module = WeUtility::createModule('core');
            $result = $reply_module->fieldsFormValidate();
            if (is_error($result)) {
                itoast($result['message'], '', 'info');
            }

            if ('module' == $reply_type) {
                $setting[$type] = array('type' => 'module', 'module' => $module);
            } else {
                $rule = pdo_get('rule_keyword', array('id' => $rule_id, 'uniacid' => $_W['uniacid']));
                $setting[$type] = array('type' => 'keyword', 'keyword' => $rule['content']);
            }
            uni_setting_save('default_message', $setting);
            itoast('发布成功', url('platform/reply', array('module_name' => 'special')), 'success');
        }
        if ($setting[$type]['type'] == 'module') {
            $rule_id = $setting[$type]['module'];
        } else {
            $rule_id = pdo_getcolumn('rule_keyword', array('uniacid' => $_W['uniacid'], 'content' => $setting[$type]['keyword']), 'rid');
            //触发的关键字，module_build_form()函数使用，因为一个规则可能对应多个关键字
            $setting_keyword = $setting[$type]['keyword'];
        }
        template('platform/specialreply-post');
    }
    if ('default' == $m || 'welcome' == $m) {
        if (checksubmit('submit')) {
            $rule_keyword_id = intval(trim(htmlspecialchars_decode($_GPC['reply']['reply_keyword']), '"'));
            if (!empty($rule_keyword_id)) {
                $rule = pdo_get('rule_keyword', array('id' => $rule_keyword_id, 'uniacid' => $_W['uniacid']));
                $settings = array(
                    $m => $rule['content'],
                );
            } else {
                $settings = array($m => '');
            }
            $item = pdo_fetch('SELECT uniacid FROM ' . tablename('uni_settings') . ' WHERE uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));
            if (!empty($item)) {
                pdo_update('uni_settings', $settings, array('uniacid' => $_W['uniacid']));
            } else {
                $settings['uniacid'] = $_W['uniacid'];
                pdo_insert('uni_settings', $settings);
            }
            cache_delete(cache_system_key('unisetting', array('uniacid' => $_W['uniacid'])));
            cache_delete(cache_system_key('keyword', array('content' => md5($rule['content']), 'uniacid' => $_W['uniacid'])));
            itoast('系统回复更新成功！', url('platform/reply', array('module_name' => $m)), 'success');
        }
    }
    if ('apply' == $m) {
        $module['title'] = '应用关键字';
        $installedmodulelist = uni_modules();
        foreach ($installedmodulelist as $key => &$value) {
            if ('system' == $value['type']) {
                unset($installedmodulelist[$key]);
                continue;
            }
            $value['official'] = empty($value['issystem']) && (strexists($value['author'], 'WeEngine Team') || strexists($value['author'], '微擎团队'));
        }
        unset($value);
        foreach ($installedmodulelist as $name => $module) {
            if (empty($module['isrulefields']) && 'core' != $name) {
                continue;
            }
            $module['title_first_pinyin'] = get_first_pinyin($module['title']);
            if ($module['issystem']) {
                $path = '../framework/builtin/' . $module['name'];
            } else {
                $path = '../addons/' . $module['name'];
            }
            $cion = $path . '/icon-custom.jpg';
            if (!file_exists($cion)) {
                $cion = $path . '/icon.jpg';
                if (!file_exists($cion)) {
                    $cion = './resource/images/nopic-small.jpg';
                }
            }
            $module['icon'] = $cion;

            if (1 == $module['enabled']) {
                $enable_modules[$name] = $module;
            } else {
                $unenable_modules[$name] = $module;
            }
        }
        $current_user_permissions = pdo_getall('users_permission', array('uid' => $_W['user']['uid'], 'uniacid' => $_W['uniacid']), array(), 'type');
        if (!empty($current_user_permissions)) {
            $current_user_permission_types = array_keys($current_user_permissions);
        }
        $moudles = true;
        template('platform/reply-post');
    }
}

if ('delete' == $do) {
    $rids = $_GPC['rid'];
    if (!is_array($rids)) {
        $rids = array($rids);
    }
    if (empty($rids)) {
        itoast('非法访问.', '', '');
    }
    foreach ($rids as $rid) {
        $rid = intval($rid);
        if (empty($rid)) {
            continue;
        }
        $reply = reply_single($rid);
        if (empty($reply) || $reply['uniacid'] != $_W['uniacid']) {
            itoast('抱歉，您操作的规则不在存或是已经被删除！', url('platform/reply', array('module_name' => $m)), 'error');
        }
        //删除回复，关键字及规则
        if (pdo_delete('rule', array('id' => $rid, 'uniacid' => $_W['uniacid']))) {
            pdo_delete('rule_keyword', array('rid' => $rid, 'uniacid' => $_W['uniacid']));
            //调用模块中的删除
            $reply_module = $m;
            $module = WeUtility::createModule($reply_module);
            if(is_error($module)) {
                if (method_exists($module, 'ruleDeleted')) {
                    $module->ruleDeleted($rid);
                }
            }
        }
    }
    reply_check_uni_default_keyword();
    itoast('规则操作成功！', referer(), 'success');
}

//非文字自动回复切换开启关闭状态
if ('change_status' == $do) {
    $m = empty($_GPC['module_name']) ? '' : safe_gpc_string($_GPC['module_name']);
    if ('service' == $m) {
        $rid = empty($_GPC['rid']) ? 0 : intval($_GPC['rid']);
        $file = safe_gpc_string($_GPC['file']);
        if (0 == $rid) {
            $rid = reply_insert_without_service($file);
            if (empty($rid)) {
                iajax(1, '参数错误');
            }
        }
        $userapi_module = module_fetch('userapi');
        $config = !empty($userapi_module['config']) && is_array($userapi_module['config']) ? $userapi_module['config'] : array();
        $config[$rid] = isset($config[$rid]) && $config[$rid] ? false : true;
        $module_api = WeUtility::createModule('userapi');
        $module_api->saveSettings($config);
        iajax(0, '');
    } else {
        $type = safe_gpc_string($_GPC['type']);
        $setting = uni_setting_load('default_message', $_W['uniacid']);
        $setting = $setting['default_message'] ? $setting['default_message'] : array();
        if (empty($setting[$type]['type'])) {
            if (!empty($setting[$type]['keyword'])) {
                $setting[$type]['type'] = 'keyword';
            }
            if (!empty($setting[$type]['module'])) {
                $setting[$type]['type'] = 'module';
            }
            if (empty($setting[$type]['type'])) {
                iajax(1, '请先设置回复内容', '');
            }
        } else {
            $setting[$type]['type'] = '';
        }
        $result = uni_setting_save('default_message', $setting);
        if ($result) {
            iajax(0, '更新成功！');
        }
    }
}

if ('change_keyword_status' == $do) {
    /*改变状态：是否开启该关键字*/
    $id = intval($_GPC['id']);
    $result = pdo_get('rule', array('id' => $id), array('status'));
    if (!empty($result)) {
        $rule = $rule_keyword = false;
        if (1 == $result['status']) {
            $rule = pdo_update('rule', array('status' => 0), array('id' => $id, 'uniacid' => $_W['uniacid']));
            $rule_keyword = pdo_update('rule_keyword', array('status' => 0), array('uniacid' => $_W['uniacid'], 'rid' => $id));
            reply_check_uni_default_keyword();
        } else {
            $rule = pdo_update('rule', array('status' => 1), array('id' => $id, 'uniacid' => $_W['uniacid']));
            $rule_keyword = pdo_update('rule_keyword', array('status' => 1), array('uniacid' => $_W['uniacid'], 'rid' => $id));
        }
        if ($rule && $rule_keyword) {
            iajax(0, '更新成功！', '');
        } else {
            iajax(-1, '更新失败！', '');
        }
    }
    iajax(-1, '更新失败！', '');
}
