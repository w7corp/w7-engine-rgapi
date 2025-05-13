<?php
/**
 * 云服务相关
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
define('FRAME', '');

if ('touch' == $action) {
    exit('success');
}

function __secure_decode($post) {
	global $_W;
	$data = base64_decode($post);
	if (base64_encode($data) !== $post) {
		$data = $post;
	}
	$ret = iunserializer($data);
	$string = ($ret['data'] . getenv('APP_SECRET'));
	if (md5($string) === $ret['sign']) {
		return $ret['data'];
	}
	return false;
}