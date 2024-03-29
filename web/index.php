<?php
/**
 * 路由控制器
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
define('IN_SYS', true);
require __DIR__ . '/../framework/bootstrap.inc.php';
require IA_ROOT . '/web/common/bootstrap.sys.inc.php';

$controllers = array();
$handle = opendir(IA_ROOT . '/web/source/');
if (!empty($handle)) {
    while ($dir = readdir($handle)) {
        if ('.' != $dir && '..' != $dir) {
            $controllers[] = $dir;
        }
    }
}
if (!in_array($controller, $controllers)) {
    $controller = 'home';
}

$init = IA_ROOT . "/web/source/{$controller}/__init.php";
if (is_file($init)) {
    require $init;
}

$actions = array();
$actions_path = file_tree(IA_ROOT . '/web/source/' . $controller);
foreach ($actions_path as $action_path) {
    $action_name = str_replace('.ctrl.php', '', basename($action_path));

    $section = basename(dirname($action_path));
    if ($section !== $controller) {
        $action_name = $section . '-' . $action_name;
    }
    $actions[] = $action_name;
}

//section可以省略，如果不在列表中，加上同名section后看是否可以使用
if (!in_array($action, $actions)) {
    $action = $action . '-' . $action;
}

if (!defined('FRAME')) {
    define('FRAME', '');
}
$acl = require IA_ROOT . '/web/common/permission.inc.php';
if (!empty($acl[$controller]) && is_array($acl[$controller]['direct']) && in_array($action, $acl[$controller]['direct'])) {
    // 如果这个目标被配置为不需要登录直接访问, 则直接访问
    require _forward($controller, $action);
    exit();
}
checklogin();
if (!getenv('LOCAL_DEVELOP') && getenv('APP_ID') && !empty($_SERVER['HTTP_SEC_FETCH_DEST']) && 'document' == $_SERVER['HTTP_SEC_FETCH_DEST'] && !in_array($do, ['getpackage'])) {
    itoast('', 'https://console.w7.cc/app/' . getenv('APP_ID') . '/founder/home');
}
if (empty($_W['setting']['modules_inited']) && ($action != 'manage-system' && $do != 'install')) {
    message('应用初始化中，请稍等片刻...', url('module/manage-system/install'));
}
if (!empty($_W['setting']['modules_inited']) && check_upgrade() && ($controller != 'cloud' && $action != 'process')) {
    message('检测到有升级，点击前往。', url('cloud/process'));
}
require _forward($controller, $action);

define('ENDTIME', microtime());
// 将运行速度过慢页面存入日志表
if (empty($_W['config']['setting']['maxtimeurl'])) {
    $_W['config']['setting']['maxtimeurl'] = 10;
}
if (((int)ENDTIME - (int)STARTTIME) > $_W['config']['setting']['maxtimeurl'] && isset($_W['setting']['copyright']['log_status']) && $_W['setting']['copyright']['log_status'] == STATUS_ON) {
    $data = array(
        'type' => '1',
        'runtime' => ENDTIME - STARTTIME,
        'runurl' => $_W['sitescheme'] . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        'createtime' => TIMESTAMP,
    );
    pdo_insert('core_performance', $data);
}
function _forward($c, $a) {
    global $_W;
    $file = IA_ROOT . '/web/source/' . $c . '/' . $a . '.ctrl.php';
    if (!file_exists($file)) {
        list($section, $a) = explode('-', $a);
        $file = IA_ROOT . '/web/source/' . $c . '/' . $section . '/' . $a . '.ctrl.php';
        if (!file_exists($file)) {
            itoast('非法访问', $_W['siteroot']);
        }
    }
    return $file;
}

function _calc_current_frames(&$frames) {
    global $_W, $controller, $action;
    $frames = empty($frames) ? array() : $frames;
    $frames['dimension'] = empty($frames['dimension']) ? '' : $frames['dimension'];
    $frames['title'] = empty($frames['title']) ? '' : $frames['title'];
    if (empty($frames['section']) || !is_array($frames['section'])) {
        return true;
    }
    $finished = STATUS_OFF;
    foreach ($frames['section'] as &$frame) {
        if (empty($frame['menu'])) {
            continue;
        }
        foreach ($frame['menu'] as &$menu) {
            if (defined('IN_MODULE') && !empty($menu['multilevel'])) {
                foreach ($menu['childs'] as $module_child_key => $module_child_menu) {
                    $query = parse_url($module_child_menu['url'], PHP_URL_QUERY);
                    $server_query = parse_url($_W['siteurl'], PHP_URL_QUERY);
                    if (0 === strpos($server_query, $query)) {
                        $menu['childs'][$module_child_key]['active'] = 'active';
                        break;
                    }
                }
            } else {
                $query = empty($menu['url']) ? array() : parse_url($menu['url'], PHP_URL_QUERY);
                if (!empty($query)) {
                    parse_str($query, $urls);
                }
                if (empty($urls)) {
                    continue;
                }
                $get = $_GET;
                $get['c'] = $controller;
                $get['a'] = $action;
                if (!empty($do)) {
                    $get['do'] = $do;
                }
                $diff = array_diff_assoc($urls, $get);
                if (empty($diff)) {
                    $menu['active'] = ' active';
                    $_W['page']['title'] = !empty($_W['page']['title']) ? $_W['page']['title'] : $menu['title'];
                    $finished = STATUS_ON;
                    break;
                }
            }
        }
        if ($finished) {
            break;
        }
    }
    return true;
}
