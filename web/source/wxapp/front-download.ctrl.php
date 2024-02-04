<?php
/**
 * 小程序下载
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('miniapp');
$dos = array('front_download', 'save_setting', 'upgrade_module', 'rgapi_publish', 'rgapi_publish_status', 'publish_buy', 'getpackage');
$do = in_array($do, $dos) ? $do : 'front_download';
$module_name = safe_gpc_string($_GPC['module_name']);
$module = $_W['current_module'] = module_fetch($module_name);
define('IN_MODULE', true);
$version_info = table('wxapp_versions')->getByAccountType(ACCOUNT_TYPE_APP_NORMAL);
$version_id = $version_info['id'];
$uniacid = $version_info['uniacid'];

if ('front_download' == $do) {
	$account_all_type_sign = uni_account_type_sign();
	$appurl = !empty($_W['account']['appdomain']) ? $_W['account']['appdomain'] : ($_W['siteroot'] . 'app/index.php');
	$config = [
		'wxapp' => pdo_get('account', ['type' => ACCOUNT_TYPE_APP_NORMAL], ['uniacid', 'app_url', 'upload_private_key']),
		'aliapp' => pdo_get('account', ['type' => ACCOUNT_TYPE_ALIAPP_NORMAL], ['uniacid', 'upload_private_key', 'tool_id']),
	];
	$account = uni_fetch($config['wxapp']['uniacid']);
	if (!empty($version_info['version'])) {
		$user_version = explode('.', $version_info['version']);
		$user_version[count($user_version) - 1] += 1;
		$user_version = join('.', $user_version);
	}

    template('wxapp/version-front-download');
}

if ('save_setting' == $do) {
	$type = safe_gpc_string($_GPC['type']);
	$account['upload_private_key'] = safe_gpc_string($_GPC['privatekey']);
	if (WXAPP_TYPE_SIGN == $type) {
		$wxapp = pdo_get('account', ['type' => ACCOUNT_TYPE_APP_NORMAL], ['uniacid']);
		$uniacid = $wxapp['uniacid'];
		$account['app_url'] = safe_gpc_url($_GPC['appurl'], false);
		if (!empty($account['app_url']) && !starts_with($account['app_url'], 'https')) {
			itoast('域名必须以https开头');
		}
		if (!empty($_FILES['file']['name']) && STATUS_OFF == $_FILES['file']['error']) {
			if ($_FILES['file']['type'] != 'text/plain') {
				itoast('文件类型错误');
			}
			$file = file_get_contents($_FILES['file']['tmp_name']);
			$file_name = $_FILES['file']['name'];
			if (!preg_match('/^[A-Za-z0-9]+$/', $file)) {
				itoast('上传文件不合法,请重新上传！');
			}
			setting_save($file, $file_name);
		}
	} else {
		$aliapp = pdo_get('account', ['type' => ACCOUNT_TYPE_ALIAPP_NORMAL], ['uniacid']);
		$uniacid = $aliapp['uniacid'];
		$account['tool_id'] = safe_gpc_string($_GPC['tool_id']);
		if (!empty($account['tool_id']) && 32 != istrlen($account['tool_id'])) {
			itoast('工具Id必须是32个字符！');
		}
	}
	pdo_update('account', $account, ['uniacid' => $uniacid]);
	itoast('配置成功！', referer(), 'success');
}

if ('upgrade_module' == $do) {
	$version = empty($_GPC['version']) ? '' : safe_gpc_string($_GPC['version']);
	$description = empty($_GPC['description']) ? '' : safe_gpc_html($_GPC['description']);
	$modules = table('wxapp_versions')
		->where(array('id' => $version_id))
		->getcolumn('modules');
	$modules = iunserializer($modules);
	if (!empty($modules)) {
		foreach ($modules as $name => $module) {
			$module_info = module_fetch($name);
			if (!empty($module_info['version'])) {
				$modules[$name]['version'] = $module_info['version'];
			}
		}
		$modules = iserializer($modules);
		table('wxapp_versions')
			->where(array('id' => $version_id))
			->fill(array(
				'modules' => $modules,
				'last_modules' => $modules,
				'version' => $version,
				'description' => $description,
				'upload_time' => TIMESTAMP,
			))
			->save();
		cache_delete(cache_system_key('miniapp_version', array('version_id' => $version_id)));
	}
	iajax(0, '更新模块信息成功');
}

if (in_array($do, array('rgapi_publish', 'rgapi_publish_status'))) {
	$type = intval($_GPC['type']);
	if (ACCOUNT_TYPE_APP_AUTH == $type) {
		if (empty($_W['setting']['platform']['authstate'])) {
			iajax(-1, '开放平台未开启，无法上传');
		}
		if (empty($_W['setting']['platform']['bindappid']) || empty($_W['setting']['platform']['upload_private_key'])) {
			iajax(-1, '未设置开放平台绑定的开发小程序或代码上传秘钥');
		}
	}
	$account = pdo_get('account', ['type' => $type]);

	$headers = [
		'W7-KEY' => 112982,
		'W7-TOKEN' => 'ab7f9463df4854dadb7e3094e3245f26',
	];
	$publish_data = [
		'appid' => ACCOUNT_TYPE_APP_AUTH == $type ? $_W['setting']['platform']['bindappid'] : $account['app_id']
	];
}

if ('rgapi_publish' == $do) {
	$publish_data['accountType'] = $type;
	$publish_data['privateKey'] = ACCOUNT_TYPE_APP_AUTH == $type ? $_W['setting']['platform']['upload_private_key'] : $account['upload_private_key'];
	$publish_data['privateKey'] = str_replace("\r", "", $publish_data['privateKey']);
	if (ACCOUNT_TYPE_ALIAPP_NORMAL == $type) {
		$publish_data['toolId'] = $account['tool_id'];
	}
	$download_type = ACCOUNT_TYPE_ALIAPP_NORMAL == $type ? ALIAPP_TYPE_SIGN : WXAPP_TYPE_SIGN;
	$publish_data['url'] = url('wxapp/front-download/getpackage', ['module_name' => key($version_info['modules']), 'version_id' => $version_id, 'type' => $download_type, '__session' => $_GPC['__session']], true);
	$publish_data['version'] = empty($_GPC['version']) ? '' : safe_gpc_string($_GPC['version']);
	if (ACCOUNT_TYPE_APP_NORMAL == $type) {
		$publish_data['desc'] = empty($_GPC['description']) ? '' : safe_gpc_string($_GPC['description']);
		$publish_data['usePlugin'] = empty($_GPC['usePlugin']) ? false : true;
		$publish_data['livePlugin'] = empty($_GPC['livePlugin']) ? false : true;
		$publish_data['usePrivateInfos'] = empty($_GPC['usePrivateInfo']) ? false : true;
		$publish_data['privacyWindows'] = empty($_GPC['privacyWindows']) ? false : true;
		$publish_data['setting']['es6'] = empty($_GPC['setting']['es6']) ? false : true;
		$publish_data['setting']['minify'] = empty($_GPC['setting']['minify']) ? false : true;
		$publish_data['setting']['minifyWXSS'] = empty($_GPC['setting']['minifyWXSS']) ? false : true;
		$publish_data['setting']['minifyWXML'] = empty($_GPC['setting']['minifyWXML']) ? false : true;
		$publish_data['setting']['minifyJS'] = empty($_GPC['setting']['minifyJS']) ? false : true;
		$publish_data['setting']['autoPrefixWXSS'] = empty($_GPC['setting']['autoPrefixWXSS']) ? false : true;
		$publish_data['setting']['codeProtect'] = empty($_GPC['setting']['codeProtect']) ? false : true;
	}

	$miniapp_push = ihttp_request(CLOUD_MINIAPP_DOMAIN_PRE . '/push', json_encode($publish_data), $headers);
	$miniapp_push = json_decode($miniapp_push['content'], true);
	if (200 != $miniapp_push['code']) {
		iajax(-1, $miniapp_push['message']);
	}
	iajax(0, 'success');
}

if ('rgapi_publish_status' == $do) {
	$miniapp_status = ihttp_request(CLOUD_MINIAPP_DOMAIN_PRE . '/status', $publish_data, $headers);
	$miniapp_status = json_decode($miniapp_status['content'], true);
	if (200 != $miniapp_status['code']) {
		iajax(-1, $miniapp_status['message']);
	}
	iajax(0, $miniapp_status['data']);
}

if ('publish_buy' == $do) {
	$data = [
		'body' => '微信小程序上传隐私指引',
		'detail' => '微信小程序上传隐私指引服务费',
		'type' => 'mini_privacy_upload',
	];
	$ticket = cloud_wxapp_get_transactions_ticket($data);
	if (is_error($ticket)) {
		iajax(-1, $ticket['message']);
	}
	iajax(0, $ticket);
}

if ('getpackage' == $do) {
    $module_root = IA_ROOT . '/addons/' . $module['name'] . '/';
	$type = safe_gpc_string($_GPC['type']);
	$dir_name = $module['name'] . '_' . $type;
    if (is_dir($module_root . $dir_name)) {
        $app_json = array();
		if (WXAPP_TYPE_SIGN == $type) {
			$tomini_lists = iunserializer($version_info['tominiprogram']);
			if (!empty($tomini_lists) && file_exists($module_root . $dir_name . '/app.json')) {
				$app_json = json_decode(file_get_contents($module_root . $dir_name . '/app.json'));
				$app_json->embeddedAppIdList = array_keys($version_info['tominiprogram']);
			}
		}
		$uniacid_zip_name = $module['name'] . '_' . $type . '_' . $uniacid . md5(time()) . '.zip';
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
		if (WXAPP_TYPE_SIGN == $type) {
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
