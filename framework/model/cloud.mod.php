<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$
 */
defined('IN_IA') or exit('Access Denied');

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
