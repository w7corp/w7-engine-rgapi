<?php
/**
 * 应用列表
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('miniapp');

$dos = ['switch_module', 'switch_uniacid'];
$do = in_array($do, $dos) ? $do : 'switch_module';

if ('switch_module' == $do) {
    $module_name = pdo_fetchcolumn("SELECT `name` FROM " . tablename('modules') . " ORDER BY `mid` ASC");
    $module_info = module_fetch($module_name);
    if (empty($module_info)) {
        message('应用尚未初始化，前去初始化。', url('module/manage-system/install'));
    }
    itoast('', url('module/welcome/display', ['module_name' => $module_name]));
}

if ('switch_uniacid' == $do) {
    $module_name = safe_gpc_string($_GPC['module_name']);
    $module_info = module_fetch($module_name);
    $support = [];
    foreach (module_support_type() as $key => $type) {
        if ($module_info[$key] == $type['support']) {
            $support[] = $type['type_num'];
        }
    }
    $account = table('account')->getOrderByTypeAsc();
    if (empty($account)) {
        $init_accounts = uni_init_accounts();
        if (is_error($init_accounts)) {
            message($init_accounts['message'], url('system/setting/basic'));
        }
        $account = table('account')->getOrderByTypeAsc();
    }
    $uniacid = $account['uniacid'];
    if (count($support) > 1) {
        $if_init_link_data = pdo_get('uni_link_uniacid', ['link_uniacid' => $uniacid, 'module_name' => $module_name]);
        if (empty($if_init_link_data)) {
            $all_account = pdo_getall('account', ['type IN ' => $support, 'uniacid !=' => $uniacid], 'uniacid');
            //只有两三个，故无所谓
            foreach ($all_account as $item) {
                pdo_insert('uni_link_uniacid', [
                    'uniacid' => $item['uniacid'],
                    'link_uniacid' => $uniacid,
                    'version_id' => 0,
                    'module_name' => $module_name
                ]);
            }
        }
    }
    
    $account_info = uni_fetch($uniacid);
    $url = url('module/welcome/display', ['module_name' => $module_name]);
    if (MODULE_SUPPORT_WXAPP == $module_info['wxapp_support']) {
        $wxapp_uniacid = pdo_getcolumn('account', ['type' => ACCOUNT_TYPE_APP_NORMAL], 'uniacid');
        $miniapp_version_info = miniapp_fetch($wxapp_uniacid);
        $version_id = $miniapp_version_info['version']['id'];
        if (empty($version_id)) {
            $version_data = [
                'uniacid' => $wxapp_uniacid,
                'description' => '默认描述信息',
                'version' => '1.1.1',
                'modules' => iserializer([$module_name => ['name' => $module_name, 'version' => $module_info['version']]]),
                'quickmenu' => '',
                'createtime' => TIMESTAMP,
                'appjson' => '',
                'default_appjson' => '',
                'use_default' => 1,
                'type' => 0,
                'entry_id' => 0,
                'tominiprogram' => '',
            ];
            table('wxapp_versions')->fill($version_data)->save();
            $version_id = pdo_insertid();
            $if_exist = pdo_get('uni_link_uniacid', ['uniacid' => $wxapp_uniacid, 'link_uniacid' => $uniacid, 'module_name' => $module_name]);
            if (!empty($if_exist)) {
                pdo_update('uni_link_uniacid', ['version_id' => $version_id], ['id' => $if_exist['id']]);
            }
        }
        $url .= '&version_id=' . $version_id;
    }
    isetcookie('__uniacid', $uniacid, 7 * 86400);
    itoast('', $url);
}
