<?php
/**
 * 数据库操作类.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');
define('PDO_DEBUG', true);

class DB {
    protected $pdo;
    protected $cfg;
    protected $tablepre;
    protected $result;
    protected $statement;
    protected $errors = array();
    protected $link = array();
    protected $name = '';
    public function getPDO() {
        return $this->pdo;
    }

    public function __construct($name = 'master') {
        global $_W;
        $this->cfg = $_W['config']['db'];
        $this->name = $name;
        //unset掉敏感信息，一些非敏感信息保留
        unset($_W['config']['db']);
        $_W['config']['db'] = array(
            'tablepre' => $this->cfg['master']['tablepre'] ?: $this->cfg['tablepre'],
            'slave_status' => $this->cfg['slave_status']
        );
        $this->connect($name);
    }

    public function reConnect($errorInfo, $params) {
        if (in_array($errorInfo[1], array(1317, 2013))) {
            $this->pdo = null;
            $this->connect($this->name);
            $method = $params['method'];
            unset($params['method']);
            return call_user_func_array(array($this, $method), $params);
        }
        return false;
    }

    public function connect($name = 'master') {
        global $_W;
        if (is_array($name)) {
            $cfg = $name;
        } else {
            $cfg = $this->cfg[$name];
        }
        $this->tablepre = $cfg['tablepre'];
        if (empty($cfg)) {
            exit("The master database is not found, Please checking 'data/config.php'");
        }
        $dsn = "mysql:dbname={$cfg['database']};host={$cfg['host']};port={$cfg['port']};charset={$cfg['charset']}";
        $dbclass = '';
        $options = array();
        if (class_exists('PDO')) {
            if (extension_loaded('pdo_mysql') && in_array('mysql', PDO::getAvailableDrivers())) {
                $dbclass = 'PDO';
                $options = array(PDO::ATTR_PERSISTENT => $cfg['pconnect']);
            } else {
                if (!class_exists('_PDO')) {
                    load()->library('pdo');
                }
                $dbclass = '_PDO';
            }
        } else {
            load()->library('pdo');
            $dbclass = 'PDO';
        }

        try {
            $pdo = new $dbclass($dsn, $cfg['username'], $cfg['password'], $options);
        } catch (\Exception $e) {
            return error(-1, '数据库连接失败，请联系管理员处理');
        }
        //if(DEVELOPMENT && class_exists('\DebugBar\DataCollector\PDO\TraceablePDO')) {
        //	$pdo = new \DebugBar\DataCollector\PDO\TraceablePDO($pdo);
        //}
        $this->pdo = $pdo;
        //$this->pdo->setAttribute(pdo::ATTR_EMULATE_PREPARES, false);
        $sql = "SET NAMES '{$cfg['charset']}';";
        $this->pdo->exec($sql);
        $this->pdo->exec("SET sql_mode='';");
        if ('root' == $cfg['username'] && in_array($cfg['host'], array('localhost', '127.0.0.1'))) {
            $this->pdo->exec('SET GLOBAL max_allowed_packet = 2*1024*1024*10;');
        }
        if (is_string($name)) {
            $this->link[$name] = $this->pdo;
        }
        $this->logging($sql);
    }

    public function prepare($sql) {
        $sqlsafe = SqlPaser::checkquery($sql);
        if (is_error($sqlsafe)) {
            trigger_error($sqlsafe['message'], E_USER_ERROR);

            return false;
        }
        $statement = $this->pdo->prepare($sql);

        return $statement;
    }

    /**
     * 执行一条非查询语句.
     *
     * @param string          $sql
     * @param array or string $params
     *
     * @return mixed
     *               成功返回受影响的行数
     *               失败返回FALSE
     */
    public function query($sql, $params = array()) {
        $sqlsafe = SqlPaser::checkquery($sql);
        if (is_error($sqlsafe)) {
            trigger_error($sqlsafe['message'], E_USER_ERROR);

            return false;
        }
        $starttime = intval(microtime(true));
        if (empty($params)) {
            $result = $this->pdo->exec($sql);
            $error_info = $this->pdo->errorInfo();
            $this->logging($sql, array(), $this->pdo->errorInfo());
            if (in_array($error_info[1], array(1317, 2013))) {
                $reConnect = $this->reConnect($error_info, array(
                    'method' => __METHOD__,
                    'sql' => $sql,
                    'params' => $params,
                ));
                return empty($reConnect) ? false : $reConnect;
            }
            return $result;
        }
        $statement = $this->prepare($sql);
        $result = $statement->execute($params);

        $this->logging($sql, $params, $statement->errorInfo());

        $endtime = intval(microtime(true));
        $this->performance($sql, $endtime - $starttime);
        $error_info = $statement->errorInfo();
        if (in_array($error_info[1], array(1317, 2013))) {
            $reConnect = $this->reConnect($error_info, array(
                'method' => __METHOD__,
                'sql' => $sql,
                'params' => $params,
            ));
            return empty($reConnect) ? false : $reConnect;
        } else {
            return $statement->rowCount();
        }
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
    public function fetchcolumn($sql, $params = array(), $column = 0) {
        $starttime = intval(microtime(true));
        $statement = $this->prepare($sql);
        $result = $statement->execute($params);

        $this->logging($sql, $params, $statement->errorInfo());

        $endtime = intval(microtime(true));
        $this->performance($sql, $endtime - $starttime);
        $error_info = $statement->errorInfo();
        if (in_array($error_info[1], array(1317, 2013))) {
            $reConnect = $this->reConnect($error_info, array(
                'method' => __METHOD__,
                'sql' => $sql,
                'params' => $params,
                'column' => $column,
            ));
            return empty($reConnect) ? false : $reConnect;
        } else {
            $data = $statement->fetchColumn($column);

            return $data;
        }
    }

    /**
     * 执行SQL返回第一行.
     *
     * @param string $sql
     * @param array  $params
     *
     * @return mixed
     */
    public function fetch($sql, $params = array()) {
        $starttime = intval(microtime(true));
        $statement = $this->prepare($sql);
        $result = $statement->execute($params);

        $this->logging($sql, $params, $statement->errorInfo());

        $endtime = intval(microtime(true));
        $this->performance($sql, intval($endtime - $starttime));
        $error_info = $statement->errorInfo();
        if (in_array($error_info[1], array(1317, 2013))) {
            $reConnect = $this->reConnect($error_info, array(
                'method' => __METHOD__,
                'sql' => $sql,
                'params' => $params,
            ));
            return empty($reConnect) ? false : $reConnect;
        } else {
            $data = $statement->fetch(pdo::FETCH_ASSOC);

            return $data;
        }
    }

    /**
     * 执行SQL返回全部记录.
     *
     * @param string $sql
     * @param array  $params
     *
     * @return mixed
     */
    public function fetchall($sql, $params = array(), $keyfield = '') {
        $starttime = intval(microtime(true));
        $statement = $this->prepare($sql);
        $result = $statement->execute($params);

        $this->logging($sql, $params, $statement->errorInfo());

        $endtime = intval(microtime(true));
        $this->performance($sql, $endtime - $starttime);
        $error_info = $statement->errorInfo();
        if (in_array($error_info[1], array(1317, 2013))) {
            $reConnect = $this->reConnect($error_info, array(
                'method' => __METHOD__,
                'sql' => $sql,
                'params' => $params,
                'keyfield' => $keyfield,
            ));
            return empty($reConnect) ? false : $reConnect;
        } else {
            if (empty($keyfield)) {
                $result = $statement->fetchAll(pdo::FETCH_ASSOC);
            } else {
                $temp = $statement->fetchAll(pdo::FETCH_ASSOC);
                $result = array();
                if (!empty($temp)) {
                    foreach ($temp as $key => &$row) {
                        if (isset($row[$keyfield])) {
                            $result[$row[$keyfield]] = $row;
                        } else {
                            $result[] = $row;
                        }
                    }
                }
            }

            return $result;
        }
    }

    public function get($tablename, $params = array(), $fields = array(), $orderby = array()) {
        $select = SqlPaser::parseSelect($fields);
        $condition = SqlPaser::parseParameter($params, 'AND');
        $orderbysql = SqlPaser::parseOrderby($orderby);

        $sql = "{$select} FROM " . $this->tablename($tablename) . (!empty($condition['fields']) ? " WHERE {$condition['fields']}" : '') . " $orderbysql LIMIT 1";

        return $this->fetch($sql, $condition['params']);
    }

    public function getall($tablename, $params = array(), $fields = array(), $keyfield = '', $orderby = array(), $limit = array()) {
        $select = SqlPaser::parseSelect($fields);
        $condition = SqlPaser::parseParameter($params, 'AND');

        $limitsql = SqlPaser::parseLimit($limit);
        $orderbysql = SqlPaser::parseOrderby($orderby);

        $sql = "{$select} FROM " . $this->tablename($tablename) . (!empty($condition['fields']) ? " WHERE {$condition['fields']}" : '') . $orderbysql . $limitsql;

        return $this->fetchall($sql, $condition['params'], $keyfield);
    }

    public function getslice($tablename, $params = array(), $limit = array(), &$total = null, $fields = array(), $keyfield = '', $orderby = array()) {
        $select = SqlPaser::parseSelect($fields);
        $condition = SqlPaser::parseParameter($params, 'AND');
        $limitsql = SqlPaser::parseLimit($limit);

        if (!empty($orderby)) {
            if (is_array($orderby)) {
                $orderbysql = implode(',', $orderby);
            } else {
                $orderbysql = $orderby;
            }
        }
        $sql = "{$select} FROM " . $this->tablename($tablename) . (!empty($condition['fields']) ? " WHERE {$condition['fields']}" : '') . (!empty($orderbysql) ? " ORDER BY $orderbysql " : '') . $limitsql;
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($tablename) . (!empty($condition['fields']) ? " WHERE {$condition['fields']}" : ''), $condition['params']);

        return $this->fetchall($sql, $condition['params'], $keyfield);
    }

    public function getcolumn($tablename, $params = array(), $field = '') {
        $result = $this->get($tablename, $params, $field);
        if (!empty($result)) {
            if (strexists($field, '(')) {
                return array_shift($result);
            } else {
                return $result[$field];
            }
        } else {
            return false;
        }
    }

    /**
     * 更新记录.
     *
     * @param string $table
     * @param array  $data
     *                       要更新的数据数组
     *                       array(
     *                       '字段名' => '值'
     *                       )
     * @param array  $params
     *                       更新条件
     *                       array(
     *                       '字段名' => '值'
     *                       )
     * @param string $glue
     *                       可以为AND OR
     *
     * @return mixed
     */
    public function update($table, $data = array(), $params = array(), $glue = 'AND') {
        $fields = SqlPaser::parseParameter($data, ',');
        $condition = SqlPaser::parseParameter($params, $glue);
        $params = array_merge($fields['params'], $condition['params']);
        $sql = 'UPDATE ' . $this->tablename($table) . " SET {$fields['fields']}";
        $sql .= $condition['fields'] ? ' WHERE ' . $condition['fields'] : '';

        return $this->query($sql, $params);
    }

    /**
     * 更新记录.
     *
     * @param string $table
     * @param array  $data
     *                        要更新的数据数组
     *                        array(
     *                        '字段名' => '值'
     *                        )
     * @param bool   $replace
     *                        是否执行REPLACE INTO
     *                        默认为FALSE
     *
     * @return mixed
     */
    public function insert($table, $data = array(), $replace = false) {
        $cmd = $replace ? 'REPLACE INTO' : 'INSERT INTO';
        $condition = SqlPaser::parseParameter($data, ',');

        return $this->query("$cmd " . $this->tablename($table) . " SET {$condition['fields']}", $condition['params']);
    }

    /**
     * 返回lastInsertId.
     */
    public function insertid() {
        return $this->pdo->lastInsertId();
    }

    /**
     * 删除记录.
     *
     * @param string $table
     * @param array  $params
     *                       更新条件
     *                       array(
     *                       '字段名' => '值'
     *                       )
     * @param string $glue
     *                       可以为AND OR
     *
     * @return mixed
     */
    public function delete($table, $params = array(), $glue = 'AND') {
        $condition = SqlPaser::parseParameter($params, $glue);
        $sql = 'DELETE FROM ' . $this->tablename($table);
        $sql .= $condition['fields'] ? ' WHERE ' . $condition['fields'] : '';

        return $this->query($sql, $condition['params']);
    }

    /**
     * 检测一条记录是否存在.
     *
     * @param unknown $tablename
     * @param array   $params
     */
    public function exists($tablename, $params = array()) {
        $row = $this->get($tablename, $params);
        if (empty($row) || !is_array($row) || 0 == count($row)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param unknown $tablename
     * @param array   $params
     */
    public function count($tablename, $params = array(), $cachetime = 30) {
        $total = pdo_getcolumn($tablename, $params, 'count(*)');

        return intval($total);
    }

    /**
     * 启动一个事务，关闭自动提交.
     */
    public function begin() {
        $this->pdo->beginTransaction();
    }

    /**
     * 提交一个事务，恢复自动提交.
     *
     * @return boolean
     */
    public function commit() {
        $this->pdo->commit();
    }

    /**
     * 回滚一个事务，恢复自动提交.
     *
     * @return boolean
     */
    public function rollback() {
        $this->pdo->rollBack();
    }

    /**
     * 执行SQL文件.
     */
    public function run($sql, $stuff = 'ims_') {
        if (!isset($sql) || empty($sql)) {
            return;
        }

        $sql = str_replace("\r", "\n", str_replace(' ' . $stuff, ' ' . $this->tablepre, $sql));
        $sql = str_replace("\r", "\n", str_replace(' `' . $stuff, ' `' . $this->tablepre, $sql));
        $ret = array();
        $num = 0;
        $sql = preg_replace("/\;[ \f\t\v]+/", ';', $sql);
        foreach (explode(";\n", trim($sql)) as $query) {
            $ret[$num] = '';
            $queries = explode("\n", trim($query));
            foreach ($queries as $query) {
                $ret[$num] .= (isset($query[0]) && '#' == $query[0]) || (isset($query[1]) && isset($query[1]) && $query[0] . $query[1] == '--') ? '' : $query;
            }
            ++$num;
        }
        unset($sql);
        foreach ($ret as $query) {
            $query = trim($query);
            if ($query) {
                $this->query($query, array());
            }
        }

        return true;
    }

    /**
     * 查询字段是否存在
     * 成功返回TRUE，失败返回FALSE.
     *
     * @param string $tablename
     *                          查询表名
     * @param string $fieldname
     *                          查询字段名
     *
     * @return boolean
     */
    public function fieldexists($tablename, $fieldname) {
        if (!$this->tableexists($tablename)) {
            return false;
        }
        $fields = $this->fetchall("SHOW COLUMNS FROM " . $this->tablename($tablename));
        if (empty($fields)) {
            return false;
        }
        foreach ($fields as $field) {
            if ($fieldname === $field['Field']) {
                return true;
            }
        }
        return false;
    }

    /**
     * 查询字段类型是否匹配
     * 成功返回TRUE，失败返回FALSE，字段存在，但类型错误返回-1.
     *
     * @param string $tablename
     *                          查询表名
     * @param string $fieldname
     *                          查询字段名
     * @param string $datatype
     *                          查询字段类型
     * @param string $length
     *                          查询字段长度
     *
     * @return boolean
     */
    public function fieldmatch($tablename, $fieldname, $datatype = '', $length = '') {
        $datatype = strtolower($datatype);
        $field_info = $this->fetch('DESCRIBE ' . $this->tablename($tablename) . " `{$fieldname}`", array());
        if (empty($field_info)) {
            return false;
        }
        if (!empty($datatype)) {
            $find = strexists($field_info['Type'], '(');
            if (empty($find)) {
                $length = '';
            }
            if (!empty($length)) {
                $datatype .= ("({$length})");
            }

            return 0 === strpos($field_info['Type'], $datatype) ? true : -1;
        }

        return true;
    }

    /**
     * 查询索引是否存在
     * 成功返回TRUE，失败返回FALSE.
     *
     * @param string $tablename
     *                          查询表名
     * @param array  $indexname
     *                          查询索引名
     *
     * @return boolean
     */
    public function indexexists($tablename, $indexname) {
        if (!$this->tableexists($tablename)) {
            return false;
        }
        if (!empty($indexname)) {
            $indexs = $this->fetchall('SHOW INDEX FROM ' . $this->tablename($tablename), array(), '');
            if (!empty($indexs) && is_array($indexs)) {
                foreach ($indexs as $row) {
                    if ($row['Key_name'] == $indexname) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * 返回完整数据表名(加前缀)(返回是主库的数据表前缀+表明).
     *
     * @param string $table 表名
     * @param bool   $force 是否强制增加前缀，某些用户设置前缀会和表名有前部一样，导致无法添加前缀
     *
     * @return string
     */
    public function tablename($table) {
        return (0 === strpos($table, $this->tablepre) || 0 === strpos($table, 'ims_')) ? $table : "`{$this->tablepre}{$table}`";
    }

    /**
     * 获取pdo操作错误信息列表.
     *
     * @param bool  $output 是否要输出执行记录和执行错误信息
     * @param array $append 加入执行信息，如果此参数不为空则 $output 参数为 false
     *
     * @return array
     */
    public function debug($output = true, $append = array()) {
        if (!empty($append)) {
            $output = false;
            array_push($this->errors, $append);
        }
        if ($output) {
            print_r($this->errors);
        } else {
            if (!empty($append['error'][1])) {
                $traces = debug_backtrace();
                $ts = '';
                foreach ($traces as $trace) {
                    $trace['file'] = str_replace('\\', '/', $trace['file']);
                    $trace['file'] = str_replace(IA_ROOT, '', $trace['file']);
                    $ts .= "file: {$trace['file']}; line: {$trace['line']}; <br />";
                }
                $params = var_export($append['params'], true);
                trigger_error("SQL: <br/>{$append['sql']}<hr/>Params: <br/>{$params}<hr/>SQL Error: <br/>{$append['error'][2]}<hr/>Traces: <br/>{$ts}", E_USER_WARNING);
            }
        }

        return $this->errors;
    }

    private function logging($sql, $params = array(), $message = '') {
        if (PDO_DEBUG) {
            $info = array();
            $info['sql'] = $sql;
            $info['params'] = $params;
            $info['error'] = empty($message) ? $this->pdo->errorInfo() : $message;
            $this->debug(false, $info);
        }

        return true;
    }

    /**
     * 判断某个数据表是否存在.
     *
     * @param string $table 表名（不加表前缀）
     *
     * @return bool
     */
    public function tableexists($table) {
        if (!empty($table)) {
            $real_table = preg_match('/[a-zA-Z0-9_]{' . strlen($table) . '}/', $table);
            if (1 !== $real_table) {
                return false;
            }
            $tablename = (0 === strpos($table, $this->tablepre)) ? ($table) : ($this->tablepre . $table);
            $data = $this->fetch("SHOW TABLES LIKE '{$tablename}'", array());
            if (!empty($data)) {
                $data = array_values($data);
                if (in_array($tablename, $data)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function performance($sql, $runtime = 0) {
        global $_W;
        if (0 == $runtime) {
            return false;
        }
        if (strexists($sql, 'core_performance')) {
            return false;
        }
        //将超时SQL语句存入数据库
        if (empty($_W['config']['setting']['maxtimesql'])) {
            $_W['config']['setting']['maxtimesql'] = 5;
        }
        if ($runtime > $_W['config']['setting']['maxtimesql'] && isset($_W['setting']['copyright']['log_status']) && $_W['setting']['copyright']['log_status'] == STATUS_ON) {
            $sqldata = array(
                'type' => '2',
                'runtime' => $runtime,
                'runurl' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                'runsql' => $sql,
                'createtime' => time(),
            );
            $this->insert('core_performance', $sqldata);
        }

        return true;
    }
}

/**
 * 格式化SQL语句.
 */
class SqlPaser {
    private static $checkcmd = array('SELECT', 'UPDATE', 'INSERT', 'REPLAC', 'DELETE');
    private static $disable = array(
        'function' => array('load_file', 'floor', 'hex', 'substring', 'if', 'ord', 'char', 'benchmark', 'reverse', 'strcmp', 'datadir', 'updatexml', 'extractvalue', 'name_const', 'multipoint', 'database', 'user'),
        'action' => array('@', 'intooutfile', 'intodumpfile', 'unionselect', 'uniondistinct', 'information_schema', 'current_user', 'current_date'),
        'note' => array('/*', '*/', '#', '--'),
    );

    public static function checkquery($sql) {
        $cmd = strtoupper(substr(trim($sql), 0, 6));
        if (in_array($cmd, self::$checkcmd)) {
            $mark = $clean = '';
            $sql = str_replace(array('\\\\', '\\\'', '\\"', '\'\''), '', $sql);
            if (false === strpos($sql, '/') && false === strpos($sql, '#') && false === strpos($sql, '-- ') && false === strpos($sql, '@') && false === strpos($sql, '`')) {
                $cleansql = preg_replace("/'(.+?)'/s", '', $sql);
            } else {
                $cleansql = self::stripSafeChar($sql);
            }

            $clean_function_sql = preg_replace("/\s+/", '', strtolower($cleansql));
            if (is_array(self::$disable['function'])) {
                foreach (self::$disable['function'] as $fun) {
                    if (false !== strpos($clean_function_sql, $fun . '(')) {
                        return error(1, 'SQL中包含禁用函数 - ' . $fun);
                    }
                }
            }
            
            $cleansql = preg_replace("/[^a-z0-9_\-\(\)#\*\/\"]+/is", '', strtolower($cleansql));
            if (is_array(self::$disable['action'])) {
                foreach (self::$disable['action'] as $action) {
                    if (false !== strpos($cleansql, $action)) {
                        return error(2, 'SQL中包含禁用操作符 - ' . $action);
                    }
                }
            }

            if (is_array(self::$disable['note'])) {
                foreach (self::$disable['note'] as $note) {
                    if (false !== strpos($cleansql, $note)) {
                        return error(3, 'SQL中包含注释信息');
                    }
                }
            }
        } elseif ('/*' === substr($cmd, 0, 2)) {
            return error(3, 'SQL中包含注释信息');
        }
    }

    private static function stripSafeChar($sql) {
        $len = strlen($sql);
        $mark = $clean = '';
        for ($i = 0; $i < $len; ++$i) {
            $str = $sql[$i];
            switch ($str) {
                case '\'':
                    if (!$mark) {
                        $mark = '\'';
                        $clean .= $str;
                    } elseif ('\'' == $mark) {
                        $mark = '';
                    }
                    break;
                case '/':
                    if (empty($mark) && '*' == $sql[$i + 1]) {
                        $mark = '/*';
                        $clean .= $mark;
                        ++$i;
                    } elseif ('/*' == $mark && '*' == $sql[$i - 1]) {
                        $mark = '';
                        $clean .= '*';
                    }
                    break;
                case '#':
                    if (empty($mark)) {
                        $mark = $str;
                        $clean .= $str;
                    }
                    break;
                case "\n":
                    if ('#' == $mark || '--' == $mark) {
                        $mark = '';
                    }
                    break;
                case '-':
                    if (empty($mark) && '-- ' == substr($sql, $i, 3)) {
                        $mark = '-- ';
                        $clean .= $mark;
                    }
                    break;
                default:
                    break;
            }
            $clean .= $mark ? '' : $str;
        }

        return $clean;
    }

    /**
     * 将数组格式化为具体的字符串
     * 增加支持 大于 小于, 不等于, not in, +=, -=等操作符.
     *
     * @param array  $params
     *                       要格式化的数组
     * @param string $glue
     *                       字符串分隔符
     *
     * @return array
     *               array['fields']是格式化后的字符串
     */
    public static function parseParameter($params, $glue = ',', $alias = '') {
        $result = array('fields' => ' 1 ', 'params' => array());
        $split = '';
        $suffix = '';
        $allow_operator = array('>', '<', '<>', '!=', '>=', '<=', '+=', '-=', 'LIKE', 'like');
        if (in_array(strtolower($glue), array('and', 'or'))) {
            $suffix = '__';
        }
        if (!is_array($params)) {
            $result['fields'] = $params;

            return $result;
        }
        if (is_array($params)) {
            $result['fields'] = '';
            foreach ($params as $fields => $value) {
                //update或是insert语句，值为null时按空处理，仅当值为NULL时，才按 IS null 处理
                if (',' == $glue) {
                    $value = null === $value ? '' : $value;
                }
                $operator = '';
                if (false !== strpos($fields, ' ')) {
                    list($fields, $operator) = explode(' ', $fields, 2);
                    if (!in_array($operator, $allow_operator)) {
                        $operator = '';
                    }
                }
                if (empty($operator)) {
                    $fields = trim($fields);
                    if (is_array($value) && !empty($value)) {
                        $operator = 'IN';
                    } elseif ('NULL' === $value) {
                        $operator = 'IS';
                    } else {
                        $operator = '=';
                    }
                } elseif ('+=' == $operator) {
                    $operator = " = `$fields` + ";
                } elseif ('-=' == $operator) {
                    $operator = " = `$fields` - ";
                } elseif ('!=' == $operator || '<>' == $operator) {
                    //如果是数组不等于情况，则转换为NOT IN
                    if (is_array($value) && !empty($value)) {
                        $operator = 'NOT IN';
                    } elseif ('NULL' === $value) {
                        $operator = 'IS NOT';
                    }
                }

                //当条件为having时，可以使用聚合函数
                $select_fields = self::parseFieldAlias($fields, $alias);
                if (is_array($value) && !empty($value)) {
                    $insql = array();
                    //忽略数组的键值，防止SQL注入
                    $value = array_values($value);
                    foreach ($value as $v) {
                        $placeholder = self::parsePlaceholder($fields, $suffix);
                        $insql[] = $placeholder;
                        $result['params'][$placeholder] = is_null($v) ? '' : $v;
                    }
                    $result['fields'] .= $split . "$select_fields {$operator} (" . implode(',', $insql) . ')';
                    $split = ' ' . $glue . ' ';
                } else {
                    $placeholder = self::parsePlaceholder($fields, $suffix);
                    $result['fields'] .= $split . "$select_fields {$operator} " . ('NULL' === $value ? 'NULL' : $placeholder);
                    $split = ' ' . $glue . ' ';
                    if ('NULL' !== $value) {
                        $result['params'][$placeholder] = is_array($value) ? '' : $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 处理字段占位符.
     *
     * @param string $field
     * @param string $suffix
     */
    private static function parsePlaceholder($field, $suffix = '') {
        static $params_index = 0;
        ++$params_index;

        $illegal_str = array('(', ')', ',', '.', '*');
        $placeholder = ":{$suffix}" . str_replace($illegal_str, '_', $field) . "_{$params_index}";

        return $placeholder;
    }

    private static function parseFieldAlias($field, $alias = '') {
        if (strexists($field, '.') || strexists($field, '*')) {
            return $field;
        }
        if (strexists($field, '(')) {
            $select_fields = str_replace(array('(', ')'), array('(' . (!empty($alias) ? "`{$alias}`." : '') . '`',  '`)'), $field);
        } else {
            $select_fields = (!empty($alias) ? "`{$alias}`." : '') . "`$field`";
        }

        return $select_fields;
    }

    /**
     * 格式化select字段.
     *
     * @param array  $field 字段
     * @param string $alias 表别名
     */
    public static function parseSelect($field = array(), $alias = '') {
        if (empty($field) || '*' == $field) {
            return ' SELECT *';
        }
        if (!is_array($field)) {
            $field = array($field);
        }
        $select = array();
        $index = 0;
        foreach ($field as $field_row) {
            if (strexists($field_row, '*')) {
                if (!strexists(strtolower($field_row), 'as')) {
                    //此代码暂时注释，否则会造成 * AS 0 的问题，忘了是为什么要加
                    //$field_row .= " AS '{$index}'";
                }
            } elseif (strexists(strtolower($field_row), 'select')) {
                //当前可能包含子查询，但不推荐此写法
                if ('(' != $field_row[0]) {
                    $field_row = "($field_row) AS '{$index}'";
                }
            } elseif (strexists($field_row, '(')) {
                $field_row = str_replace(array('(', ')'), array('(' . (!empty($alias) ? "`{$alias}`." : '') . '`',  '`)'), $field_row);
                //如果聚合函数没有指定AS字段，则添加当前索引为AS
                if (!strexists(strtolower($field_row), 'as')) {
                    $field_row .= " AS '{$index}'";
                }
            } else {
                $field_row = self::parseFieldAlias($field_row, $alias);
            }
            $select[] = $field_row;
            ++$index;
        }

        return ' SELECT ' . implode(',', $select);
    }

    public static function parseLimit($limit, $inpage = true) {
        $limitsql = '';
        if (empty($limit)) {
            return $limitsql;
        }
        if (is_array($limit)) {
            //兼容第一个值为0的写法
            if (empty($limit[0]) && !empty($limit[1])) {
                $limitsql = ' LIMIT 0, ' . $limit[1];
            } else {
                $limit[0] = max(intval($limit[0]), 1);
                !empty($limit[1]) && $limit[1] = max(intval($limit[1]), 1);
                if (empty($limit[0]) && empty($limit[1])) {
                    $limitsql = '';
                } elseif (!empty($limit[0]) && empty($limit[1])) {
                    $limitsql = ' LIMIT ' . $limit[0];
                } else {
                    $limitsql = ' LIMIT ' . ($inpage ? ($limit[0] - 1) * $limit[1] : $limit[0]) . ', ' . $limit[1];
                }
            }
        } else {
            $limit = trim($limit);
            if (preg_match('/^(?:limit)?[\s,0-9]+$/i', $limit)) {
                $limitsql = strexists(strtoupper($limit), 'LIMIT') ? " $limit " : " LIMIT $limit";
            }
        }

        return $limitsql;
    }

    public static function parseOrderby($orderby, $alias = '') {
        $orderbysql = '';
        if (empty($orderby)) {
            return $orderbysql;
        }
        if (!is_array($orderby)) {
            $orderby = explode(',', $orderby);
        }
        foreach ($orderby as $i => &$row) {
            if (strtoupper($row) == 'RAND()') {
                $row = strtoupper($row);
            } else {
                $row = strtolower($row);
                $orderbydata = explode(' ', $row);
                $field = empty($orderbydata[0]) ? '' : $orderbydata[0];
                $orderbyrule = empty($orderbydata[1]) ? '' : $orderbydata[1];

                if ('asc' != $orderbyrule && 'desc' != $orderbyrule) {
                    unset($orderby[$i]);
                }
                $field = self::parseFieldAlias($field, $alias);
                $row = "{$field} {$orderbyrule}";
            }
        }
        $orderbysql = implode(',', $orderby);
        return !empty($orderbysql) ? " ORDER BY $orderbysql " : '';
    }

    public static function parseGroupby($statement, $alias = '') {
        if (empty($statement)) {
            return $statement;
        }
        if (!is_array($statement)) {
            $statement = explode(',', $statement);
        }
        foreach ($statement as $i => &$row) {
            $row = self::parseFieldAlias($row, $alias);
            if (strexists($row, ' ')) {
                unset($statement[$i]);
            }
        }
        $statementsql = implode(', ', $statement);

        return !empty($statementsql) ? " GROUP BY $statementsql " : '';
    }
}
