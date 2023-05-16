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
        header('Location: ' . $_W['siteroot'] . 'web/index.php?c=account&a=welcome');
    }
}
