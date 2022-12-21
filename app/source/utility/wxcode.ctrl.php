<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->library('qrcode');

$dos = array('verifycode', 'image', 'qrcode');
$do = in_array($do, $dos) ? $do : 'verifycode';

$_W['uniacid'] = intval($_GPC['uniacid']);
if ($do == 'verifycode') {
    //微信验证码
    $username = safe_gpc_string($_GPC['username']);
    $response = ihttp_get("https://mp.weixin.qq.com/cgi-bin/verifycode?username={$username}&r=" . TIMESTAMP);
    if (!is_error($response)) {
        isetcookie('code_cookie', $response['headers']['Set-Cookie']);
        header('Content-type: image/jpg');
        echo $response['content'];
        exit();
    }
} elseif ($do == 'image') {
    //微信图片
    $image = safe_gpc_url($_GPC['attach'], false);
    if (empty($image)) {
        exit();
    }
    //不是微信图片的统一false，防止ssrf
    if (!starts_with($image, array('http://mmbiz.qpic.cn/', 'https://mmbiz.qpic.cn/', 'http://mmbiz.qlogo.cn', 'https://mmbiz.qlogo.cn'))) {
        exit();
    }
    $content = ihttp_request($image, '', array('CURLOPT_REFERER' => 'http://www.qq.com'));
    header('Content-Type:image/jpg');
    echo $content['content'];
    exit();
} elseif ($do == 'qrcode') {
    $errorCorrectionLevel = "L";
    $matrixPointSize = "8";
    $text = safe_gpc_string($_GPC['text']);
    QRcode::png($text, false, $errorCorrectionLevel, $matrixPointSize);
    exit();
}
