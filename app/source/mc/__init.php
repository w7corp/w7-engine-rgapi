<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');
if ($action != 'cash') {
    checkauth();
}
if ($controller == 'mc' && $action == 'card') {
    if ($do == 'sign_display') {
        header('Location: ' . murl('entry', array('m' => 'we7_coupon', 'do' => 'card', 'op' => 'sign_display')));
        exit;
    } elseif ($do == 'notice') {
        header('Location: ' . murl('entry', array('m' => 'we7_coupon', 'do' => 'card', 'op' => 'notice')));
        exit;
    } else {
        header('Location: ' . murl('entry', array('m' => 'we7_coupon', 'do' => 'card')));
        exit;
    }
}
