<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/framework/model/extension.mod.php : v fc9f77cc82f2 : 2015/08/31 07:00:43 : yanghf $
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 根据XML解析结果获取模块信息
 * @param array $manifest XML文件解析结果
 * @return array
 */
function ext_module_convert($manifest) {
    if (!empty($manifest['platform']['supports'])) {
        $app_support = in_array('app', $manifest['platform']['supports']) ? MODULE_SUPPORT_ACCOUNT : MODULE_NONSUPPORT_ACCOUNT;
        $wxapp_support = in_array('wxapp', $manifest['platform']['supports']) ? MODULE_SUPPORT_WXAPP : MODULE_NONSUPPORT_WXAPP;
        $welcome_support = in_array('system_welcome', $manifest['platform']['supports']) ? MODULE_SUPPORT_SYSTEMWELCOME : MODULE_NONSUPPORT_SYSTEMWELCOME;
        $webapp_support = in_array('webapp', $manifest['platform']['supports']) ? MODULE_SUPPORT_WEBAPP : MODULE_NOSUPPORT_WEBAPP;
        $aliapp_support = in_array('aliapp', $manifest['platform']['supports']) ? MODULE_SUPPORT_ALIAPP : MODULE_NOSUPPORT_ALIAPP;
        $baiduapp_support = in_array('baiduapp', $manifest['platform']['supports']) ? MODULE_SUPPORT_BAIDUAPP : MODULE_NOSUPPORT_BAIDUAPP;
        $toutiaoapp_support = in_array('toutiaoapp', $manifest['platform']['supports']) ? MODULE_SUPPORT_TOUTIAOAPP : MODULE_NOSUPPORT_TOUTIAOAPP;
        $android_support = in_array('android', $manifest['platform']['supports']) ? MODULE_SUPPORT_ANDROID : MODULE_NOSUPPORT_ANDROID;
        $ios_support = in_array('ios', $manifest['platform']['supports']) ? MODULE_SUPPORT_IOS : MODULE_NOSUPPORT_IOS;
        $phoneapp_support = ($android_support == MODULE_SUPPORT_ANDROID || $ios_support == MODULE_SUPPORT_IOS) ? MODULE_SUPPORT_PHONEAPP : MODULE_NOSUPPORT_PHONEAPP;
        if ($app_support == MODULE_NONSUPPORT_ACCOUNT
            && $wxapp_support == MODULE_NONSUPPORT_WXAPP
            && $welcome_support == MODULE_NONSUPPORT_SYSTEMWELCOME
            && $webapp_support == MODULE_NOSUPPORT_WEBAPP
            && $aliapp_support == MODULE_NOSUPPORT_ALIAPP
            && $baiduapp_support == MODULE_NOSUPPORT_BAIDUAPP
            && $toutiaoapp_support == MODULE_NOSUPPORT_TOUTIAOAPP
            && $phoneapp_support == MODULE_NOSUPPORT_PHONEAPP
        ) {
            $app_support = MODULE_SUPPORT_ACCOUNT;
        }
    } else {
        $app_support = MODULE_SUPPORT_ACCOUNT;
        $wxapp_support = MODULE_NONSUPPORT_WXAPP;
        $welcome_support = MODULE_NONSUPPORT_SYSTEMWELCOME;
        $webapp_support = MODULE_NOSUPPORT_WEBAPP;
        $aliapp_support = MODULE_NOSUPPORT_ALIAPP;
        $baiduapp_support = MODULE_NOSUPPORT_BAIDUAPP;
        $toutiaoapp_support = MODULE_NOSUPPORT_TOUTIAOAPP;
        $phoneapp_support = MODULE_NOSUPPORT_PHONEAPP;
    }
    return array(
        'name' => $manifest['application']['identifie'],
        'title' => $manifest['application']['name'],
        'version' => $manifest['application']['version'],
        'type' => $manifest['application']['type'],
        'ability' => $manifest['application']['ability'],
        'description' => $manifest['application']['description'],
        'author' => $manifest['application']['author'],
        'url' => $manifest['application']['url'],
        'settings' => intval($manifest['application']['setting']),
        'subscribes' => iserializer(is_array($manifest['platform']['subscribes']) ? $manifest['platform']['subscribes'] : array()),
        'handles' => iserializer(is_array($manifest['platform']['handles']) ? $manifest['platform']['handles'] : array()),
        'isrulefields' => intval($manifest['platform']['isrulefields']),
        'oauth_type' => $manifest['platform']['oauth_type'],
        'page' => $manifest['bindings']['page'],
        'cover' => $manifest['bindings']['cover'],
        'rule' => $manifest['bindings']['rule'],
        'menu' => $manifest['bindings']['menu'],
        'home' => $manifest['bindings']['home'],
        'profile' => $manifest['bindings']['profile'],
        'webapp' => $manifest['bindings']['webapp'],
        'phoneapp' => $manifest['bindings']['phoneapp'],
        'account_support' => $app_support,
        'wxapp_support' => $wxapp_support,
        'webapp_support' => $webapp_support,
        'phoneapp_support' => $phoneapp_support,
        'aliapp_support' => $aliapp_support,
        'baiduapp_support' => $baiduapp_support,
        'toutiaoapp_support' => $toutiaoapp_support,
        'welcome_support' => $welcome_support,
        'function' => $manifest['bindings']['function'],
        'permissions' => !empty($manifest['permissions']) ? $manifest['permissions'] : array(),
        'cloudsetting' => !empty($manifest['cloudsetting']) ? $manifest['cloudsetting'] : '',
    );
}

/**
 * 将模块XML配置文件解析为数组
 * @param string $xml XML文件内容
 * @return array
 */
function ext_module_manifest_parse($xml) {
    if (PHP_VERSION_ID < 80000) {
        libxml_disable_entity_loader(true);
    }
    if (!strexists($xml, '<manifest')) {
        $xml = base64_decode($xml);
    }
    if (empty($xml) || preg_match('/(\<\!DOCTYPE|\<\!ENTITY)/i', $xml)) {
        return array();
    }
    $xml = preg_replace('/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f\\x7f]/', '', $xml); //过滤xml中的控制字符
    // 清除缓冲区 输出xml之前不能有输出
    ob_start();
    ob_clean();
    $dom = new DOMDocument();
    $dom->loadXML($xml);
    // 0.51xml
    $root = $dom->getElementsByTagName('manifest')->item(0);
    if (empty($root)) {
        return array();
    }
    $manifest['install'] = $root->getElementsByTagName('install')->item(0)->textContent;
    $manifest['uninstall'] = $root->getElementsByTagName('uninstall')->item(0)->textContent;
    $manifest['upgrade'] = $root->getElementsByTagName('upgrade')->item(0)->textContent;
    $application = $root->getElementsByTagName('application')->item(0);
    if (empty($application)) {
        return array();
    }
    $manifest['application'] = array(
        'name' => trim($application->getElementsByTagName('name')->item(0)->textContent),
        'identifie' => trim($application->getElementsByTagName('identifie')->item(0)->textContent),
        'version' => trim($application->getElementsByTagName('version')->item(0)->textContent),
        'type' => trim($application->getElementsByTagName('type')->item(0)->textContent),
        'ability' => trim($application->getElementsByTagName('ability')->item(0)->textContent),
        'description' => trim($application->getElementsByTagName('description')->item(0)->textContent),
        'author' => trim($application->getElementsByTagName('author')->item(0)->textContent),
        'url' => trim($application->getElementsByTagName('url')->item(0)->textContent),
        'setting' => trim($application->getAttribute('setting')) == 'true',
        'cloud_setting' => trim($application->getAttribute('cloud_setting')) == 'true',
    );
    $platform = $root->getElementsByTagName('platform')->item(0);
    if (!empty($platform)) {
        $manifest['platform'] = array(
            'subscribes' => array(),
            'handles' => array(),
            'isrulefields' => false,
            'iscard' => false,
            'supports' => array(),
            'oauth_type' => OAUTH_TYPE_BASE,
        );
        //订阅信息
        $subscribes = $platform->getElementsByTagName('subscribes')->item(0);
        if (!empty($subscribes)) {
            $messages = $subscribes->getElementsByTagName('message');
            for ($i = 0; $i < $messages->length; $i++) {
                $t = $messages->item($i)->getAttribute('type');
                if (!empty($t)) {
                    $manifest['platform']['subscribes'][] = $t;
                }
            }
        }
        //直接处理消息
        $handles = $platform->getElementsByTagName('handles')->item(0);
        if (!empty($handles)) {
            $messages = $handles->getElementsByTagName('message');
            for ($i = 0; $i < $messages->length; $i++) {
                $t = $messages->item($i)->getAttribute('type');
                if (!empty($t)) {
                    $manifest['platform']['handles'][] = $t;
                }
            }
        }
        //是否嵌入规则
        $rule = $platform->getElementsByTagName('rule')->item(0);
        if (!empty($rule) && $rule->getAttribute('embed') == 'true') {
            $manifest['platform']['isrulefields'] = true;
        }
        //是否嵌入卡券
        $card = $platform->getElementsByTagName('card')->item(0);
        if (!empty($card) && $card->getAttribute('embed') == 'true') {
            $manifest['platform']['iscard'] = true;
        }
        $oauth_type = $platform->getElementsByTagName('oauth')->item(0);
        if (!empty($oauth_type) && $oauth_type->getAttribute('type') == OAUTH_TYPE_USERINFO) {
            $manifest['platform']['oauth_type'] = OAUTH_TYPE_USERINFO;
        }
        $supports = $platform->getElementsByTagName('supports')->item(0);
        if (!empty($supports)) {
            $support_type = $supports->getElementsByTagName('item');
            for ($i = 0; $i < $support_type->length; $i++) {
                $t = $support_type->item($i)->getAttribute('type');
                if (!empty($t)) {
                    $manifest['platform']['supports'][] = $t;
                }
            }
        }
        //模块扩展插件
        $plugins = $platform->getElementsByTagName('plugins')->item(0);
        if (!empty($plugins)) {
            $plugin_list = $plugins->getElementsByTagName('item');
            for ($i = 0; $i < $plugin_list->length; $i++) {
                $plugin = $plugin_list->item($i)->getAttribute('name');
                if (!empty($plugin)) {
                    $manifest['platform']['plugin_list'][] = $plugin;
                }
            }
        }
        $plugin_main = $platform->getElementsByTagName('plugin-main')->item(0);
        if (!empty($plugin_main)) {
            $plugin_main = $plugin_main->getAttribute('name');
            if (!empty($plugin_main)) {
                $manifest['platform']['main_module'] = $plugin_main;
            }
        }
    }
    //模块注册菜单
    $bindings = $root->getElementsByTagName('bindings')->item(0);
    if (!empty($bindings)) {
        $points = ext_module_bindings();
        if (!empty($points)) {
            $ps = array_keys($points);
            $manifest['bindings'] = array();
            foreach ($ps as $p) {
                $define = $bindings->getElementsByTagName($p)->item(0);
                $manifest['bindings'][$p] = _ext_module_manifest_entries($define);
            }
        }
    }
    //权限
    $permissions = $root->getElementsByTagName('permissions')->item(0);
    if (!empty($permissions)) {
        $manifest['permissions'] = array();
        $items = $permissions->getElementsByTagName('entry');
        for ($i = 0; $i < $items->length; $i++) {
            $item = $items->item($i);
            $row = array(
                'title' => $item->getAttribute('title'),
                'permission' => $item->getAttribute('do'),
            );
            if (!empty($row['title']) && !empty($row['permission'])) {
                $manifest['permissions'][] = $row;
            }
        }
    }
    //云参数设置
    $cloud_setting = empty($root->getElementsByTagName('cloudsetting')->length) ? '' : $root->getElementsByTagName('cloudsetting')->item(0)->textContent;
    if (!empty($cloud_setting)) {
        $cloud_setting = iunserializer($cloud_setting);
        $manifest['cloudsetting'] = $cloud_setting['data'];
    }
    return $manifest;
}

/**
 * 格式化 manifest 数据
 * @param array $manifest manifest文件内容
 * @return array
 */
function ext_module_manifest_format($manifest) {
    if (empty($manifest) || !is_array($manifest)) {
        return array();
    }
    $manifest['application']['setting'] = empty($manifest['application']['setting']) || 'false' == $manifest['application']['setting'] ? STATUS_OFF : STATUS_ON;
    $manifest['application']['cloud_setting'] = empty($manifest['application']['cloud_setting']) || 'false' == $manifest['application']['cloud_setting'] ? STATUS_OFF : STATUS_ON;
    $manifest['platform'] = array(
        'subscribes' => $manifest['platform']['subscribes'],
        'handles' => $manifest['platform']['handles'],
        'isrulefields' => !('false' == $manifest['platform']['rule']),
        'iscard' => !('false' == $manifest['platform']['card']),
        'supports' => $manifest['platform']['supports'],
        'oauth_type' => OAUTH_TYPE_USERINFO == $manifest['platform']['oauth'] ? OAUTH_TYPE_USERINFO : OAUTH_TYPE_BASE,
        'plugin_list' => $manifest['platform']['plugins'],
        'main_module' => $manifest['platform']['plugin-main'],
    );

    $bindings = $manifest['bindings'];
    if (!empty($bindings)) {
        $points = ext_module_bindings();
        if (!empty($points)) {
            $ps = array_keys($points);
            foreach ($ps as $p) {
                if (empty($bindings[$p])) {
                    continue;
                }
                foreach ($bindings[$p] as $k => $entry) {
                    $manifest['bindings'][$p][$k]['multilevel'] = 'false' == $entry['multilevel'] || empty($entry['multilevel']) ? STATUS_OFF : STATUS_ON;
                    $manifest['bindings'][$p][$k]['direct'] = 'false' == $entry['direct'] || empty($entry['direct']) ? STATUS_OFF : STATUS_ON;
                }
            }
        }
    }

    return $manifest;
}

/**
 * 根据模块名称读取其配置信息
 * @param string $modulename 模块名称
 * @return array
 */
function ext_module_manifest($modulename) {
    $root = IA_ROOT . '/addons/' . $modulename;
    $filename = $root . '/manifest.xml';
    if (!file_exists($filename)) {
        return array();
    }
    $xml = file_get_contents($filename);
    $xml = ext_module_manifest_parse($xml);

    if (!empty($xml)) {
        $xml['application']['logo'] = tomedia($root . '/icon.jpg');
        if (file_exists($root . '/preview-custom.jpg')) {
            $xml['application']['preview'] = tomedia($root . '/preview-custom.jpg');
        } else {
            $xml['application']['preview'] = tomedia($root . '/preview.jpg');
        }
        if (empty($xml['platform']['supports'])) {
            $xml['platform']['supports'][] = 'app';
        }
    }
    return $xml;
}

/**
 * 获取模块菜单信息
 * @param $elm XML节点对象
 * @return array
 */
function _ext_module_manifest_entries($elm) {
    $ret = array();
    if (!empty($elm)) {
        $call = $elm->getAttribute('call');
        if (!empty($call)) {
            $ret[] = array('call' => $call);
        }
        $entries = $elm->getElementsByTagName('entry');
        for ($i = 0; $i < $entries->length; $i++) {
            $entry = $entries->item($i);
            $direct = $entry->getAttribute('direct');
            $is_multilevel_menu = $entry->getAttribute('multilevel');
            $row = array(
                'title' => $entry->getAttribute('title'),
                'do' => $entry->getAttribute('do'),
                'direct' => !empty($direct) && $direct != 'false' ? STATUS_ON : STATUS_OFF,
                'state' => $entry->getAttribute('state'),
                'icon' => $entry->getAttribute('icon'),
                'displayorder' => $entry->getAttribute('displayorder'),
                'multilevel' => !empty($is_multilevel_menu) && $is_multilevel_menu == 'true' ? STATUS_ON : STATUS_OFF,
                'parent' => $entry->getAttribute('parent'),
            );
            if (!empty($row['title']) && !empty($row['do'])) {
                $ret[$row['do']] = $row;
            }
        }
    }
    return $ret;
}

/**
 * 获取模块入口类型
 * @return array
 */
function ext_module_bindings() {
    static $bindings = array(
        'cover' => array(
            'name' => 'cover',
            'title' => '功能封面',
            'desc' => '功能封面是定义微站里一个独立功能的入口(手机端操作), 将呈现为一个图文消息, 点击后进入微站系统中对应的功能.'
        ),
        'rule' => array(
            'name' => 'rule',
            'title' => '规则列表',
            'desc' => '规则列表是定义可重复使用或者可创建多次的活动的功能入口(管理后台Web操作), 每个活动对应一条规则. 一般呈现为图文消息, 点击后进入定义好的某次活动中.'
        ),
        'menu' => array(
            'name' => 'menu',
            'title' => '管理中心导航菜单',
            'desc' => '管理中心导航菜单将会在管理中心生成一个导航入口(管理后台Web操作), 用于对模块定义的内容进行管理.'
        ),
        'home' => array(
            'name' => 'home',
            'title' => '微站首页导航图标',
            'desc' => '在微站的首页上显示相关功能的链接入口(手机端操作), 一般用于通用功能的展示.'
        ),
        'profile' => array(
            'name' => 'profile',
            'title' => '微站个人中心导航',
            'desc' => '在微站的个人中心上显示相关功能的链接入口(手机端操作), 一般用于个人信息, 或针对个人的数据的展示.'
        ),
        'shortcut' => array(
            'name' => 'shortcut',
            'title' => '微站快捷功能导航',
            'desc' => '在微站的快捷菜单上展示相关功能的链接入口(手机端操作), 仅在支持快捷菜单的微站模块上有效.'
        ),
        'function' => array(
            'name' => 'function',
            'title' => '微站独立功能',
            'desc' => '需要特殊定义的操作, 一般用于将指定的操作指定为(direct). 如果一个操作没有在具体位置绑定, 但是需要定义为(direct: 直接访问), 可以使用这个嵌入点'
        ),
        'page' => array(
            'name' => 'page',
            'title' => '小程序入口',
            'desc' => '用于小程序入口的链接'
        ),
        'system_welcome' => array(
            'name' => 'system_welcome',
            'title' => '系统首页导航菜单',
            'desc' => '系统首页导航菜单将会在管理中心生成一个导航入口, 用于对系统首页定义的内容进行管理.',
        ),
        'webapp' => array(
            'name' => 'webapp',
            'title' => 'PC入口',
            'desc' => '用于PC入口的链接',
        ),
        'phoneapp' => array(
            'name' => 'phoneapp',
            'title' => 'APP入口',
            'desc' => '用于APP入口的链接',
        )
    );
    return $bindings;
}

/**
 * 模块信息删除
 * @param string $modulename 模块名称
 * @param boolean $isCleanRule 是否删除相关规则
 * @return void
 */
function ext_module_clean($modulename) {
    table('modules')->deleteByName($modulename);
    table('modules_bindings')->deleteByName($modulename);
    pdo_delete('modules_plugin', array('main_module' => $modulename));
    return true;
}

/**
 * 获取微站模板配置信息
 * @param string $tpl 微站模板名称
 * @param boolean $cloud 是否从云服务读取配置信息(缺少配置文件情况下)
 * @return array
 */
function ext_template_manifest($tpl, $cloud = true) {
    $filename = IA_ROOT . '/app/themes/' . $tpl . '/manifest.xml';
    if (!file_exists($filename)) {
        return array();
    }
    $manifest = ext_template_manifest_parse(file_get_contents($filename));
    if (empty($manifest['name']) || $manifest['name'] != $tpl) {
        return array();
    }
    return $manifest;
}

/**
 * 将模板XML配置文件解析为数组
 * @param $xml 模板XML文件内容
 * @return array
 */
function ext_template_manifest_parse($xml) {
    $xml = str_replace(array('&'), array('&amp;'), $xml);
    $xml = @isimplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (empty($xml)) {
        return array();
    }
    $manifest['name'] = strval($xml->identifie);
    $manifest['title'] = strval($xml->title);
    if (empty($manifest['title'])) {
        return array();
    }
    $manifest['type'] = !empty($xml->type) ? strval($xml->type) : 'other';
    $manifest['description'] = strval($xml->description);
    $manifest['author'] = strval($xml->author);
    $manifest['url'] = strval($xml->url);
    if (isset($xml->sections)) {
        $manifest['sections'] = strval($xml->sections);
    }
    if ($xml->settings->item) {
        foreach ($xml->settings->item as $msg) {
            $attrs = $msg->attributes();
            $manifest['settings'][] = array('key' => trim(strval($attrs['variable'])), 'value' => trim(strval($attrs['content'])), 'desc' => trim(strval($attrs['description'])));
        }
    }
    return $manifest;
}

/**
 * 获取微站模板行业分类
 * @return array
 */
function ext_template_type() {
    static $types = array(
        'often' => array(
            'name' => 'often',
            'title' => '常用模板',
        ),
        'rummery' => array(
            'name' => 'rummery',
            'title' => '酒店',
        ),
        'car' => array(
            'name' => 'car',
            'title' => '汽车',
        ),
        'tourism' => array(
            'name' => 'tourism',
            'title' => '旅游',
        ),
        'drink' => array(
            'name' => 'drink',
            'title' => '餐饮',
        ),
        'realty' => array(
            'name' => 'realty',
            'title' => '房地产',
        ),
        'medical' => array(
            'name' => 'medical',
            'title' => '医疗保健'
        ),
        'education' => array(
            'name' => 'education',
            'title' => '教育'
        ),
        'cosmetology' => array(
            'name' => 'cosmetology',
            'title' => '健身美容'
        ),
        'shoot' => array(
            'name' => 'shoot',
            'title' => '婚纱摄影'
        ),
        'other' => array(
            'name' => 'other',
            'title' => '其它行业'
        )
    );
    return $types;
}

/**
 * 清除模块目录脚本文件
 * @param string $modulename 模块名称
 * @param array $manifest 配置XML文件信息
 * @return void
 */
function ext_module_script_clean($modulename, $manifest) {
    $moduleDir = IA_ROOT . '/addons/' . $modulename . '/';
    $manifest['install'] = trim($manifest['install']);
    $manifest['uninstall'] = trim($manifest['uninstall']);
    $manifest['upgrade'] = trim($manifest['upgrade']);
    if (strexists($manifest['install'], '.php')) {
        if (file_exists($moduleDir . $manifest['install'])) {
            unlink($moduleDir . $manifest['install']);
        }
    }
    if (strexists($manifest['uninstall'], '.php')) {
        if (file_exists($moduleDir . $manifest['uninstall'])) {
            unlink($moduleDir . $manifest['uninstall']);
        }
    }
    if (strexists($manifest['upgrade'], '.php')) {
        if (file_exists($moduleDir . $manifest['upgrade'])) {
            unlink($moduleDir . $manifest['upgrade']);
        }
    }
    if (file_exists($moduleDir . 'manifest.xml')) {
        unlink($moduleDir . 'manifest.xml');
    }
}

/**
 * 模块订阅消息类型
 * @return multitype:string
 */
function ext_module_msg_types() {
    $mtypes = array();
    $mtypes['text'] = '文本消息(重要)';
    $mtypes['image'] = '图片消息';
    $mtypes['voice'] = '语音消息';
    $mtypes['video'] = '视频消息';
    $mtypes['shortvideo'] = '小视频消息';
    $mtypes['location'] = '位置消息';
    $mtypes['link'] = '链接消息';
    $mtypes['subscribe'] = '粉丝开始关注';
    $mtypes['unsubscribe'] = '粉丝取消关注';
    $mtypes['qr'] = '扫描二维码';
    $mtypes['trace'] = '追踪地理位置';
    $mtypes['click'] = '点击菜单(模拟关键字)';
    $mtypes['view'] = '点击菜单(链接)';
    $mtypes['merchant_order'] = '微小店消息';
    $mtypes['user_get_card'] = '用户领取卡券事件';
    $mtypes['user_del_card'] = '用户删除卡券事件';
    $mtypes['user_consume_card'] = '用户核销卡券事件';
    $mtypes['user_view_card'] = '进入会员卡事件';
    $mtypes['user_gifting_card'] = '用户转赠卡券事件';
    $mtypes['mass_send_job_finish'] = '事件推送群发结果';
    $mtypes['template_send_job_finish'] = '模板消息发送结束';
    return $mtypes;
}

/**
 * 检查模块订阅消息是否成功
 * @param $modulename string 模块标识;
 * @return array();
 */
function ext_check_module_subscribe($modulename) {
    global $_W, $_GPC;
    if (empty($modulename)) {
        return true;
    }
    if (!empty($_W['setting']['module_receive_ban']) && !is_array($_W['setting']['module_receive_ban'])) {
        $_W['setting']['module_receive_ban'] = array();
    }
    load()->func('communication');
    $response = ihttp_request($_W['siteroot'] . 'web/' . url('utility/modules/check_receive', array('module_name' => $modulename)));
    $response['content'] = json_decode($response['content'], true);
    if (empty($response['content']['message']['errno'])) {
        unset($_W['setting']['module_receive_ban'][$modulename]);
        $module_subscribe_success = true;
    } else {
        $_W['setting']['module_receive_ban'][$modulename] = $modulename;
        $module_subscribe_success = false;
    }
    setting_save($_W['setting']['module_receive_ban'], 'module_receive_ban');
    return $module_subscribe_success;
}

/**
 * 检查模块配置项
 * @param $module_name string 模块标识;
 * @param $manifest array() 模块配置项;
 * @return array();
 */
function ext_manifest_check($module_name, $manifest) {
    if (is_string($manifest)) {
        return error(1, '模块 mainfest.xml 配置文件有误, 具体错误内容为: <br />' . $manifest);
    }
    $error_msg = '';
    if (empty($manifest['application']['name'])) {
        $error_msg .= '<br/>&lt;application&gt;&lt;name&gt;名称节点不能为空';
    }
    if (empty($manifest['application']['identifie']) || !preg_match('/^[a-z][a-z\d_]+$/i', $manifest['application']['identifie'])) {
        $error_msg .= '<br/>&lt;application&gt;&lt;identifie&gt;标识符节点不能为空或格式错误(仅支持字母和数字, 且只能以字母开头)';
    } elseif (strtolower($module_name) != strtolower($manifest['application']['identifie'])) {
        $error_msg .= '<br/>&lt;application&gt;&lt;identifie&gt;标识符节点与模块路径名称定义不匹配';
    }
    if (empty($manifest['application']['version']) || !preg_match('/^[\d\.]+$/i', $manifest['application']['version'])) {
        $error_msg .= '<br/>&lt;application&gt;&lt;version&gt;版本号节点未定义或格式不正确(仅支持数字和句点)';
    }
    if (empty($manifest['application']['ability'])) {
        $error_msg .= '<br/>&lt;application&gt;&lt;ability&gt;功能简述节点不能为空';
    }
    if ($manifest['platform']['isrulefields'] && !in_array('text', $manifest['platform']['handles'])) {
        $error_msg .= '<br/>模块功能定义错误, 嵌入规则必须要能够处理文本类型消息';
    }
    if ((!empty($manifest['cover']) || !empty($manifest['rule'])) && !$manifest['platform']['isrulefields']) {
        $error_msg .= '<br/>模块功能定义错误, 存在封面或规则功能入口绑定时, 必须要嵌入规则';
    }
    global $points;
    if (!empty($points)) {
        foreach ($points as $name => $point) {
            if (is_array($manifest[$name])) {
                foreach ($manifest[$name] as $menu) {
                    if (trim($menu['title']) == '' || !preg_match('/^[a-z\d]+$/i', $menu['do']) && empty($menu['call'])) {
                        $error_msg .= "<br/>&lt;$name&gt;节点" . $point['title'] . ' 扩展项功能入口定义错误, (操作标题[title], 入口方法[do])格式不正确.';
                    }
                }
            }
        }
    }
    //模块权限检测
    if (isset($manifest['permissions']) && is_array($manifest['permissions']) && !empty($manifest['permissions'])) {
        foreach ($manifest['permissions'] as $permission) {
            if (trim($permission['title']) == '' || !preg_match('/^[a-z\d_]+$/i', $permission['permission'])) {
                $error_msg .= '<br/>' . "&lt;permissions&gt;节点名称为： {$permission['title']} 的权限标识格式不正确,请检查标识名称或标识格式是否正确";
            }
        }
    }
    if (!empty($error_msg)) {
        return error(-1, '模块 mainfest.xml 配置文件有误<br/>' . $error_msg);
    }
    return error(0);
}

function ext_file_check($module_name, $manifest) {
    $module_path = IA_ROOT . '/addons/' . $module_name . '/';
    if (empty($manifest['platform']['main_module']) &&
        !file_exists($module_path . 'processor.php') &&
        !file_exists($module_path . 'module.php') &&
        !file_exists($module_path . 'site.php')) {
        return error(1, '模块缺失文件，请检查模块文件中site.php, processor.php, module.php, receiver.php 文件是否存在！');
    }
    return true;
}

/**
 *  执行模块的卸载脚本
 * @param string $module_name 模块标识
 */
function ext_execute_uninstall_script($module_name) {
    load()->model('cloud');
    $modulepath = IA_ROOT . '/addons/' . $module_name . '/';
    $manifest = ext_module_manifest($module_name);
    if (!empty($manifest)) {
        if (!empty($manifest['uninstall'])) {
            if (strexists($manifest['uninstall'], '.php')) {
                if (file_exists($modulepath . $manifest['uninstall'])) {
                    require($modulepath . $manifest['uninstall']);
                }
            } else {
                pdo_run($manifest['uninstall']);
            }
        }
    }
    return true;
}

function ext_module_run_script($manifest, $scripttype) {
    if (!in_array($scripttype, array('install', 'upgrade'))) {
        return false;
    }
    $modulename = $manifest['application']['identifie'];
    $module_path = IA_ROOT . '/addons/' . $modulename . '/';
    if (!empty($manifest[$scripttype])) {
        if (strexists($manifest[$scripttype], '.php')) {
            if (file_exists($module_path . $manifest[$scripttype])) {
                include_once $module_path . $manifest[$scripttype];
            }
        } else {
            pdo_run($manifest[$scripttype]);
        }
    }

    if (defined('ONLINE_MODULE')) {
        // 如果模块来自应用商城，删除对应文件
        ext_module_script_clean($modulename, $manifest);
    }
    return true;
}
