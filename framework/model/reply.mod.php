<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 规则查询 `rule`
 * @param string $condition 查询条件 WHERE 后内容, eg: $condition='id=:id, acid=:acid';
 * @param array $params 查询参数, eg: array(':id'=>$id,':acid'=>$acid);
 * @param int $pindex 当前页码, 0 全部记录
 * @param int $psize 分页大小
 * @param int $total 总记录数
 * @return array
 */
function reply_search($condition = '', $params = array(), $pindex = 0, $psize = 10, &$total = 0) {
    if (!empty($condition)) {
        $where = " WHERE {$condition}";
    }
    $sql = "SELECT * FROM " . tablename('rule') . $where . " ORDER BY status DESC, displayorder DESC, id DESC";
    $pindex = intval($pindex);
    $psize = intval($psize);
    if ($pindex > 0) {
        // 需要分页
        $start = ($pindex - 1) * $psize;
        $sql .= " LIMIT {$start},{$psize}";
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('rule') . $where, $params);
    }
    return pdo_fetchall($sql, $params);
}

/**
 * 查询单条规则及其下的所有关键字
 * @param number $id
 * @return array array('rule'=>$rule,'keyword'=>array($rule_key,...))
 */
function reply_single($id) {
    $id = intval($id);
    $result = table('rule')->getById($id);
    if (empty($result)) {
        return $result;
    }
    $result['keywords'] = table('rule_keyword')->whereRid($id)->getall();
    return $result;
}

/**
 * 从 `rule_keyword` 查询满足条件的所有规则关键字
 * @param string $condition 查询条件 WHERE 后内容, eg: $condition='id=:id, acid=:acid';
 * @param array $params 查询参数, eg: array(':id'=>$id,':acid'=>$acid);
 * @param int $pindex 当前页码, 0 全部记录.
 * @param int $psize 分页大小
 * @param int $total 总记录数
 * @return array
 */
function reply_keywords_search($condition = '', $params = array(), $pindex = 0, $psize = 10, &$total = 0) {
    global $_W;
    if (!empty($condition)) {
        $where = " WHERE {$condition} ";
    }
    $sql = 'SELECT * FROM ' . tablename('rule_keyword') . $where . ' ORDER BY displayorder DESC, `type` ASC, id DESC';
    $pindex = intval($pindex);
    $psize = intval($psize);
    if ($pindex > 0) {
        // 需要分页
        $start = ($pindex - 1) * $psize;
        $sql .= " LIMIT {$start},{$psize}";
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('rule_keyword') . $where, $params);
    }
    $result = pdo_fetchall($sql, $params);
    if (!empty($result)) {
        // 判断是否常用服务为开启
        $rule_setting_select = table('uni_account_modules')->getByUniacidAndModule('userapi', $_W['uniacid']);
        foreach ($result as $key => $val) {
            if ($val['module'] == 'userapi' && empty($val['uniacid'])) {
                if (empty($rule_setting_select['settings'][$val['rid']])) {
                    unset($result[$key]);
                    continue;
                }
            }

            $containtypes = pdo_get('rule', array('id' => $val['rid']), array('containtype'));
            if (!empty($containtypes)) {
                $containtype = explode(',', $containtypes['containtype']);
                $containtype = array_filter($containtype);
            } else {
                $containtype = array();
            }
            $result[$key]['reply_type'] = $containtype;
        }
    } else {
        $result = array();
    }
    return $result;
}

/**
 * 查询某一关键字回复中所有回复内容
 * @param int $rid  要查询的rule规则ID
 * @param array $params  查询参数
 * @return array
 */
function reply_content_search($rid = 0) {
    $result = array();
    $rid = intval($rid);
    if (empty($rid)) {
        return $result;
    }

    $modules = array('basic', 'images', 'news', 'music', 'voice', 'video', 'wxapp');
    $params = array(':rid' => $rid);
    $result['sum'] = 0;
    foreach ($modules as $key => $module) {
        $result[$module] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($module . '_reply') . ' WHERE `rid` = :rid', $params);
        $result['sum'] += $result[$module];
    }
    return $result;
}

/**
 * 系统预定义的所有常用服务.
 * @return array
 */
function reply_predefined_service() {
    $predefined_service = array(
        'weather.php' => array(
            'title' => '城市天气',
            'description' => '"城市名+天气", 如: "北京天气"',
            'keywords' => array(
                    array('3', '^.+天气$')
            )
        ),
        'baike.php' => array(
            'title' => '百度百科',
            'description' => '"百科+查询内容" 或 "定义+查询内容", 如: "百科姚明", "定义自行车"',
            'keywords' => array(
                    array('3', '^百科.+$'),
                    array('3', '^定义.+$'),
            )
        ),
        'translate.php' => array(
            'title' => '即时翻译',
            'description' => '"@查询内容(中文或英文)"',
            'keywords' => array(
                    array('3', '^@.+$'),
            )
        ),
        'calendar.php' => array(
            'title' => '今日老黄历',
            'description' => '"日历", "万年历", "黄历"或"几号"',
            'keywords' => array(
                    array('1', '日历'),
                    array('1', '万年历'),
                    array('1', '黄历'),
                    array('1', '几号'),
            )
        ),
        'news.php' => array(
            'title' => '看新闻',
            'description' => '"新闻"',
            'keywords' => array(
                    array('1', '新闻'),
            )
        ),
        'express.php' => array(
            'title' => '快递查询',
            'description' => '"快递+单号", 如: "申通1200041125"',
            'keywords' => array(
                    array('3', '^(申通|圆通|中通|汇通|韵达|顺丰|EMS) *[a-z0-9]{1,}$')
            )
        ),
    );
    return $predefined_service;
}

/**
 * 获取常用服务信息
 * @param $rule_setting_select
 * @return array
 */
function reply_getall_common_service() {
    global $_W;
    $rule_setting_select = table('uni_account_modules')->getByUniacidAndModule('userapi', $_W['uniacid']);
    $rule_setting_select = empty($rule_setting_select['settings']) ? array() : (array)$rule_setting_select['settings'];
    $exists_rule = table('rule')->where(array('uniacid' => 0, 'module' => 'userapi', 'status' => 1))->getall();
    $service_list = array();
    $rule_ids = array();
    $api_url = array();
    if (!empty($exists_rule)) {
        foreach ($exists_rule as $rule_detail) {
            $rule_ids[] = $rule_detail['id'];
            $service_list[$rule_detail['id']] = $rule_detail;
        }

        $all_description = table('userapi_reply')->where('rid IN', $rule_ids)->getall();
        if (!empty($all_description)) {
            foreach ($all_description as $description) {
                $service_list[$description['rid']]['description'] = $description['description'];
                $service_list[$description['rid']]['switch'] = isset($rule_setting_select[$description['rid']]) && $rule_setting_select[$description['rid']] ? 'checked' : '';
                $api_url[] = $description['apiurl'];
            }
        }
    }

    $all_service = reply_predefined_service();
    $all_url = array_keys($all_service);
    $diff_url = array_diff($all_url, $api_url);
    if (!empty($diff_url)) {
        foreach ($diff_url as $url) {
            $userapi_reply_info = table('userapi_reply')->getByApiurl($url);
            $userapi_reply_info['rid'] = empty($userapi_reply_info['rid']) ? 0 : $userapi_reply_info['rid'];
            $service_list[$userapi_reply_info['rid']]['url'] = empty($userapi_reply_info['apiurl']) ? '' : $userapi_reply_info['apiurl'];
            $service_list[$userapi_reply_info['rid']]['rid'] = $userapi_reply_info['rid'];
            $service_list[$userapi_reply_info['rid']]['id'] = empty($userapi_reply_info['id']) ? 0 : $userapi_reply_info['id'];
            $service_list[$userapi_reply_info['rid']]['name'] = empty($all_service[$url]['title']) ? '' : $all_service[$url]['title'];
            $service_list[$userapi_reply_info['rid']]['description'] = empty($all_service[$url]['description']) ? '' : $all_service[$url]['description'];
            $service_list[$userapi_reply_info['rid']]['switch'] = isset($rule_setting_select[$userapi_reply_info['rid']]) && $rule_setting_select[$userapi_reply_info['rid']] ? 'checked' : '';
        }
    }
    return $service_list;
}

/**
 * 添加常用服务返回新增id
 * @param $file
 * @return int
 */
function reply_insert_without_service($file) {
    $all_service = reply_predefined_service();
    $all_url = array_keys($all_service);
    if (!in_array($file, $all_url)) {
        return false;
    }
    $userapi_reply_info = table('userapi_reply')->getByApiurl($file);
    if (!empty($userapi_reply_info) && !empty($userapi_reply_info['rid'])) {
        return $userapi_reply_info['rid'];
    }

    $rule_info = array('uniacid' => 0, 'name' => $all_service[$file]['title'], 'module' => 'userapi', 'displayorder' => 255, 'status' => 1);
    table('rule')->fill($rule_info)->save();

    $rule_id = pdo_insertid();
    $rule_keyword_info = array('rid' => $rule_id, 'uniacid' => 0, 'module' => 'userapi', 'displayorder' => $rule_info['displayorder'], 'status' => $rule_info['status']);
    if (!empty($all_service[$file]['keywords'])) {
        foreach ($all_service[$file]['keywords'] as $keyword_info) {
            $rule_keyword_info['content'] = $keyword_info[1];
            $rule_keyword_info['type'] = $keyword_info[0];
            table('rule_keyword')->fill($rule_keyword_info)->save();
        }
    }

    $userapi_reply = array('rid' => $rule_id, 'description' => htmlspecialchars($all_service[$file]['description']), 'apiurl' => $file);
    table('userapi_reply')->fill($userapi_reply)->save();
    return $rule_id;
}

function reply_check_uni_default_keyword($uniacid = 0) {
    global $_W;
    $uniacid = empty($uniacid) ? $_W['uniacid'] : $uniacid;

    $default = uni_setting_load('default', $uniacid);
    if (!empty($default['default'])) {
        $rule = table('rule_keyword')->getByUniacidAndContent($uniacid, $default['default']);
        if (empty($rule)) {
            uni_setting_save('default', '');
            cache_delete(cache_system_key('unisetting', array('uniacid' => $uniacid)));
        }
    }
    return true;
}
