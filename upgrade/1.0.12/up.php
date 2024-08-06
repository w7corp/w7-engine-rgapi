<?php
namespace W7\U1012;

defined('IN_IA') or exit('Access Denied');
class Up {
    const DESCRIPTION = '小程序授权上传';
    public function up() {
        global $_W;
        if (pdo_tableexists('account') && !pdo_fieldexists('account', 'token')) {
            pdo_run("ALTER TABLE `ims_account` ADD COLUMN `token` VARCHAR(32) NOT NULL;");
        }
        if (pdo_tableexists('account') && !pdo_fieldexists('account', 'encodingaeskey')) {
            pdo_run("ALTER TABLE `ims_account` ADD COLUMN `encodingaeskey` VARCHAR(200) NOT NULL;");
        }
        if (pdo_tableexists('account') && !pdo_fieldexists('account', 'app_url')) {
            pdo_run("ALTER TABLE `ims_account` ADD COLUMN `app_url` VARCHAR(200) NOT NULL;");
        }
        if (pdo_tableexists('account') && !pdo_fieldexists('account', 'upload_private_key')) {
            pdo_run("ALTER TABLE `ims_account` ADD COLUMN `upload_private_key` MEDIUMTEXT NOT NULL;");
        }
        if (pdo_tableexists('account') && !pdo_fieldexists('account', 'tool_id')) {
            pdo_run("ALTER TABLE `ims_account` ADD COLUMN `tool_id` CHAR(32) NOT NULL;");
        }
        if (pdo_tableexists('account') && pdo_fieldexists('account', 'type')) {
            pdo_run("ALTER TABLE `ims_account` ADD UNIQUE(`type`);");
        }
        /*
        TODO 废弃软擎授权系统
        
        $uni_accounts = pdo_getall('uni_account', [], [], 'type');
        if (empty($uni_accounts)) {
            return true;
        }
        load()->library('sdk-module');
        try {
            $api = new \W7\Sdk\Module\Api(getenv('APP_ID'), getenv('APP_SECRET'), $_W['setting']['server_setting']['app_id'], 0, V3_API_DOMAIN);
            $rgapi_accounts = $api->getAccountList()->toArray();
            if (!empty($rgapi_accounts) && is_array($rgapi_accounts)) {
                foreach ($rgapi_accounts as $account) {
                    if (empty($uni_accounts[$account['type']])) {
                        continue;
                    }
                    pdo_update('account', ['app_secret' => $account['app_secret'] ?? ''], ['uniacid' => $uni_accounts[$account['type']]['uniacid']]);
                }
            } else {
                throw new \Exception('API授权获取平台失败，可能原因：1. 人为关闭了API授权；2. 平台被删除；');
            }
        } catch (\W7\Sdk\Module\Exceptions\ApiException $e) {
            return error(-1, $e->getResponse()->getBody()->getContents());
        }
        */
        return true;
    }

    public function down() {
        return true;
    }
}
