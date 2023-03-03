<?php
/**
 * 小程序下载
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('miniapp');
load()->classs('uploadedfile');
load()->func('file');
$dos = array('front_download', 'getpackage', 'custom', 'custom_save', 'custom_default', 'tominiprogram');
$do = in_array($do, $dos) ? $do : 'front_download';
$module_name = safe_gpc_string($_GPC['module_name']);
$module = $_W['current_module'] = module_fetch($module_name);
define('IN_MODULE', true);
$version_info = table('wxapp_versions')->getByAccountType(ACCOUNT_TYPE_APP_NORMAL);
$version_id = $version_info['id'];
$uniacid = $version_info['uniacid'];
// 自定义appjson 入口
if ('custom' == $do) {
    $default_appjson = miniapp_code_current_appjson($version_id);

    $default_appjson = json_encode($default_appjson);
    template('wxapp/version-front-download');
}
// 使用默认appjson
if ('custom_default' == $do) {
    $result = miniapp_code_set_default_appjson($version_id);
    if (false === $result) {
        iajax(1, '操作失败，请重试！');
    } else {
        iajax(0, '设置成功！', url('wxapp/front-download/front_download', array('version_id' => $version_id)));
    }
}

// 保存自定义appjson
if ('custom_save' == $do) {
    if (empty($version_info)) {
        iajax(1, '参数错误！');
    }
    $json = array();
    if (!empty($_GPC['json']['window'])) {
        $json['window'] = array(
            'navigationBarTitleText' => safe_gpc_string($_GPC['json']['window']['navigationBarTitleText']),
            'navigationBarTextStyle' => safe_gpc_string($_GPC['json']['window']['navigationBarTextStyle']),
            'navigationBarBackgroundColor' => safe_gpc_string($_GPC['json']['window']['navigationBarBackgroundColor']),
            'backgroundColor' => safe_gpc_string($_GPC['json']['window']['backgroundColor']),
        );
    }
    if (!empty($_GPC['json']['tabBar'])) {
        $json['tabBar'] = array(
            'color' => safe_gpc_string($_GPC['json']['tabBar']['color']),
            'selectedColor' => safe_gpc_string($_GPC['json']['tabBar']['selectedColor']),
            'backgroundColor' => safe_gpc_string($_GPC['json']['tabBar']['backgroundColor']),
            'borderStyle' => in_array($_GPC['json']['tabBar']['borderStyle'], array('black', 'white')) ? safe_gpc_string($_GPC['json']['tabBar']['borderStyle']) : '',
        );
    }
    $result = miniapp_code_save_appjson($version_id, $json);
    cache_delete(cache_system_key('miniapp_version', array('version_id' => $version_id)));
    iajax(0, '设置成功！', url('wxapp/front-download/front_download', array('version_id' => $version_id)));
}

if ('tominiprogram' == $do) {
    $tomini_lists = iunserializer($version_info['tominiprogram']);
    if (!is_array($tomini_lists)) {
        $tomini_lists = array();
        miniapp_version_update($version_id, array('tominiprogram' => iserializer(array())));
    }

    if (checksubmit()) {
        $appids = safe_gpc_array($_GPC['appid']);
        $app_names = safe_gpc_array($_GPC['app_name']);
        $is_add = intval($_GPC['is_add']);

        if (!is_array($appids) || !is_array($app_names)) {
            itoast('参数有误！', referer(), 'error');
        }
        $data = $is_add ? $tomini_lists : array();
        foreach ($appids as $k => $appid) {
            if (empty($appid) || empty($app_names[$k])) {
                continue;
            }
            $appid = safe_gpc_string($appid);
            $data[$appid] = array(
                'appid' => $appid,
                'app_name' => safe_gpc_string($app_names[$k])
            );
            if (count($data) >= 10) {
                break;
            }
        }
        miniapp_version_update($version_id, array('tominiprogram' => iserializer($data)));
        itoast('保存成功！', referer(), 'success');
    }
    template('wxapp/version-front-download');
}

if ('front_download' == $do) {
    $type = 'wxapp';
    if (!empty($_W['setting']['server_setting']) && !empty($_W['setting']['server_setting']['app_id'])) {
        $package_url = url('wxapp/front-download/getpackage', ['module_name' => $module_name, 'version_id' => $version_id, '__session' => $_GPC['__session']], true);
        $upload_route = '/upload?url=' . urlencode($package_url) . '&app_id=' . $_W['setting']['server_setting']['app_id'] . '&support_type[]=2';
    }
    template('wxapp/version-front-download');
}
if ('getpackage' == $do) {
    $module_root = IA_ROOT . '/addons/' . $module['name'] . '/';
    $dir_name = $module['name'] . '_wxapp';
    if (is_dir($module_root . $dir_name)) {
        $app_json = array();
        $tomini_lists = iunserializer($version_info['tominiprogram']);
        if (!empty($tomini_lists) && file_exists($module_root . $dir_name . '/app.json')) {
            $app_json = json_decode(file_get_contents($module_root . $dir_name . '/app.json'));
            $app_json->embeddedAppIdList = array_keys($version_info['tominiprogram']);
        }
        $uniacid_zip_name = $module['name'] . '_wxapp_' . $uniacid . md5(time()) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($module_root . $uniacid_zip_name, ZipArchive::CREATE) === true) {//如果只用ZipArchive::OVERWRITE那么如果指定目标存在的话就会复写，否则返回错误9，而两个都用则会避免这个错误
            addFileToZip($module_root . $dir_name, $zip, $module_root . $dir_name . '/');
            $zip->close();
        }
        if (!is_dir(ATTACHMENT_ROOT . '/siteinfo')) {
            mkdir(ATTACHMENT_ROOT . '/siteinfo');
        }
        $copy_result = copy($module_root . $uniacid_zip_name, ATTACHMENT_ROOT . '/siteinfo/' . $uniacid_zip_name);
        if (!$copy_result) {
            itoast('小程序前端报预处理打包失败，请将权限设置成755后再试！');
        } else {
            @unlink($module_root . $uniacid_zip_name);
        }
        $siteinfo_content = <<<EOF
var siteinfo = {
"name": "{$module_name}",
"uniacid": "{$uniacid}",
"acid": "{$uniacid}",
"multiid": "0",
"version": "{$version_info['version']}",
"siteroot": "{$_W['siteroot']}app/index.php",
"method_design": "3"
};
module.exports = siteinfo;
EOF;
        $tmp_siteinfo_file = 'siteinfo/siteinfo_' . $uniacid . '.js';
        file_write($tmp_siteinfo_file, $siteinfo_content);
        $tmp_app_json_file = '';
        if (!empty($app_json)) {
            $tmp_app_json_file = 'siteinfo/app_' . $uniacid . '.json';
            file_write($tmp_app_json_file, json_encode($app_json));
        }
        if ($zip->open(ATTACHMENT_ROOT . '/siteinfo/' . $uniacid_zip_name) === true) {
            $zip->addFile(ATTACHMENT_ROOT . '/' . $tmp_siteinfo_file, 'siteinfo.js');
            if (!empty($tmp_app_json_file)) {
                $zip->addFile(ATTACHMENT_ROOT . '/' . $tmp_app_json_file, 'app.json');
            }
            $zip->close();
            $result = array('url' => ATTACHMENT_ROOT . '/siteinfo/' . $uniacid_zip_name);
        }
        @unlink(ATTACHMENT_ROOT . '/' . $tmp_siteinfo_file);
        if (!empty($tmp_app_json_file)) {
            @unlink(ATTACHMENT_ROOT . '/' . $tmp_app_json_file);
        }
    } else {
        $result = error(-1, '没有检测到小程序前端包的存在，请联系开发者将前端包上传至addons目录解压后再重试！');
    }
    if (is_error($result)) {
        itoast($result['message'], '', '');
    } else {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition:attachment;filename=' . $uniacid_zip_name);
        $fp = fopen(ATTACHMENT_ROOT . '/siteinfo/' . $uniacid_zip_name, 'r+');
        $buffer = 1024;
        while (!feof($fp)) {
            $file_data = fread($fp, $buffer);
            echo $file_data;
        }
        fclose($fp);
    }
    exit;
}

function addFileToZip($path, $zip, $root_path) {
    $handler = opendir($path);
    while (($filename = readdir($handler)) !== false) {
        if ($filename != "." && $filename != "..") {
            if (is_dir($path . "/" . $filename)) {
                addFileToZip($path . "/" . $filename, $zip, $root_path);
            } else {
                $zip->addFile($path . "/" . $filename, substr($path . "/" . $filename, strlen($root_path)));
            }
        }
    }
    @closedir($handler);
    return true;
}
