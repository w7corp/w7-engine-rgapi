<?php
/** 更新缓存
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/web/source/system/updatecache.ctrl.php : v 25c4f271f9c1 : 2015/09/16 10:49:43 : RenChao $.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('cache');
load()->model('setting');

$dos = array('update_cache');
$do = in_array($do, $dos) ? $do : '';

if ('update_cache' == $do) {
    cache_updatecache();
    if ($_W['isajax']) {
        iajax(0, '更新缓存成功！', '');
    }
    itoast('更新缓存成功', '', 'success');
}
template('system/update-cache');
