<?php
namespace W7\U1014;

defined('IN_IA') or exit('Access Denied');
class Up {
    const DESCRIPTION = '平台数据初始化';
    public function up() {
        global $_W;
        $accounts = [];
        $init_accounts = require IA_ROOT . '/data/init-accounts.php';
        foreach ($init_accounts as $account) {
            if (empty($account)) {
                continue;
            }
            $accounts[] = $account;
        }
        if (empty($accounts)) {
            return error(-1, '“/data/init-accounts.php”文件下数据缺失，请重新安装');
        }
        $uni_accounts = pdo_getall('uni_account', [], [], 'type');
        foreach ($accounts as $account) {
            if (!empty($uni_accounts[$account['type']])) {
                continue;
            }
            pdo_insert('uni_account', [
                'type' => $account['type'],
                'isconnect' => 1,
                'createtime' => TIMESTAMP
            ]);
            $uniacid = pdo_insertid();
            $data = [
                'uniacid' => $uniacid,
                'name' => $account['name'],
                'logo' => $account['logo_url'],
                'type' => $account['type'],
                'level' => intval($account['account_type']) ?: 1, //1订阅号;2服务号;3认证订阅号;4认证服务号
                'access_type' => intval($account['access_type']) ?: 1, //1普通接入;2授权接入
                'app_id' => $account['app_id'],
                'app_secret' => $account['app_secret'] ?? '',
                'app_key' => $account['app_key'] ?? '',
            ];
            pdo_insert('account', $data);
        }
        return true;
    }

    public function down() {
        return true;
    }
}
