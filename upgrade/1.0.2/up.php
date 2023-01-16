<?php
namespace W7\U102;

defined('IN_IA') or exit('Access Denied');
class Up {
    const DESCRIPTION = '修复应用插件问题';
    public function up() {
        if (!pdo_tableexists('core_menu_shortcut')) {
            pdo_run("CREATE TABLE `ims_core_menu_shortcut` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` int NOT NULL DEFAULT '0',
  `uniacid` int NOT NULL DEFAULT '0',
  `modulename` varchar(100) NOT NULL DEFAULT '',
  `displayorder` int NOT NULL DEFAULT '0' COMMENT '排序，数字越大越靠前',
  `position` varchar(100) NOT NULL DEFAULT '',
  `updatetime` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `uid` (`uid`)
) ENGINE=InnoDB COMMENT='模块插件快捷菜单表';");
        }
        return true;
    }

    public function down() {
        return true;
    }
}
