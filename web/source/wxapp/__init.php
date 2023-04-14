<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
define('FRAME', 'account');
if (!getenv('LOCAL_DEVELOP') && empty($_W['setting']['server_setting']['app_id'])) {
    message('请先在模块首页点击“+”关联至少一个平台！', url('module/display/switch_module'), 'error');
}
