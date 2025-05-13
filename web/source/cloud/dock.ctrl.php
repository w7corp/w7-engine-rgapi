<?php

/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('cloud');
load()->model('setting');
load()->model('statistics');

$dos = ['auth', 'build', 'init', 'schema', 'download', 'module.query', 'module.bought', 'module.info', 'module.build',
	'module.setting.cloud', 'theme.query', 'theme.info', 'theme.build', 'application.build', 'api.oauth', 'unm',
	'member_module', 'module_support', 'app_redirect', 'if_has_unbind_user'];
$do = in_array($do, $dos) ? $do : '';

if ('auth' != $do) {
	//if (is_error(cloud_prepare())) {
		//exit('cloud service is unavailable.');
	//}
}

$post = file_get_contents('php://input');
if ('auth' == $do) {
	$auth = @json_decode(base64_decode($post), true);
	if (empty($auth)) {
		exit('推送的站点数据有误');
	}
	//对要写入的数据，再次发起请求，以防非法写入
	$_W['setting']['site'] = isset($_W['setting']['site']) && is_array($_W['setting']['site']) ? $_W['setting']['site'] : [];
	$_W['setting']['site']['url'] = $auth['url'];
	$_W['setting']['site']['key'] = $auth['key'];
	$_W['setting']['site']['token'] = $auth['token'];
	$site_info = cloud_site_info();
	if (is_error($site_info)) {
		exit('非法请求，详情：' . $site_info['message'] . '。请联系官方处理！');
	}
	if ($site_info['key'] != $auth['key']) {
		exit('非法请求，站点key不一致！');
	}
	$auth['username'] = $site_info['username'];
	setting_save($auth, 'site');
	setting_upgrade_version($auth['family'], $auth['version'], $auth['release']);
	cache_updatecache();
	exit('success');
}

if ('app_redirect' == $do) {
	$type = safe_gpc_string($_GPC['msg_type']);
	switch ($type) {
		case 'invite_unite':
			$data = array_filter($_GPC, function ($key) {
				return in_array($key, ['appid', 'msg_type', 'nonce', 'timestamp', 'user_info', 'sign']);
			}, ARRAY_FILTER_USE_KEY);
			if (__get_sign($data, $_W['setting']['site']['token']) !== $data['sign']) {
				exit('参数校验失败，请联系管理员处理！');
			}
			pdo_delete('users_bind', ['third_type' => USER_REGISTER_TYPE_CONSOLE, 'bind_sign' => $data['user_info']['openid']]);
			exit('success');
		case 'invite_create_user':
			$data = array_filter($_GPC, function ($key) {
				return in_array($key, ['appid', 'msg_type', 'nonce', 'timestamp', 'user_info', 'sign']);
			}, ARRAY_FILTER_USE_KEY);
			if (__get_sign($data, $_W['setting']['site']['token']) !== $data['sign']) {
				exit('参数校验失败，请联系管理员处理！');
			}
			$cloud_user_info = [
				'openid' => safe_gpc_string($data['user_info']['openid']),
				'username' => safe_gpc_string($data['user_info']['user_name']),
				'password' => safe_gpc_url($data['user_info']['password']),
				'role_identify' => safe_gpc_string($data['user_info']['role']),
			];
			if (empty($cloud_user_info['openid']) || empty($cloud_user_info['username'])) {
				exit('非法请求！');
			}
			$user_info = [
				'username' => user_check(['username' => $cloud_user_info['username']]) ? ($cloud_user_info['username'] . random(4)) : $cloud_user_info['username'],
				'password' => $cloud_user_info['password'],
				'repassword' => $cloud_user_info['password'],
				'remark' => '云端账号密码邀请用户',
				'starttime' => TIMESTAMP,
				'founder_groupid' => 0,
				'groupid' => empty($_W['setting']['register']['groupid']) ? 0 : safe_gpc_int($_W['setting']['register']['groupid']),
				'owner_uid' => 0
			];
			if (in_array($cloud_user_info['role_identify'], [ACCOUNT_MANAGE_NAME_VICE_FOUNDER_RULE])) {
				$user_info['founder_groupid'] = 2;
				$user_info['groupid'] = 0;
			}
			$user_save_result = user_info_save($user_info);
			if (is_error($user_save_result)) {
				exit($user_save_result['message']);
			}
			pdo_insert('users_bind', ['uid' => $user_save_result['uid'], 'bind_sign' => $cloud_user_info['openid'], 'third_nickname' => $cloud_user_info['username'], 'third_type' => USER_REGISTER_TYPE_CONSOLE]);
			exit('success');
		case 'app_redirect':
			$console_data = __secure_decode($_GPC['data']);
			$console_data = json_decode($console_data, true);
			if (empty($console_data)) {
				exit('不是有效的推送数据，请联系管理员处理！');
			}
			$settings = $_W['setting']['copyright'];
			$settings['app_redirect_info'] = array_filter($console_data, function ($key) {
				return in_array($key, ['logo', 'copyright_on', 'copyright_below', 'record_address', 'record_online', 'record', 'record_url']);
			}, ARRAY_FILTER_USE_KEY);
			setting_save($settings, 'copyright');
			exit('success');
		case 'register_user':
			if (!empty($_GPC['state']['not_bind']) || !empty($_GPC['out_uid'])) {
				exit('success');
			}
			$cloud_user_info = [
				'openid' => safe_gpc_string($_GPC['openid']),
				'username' => safe_gpc_string($_GPC['username']),
				'avatar' => safe_gpc_url($_GPC['avatar']),
				'role_identify' => safe_gpc_string($_GPC['role_identify']),
			];
			if (empty($cloud_user_info['openid']) || empty($cloud_user_info['username'])) {
				exit('非法请求！');
			}
			$bind = pdo_get('users_bind', ['bind_sign' => $cloud_user_info['openid'], 'third_type' => USER_REGISTER_TYPE_CONSOLE]);
			if (!empty($bind)) {
				exit('用户已绑定！');
			}
			$vice_founder_id = empty($_GPC['state']['owner_uid']) ? 0 : safe_gpc_int($_GPC['state']['owner_uid']);
			if (!empty($vice_founder_id)) {
				$vice_founder_info = user_single($vice_founder_id);
				if (empty($vice_founder_info) || !user_is_vice_founder($vice_founder_info['uid'])) {
					exit('副创始人不存在！');
				}
				$user_modules_info = user_modules($vice_founder_info['uid']);
				$user_modules = array_keys($user_modules_info);
				if (!empty($user_modules)) {
					$module_expired_list = module_expired_list();
					if (is_error($module_expired_list)) {
						exit($module_expired_list['message']);
					}
					$expired_modules_name = module_expired_diff($module_expired_list, $user_modules);
					if (!empty($expired_modules_name)) {
						exit('副创始人 ' . $vice_founder_info['username'] . ' 的应用：' . $expired_modules_name . '，服务费到期，无法添加！');
					}
				}
			}
			$str = random(8);
			$user_info = [
				'username' => user_check(['username' => $cloud_user_info['username']]) ? ($cloud_user_info['username'] . random(4)) : $cloud_user_info['username'],
				'password' => $str,
				'repassword' => $str,
				'remark' => '',
				'starttime' => TIMESTAMP,
				'founder_groupid' => 0,
				'groupid' => empty($_W['setting']['register']['groupid']) ? 0 : safe_gpc_int($_W['setting']['register']['groupid']),
				'owner_uid' => $vice_founder_id
			];
			if (in_array($cloud_user_info['role_identify'], [ACCOUNT_MANAGE_NAME_VICE_FOUNDER_RULE])) {
				$user_info['founder_groupid'] = 2;
				$user_info['groupid'] = 0;
			}
			$user_save_result = user_info_save($user_info);
			if (is_error($user_save_result)) {
				exit($user_save_result['message']);
			}
			pdo_insert('users_bind', ['uid' => $user_save_result['uid'], 'bind_sign' => $cloud_user_info['openid'], 'third_nickname' => $cloud_user_info['username'], 'third_type' => USER_REGISTER_TYPE_CONSOLE]);
			exit('success');
	}
	exit('非法请求！');
}

if ('build' == $do) {
	$dat = __secure_decode($post);
	if (!empty($dat)) {
		$secret = random(32);
		$ret = [];
		$ret['data'] = $dat;
		$ret['secret'] = $secret;
		file_put_contents(IA_ROOT . '/data/application.build' . md5(complex_authkey()), iserializer($ret));
		exit($secret);
	}
}

if ('schema' == $do) {
	$dat = __secure_decode($post);
	if (!empty($dat)) {
		$secret = random(32);
		$ret = [];
		$ret['data'] = $dat;
		$ret['secret'] = $secret;
		file_put_contents(IA_ROOT . '/data/application.schema' . md5(complex_authkey()), iserializer($ret));
		exit($secret);
	}
}

if ('download' == $do) {
	$data = base64_decode($post);
	if (base64_encode($data) !== $post) {
		$data = $post;
	}
	$ret = iunserializer($data);
	$gz = function_exists('gzcompress') && function_exists('gzuncompress');
	$file = base64_decode($ret['file']);
	if ($gz) {
		$file = gzuncompress($file);
	}

	$_W['setting']['site']['token'] = authcode(cache_load(cache_system_key('cloud_transtoken')), 'DECODE');
	$string = (md5($file) . $ret['path'] . $_W['setting']['site']['token']);
	if (!empty($_W['setting']['site']['token']) && md5($string) === $ret['sign']) {
		//模块和微官网模板无需先放在data下，系统文件需放在data下以防升级时文件没有更新完而报错
		//$path = IA_ROOT . $ret['path'];
		if (0 === strpos($ret['path'], '/web/') || 0 === strpos($ret['path'], '/framework/')) {
			$patch_path = sprintf('%s/data/patch/upgrade/%s', IA_ROOT, date('Ymd'));
		} else {
			$patch_path = IA_ROOT;
		}
		$path = $patch_path . $ret['path'];
		load()->func('file');
		@mkdirs(dirname($path));
		file_put_contents($path, $file);
		$sign = md5(md5_file($path) . $ret['path'] . $_W['setting']['site']['token']);
		if ($ret['sign'] === $sign) {
			exit('success');
		}
	}
	exit('failed');
}

if (in_array($do, ['module.query', 'module.bought', 'module.info', 'module.build', 'theme.query', 'theme.info', 'theme.build', 'application.build'])) {
	$dat = __secure_decode($post);
	if (!empty($dat)) {
		$secret = random(32);
		$ret = [];
		$ret['data'] = $dat;
		$ret['secret'] = $secret;
		file_put_contents(IA_ROOT . '/data/' . $do . md5(complex_authkey()), iserializer($ret));
		exit($secret);
	}
}

if ('module.setting.cloud' == $do) {
	$data = __secure_decode($post);
	$data = iunserializer($data);
	$setting = $data['setting'];
	$_W['uniacid'] = $data['acid'];
	$module = WeUtility::createModule($data['module']);
	$module->saveSettings($setting);
	cache_delete(cache_system_key('module_info', ['module_name' => $data['module']]));
	cache_delete(cache_system_key('module_setting', ['module_name' => $data['module'], 'uniacid' => $_W['uniacid']]));
	echo 'success';
	exit;
}

//对openapi通知的模块进行卸载
if ('unm' == $do) {
	load()->model('extension');
	$dat = __secure_decode($_GPC['data']);
	$dat = iunserializer($dat);
	$module_name = $dat['module_name'];
	$module_exists = table('modules')->getByName($module_name);
	if (empty($module_name) || empty($module_exists)) {
		exit('1');
	}
	ext_module_clean($module_name, true);
	ext_execute_uninstall_script($module_name);
	cache_build_module_subscribe_type();

	$module_support_info = $module_exists;
	if (empty($module_support_info)) {
		$module_support_info = pdo_get('modules_recycle', array('name' => $module_name));
	}
	if (empty($module_support_info)) {
		$module_support_info = pdo_get('modules_cloud', array('name' => $module_name));
	}

	table('users_extra_modules')->where(array('module_name' => $module_name))->delete();
	table('system_welcome_binddomain')->where(array('module_name' => $module_name))->delete();

	if (!empty($module_support_info['wxapp_support']) && MODULE_SUPPORT_WXAPP == $module_support_info['wxapp_support']) {
		$wxapp_version_table = table('wxapp_versions');
		$wxapp_versions = $wxapp_version_table->where(array('modules LIKE' => "%$module_name%"))->getall();
		foreach ($wxapp_versions as $wxapp_version) {
			$modules = iunserializer($wxapp_version['modules']);
			foreach ($modules as $key => $module) {
				if ($key != $module_name) {
					continue;
				}
				unset($modules[$key]);
				break;
			}
			$wxapp_version_table->where(array('id' => $wxapp_version['id']))->fill(array('modules' => iserializer($modules)))->save();
			cache_delete(cache_system_key('miniapp_version', array('version_id' => $wxapp_version['id'])));
		}
	}
	pdo_delete('site_styles', array('templateid' => intval($module['mid'])));
	pdo_delete('site_styles_vars', array('templateid' => intval($module['mid'])));

	cache_build_account_modules(0, $_W['uid']);
	cache_build_module_info($module_name);
	cache_build_uni_group();
	module_upgrade_info();
	@rmdirs(IA_ROOT . '/addons/' . $module_name);
	exit('1');
}

//返回当前应用已安装的支持
if ('module_support' == $do) {
	$module_name = safe_gpc_string($_GPC['module_name']);
	$result = [];
	$module_info = module_fetch($module_name);
	if (empty($module_info)) {
		exit(json_encode($result));
	}
	foreach (module_support_type() as $support => $support_info) {
		if ($support_info['support'] == $module_info[$support]) {
			$result[] = $support_info['type'];
		}
	}
	exit(json_encode($result));
}
//会员和模块统计
if ('member_module' == $do) {
	$result['member'] = stat_mc_member();
	$result['module'] = stat_module();
	exit(json_encode($result));
}

//是否有未绑定云端的该站点用户
if ('if_has_unbind_user' == $do) {
	$uids = table('users_bind')->select('uid')->where('third_type', USER_REGISTER_TYPE_CONSOLE)->getall();
	$uids = array_column($uids, 'uid');
	$uids = array_unique(array_merge($uids, explode(',', $_W['config']['setting']['founder'])));
	$data = pdo_fetch('SELECT `uid` FROM ' . tablename('users') . ' WHERE `uid` NOT IN (' . implode(',', $uids) . ')');
	exit($data ? '1' : '0');
}
if ('api.oauth' == $do) {
	$dat = __secure_decode($post);
	$dat = iunserializer($dat);
	if (!empty($dat) && is_array($dat)) {
		if ('core' == $dat['module']) {
			$result = file_put_contents(IA_ROOT . '/framework/builtin/core/' . md5(complex_authkey()) . '.cer', $dat['access_token']);
		} else {
			$result = file_put_contents(IA_ROOT . "/addons/{$dat['module']}/" . md5('module' . complex_authkey()) . '.cer', $dat['access_token']);
		}
		if (false !== $result) {
			die('success');
		}
		die('获取到的访问云API的数字证书写入失败.');
	}
	die('获取云API授权失败: api oauth.');
}
