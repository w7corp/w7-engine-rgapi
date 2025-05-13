<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$
 */
defined('IN_IA') or exit('Access Denied');

function cloud_not_must_authorization_method() {
	return [
		'module/setting/index',
		'module/setting/save',
		'module/plugins/list',
		'module/query',
		'module/info',
		'sms/info',
		'sms/sign',
		'wxapp/info',
		'wxapp/login/qr-code',
		'wxapp/login/qr-scan',
		'wxapp/publish',
		'wxapp/publish/download',
		'theme/query',
		'we7/oauth/user-bind/mobile-bind-info',
		'we7/oauth/user-bind/mobile-code',
		'we7/oauth/user-bind/complete',
		'we7/oauth/user-bind/info',
		'we7/oauth/user-bind/complete-with-accesstoken',
		'we7/site/console/visible',
		'we7/site/console/index-url',
		'we7/site/console/share-url',
		'site/oauth/user/web-token/verify',
		'site/oauth/register-url/index',
		'site/oauth/user/info',
		'site/token/index',
		'site/oauth/login-url/index',
		'site/oauth/access-token/code',
	];
}

function cloud_get_sign($data, $appsecret = '') {
    unset($data['sign']);

    ksort($data, SORT_STRING);
    reset($data);

    $sign = md5(http_build_query($data, '', '&') . $appsecret);
    return $sign;
}

function _cloud_shipping_parse($dat) {
    if (is_error($dat)) {
        return error(-1, '网络传输故障，详情： ' . (strpos($dat['message'], 'Connection reset by peer') ? '云服务瞬时访问过大而导致网络传输中断，请稍后重试。' : $dat['message']));
    }
    $content = json_decode($dat['content'], true);
    if (!empty($content['error'])) {
        return error(-1, $content['error']);
    }
    if (!empty($content) && is_array($content)) {
        return $content;
    }

    $dat['content'] = iunserializer($dat['content']);
    if (is_array($dat['content']) && isset($dat['content']['files'])) {
        if (!empty($dat['content']['manifest'])) {
            $dat['content']['manifest'] = base64_decode($dat['content']['manifest']);
        }
        if (!empty($dat['content']['scripts'])) {
            $dat['content']['scripts'] = base64_decode($dat['content']['scripts']);
        }
        return $dat['content'];
    }
    if (is_array($dat['content']) && isset($dat['content']['data'])) {
        $data = $dat['content'];
    } else {
        return error(-1, '云服务平台向您的服务器传输数据过程中出现错误,详情:' . $dat['content']);
    }

    $ret = @iunserializer($data);
    if (empty($data) || empty($ret)) {
        return error(-1, '云服务平台向您的服务器传输的数据校验失败.可尝试：1、更新缓存 2、云服务诊断');
    }
    $ret = iunserializer($ret['data']);
    if (!is_error($ret) && is_array($ret)) {
        return $ret;
    } else {
        return error($ret['errno'], "发生错误: {$ret['message']}");
    }
}

function cloud_api($method, $data = array(), $extra = array(), $timeout = 60) {
    global $_W;
    $cache_key = cache_system_key('cloud_api', array('method' => md5($method . json_encode($data))));
    $cache = cache_load($cache_key);
    $extra['use_cache'] = !empty($extra['use_cache']);
    if (!empty($cache) && !$extra['use_cache']) {
        return $cache;
    }
    $api_url = CLOUD_API_DOMAIN . '/%s';
    $pars['appid'] = getenv('APP_ID') ?? '';
    $app_secret = getenv('APP_SECRET') ?? '';
    $data = array_merge($pars, $data);
    $data['timestamp'] = time();
    $data['nonce'] = random(16);
    $data['sign'] = cloud_get_sign($data, $app_secret);

    if (starts_with($_SERVER['HTTP_USER_AGENT'], 'we7')) {
        $extra['CURLOPT_USERAGENT'] = $_SERVER['HTTP_USER_AGENT'];
    }
    if (!empty($_W['config']['setting']['useragent']) && starts_with($_W['config']['setting']['useragent'], 'we7')) {
        $extra['CURLOPT_USERAGENT'] = $_W['config']['setting']['useragent'];
    }
    $extra['X-We7-Cache'] = cache_random(4, $extra['use_cache']);
    $response = ihttp_request(sprintf($api_url, $method), $data, $extra, $timeout);
    $ret = _cloud_shipping_parse($response);
    if (is_error($ret)) {
        WeUtility::logging('cloud-api-error', array('method' => sprintf($api_url, $method), 'data' => $data, 'extra' => $extra, 'response' => $response), true);
    }
    if (!is_error($ret) && !empty($ret)) {
        cache_write($cache_key, $ret, CACHE_EXPIRE_MIDDLE);
    }
    return $ret;
}

function cloud_openapi($method, $data = array(), $extra = array(), $timeout = 60) {
	global $_W;
    if (empty($_W['setting']['copyright']['cloud_status'])) {
		return '';
	}
	$cache_key = cache_system_key('cloud_api', array('method' => md5($method . json_encode($data))));
	$cache = cache_load($cache_key);
	$extra['use_cache'] = !isset($extra['use_cache']) || !empty($extra['use_cache']) ? true : false;
	if (!empty($cache) && $extra['use_cache']) {
		return $cache;
	}
	$api_url = CLOUD_API_DOMAIN . '/%s';
	$not_must_authorization_method = cloud_not_must_authorization_method();
	$must_authorization_host = !in_array($method, $not_must_authorization_method);
	$pars = _cloud_build_params($must_authorization_host);
	if ($method != 'site/token/index') {
		$pars['token'] = cache_load(cache_system_key('cloud_transtoken_spare'));
		if (empty($pars['token'])) {
			$pars['token'] = cloud_build_transtoken();
		}
	}
	$data = array_merge($pars, $data);
	if (starts_with($_SERVER['HTTP_USER_AGENT'], 'we7')) {
		$extra['CURLOPT_USERAGENT'] = $_SERVER['HTTP_USER_AGENT'];
	}
	if (!empty($_W['config']['setting']['useragent']) && starts_with($_W['config']['setting']['useragent'], 'we7')) {
		$extra['CURLOPT_USERAGENT'] = $_W['config']['setting']['useragent'];
	}
	$extra['X-We7-Cache'] = cache_random(4, $extra['use_cache']);
	$response = ihttp_request(sprintf($api_url, $method), $data, $extra, $timeout);
	$file = IA_ROOT . '/data/' . (!empty($data['file']) ? $data['file'] : str_replace('/', '', $method));
	$file = $file . md5(complex_authkey());
    $ret = _cloud_shipping_parse_openapi($response, $file);
	if (is_error($ret)) {
		WeUtility::logging('cloud-api-error', array('method' => sprintf($api_url, $method), 'data' => $data, 'extra' => $extra, 'response' => $response), true);
	}
	if (!is_error($ret) && !empty($ret)) {
		if ($method == 'site/token/index') {
			cache_write($cache_key, $ret);
		} else {
			cache_write($cache_key, $ret, CACHE_EXPIRE_MIDDLE);
		}
	}
	return $ret;
}

function _cloud_shipping_parse_openapi($dat, $file) {
	if (is_error($dat)) {
		return error(-1, '网络传输故障，详情： ' . (strpos($dat['message'], 'Connection reset by peer') ? '云服务瞬时访问过大而导致网络传输中断，请稍后重试。' : $dat['message']));
	}
	$tmp = iunserializer($dat['content']);
	if (is_array($tmp) && is_error($tmp)) {
		if ($tmp['errno'] == '-2') {
			file_put_contents(IA_ROOT . '/framework/version.inc.php', str_replace("'x'", "'v'", file_get_contents(IA_ROOT . '/framework/version.inc.php')));
		}
		if ($tmp['errno'] == 401) {
			register_shutdown_function('cloud_reset_siteinfo');
			$tmp['message'] = '<div class="text-left"><span class="text-left">1.本域名为非微擎授权域名，系统已帮您自动重置，请使用授权域名访问或 <a href="">刷新重试</a>！</span><br><span class="text-left">2.如您已操作域名修改，请配置当前站点域名访问和https证书！</span><br><span class="text-left">3.刷新后若仍未解决请 <a target="_blank" href="https://task.w7.com/taskrelease?type_id=11&team_id=1">提交工单</a>！</span></div>';
		}
		return $tmp;
	}
	if ($dat['content'] == 'patching') {
		return error(-1, '补丁程序正在更新中，请稍后再试！');
	}
	if ($dat['content'] == 'frequent') {
		return error(-1, '更新操作太频繁，请稍后再试！');
	}
	if ($dat['content'] == 'blacklist') {
		return error(-1, '抱歉，您的站点已被列入云服务黑名单，云服务一切业务已被禁止，请联系微擎客服！');
	}
	if ($dat['content'] == 'install-theme-protect' || $dat['content'] == 'install-module-protect') {
		return error('-1', '此' . ($dat['content'] == 'install-theme-protect' ? '模板' : '模块') . '已设置版权保护，您只能通过云平台来安装，请先删除该模块的所有文件，购买后再行安装。');
	}
	$content = json_decode($dat['content'], true);
	if (!empty($content['error'])) {
		return error(-1, $content['error']);
	}
	if (!empty($content) && is_array($content)) {
		return $content;
	}

	if (strlen($dat['content']) != 32) {
		$dat['content'] = iunserializer($dat['content']);
		if (is_array($dat['content']) && isset($dat['content']['files'])) {
			if (!empty($dat['content']['manifest'])) {
				$dat['content']['manifest'] = base64_decode($dat['content']['manifest']);
			}
			if (!empty($dat['content']['scripts'])) {
				$dat['content']['scripts'] = base64_decode($dat['content']['scripts']);
			}
			return $dat['content'];
		}
		if (is_array($dat['content']) && isset($dat['content']['data'])) {
			$data = $dat['content'];
		} else {
			return error(-1, '云服务平台向您的服务器传输数据过程中出现错误,详情:' . var_export($dat['content'], true));
		}
	} else {
		$data = @file_get_contents($file);
		@unlink($file);
	}

	$ret = @iunserializer($data);
	if (empty($data) || empty($ret)) {
		return error(-1, '云服务平台向您的服务器传输的数据校验失败.可尝试：1、更新缓存 2、云服务诊断');
	}
	$ret = iunserializer($ret['data']);
	if (is_array($ret) && is_error($ret)) {
		if ($ret['errno'] == '-2') {
			file_put_contents(IA_ROOT . '/framework/version.inc.php', str_replace("'x'", "'v'", file_get_contents(IA_ROOT . '/framework/version.inc.php')));
		}
		if ($ret['errno'] == '-3') { //模块升级服务到期
			return array(
				'errno' => $ret['errno'],
				'message' => $ret['message'],
				'cloud_id' => $ret['data'],
			);
		}
	}
	if (!is_error($ret) && is_array($ret)) {
		if (!empty($ret) && !empty($ret['state']) && $ret['state'] == 'fatal') {
			return error($ret['errorno'], '发生错误: ' . $ret['message']);
		}
		return $ret;
	} else {
		return error($ret['errno'], "发生错误: {$ret['message']}");
	}
}

function _cloud_build_params($must_authorization_host = true) {
	global $_W;
	$pars = array();
	$pars['host'] = strexists($_SERVER['HTTP_HOST'], ':') ? parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST) : $_SERVER['HTTP_HOST'];
	if (is_array($_W['setting']['site']) && !empty($_W['setting']['site']['url']) && !$must_authorization_host) {
		$pars['host'] = parse_url($_W['setting']['site']['url'], PHP_URL_HOST);
	}
	$pars['https'] = $_W['ishttps'] ? 1 : 0;
	$pars['family'] = IMS_FAMILY;
	$pars['version'] = IMS_VERSION;
	$pars['php_version'] = PHP_VERSION;
	$pars['current_host'] = $_SERVER['HTTP_HOST'];
	$pars['release'] = IMS_RELEASE_DATE;
	if (!empty(getenv('APP_ID')) && !empty(getenv('APP_SECRET'))) {
		$pars['key'] = getenv('APP_ID');
		$pars['password'] = md5(getenv('APP_ID') . getenv('APP_SECRET'));
	}
	$clients = cloud_client_define();
	$string = '';
	foreach ($clients as $cli) {
		$string .= md5_file(IA_ROOT . $cli);
	}
	$pars['client'] = md5($string);
	return $pars;
}

function cloud_client_define() {
	return array(
		'/framework/function/communication.func.php',
		'/framework/model/cloud.mod.php',
		'/web/source/cloud/upgrade.ctrl.php',
		'/web/source/cloud/dock.ctrl.php',
		'/web/themes/default/cloud/upgrade.html',
	);
}

function cloud_build_transtoken() {
	$pars['method'] = 'application.token';
	$pars['file'] = 'application.build';
	$ret = cloud_openapi('site/token/index', $pars);
	if (!empty($ret['token'])) {
		cache_write(cache_system_key('cloud_transtoken'), authcode($ret['token'], 'ENCODE'));
		return $ret['token'];
	}
	return '';
}

function cloud_oauth_login_url() {
    $ret = cloud_api('we7/open/oauth/login-url/index', ['redirect' => url('user/login', [], true)]);
    if (is_error($ret)) {
        return error(-1, $ret['message']);
    }
    return $ret['url'];
}

function cloud_oauth_accesstoken($code) {
    $result = cloud_api('we7/open/oauth/access-token/code', ['code' => $code]);
    if (is_error($result)) {
        return error(-1, $result['message']);
    }
    $cache_key = cache_system_key('oauthaccesstoken');
    cache_write($cache_key, $result['access_token'], $result['expire_time']);
    return $result['access_token'];
}

function cloud_oauth_user($access_token) {
    return cloud_api('we7/open/oauth/user/info', ['access_token' => $access_token]);
}

/**
 * 微信小程序备案 获取下单ticket
 */
function cloud_wxapp_get_transactions_ticket($data) {
	global $_W;
	if (empty($data['body']) || empty($data['detail']) || empty($data['type'])) {
		return error(-1, '参数错误！');
	}
	$api_url = CLOUD_PROSERVICE_DOMAIN . '/w7pay/transactions';
	$params = [
		'site_id' => intval($_SERVER['APP_ID']),
		'openid' => $_W['user']['openid'],
		'component_appid' => intval($_SERVER['APP_ID']),
		'body' => $data['body'],
		'detail' => $data['detail'],
		'type' => $data['type'],
	];
	$response = ihttp_request($api_url, $params);
	$result = json_decode($response['content'], true);
	if (!empty($result['code'])) {
		WeUtility::logging('cloud-api-error', array('method' => $api_url, 'data' => $params, 'response' => $response), true);
		return error(-1, $result['message']);
	}
	return $result['data']['ticket'];
}

function cloud_m_plugins($module_name) {
	return cloud_openapi('module/plugins/list', array('module' => $module_name));
}

function cloud_prepare() {
	global $_W;
	setting_load();
	if (empty($_W['setting']['site']['key']) || empty($_W['setting']['site']['token'])) {
		register_shutdown_function('cloud_reset_siteinfo');
		return error('-1', '站点注册信息丢失, 系统已帮您重置站点，请刷新重试！如还未解决您可以提交工单解决！');
	}
	return true;
}

function cloud_reset_siteinfo() {
	global $_W;
	return cloud_openapi('site/register/profile', array('url' => $_W['siteroot']));
}

function cloud_m_build($modulename, $type = 'install') {
	$type = in_array($type, array('uninstall', 'upgrade', 'install')) ? $type : 'install';
	if (empty($modulename)) {
		return array();
	}
	$module_info = cloud_m_info($modulename);
	if (is_error($module_info)) {
		return $module_info;
	}

	$pars['module'] = $modulename;
	$pars['type'] = $type;
	$pars['module_version'] = $module_info['version']['version'];
	$pars['file'] = 'module.build';
	$ret = cloud_openapi('module/build', $pars);

	if (!is_error($ret)) {
		$dir = IA_ROOT . '/addons/' . $modulename;
		$files = array();
		$messy_code_file = '';
		if (!empty($ret['files'])) {
			foreach ($ret['files'] as $file) {
				if ($file['path'] == '/map.json') {
					continue;
				}
				if (empty(safe_gpc_path($file['path']))) {
					$messy_code_file .= '<br>/' . $modulename . $file['path'];
				} else {
					$entry = $dir . $file['path'];
					if (!is_file($entry) || md5_file($entry) != $file['checksum']) {
						$files[] = '/' . $modulename . $file['path'];
					}
				}
			}
		}
		$ret['files'] = $files;
		if (!empty($messy_code_file) && !igetcookie($modulename . '_install')) {
			//return error(-2, '包含命名不规范文件，请联系开发者处理或忽略异常文件点击“确定”后再次点击“去安装”以继续安装。<br>文件如下：' . $messy_code_file);
		}
		$schemas = array();
		if (!empty($ret['schemas'])) {
			load()->func('db');
			foreach ($ret['schemas'] as $remote) {
				$name = substr($remote['tablename'], 4);
				$local = db_table_schema(pdo(), $name);
				unset($remote['increment']);
				unset($local['increment']);
				if (empty($local)) {
					$schemas[] = $remote;
				} else {
					$diffs = db_table_fix_sql($local, $remote);
					if (!empty($diffs)) {
						$schemas[] = $remote;
					}
				}
			}
		}
		$ret['type'] = 'module';
		$ret['schemas'] = $schemas;
		//如果是安装模块,根据这个标志不处理script
		$module = table('modules')->getByName($modulename);
		if (empty($module)) {
			$ret['install'] = STATUS_ON;
		} else {
			$ret['upgrade'] = STATUS_ON;
		}
	}
	return $ret;
}

function cloud_m_info($name) {
	$pars['method'] = 'module.info';
	$pars['module'] = $name;
	$ret = cloud_openapi('module/info', $pars);
	return $ret;
}

function cloud_file_tree($path, $include = array()) {
	$files = array();
	if (!empty($include)) {
		$ds = glob($path . '/{' . implode(',', $include) . '}', GLOB_BRACE);
	} else {
		$ds = glob($path . '/*');
	}
	if (is_array($ds)) {
		foreach ($ds as $entry) {
			if (is_file($entry)) {
				$files[] = $entry;
			}
			if (is_dir($entry)) {
				$rs = cloud_file_tree($entry);
				foreach ($rs as $f) {
					$files[] = $f;
				}
			}
		}
	}
	return $files;
}