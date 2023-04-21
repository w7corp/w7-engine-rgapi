<?php
namespace W7\U108;

defined('IN_IA') or exit('Access Denied');
class Up {
    const DESCRIPTION = '对接api授权';
    public function up() {
        if (pdo_tableexists('account') && !pdo_fieldexists('account', 'app_secret')) {
            pdo_run("ALTER TABLE `ims_account`
DROP COLUMN `token`,
DROP COLUMN `aes_key`,
ADD COLUMN `app_key` VARCHAR(32) NOT NULL,
ADD COLUMN `app_secret` VARCHAR(32) NOT NULL;");
        }
        return true;
    }

    public function down() {
        return true;
    }
}
