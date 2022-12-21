<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->model('visit');

$dos = array('showjs', 'health');
$do = in_array($do, $dos) ? $do : 'showjs';

if ($do == 'showjs') {
    exit('');
}

// https 站点校验是否能正常访问
if ($do == 'health') {
    echo json_encode(error(0, 'success'));
    exit;
}
