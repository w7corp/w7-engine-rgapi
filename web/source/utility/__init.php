<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/web/source/utility/__init.php : v 8ce4c4d4ca11 : 2014/10/22 10:28:06 : yanghf $.
 *
 * account 所有操作在GW界面进行
 */
define('IN_GW', true);

if ('wechat_upload' == $do) {
    define('FRAME', 'account');
}
