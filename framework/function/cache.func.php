<?php
/**
 * 缓存统一接口.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->func('cache.' . cache_type());

/**
 * 获取缓存类型.
 * @param $cache_type
 */
function cache_type() {
    global $_W;
    $cacher = $connect = '';
    $cache_type = strtolower($_W['config']['setting']['cache']);

    if (extension_loaded($cache_type)) {
        $config = $_W['config']['setting'][$cache_type];
        if (!empty($config['server']) && !empty($config['port'])) {
            if ('memcached' == $cache_type) {
                $cacher = new Memcached();
                $connect = $cacher->addServer(
                    $config['server'],
                    !empty($config['port']) ? $config['port'] : 11211,
                    !empty($config['weight']) ? $config['weight'] : 1
                );
                if (isset($config['username']) && isset($config['password'])) {
                    $cacher->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
                    $cacher->setSaslAuthData($config['username'], $config['password']);
                }
            } elseif ('memcache' == $cache_type) {
                $cacher = new Memcache();
                $connect = $cacher->connect($config['server'], $config['port']);
            } elseif ('redis' == $cache_type) {
                $cacher = new Redis();
                $connect = $cacher->connect($config['server'], $config['port']);
            }
        }
    }
    if (empty($cacher) || empty($connect)) {
        $cache_type = 'mysql';
    }

    return $cache_type;
}

/**
 * 读取缓存，并将缓存加载至 $_W 全局变量中.
 * @param string $key         缓存键名
 * @param bool   $unserialize 是否反序列化
 * @return array
 */
function cache_load($key, $unserialize = false) {
    global $_W;
    static $we7_cache;

    if (is_error($key)) {
        trigger_error($key['message'], E_USER_WARNING);

        return false;
    }
    if (!empty($we7_cache[$key])) {
        return $we7_cache[$key];
    }
    $data = $we7_cache[$key] = cache_read($key);
    if ('setting' == $key) {
        $_W['setting'] = $data;

        return $_W['setting'];
    } elseif ('modules' == $key) {
        $_W['modules'] = $data;

        return $_W['modules'];
    } elseif ('module_receive_enable' == $key && empty($data)) {
        //如果不存在订阅模块数据，就再获取一下缓存
        cache_build_module_subscribe_type();

        return cache_read($key);
    } else {
        return $unserialize ? iunserializer($data) : $data;
    }
}

function &cache_global($key) {
}

/**
 * 获取缓存名称.
 * @param $cache_key
 * @param array $params
 * @return array|mixed|string
 */
function cache_system_key($cache_key) {
    $cache_key_all = cache_key_all();

    // 兼容旧函数（字符串形式传入参数拼接缓存键）
    $params = array();
    $args = func_get_args();
    if (empty($args[1])) {
        $args[1] = '';
    }
    if (!is_array($args[1])) {
        $cache_key = empty($cache_key_all['caches'][$cache_key]['key']) ? '' : $cache_key_all['caches'][$cache_key]['key'];
        preg_match_all('/\%([a-zA-Z\_\-0-9]+)/', $cache_key, $matches);
        for ($i = 0; $i < func_num_args() - 1; ++$i) {
            $matches[0][$i] = empty($matches[0][$i]) ? '' : $matches[0][$i];
            $cache_key = str_replace($matches[0][$i], $args[$i + 1], $cache_key);
        }

        return 'we7:' . $cache_key;
    } else {
        $params = $args[1];
    }

    // 如果是直接传入字符串缓存键（如module_info:wnstore:128），检查后直接返回
    if (empty($params)) {
        $res = preg_match_all('/([a-zA-Z\_\-0-9]+):/', $cache_key, $matches);
        if ($res) {
            $key = count($matches[1]) > 0 ? $matches[1][0] : $matches[1];
        } else {
            $key = $cache_key;
        }
        if (empty($cache_key_all['caches'][$key])) {
            return error(1, '缓存' . $key . ' 不存在!');
        } else {
            $cache_info_key = $cache_key_all['caches'][$key]['key'];
            preg_match_all('/\%([a-zA-Z\_\-0-9]+)/', $cache_info_key, $key_params);
            preg_match_all('/\:([a-zA-Z\_\-0-9]+)/', $cache_key, $val_params);

            if (count($key_params[1]) != count($val_params[1])) {
                foreach ($key_params[1] as $key => $val) {
                    if (in_array($val, array_keys($cache_key_all['common_params']))) {
                        $cache_info_key = str_replace('%' . $val, $cache_key_all['common_params'][$val], $cache_info_key);
                        unset($key_params[1][$key]);
                    }
                }

                // 当传入的参数包含多个参数且同时包含公共参数和非公共参数时
                if (count($key_params[1]) == count($val_params[1])) {
                    $arr = array_combine($key_params[1], $val_params[1]);
                    foreach ($arr as $key => $val) {
                        if (preg_match('/\%' . $key . '/', $cache_info_key)) {
                            $cache_info_key = str_replace('%' . $key, $val, $cache_info_key);
                        }
                    }
                }

                if (strexists($cache_info_key, '%')) {
                    return error(1, '缺少缓存参数或参数不正确!');
                } else {
                    return 'we7:' . $cache_info_key;
                }
            } else {
                return 'we7:' . $cache_key;
            }
        }
    }

    $cache_info = $cache_key_all['caches'][$cache_key];
    $cache_common_params = $cache_key_all['common_params'];

    if (empty($cache_info)) {
        return error(2, '缓存 ' . $cache_key . ' 不存在!');
    } else {
        $cache_key = $cache_info['key'];
    }

    // 如果缺少传入的参数，先从 common_params 中寻找并获取
    foreach ($cache_common_params as $param_name => $param_val) {
        preg_match_all('/\%([a-zA-Z\_\-0-9]+)/', $cache_key, $matches);
        if (in_array($param_name, $matches[1]) && !in_array($param_name, array_keys($params))) {
            $params[$param_name] = $cache_common_params[$param_name];
        }
    }

    if (is_array($params) && !empty($params)) {
        foreach ($params as $key => $param) {
            $cache_key = str_replace('%' . $key, $param, $cache_key);
        }

        if (strexists($cache_key, '%')) {
            return error(1, '缺少缓存参数或参数不正确!');
        }
    }

    $cache_key = 'we7:' . $cache_key;
    if (strlen($cache_key) > CACHE_KEY_LENGTH) {
        trigger_error('Cache name is over the maximum length');
    }

    return $cache_key;
}

/**
 * （根据缓存键）获取缓存的关联信息.
 * @param $key 传入的缓存键
 * @return array|int|string
 */
function cache_relation_keys($key) {
    if (!is_string($key)) {
        return $key;
    }

    if (!strexists($key, 'we7:')) {
        return array($key);
    }

    // 将传入的缓存键的参数值取出 => we7:user:liuguilong:18
    $cache_param_values = explode(':', $key);
    $cache_name = $cache_param_values[1];
    unset($cache_param_values[0]);
    unset($cache_param_values[1]);

    if (empty($cache_param_values)) {
        preg_match_all('/\:([a-zA-Z\_\-0-9]+)/', $key, $matches);
        $cache_name = empty($matches[1][0]) ? '' : $matches[1][0];
    }

    $cache_key_all = cache_key_all();
    $cache_relations = $cache_key_all['groups'];
    $cache_common_params = $cache_key_all['common_params'];
    $cache_info = empty($cache_name) ? array() : $cache_key_all['caches'][$cache_name];
    if (empty($cache_info)) {
        return error(2, '缓存 : ' . $key . '不存在');
    }

    if (!empty($cache_info['group'])) {
        if (empty($cache_relations[$cache_info['group']])) {
            return error(1, '关联关系未定义');
        }
        $relation_keys = $cache_relations[$cache_info['group']]['relations'];
        $cache_keys = array();
        foreach ($relation_keys as $key => $val) {
            // 获取到 $cache_key_all 数组中保存的缓存键名
            if ($val == $cache_name) {
                $relation_cache_key = $cache_key_all['caches'][$val]['key'];
            } else {
                $relation_cache_key = $cache_key_all['caches'][$cache_name]['key'];
            }
            foreach ($cache_common_params as $param_name => $param_val) {
                // 取出参数名称 => user:%name:%uid
                preg_match_all('/\%([a-zA-Z\_\-0-9]+)/', $relation_cache_key, $matches);
                if (in_array($param_name, $matches[1])) {
                    // 如果包含公共参数
                    $cache_key_params[$param_name] = $cache_common_params[$param_name];
                }
                // 将参数名称 和 参数值进行拼接 array('name' => 'liuguilong', 'uid' => 18)
                if (!empty($cache_prams_values) || count($matches[1]) == count($cache_param_values)) {
                    $cache_key_params = array_combine($matches[1], $cache_param_values);
                } else {
                    $cache_key_params = array();
                }
            }

            $cache_key = cache_system_key($val, $cache_key_params);
            if (!is_error($cache_key)) {
                $cache_keys[] = $cache_key;
            } else {
                return error(1, $cache_key['message']);
            }
        }
    } else {
        $cache_keys[] = $key;
    }

    return $cache_keys;
}

/**
 * 获取所有缓存键及缓存键的关联信息.
 * @key string 缓存键值
 * @relation string 关联关系组名称
 * @relations array 关联关系组
 * @relation_params array 构建关联缓存键的参数
 * @return array
 */
function cache_key_all() {
    global $_W;
    $caches_all = array(
        'common_params' => array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['uid'],
        ),

        'caches' => array(
            'module_info' => array(
                // 模块详细信息
                'key' => 'module_info:%module_name',
                'group' => 'module',
            ),

            'module_main_info' => array(
                // 模块主要信息(展示用)
                'key' => 'module_main_info:%module_name',
                'group' => 'module',
            ),

            'module_setting' => array(
                // 模块配置信息
                'key' => 'module_setting:%module_name:%uniacid',
                'group' => 'module',
            ),

            'last_account' => array(
                // 对某一模块，保留最后一次进入的小程序OR公众号，以便点进入列表页时可以默认进入
                'key' => 'last_account:%switch:%uid',
                'group' => '',
            ),

            'last_account_type' => array(
                'key' => 'last_account_type',
                'group' => '',
            ),

            'user_modules' => array(
                //当前用户拥有的所有模块及小程序标识
                'key' => 'user_modules:%uid',
                'group' => '',
            ),

            'user_accounts' => array(
                // 获取用户可操作的所有公众号或小程序或PC
                'key' => 'user_accounts:%type:%uid',
                'group' => '',
            ),

            'unimodules' => array(
                // 当前公众号及所有者可用的模块(获取指定公号下所有安装模块及模块信息)
                'key' => 'unimodules:%uniacid',
                'group' => '',
            ),

            'unimodules_binding' => array(
                // 模块所有注册菜单
                'key' => 'unimodules_binding:%uniacid',
                'group' => '',
            ),

            'expired_modules' => array(
                // 过期模块
                'key' => 'expired_modules',
                'group' => '',
            ),

            'local_dev_modules' => array(
                // 本地应用
                'key' => 'local_dev_modules',
                'group' => '',
            ),

            'uni_groups' => array(
                // 一个或多个公众号套餐信息
                'key' => 'uni_groups:%groupids',
                'group' => '',
            ),

            'permission' => array(
                // 管理员或操作员权限数据
                'key' => 'permission:%uniacid:%uid',
                'group' => '',
            ),

            'memberinfo' => array(
                'key' => 'memberinfo:%uid',
                'group' => '',
            ),

            'statistics' => array(
                'key' => 'statistics:%uniacid',
                'group' => '',
            ),

            'uniacid_visit' => array(
                'key' => 'uniacid_visit:%uniacid:%today',
                'group' => '',
            ),

            'material_reply' => array(
                // 构造素材回复消息结构(回复消息结构)
                'key' => 'material_reply:%attach_id',
                'group' => '',
            ),

            'keyword' => array(
                // conent 需要MD5 加密
                'key' => 'keyword:%content:%uniacid',
                'group' => '',
            ),

            'back_days' => array(
                'key' => 'back_days',
                'group' => '',
            ),

            'miniapp_version' => array(
                'key' => 'miniapp_version:%version_id',
                'group' => '',
            ),

            'proxy_wechatpay_account' => array(
                'key' => 'proxy_wechatpay_account',
                'group' => '',
            ),

            'recycle_module' => array(
                'key' => 'recycle_module',
                'group' => '',
            ),
            'random' => array(
                'key' => 'random',
                'group' => '',
            ),
            'sync_fans_pindex' => array(
                'key' => 'sync_fans_pindex:%uniacid',
                'group' => '',
            ),

            'uniaccount' => array(
                // 指定统一公众号下默认子号的信息
                'key' => 'uniaccount:%uniacid',
                'group' => 'uniaccount',
            ),

            'unisetting' => array(
                // 公众号的配置项
                'key' => 'unisetting:%uniacid',
                'group' => 'uniaccount',
            ),

            'defaultgroupid' => array(
                'key' => 'defaultgroupid:%uniacid',
                'group' => 'uniaccount',
            ),

            'uniaccount_type' => array(
                'key' => 'uniaccount_type:%account_type',
                'group' => '',
            ),

            /* accesstoken */
            'accesstoken' => array(
                'key' => 'accesstoken:%uniacid',
                'group' => 'accesstoken',
            ),

            'jsticket' => array(
                'key' => 'jsticket:%uniacid',
                'group' => 'accesstoken',
            ),

            'cardticket' => array(
                'key' => 'cardticket:%uniacid',
                'group' => 'accesstoken',
            ),
            /* accesstoken */

            'accesstoken_key' => array(
                'key' => 'accesstoken_key:%key',
                'group' => '',
            ),

            'account_oauth_refreshtoken' => array(
                'key' => 'account_oauth_refreshtoken:%acid',
                'group' => '',
            ),

            'account_auth_refreshtoken' => array(
                'key' => 'account_auth_refreshtoken:%uniacid',
                'group' => '',
            ),

            'account_tags' => array(
                'key' => 'account_tags:%uniacid',
                'group' => '',
            ),

            'unicount' => array(
                // 公众号下的子号的数量
                'key' => 'unicount:%uniacid',
                'group' => '',
            ),

            'checkupgrade' => array(
                'key' => 'checkupgrade',
                'group' => '',
            ),

            'cloud_transtoken' => array(
                'key' => 'cloud_transtoken',
                'group' => '',
            ),
            'cloud_w7_request_user_token' => array(
                'key' => 'cloud_w7_request_user_token:%token',
                'group' => '',
            ),

            'upgrade' => array(
                'key' => 'upgrade',
                'group' => '',
            ),

            'account_ticket' => array(
                'key' => 'account_ticket',
                'group' => '',
            ),

            'oauthaccesstoken' => array(
                'key' => 'oauthaccesstoken:%acid',
                'group' => '',
            ),

            'account_component_assesstoken' => array(
                'key' => 'account_component_assesstoken',
                'group' => '',
            ),
            'cloud_api' => array(
                'key' => 'cloud_api:%method',
                'group' => '',
            ),

            'cloud_file_permission_pass' => array(
                'key' => 'cloud_file_permission_pass',
                'group' => '',
            ),

            'couponsync' => array(
                'key' => 'couponsync:%uniacid',
                'group' => '',
            ),

            'storesync' => array(
                'key' => 'storesync:%uniacid',
                'group' => '',
            ),

            'cloud_auth_transfer' => array(
                'key' => 'cloud_auth_transfer',
                'group' => '',
            ),

            'modulesetting' => array(
                'key' => 'modulesetting:%module:%acid',
                'group' => '',
            ),

            'scan_config' => array(
                'key' => 'scan_config',
                'group' => 'scan_file',
            ),

            'scan_file' => array(
                'key' => 'scan_file',
                'group' => 'scan_file',
            ),

            'scan_badfile' => array(
                'key' => 'scan_badfile',
                'group' => 'scan_file',
            ),

            'bomtree' => array(
                'key' => 'bomtree',
                'group' => '',
            ),

            'setting' => array(
                'key' => 'setting',
                'group' => '',
            ),

            'stat_todaylock' => array(
                'key' => 'stat_todaylock:%uniacid',
                'group' => '',
            ),

            'account_preauthcode' => array(
                'key' => 'account_preauthcode',
                'group' => '',
            ),

            'account_auth_accesstoken' => array(
                'key' => 'account_auth_accesstoken:%key',
                'group' => '',
            ),

            'usersfields' => array(
                'key' => 'usersfields',
                'group' => '',
            ),

            'userbasefields' => array(
                'key' => 'userbasefields',
                'group' => '',
            ),

            'system_frame' => array(
                'key' => 'system_frame:%uniacid',
                'group' => '',
            ),

            'module_receive_enable' => array(
                'key' => 'module_receive_enable',
                'group' => '',
            ),

            'module_entry_call' => array(
                'key' => 'module_entry_call:%module_name:%uniacid',
                'group' => '',
            ),

            'system_check' => array(
                'key' => 'system_check',
                'group' => '',
            ),

            'delete_visit_ip' => array(
                'key' => 'delete_visit_ip:%date',
                'group' => '',
            ),

            'account_web_view_domain' => array(
                'key' => 'account_web_view_domain:%uniacid',
                'group' => '',
            ),
            'system_db_diff' => array(
                'key' => 'system_db_diff',
                'group' => '',
            ),
        ),
        // 缓存键关联关系数组
        'groups' => array(
            'uniaccount' => array(
                'relations' => array('uniaccount', 'unisetting', 'defaultgroupid'),
            ),

            'accesstoken' => array(
                'relations' => array('accesstoken', 'jsticket', 'cardticket'),
            ),

            'scan_file' => array(
                'relations' => array('scan_file', 'scan_config', 'scan_badfile'),
            ),

            // '模块'
            'module' => array(
                'relations' => array('module_info', 'module_setting', 'module_main_info'),
            ),
        ),
    );

    return $caches_all;
}
