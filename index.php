<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/index.php : v 815cdc81ea88 : 2015/08/29 09:40:39 : RenChao $
 */

require __DIR__ . '/framework/bootstrap.inc.php';

if ($_W['os'] == 'mobile' && (!empty($_GPC['i']) || !empty($_SERVER['QUERY_STRING']))) {
    header('Location: ' . $_W['siteroot'] . 'app/index.php?' . $_SERVER['QUERY_STRING']);
} else {
    if (!empty($_SERVER['QUERY_STRING'])) {
        header('Location: ' . $_W['siteroot'] . 'web/index.php?' . $_SERVER['QUERY_STRING']);
    } else {
        $cache_key = cache_system_key('zhida_content');
        $cache = cache_load($cache_key);
        if (!empty($cache)) {
            die($cache);
        }
        $iframe = 'https://zhida.w7.cc/indexIframe?site_key=' . getenv('APP_ID');
        $response = ihttp_get($iframe);
        if ($response['code'] == 200 && !empty($response['content'])) {
            cache_write($cache_key, $response['content'], CACHE_EXPIRE_LONG);
            die($response['content']);
        }
        die(ierror_page('服务异常（应用直达），请尽快联系管理员处理！'));
    }
}
