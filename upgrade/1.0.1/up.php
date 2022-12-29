<?php
namespace W7\U101;

defined('IN_IA') or exit('Access Denied');
class Up {
    const DESCRIPTION = '兼容升级问题';
    public function up() {
        return true;
    }

    public function down() {
        return true;
    }
}
