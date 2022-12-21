<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$.
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 获取  DB 的单例.
 *
 * @return DB
 */
function pdo() {
    global $_W;
    static $db;
    if (empty($db)) {
        if ($_W['config']['db']['slave_status'] == true && !empty($_W['config']['db']['slave'])) {
            load()->classs('slave.db');
            $db = new SlaveDb('master');
        } else {
            load()->classs('db');
            if (empty($_W['config']['db']['master'])) {
                $_W['config']['db']['master'] = $GLOBALS['_W']['config']['db'];
            }
            $db = new DB('master');
        }
    }

    return $db;
}

/**
 * 返回一个查询构造器.
 *
 * @return Query
 */
function pdos($table = '') {
    return load()->singleton('Query');
}

/**
 * 执行一条非查询语句.
 *
 * @param string $sql
 * @param array  $params
 *
 * @return mixed 成功返回受影响的行数,失败返回FALSE
 */
function pdo_query($sql, $params = array()) {
    return pdo()->query($sql, $params);
}

/**
 * 执行SQL返回第一个字段.
 *
 * @param string $sql
 * @param array  $params
 * @param int    $column 返回查询结果的某列，默认为第一列
 *
 * @return mixed
 */
function pdo_fetchcolumn($sql, $params = array(), $column = 0) {
    return pdo()->fetchcolumn($sql, $params, $column);
}
/**
 * 执行SQL返回第一行.
 *
 * @param string $sql
 * @param array  $params
 *
 * @return mixed
 */
function pdo_fetch($sql, $params = array()) {
    return pdo()->fetch($sql, $params);
}
/**
 * 执行SQL返回全部记录.
 *
 * @param string $sql
 * @param array  $params
 * @param string $keyfield 将该字段的值作为结果索引
 *
 * @return mixed
 */
function pdo_fetchall($sql, $params = array(), $keyfield = '') {
    return pdo()->fetchall($sql, $params, $keyfield);
}

/**
 * 只能查询单条记录, 查询条件为 AND 的情况.
 *
 * @param string $tablename
 * @param array  $condition 查询条件
 * @param array  $fields
 *
 * @return string|Ambigous <mixed, boolean>
 */
function pdo_get($tablename, $condition = array(), $fields = array()) {
    return pdo()->get($tablename, $condition, $fields);
}
/**
 * 获取全部记录, 查询条件为 AND 的情况.
 *
 * @param string $tablename
 * @param array  $condition 查询条件
 * @param array  $fields    获取字段名
 * @param string $keyfield
 *
 * @return Ambigous <mixed, boolean, multitype:unknown >
 */
function pdo_getall($tablename, $condition = array(), $fields = array(), $keyfield = '', $orderby = array(), $limit = array()) {
    return pdo()->getall($tablename, $condition, $fields, $keyfield, $orderby, $limit);
}
/**
 * 获取多条记录, 查询条件为 AND 的情况.
 *
 * @param string           $tablename
 * @param array            $params    查询条件
 * @param array|int|string $limit     分页，array(当前页, 每页条页)|直接string
 * @param reference int 数据总条数
 * @param array  $fields   获取字段名
 * @param string $keyfield
 *
 * @return Ambigous <mixed, boolean, multitype:unknown >
 */
function pdo_getslice($tablename, $condition = array(), $limit = array(), &$total = null, $fields = array(), $keyfield = '', $orderby = array()) {
    return pdo()->getslice($tablename, $condition, $limit, $total, $fields, $keyfield, $orderby);
}

function pdo_getcolumn($tablename, $condition = array(), $field = '') {
    return pdo()->getcolumn($tablename, $condition, $field);
}

/**
 * 返回满足条件的记录是否存在.
 *
 * @param string $tablename
 * @param array  $condition
 */
function pdo_exists($tablename, $condition = array()) {
    return pdo()->exists($tablename, $condition);
}

/**
 * 返回满足条件的记录数.
 *
 * @param string $tablename
 * @param array  $condition
 * @param number $cachetime 缓存时间，由于count操作过于消耗资源，故增加缓存优化
 */
function pdo_count($tablename, $condition = array(), $cachetime = 15) {
    return pdo()->count($tablename, $condition, $cachetime);
}

/**
 * 更新记录.
 *
 * @param string $table  数据表名
 * @param array  $data   更新记录
 * @param array  $params 更新条件
 * @param string $glue   条件类型 可以为AND OR
 *
 * @return mixed
 */
function pdo_update($table, $data = array(), $params = array(), $glue = 'AND') {
    return pdo()->update($table, $data, $params, $glue);
}

/**
 * 添加或更新纪录.
 *
 * @param string $table   数据表名
 * @param array  $data    插入数据
 * @param bool   $replace 是否执行REPLACE INTO
 *
 * @return mixed
 */
function pdo_insert($table, $data = array(), $replace = false) {
    return pdo()->insert($table, $data, $replace);
}

/**
 * 删除记录.
 *
 * @param string $table  数据表名
 * @param array  $params 参数列表
 * @param string $glue   条件类型 可以为AND OR
 *
 * @return mixed
 */
function pdo_delete($table, $params = array(), $glue = 'AND') {
    return pdo()->delete($table, $params, $glue);
}

/**
 * 获取上一步 INSERT 操作产生的 ID.
 *
 * @return int
 */
function pdo_insertid() {
    return pdo()->insertid();
}

/**
 * 启动一个事务，关闭自动提交.
 *
 * @return boolean
 */
function pdo_begin() {
    pdo()->begin();
}

/**
 * 提交一个事务，恢复自动提交.
 *
 * @return boolean
 */
function pdo_commit() {
    pdo()->commit();
}

/**
 * 回滚一个事务，恢复自动提交.
 *
 * @return boolean
 */
function pdo_rollback() {
    pdo()->rollBack();
}

/**
 * 获取pdo操作错误信息列表.
 *
 * @param bool  $output 是否要输出执行记录和执行错误信息
 * @param array $append 加入执行信息，如果此参数不为空则 $output 参数为 false
 *
 * @return array
 */
function pdo_debug($output = true, $append = array()) {
    return pdo()->debug($output, $append);
}
/**
 * 执行 SQL 语句.
 *
 * @param string $sql SQL语句
 */
function pdo_run($sql) {
    return pdo()->run($sql);
}

/**
 * 查询字段是否存在
 * 成功返回TRUE，失败返回FALSE.
 *
 * @param string $tablename 查询表名
 * @param string $fieldname 查询字段名
 *
 * @return boolean
 */
function pdo_fieldexists($tablename, $fieldname = '') {
    return pdo()->fieldexists($tablename, $fieldname);
}

function pdo_fieldmatch($tablename, $fieldname, $datatype = '', $length = '') {
    return pdo()->fieldmatch($tablename, $fieldname, $datatype, $length);
}
/**
 * 查询索引是否存在
 * 成功返回TRUE，失败返回FALSE.
 *
 * @param string $tablename 查询表名
 * @param string $indexname 查询索引名
 *
 * @return boolean
 */
function pdo_indexexists($tablename, $indexname = '') {
    return pdo()->indexexists($tablename, $indexname);
}

/**
 * 获取所有字段名称.
 *
 * @param string $tablename 数据表名
 *
 * @return array
 */
function pdo_fetchallfields($tablename) {
    $fields = pdo_fetchall("DESCRIBE {$tablename}", array(), 'Field');
    $fields = array_keys($fields);

    return $fields;
}

/**
 * 检测数据表是否存在.
 *
 * @param string $tablename 数据表名
 *
 * @return boolean
 */
function pdo_tableexists($tablename) {
    return pdo()->tableexists($tablename);
}
