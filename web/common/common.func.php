<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');

/**
 * 生成URL，统一生成方便管理.
 * @param string $segment
 * @param array  $params
 * @return string eg:(./index.php?c=*&a=*&do=*&...)
 */
function url($segment, $params = array(), $contain_domain = false) {
    return wurl($segment, $params, $contain_domain);
}

/**
 * 消息提示窗.
 * @param string $msg
 *                         提示消息内容
 * @param string $redirect
 *                         跳转地址
 * @param string $type     提示类型
 *                         <pre>
 *                         success  成功
 *                         error    错误
 *                         info     提示(灯泡)
 *                         warning  警告(叹号)
 *                         ajax     json
 *                         sql
 *                         </pre>
 * @param bool   $tips     是否是以tips形式展示（兼容1.0之前版本该函数的页面展示形式）
 * @param array  $extend   扩展按钮,支持多按钮
 *                         title string 扩展按钮名称
 *                         url string 跳转链接
 */
function message($msg, $redirect = '', $type = '', $tips = false, $extend = array()) {
    global $_W, $_GPC;

    if ('refresh' == $redirect) {
        $redirect = $_W['script_name'] . '?' . $_SERVER['QUERY_STRING'];
    }
    if ('referer' == $redirect) {
        $redirect = referer();
    }
    // 跳转链接只能跳转本域名下 防止钓鱼 如: 用户可能正常从信任站点微擎登录 跳转到第三方网站 会误认为第三方网站也是安全的
    $redirect = safe_gpc_url($redirect);

    if ('' == $redirect) {
        $type = in_array($type, array('success', 'error', 'info', 'warning', 'ajax', 'sql', 'expired')) ? $type : 'info';
    } else {
        $type = in_array($type, array('success', 'error', 'info', 'warning', 'ajax', 'sql', 'expired')) ? $type : 'success';
    }
    if ($_W['isajax'] || !empty($_GET['isajax']) || 'ajax' == $type) {
        if ('ajax' != $type && !empty($_GPC['target'])) {
            exit('
<script type="text/javascript">
    var url = ' . (!empty($redirect) ? 'parent.location.href' : "''") . ";
    var modalobj = util.message('" . $msg . "', '', '" . $type . "');
    if (url) {
        modalobj.on('hide.bs.modal', function(){\$('.modal').each(function(){if(\$(this).attr('id') != 'modal-message') {\$(this).modal('hide');}});top.location.reload()});
    }
</script>");
        } else {
            $vars = array();
            $vars['message'] = $msg;
            $vars['redirect'] = $redirect;
            $vars['type'] = $type;
            exit(json_encode($vars));
        }
    }
    if (empty($msg) && !empty($redirect)) {
        header('Location: ' . $redirect);
        exit;
    }
    $label = $type;
    if ('error' == $type || 'expired' == $type) {
        $label = 'danger';
    }
    if ('ajax' == $type || 'sql' == $type) {
        $label = 'warning';
    }

    if ($tips) {
        if (is_array($msg)) {
            $message_cookie['title'] = 'MYSQL 错误';
            $message_cookie['msg'] = 'php echo cutstr(' . $msg['sql'] . ', 300, 1);';
        } else {
            $message_cookie['title'] = $caption;
            $message_cookie['msg'] = $msg;
        }
        $message_cookie['type'] = $label;
        $message_cookie['redirect'] = $redirect ? $redirect : referer();
        $message_cookie['msg'] = rawurlencode($message_cookie['msg']);
        $extend_button = array();
        if (!empty($extend) && is_array($extend)) {
            foreach ($extend as $button) {
                if (!empty($button['title']) && !empty($button['url'])) {
                    $button['url'] = safe_gpc_url($button['url'], false);
                    $button['title'] = rawurlencode($button['title']);
                    $extend_button[] = $button;
                }
            }
        }
        $message_cookie['extend'] = !empty($extend_button) ? $extend_button : '';

        isetcookie('message', stripslashes(json_encode($message_cookie, JSON_UNESCAPED_UNICODE)));
        header('Location: ' . $message_cookie['redirect']);
    } else {
        include template('common/message', TEMPLATE_INCLUDEPATH);
    }
    exit;
}

function iajax($code = 0, $message = '', $redirect = '') {
    message(error($code, $message), $redirect, 'ajax', false);
}

function itoast($message, $redirect = '', $type = '', $extend = array()) {
    message($message, $redirect, $type, true, $extend);
}

/**
 * 验证操作用户是否已登录.
 * @return boolean
 */
function checklogin() {
    global $_W;
    if (empty($_W['uid'])) {
        if (!empty($_SERVER['HTTP_SEC_FETCH_DEST']) && 'document' == $_SERVER['HTTP_SEC_FETCH_DEST']) {
            $url = 'https://console.w7.cc/app/' . getenv('APP_ID') . '/founder/home';
            header('Location:' . $url);
            exit;
        }
        $url = cloud_oauth_login_url();
        if (is_error($url)) {
            message('授权用户登录失败，请联系管理员处理。详情：' . $url['message']);
        }
        header('Location:' . $url);
        exit;
    }
    return true;
}

function check_upgrade() {
    load()->model('extension');
    $cachekey = cache_system_key('checkupgrade');

    $cache = cache_load($cachekey);
    if (!empty($cache)) {
        return $cache;
    }
    $result = [];
    $upgrade = glob(IA_ROOT . '/upgrade/*');

    foreach ($upgrade as $item) {
        $path_array = explode('/', $item);
        $version = end($path_array);
        if (!str_is_version($version)) {
            continue;
        }
        if (version_compare($version, IMS_VERSION, '<=')) {
            continue;
        }
        include_once $item . '/up.php';
        $class_name = 'W7\\U' . str_replace('.', '', $version) . '\\Up';
        $result['system'][] = array('version' => $version, 'description' => $class_name::DESCRIPTION);
    }

    $modules = pdo_getall('modules', [], ['name', 'version'], 'name');
    foreach ($modules as $module_name => $module) {
        $root = IA_ROOT . '/addons/' . $module_name;
        $filename = $root . '/manifest.xml';
        if (!file_exists($filename)) {
            continue;
        }
        $xml = file_get_contents($filename);
        $xml = ext_module_manifest_parse($xml);
        $version = !empty($xml['application']['version']) ? $xml['application']['version'] : '1.0.0';
        if (version_compare($version, $module['version'], '<=')) {
            continue;
        }
        $result['module'][] = $module;
    }
    if (!empty($result)) {
        cache_write($cachekey, $result);
    }
    return $result;
}
//新版buildframes
function buildframes($framename = '') {
    global $_W, $_GPC;
    $frames = system_menu_permission_list();
    //特定的控制器减少数据获取
    if (defined('FRAME') && (!in_array(FRAME, array('account')))) {
        return empty($framename) ? array() : $frames[$framename];
    }
    //进入应用后权限
    $modulename = empty($_GPC['module_name']) ? empty($_GPC['m']) ? '' : safe_gpc_string($_GPC['m']) : safe_gpc_string($_GPC['module_name']);
    $eid = empty($_GPC['eid']) ? 0 : intval($_GPC['eid']);
    $version_id = empty($_GPC['version_id']) ? 0 : intval($_GPC['version_id']);
    if ((!empty($modulename) || !empty($eid))) {
        if (!empty($eid)) {
            $entry = pdo_get('modules_bindings', array('eid' => $eid));
        }
        if (empty($modulename)) {
            $modulename = $entry['module'];
        }
        $module = module_fetch($modulename);
        $entries = module_entries($modulename);
        $frames['account']['section'] = array();

        if (!defined('SYSTEM_WELCOME_MODULE')) {
            $frames['account']['section']['platform_module_common']['menu']['platform_module_welcome'] = array(
                'title' => '模块首页',
                'icon' => 'wi wi-home',
                'url' => url('module/welcome', array('module_name' => $modulename)),
                'is_display' => empty($module['main_module']) ? true : false,
                'module_welcome_display' => true,
            );
        }
        $frames['account']['section']['platform_module_common']['menu']['platform_module_member'] = [
            'title' => '会员管理',
            'icon' => 'wi wi-user',
            'url' => url('platform/sync-member/display', ['module_name' => $modulename]),
            'is_display' => 1,
        ];
        $frames['account']['section']['platform_module_common']['menu']['platform_module_member'] = [
            'title' => '支付参数',
            'icon' => 'wi wi-user',
            'url' => url('profile/payment/display', ['module_name' => $modulename]),
            'is_display' => 1,
        ];
        if (MODULE_SUPPORT_WXAPP == $module['wxapp_support']) {
            $frames['account']['section']['platform_module_common']['menu']['platform_module_publish'] = [
                'title' => '发布设置',
                'icon' => 'wi wi-examine',
                'url' => url('wxapp/front-download/front_download', ['module_name' => $modulename]),
                'is_display' => 1,
            ];
        }
        if ($module['isrulefields'] || !empty($entries['cover']) || !empty($entries['mine'])) {
            if (!empty($module['isrulefields']) && !empty($_W['account']) && in_array($_W['account']['type'], array(ACCOUNT_TYPE_OFFCIAL_NORMAL, ACCOUNT_TYPE_OFFCIAL_AUTH))) {
                $url = url('platform/reply', array('module_name' => $modulename));
            }
            if (empty($url) && !empty($entries['cover'])) {
                $url = url('platform/cover', array('eid' => $entries['cover'][0]['eid']));
            }
            if (!empty($url)) {
                $frames['account']['section']['platform_module_common']['menu']['platform_module_entry'] = array(
                    'title' => '应用入口',
                    'icon' => 'wi wi-reply',
                    'url' => $url,
                    'is_display' => 1,
                );
            }
        }
        if ($module['settings']) {
            $frames['account']['section']['platform_module_common']['menu']['platform_module_settings'] = array(
                'title' => '参数设置',
                'icon' => 'wi wi-parameter-setting',
                'url' => url('module/manage-account/setting', array('module_name' => $modulename)),
                'is_display' => 1,
            );
        }
        if (!empty($entries['cover'])) {
            foreach ($entries['cover'] as $menu) {
                $frames['account']['section']['platform_module_common']['menu']['platform_module_cover'][] = array(
                    'title' => "{$menu['title']}",
                    'url' => url('platform/cover', array('eid' => $menu['eid'])),
                    'is_display' => 0,
                );
            }
        }

        /* 模块菜单 - 插件*/
        if (!empty($module['plugin_list']) || !empty($module['main_module'])) {
            $modules = uni_modules();
            if (!empty($module['main_module'])) {
                $main_module = module_fetch($module['main_module']);
                $plugin_list = $main_module['plugin_list'];
            } else {
                $plugin_list = $module['plugin_list'];
            }
            $plugin_list = array_intersect($plugin_list, array_column($modules, 'name'));
        }

        if (!empty($module['plugin_list']) && empty($module['main_module'])) {
            $frames['account']['section']['platform_module_plugin']['title'] = '常用插件';
            $module_menu_plugin_list = table('core_menu_shortcut')->getCurrentModuleMenuPluginList($module['name']);
            if (!empty($module_menu_plugin_list)) {
                $plugin_list = array_keys($module_menu_plugin_list);
            }
            if (!empty($plugin_list)) {
                $i = 0;
                foreach ($plugin_list as $plugin_module) {
                    $plugin_module_info = module_fetch($plugin_module);
                    if (3 == $i && empty($module_menu_plugin_list)) {
                        break;
                    }
                    $frames['account']['section']['platform_module_plugin']['menu']['platform_' . $plugin_module_info['name']] = array(
                        'main_module' => $plugin_module_info['main_module'],
                        'title' => $plugin_module_info['title'],
                        'icon' => $plugin_module_info['logo'],
                        'url' => url('module/welcome/display', array('module_name' => $plugin_module_info['name'], 'uniacid' => $_W['uniacid'])),
                        'is_display' => 1,
                    );
                    ++$i;
                }
            }

            if (!empty($module['main_module'])) {
                $platform_module_plugin_more_url = url('module/plugin', array('module_name' => $module['main_module']));
            } else {
                $platform_module_plugin_more_url = url('module/plugin', array('module_name' => $module['name']));
            }

            if (!empty($plugin_list)) {
                $frames['account']['section']['platform_module_plugin']['menu']['platform_module_plugin_more'] = array(
                    'title' => '更多插件',
                    'url' => $platform_module_plugin_more_url,
                    'is_display' => empty($module['main_module']) ? 1 : 0,
                );
            } else {
                $frames['account']['section']['platform_module_plugin']['is_display'] = false;
            }
        }

        if (!empty($entries['menu'])) {
            $frames['account']['section']['platform_module_menu']['title'] = '业务菜单';
            foreach ($entries['menu'] as $row) {
                if (empty($row)) {
                    continue;
                }
                if (!empty($row['parent']) && !empty($frames['account']['section']['platform_module_menu']['menu']['platform_module_menu' . $row['parent']])) {
                    $frames['account']['section']['platform_module_menu']['menu']['platform_module_menu' . $row['parent']]['childs'][] = array(
                        'title' => $row['title'],
                        'url' => $row['url'] . '&version_id=' . $version_id,
                        'icon' => empty($row['icon']) ? 'wi wi-appsetting' : $row['icon'],
                        'is_display' => 1,
                    );
                    continue;
                }
                //因为模块DIY菜单不一定有do值，故有此if()else()
                if (!empty($row['from']) && 'call' == $row['from']) {
                    $frames['account']['section']['platform_module_menu']['menu']['platform_module_menu' . $row['eid']] = array(
                        'title' => $row['title'],
                        'url' => $row['url'] . '&version_id=' . $version_id,
                        'icon' => empty($row['icon']) ? 'wi wi-appsetting' : $row['icon'],
                        'is_display' => 1,
                    );
                } else {
                    $frames['account']['section']['platform_module_menu']['menu']['platform_module_menu' . $row['do']] = array(
                        'title' => $row['title'],
                        'url' => $row['url'] . '&version_id=' . $version_id,
                        'icon' => empty($row['icon']) ? 'wi wi-appsetting' : $row['icon'],
                        'is_display' => 1,
                        'multilevel' => $row['multilevel'],
                    );
                }
            }

            foreach ($frames['account']['section']['platform_module_menu']['menu'] as $key => $row) {
                if (!empty($row['multilevel']) && empty($row['childs'])) {
                    unset($frames['account']['section']['platform_module_menu']['menu'][$key]);
                }
            }
        }
    }
    return !empty($framename) ? $frames[$framename] : $frames;
}

/**
 * 在当前URL上拼接查询参数，生成url.
 *  @param string $params 需要拼接的参数。例如："time:1,group:2"，会在当前URL上加上&time=1&group=2
 * */
function filter_url($params) {
    global $_W;
    if (empty($params)) {
        return '';
    }
    $query_arr = array();
    $parse = parse_url($_W['siteurl']);
    if (!empty($parse['query'])) {
        $query = $parse['query'];
        parse_str($query, $query_arr);
    }
    $params = explode(',', $params);
    foreach ($params as $val) {
        if (!empty($val)) {
            $data = explode(':', $val);
            $query_arr[$data[0]] = trim($data[1]);
        }
    }
    $query_arr['page'] = 1;
    $query = http_build_query($query_arr);

    return './index.php?' . $query;
}

function url_params($url) {
    $result = array();
    if (empty($url)) {
        return $result;
    }
    $components = parse_url($url);
    $params = empty($components['query']) ? array() : explode('&', $components['query']);
    foreach ($params as $param) {
        if (!empty($param)) {
            $param_array = explode('=', $param);
            $result[$param_array[0]] = $param_array[1];
        }
    }

    return $result;
}
function home_url() {
    global $_W;
    return $_W['siteroot'] . 'web/index.php?c=account&a=manage&do=display';
}
