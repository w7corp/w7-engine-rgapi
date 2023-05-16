<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$.
 */
defined('IN_IA') or exit('Access Denied');

$iframe = 'https://zhida.w7.cc/indexIframe?site_key=' . getenv('APP_ID');
$response = ihttp_get($iframe);
if ($response['code'] == 200 && !empty($response['content'])) {
    die($response['content']);
}
echo ierror_page('服务异常（应用直达），请尽快联系管理员处理！');
exit;
