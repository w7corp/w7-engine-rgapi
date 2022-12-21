<?php

defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
paycenter_check_login();
$store_name = $_W['user']['store_name'];
$clerk_name = $_W['user']['name'];
if ('more' == $_GPC['do']) {
	$clerk_info = table('mc_members')->select('mobile')->where(array('uid' => $_W['user']['uid']))->get();
}

include $this->template('more');
