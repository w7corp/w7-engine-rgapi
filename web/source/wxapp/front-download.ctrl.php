<?php
/**
 * 小程序下载
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('miniapp');
$dos = array('front_download', 'getpackage');
$do = in_array($do, $dos) ? $do : 'front_download';
$module_name = safe_gpc_string($_GPC['module_name']);
$module = $_W['current_module'] = module_fetch($module_name);
define('IN_MODULE', true);
$version_info = table('wxapp_versions')->getByAccountType(ACCOUNT_TYPE_APP_NORMAL);
$version_id = $version_info['id'];
$uniacid = $version_info['uniacid'];

if ('front_download' == $do) {
    $if_link_wxapp = pdo_exists('account', ['type' => ACCOUNT_TYPE_APP_NORMAL]) ? STATUS_ON : STATUS_OFF;
    if (!empty($_W['setting']['server_setting']) && !empty($_W['setting']['server_setting']['app_id']) && $if_link_wxapp) {
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
