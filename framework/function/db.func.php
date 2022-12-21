<?php
/**
 * 函数版本兼容.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');
$GLOBALS['_W']['config']['db']['tablepre'] = empty($GLOBALS['_W']['config']['db']['tablepre']) ? $GLOBALS['_W']['config']['db']['master']['tablepre'] : $GLOBALS['_W']['config']['db']['tablepre'];
/**
 * 获得某个数据表的结构.
 *
 * @param DB     $db        数据库操作对象
 * @param string $tablename 表名
 *
 * @return array eg: $ret = array();
 *
 * <pre>
 * // SHOW FULL COLUMNS FROM 'tablename'
 * $ret['tablename'] = '表名'; //string
 * $ret['charset']   = '字符集'; //string
 * $ret['engine']	= '存储引擎'; //string
 * $ret['increment'] = '主键自增基数'; //int
 *
 * $ret['fields'] = array(); // 数据字段
 * $ret['fields']['field1'] = array();
 * $ret['fields']['field1']['name']	  = '字段名称'; //string
 * $ret['fields']['field1']['type']	  = '字段类型'; //string
 * $ret['fields']['field1']['length']	= '字段长度'; //string
 * $ret['fields']['field1']['null']	  = '是否可空'; //bool
 * $ret['fields']['field1']['default']   = '字段默认值'; //string
 * $ret['fields']['field1']['signed']	= '有符号, 无符号'; //bool
 * $ret['fields']['field1']['increment'] = '是否增字段'; //bool
 * $ret['fields']['field1']['comment']   = '备注信息'; //string
 * $ret['fields']['field2'] = ...
 *
 * // SHOW INDEX FROM 'tablename'
 * $ret['indexes'] = array(); // 数据索引项
 * $ret['indexes']['index1'] = array(); // 数据索引项
 * $ret['indexes']['index1']['name']   = '索引名称'; // string
 * $ret['indexes']['index1']['type']   = '索引类型'; // primary|index|unique
 * $ret['indexes']['index1']['fields'] = array('f1','f2',...) // '索引包含的字段'; array 每个元素为字段名
 * $ret['indexes']['index2'] = ...;
 * ...
 * </pre>
 */
function db_table_schema($db, $tablename = '') {
    $result = $db->fetch("SHOW TABLE STATUS LIKE '" . trim($db->tablename($tablename), '`') . "'");
    if (empty($result)) {
        return array();
    }
    $ret['tablename'] = $result['Name'];
    $ret['charset'] = $result['Collation'];
    $ret['engine'] = $result['Engine'];
    $ret['increment'] = $result['Auto_increment'];
    $result = $db->fetchall('SHOW FULL COLUMNS FROM ' . $db->tablename($tablename));
    foreach ($result as $value) {
        $temp = array();
        $type = explode(' ', $value['Type'], 2);
        $temp['name'] = $value['Field'];
        $pieces = explode('(', $type[0], 2);
        $temp['type'] = $pieces[0];
        $temp['length'] = rtrim($pieces[1], ')');
        $temp['null'] = 'NO' != $value['Null'];
        //暂时去掉默认值的对比
        //if(isset($value['Default'])) {
        //	$temp['default'] = $value['Default'];
        //}
        $temp['signed'] = empty($type[1]);
        $temp['increment'] = 'auto_increment' == $value['Extra'];
        $ret['fields'][$value['Field']] = $temp;
    }
    $result = $db->fetchall('SHOW INDEX FROM ' . $db->tablename($tablename));
    foreach ($result as $value) {
        $ret['indexes'][$value['Key_name']]['name'] = $value['Key_name'];
        $ret['indexes'][$value['Key_name']]['type'] = ('PRIMARY' == $value['Key_name']) ? 'primary' : (0 == $value['Non_unique'] ? 'unique' : 'index');
        $ret['indexes'][$value['Key_name']]['fields'][] = $value['Column_name'];
    }

    return $ret;
}

/**
 * 获得数据表的序列化结构.
 *
 * @param DB     $db     数据库操作对象
 * @param string $dbname 数据库名
 *
 * @return string $result 序列后的数据表结构
 */
function db_table_serialize($db, $dbname) {
    $tables = $db->fetchall('SHOW TABLES');
    if (empty($tables)) {
        return '';
    }
    $struct = array();
    foreach ($tables as $value) {
        $structs[] = db_table_schema($db, substr($value['Tables_in_' . $dbname], strpos($value['Tables_in_' . $dbname], '_') + 1));
    }

    return iserializer($structs);
}

function db_table_create_sql($schema) {
    $pieces = explode('_', $schema['charset']);
    $charset = $pieces[0];
    $engine = $schema['engine'];
    $schema['tablename'] = str_replace('ims_', $GLOBALS['_W']['config']['db']['tablepre'], $schema['tablename']);
    $sql = "CREATE TABLE IF NOT EXISTS `{$schema['tablename']}` (\n";
    foreach ($schema['fields'] as $value) {
        $piece = _db_build_field_sql($value);
        $sql .= "`{$value['name']}` {$piece},\n";
    }
    foreach ($schema['indexes'] as $value) {
        $fields = implode('`,`', $value['fields']);
        if ('index' == $value['type']) {
            $sql .= "KEY `{$value['name']}` (`{$fields}`),\n";
        }
        if ('unique' == $value['type']) {
            $sql .= "UNIQUE KEY `{$value['name']}` (`{$fields}`),\n";
        }
        if ('primary' == $value['type']) {
            $sql .= "PRIMARY KEY (`{$fields}`),\n";
        }
    }
    $sql = rtrim($sql);
    $sql = rtrim($sql, ',');

    $sql .= "\n) ENGINE=$engine DEFAULT CHARSET=$charset;\n\n";

    return $sql;
}

/**
 * 比较两个表结构.
 *
 * @param array $table1
 * @param array $table2
 *
 * @return 返回两个数据结构差异项. eg: $ret = array();
 *                                            <pre>
 *                                            $ret['diffs']['tablename'] = true; //如果表名不同, 记录此元素
 *                                            $ret['diffs']['charset'] = true; //如果字符集不同, 记录此元素
 *                                            $ret['diffs']['engine'] = true; //如果存储引擎不同, 记录此元素
 *                                            $ret['diffs']['increment'] = true; //如果自增基数不同, 记录此元素
 *
 * $ret['fields'] 字段差异
 * $ret['fields']['greater'] $table1中存在, $table2中不存在的字段
 * $ret['fields']['less'] $table1中不存在, $table2中存在的字段
 * $ret['fields']['diff'] $table1和$table2都存在, 但是定义不同的字段
 *
 * $ret['indexes'] 索引差异
 * $ret['indexes']['greater']  $table1中存在, $table2中不存在的索引
 * $ret['indexes']['less'] $table1中不存在, $table2中存在的索引
 * $ret['indexes']['diff'] $table1和$table2都存在, 但是定义不同的索引
 * </pre>
 */
function db_schema_compare($table1, $table2) {
    $table1['charset'] == $table2['charset'] ? '' : $ret['diffs']['charset'] = true;

    $fields1 = array_keys($table1['fields']);
    $fields2 = array_keys($table2['fields']);
    $diffs = array_diff($fields1, $fields2);
    if (!empty($diffs)) {
        $ret['fields']['greater'] = array_values($diffs);
    }
    $diffs = array_diff($fields2, $fields1);
    if (!empty($diffs)) {
        $ret['fields']['less'] = array_values($diffs);
    }
    $diffs = array();
    $intersects = array_intersect($fields1, $fields2);
    if (!empty($intersects)) {
        foreach ($intersects as $field) {
            //mysql8.0弃用了整数数据类型的宽度规范
            if (in_array($table2['fields'][$field]['type'], array('int', 'tinyint', 'smallint', 'bigint'))) {
                unset($table1['fields'][$field]['length']);
                unset($table2['fields'][$field]['length']);
            }
            if ($table1['fields'][$field] != $table2['fields'][$field]) {
                $diffs[] = $field;
            }
        }
    }
    if (!empty($diffs)) {
        $ret['fields']['diff'] = array_values($diffs);
    }

    $indexes1 = is_array($table1['indexes']) ? array_keys($table1['indexes']) : array();
    $indexes2 = is_array($table2['indexes']) ? array_keys($table2['indexes']) : array();
    $diffs = array_diff($indexes1, $indexes2);
    if (!empty($diffs)) {
        $ret['indexes']['greater'] = array_values($diffs);
    }
    $diffs = array_diff($indexes2, $indexes1);
    if (!empty($diffs)) {
        $ret['indexes']['less'] = array_values($diffs);
    }
    $diffs = array();
    $intersects = array_intersect($indexes1, $indexes2);
    if (!empty($intersects)) {
        foreach ($intersects as $index) {
            if ($table1['indexes'][$index] != $table2['indexes'][$index]) {
                $diffs[] = $index;
            }
        }
    }
    if (!empty($diffs)) {
        $ret['indexes']['diff'] = array_values($diffs);
    }

    return $ret;
}
/**
 * 创建修复两张表差异的SQL语句.
 *
 * @param string $schema1 表结构 需要修复的表
 * @param string $schema2 表结构 基准表
 * @param bool   $strict  使用严格模式, 严格模式将会把表2完全变成表1的结构, 否则将只处理表2种大于表1的内容(多出的字段和索引)
 *
 * @return array $sql 修复SQL语句组成的数组
 */
function db_table_fix_sql($schema1, $schema2, $strict = false) {
    if (empty($schema1)) {
        return array(db_table_create_sql($schema2));
    }
    $diff = $result = db_schema_compare($schema1, $schema2);
    if (!empty($diff['diffs']['tablename'])) {
        return array(db_table_create_sql($schema2));
    }
    $sqls = array();
    if (!empty($diff['diffs']['engine'])) {
        $sqls[] = "ALTER TABLE `{$schema1['tablename']}` ENGINE = {$schema2['engine']}";
    }

    if (!empty($diff['diffs']['charset'])) {
        $pieces = explode('_', $schema2['charset']);
        $charset = $pieces[0];
        $sqls[] = "ALTER TABLE `{$schema1['tablename']}` DEFAULT CHARSET = {$charset}";
    }

    if (!empty($diff['fields'])) {
        if (!empty($diff['fields']['less'])) {
            foreach ($diff['fields']['less'] as $fieldname) {
                $field = $schema2['fields'][$fieldname];
                $piece = _db_build_field_sql($field);
                if (!empty($field['rename']) && !empty($schema1['fields'][$field['rename']])) {
                    $sql = "ALTER TABLE `{$schema1['tablename']}` CHANGE `{$field['rename']}` `{$field['name']}` {$piece}";
                    unset($schema1['fields'][$field['rename']]);
                } else {
                    if ($field['position']) {
                        $pos = ' ' . $field['position'];
                    }
                    $sql = "ALTER TABLE `{$schema1['tablename']}` ADD `{$field['name']}` {$piece}{$pos}";
                }
                //如果此条SQL语句为自增，则需要先把其它自增字段去掉，并把此字段设置为主键
                $primary = array();
                $isincrement = array();
                if (strexists($sql, 'AUTO_INCREMENT')) {
                    $isincrement = $field;
                    $sql = str_replace('AUTO_INCREMENT', '', $sql);
                    foreach ($schema1['fields'] as $field) {
                        if (1 == $field['increment']) {
                            $primary = $field;
                            break;
                        }
                    }
                    if (!empty($primary)) {
                        $piece = _db_build_field_sql($primary);
                        if (!empty($piece)) {
                            $piece = str_replace('AUTO_INCREMENT', '', $piece);
                        }
                        $sqls[] = "ALTER TABLE `{$schema1['tablename']}` CHANGE `{$primary['name']}` `{$primary['name']}` {$piece}";
                    }
                }
                $sqls[] = $sql;
            }
        }
        if (!empty($diff['fields']['diff'])) {
            foreach ($diff['fields']['diff'] as $fieldname) {
                $field = $schema2['fields'][$fieldname];
                $piece = _db_build_field_sql($field);
                if (!empty($schema1['fields'][$fieldname])) {
                    $sqls[] = "ALTER TABLE `{$schema1['tablename']}` CHANGE `{$field['name']}` `{$field['name']}` {$piece}";
                }
            }
        }
        if ($strict && !empty($diff['fields']['greater'])) {
            foreach ($diff['fields']['greater'] as $fieldname) {
                if (!empty($schema1['fields'][$fieldname])) {
                    $sqls[] = "ALTER TABLE `{$schema1['tablename']}` DROP `{$fieldname}`";
                }
            }
        }
    }

    if (!empty($diff['indexes'])) {
        if (!empty($diff['indexes']['less'])) {
            foreach ($diff['indexes']['less'] as $indexname) {
                $index = $schema2['indexes'][$indexname];
                $piece = _db_build_index_sql($index);
                $sqls[] = "ALTER TABLE `{$schema1['tablename']}` ADD {$piece}";
            }
        }
        if (!empty($diff['indexes']['diff'])) {
            foreach ($diff['indexes']['diff'] as $indexname) {
                $index = $schema2['indexes'][$indexname];
                $piece = _db_build_index_sql($index);

                $sqls[] = "ALTER TABLE `{$schema1['tablename']}` DROP " . ('PRIMARY' == $indexname ? ' PRIMARY KEY ' : "INDEX {$indexname}") . ", ADD {$piece}";
            }
        }
        if ($strict && !empty($diff['indexes']['greater'])) {
            foreach ($diff['indexes']['greater'] as $indexname) {
                $sqls[] = "ALTER TABLE `{$schema1['tablename']}` DROP `{$indexname}`";
            }
        }
    }
    if (!empty($isincrement)) {
        $piece = _db_build_field_sql($isincrement);
        $sqls[] = "ALTER TABLE `{$schema1['tablename']}` CHANGE `{$isincrement['name']}` `{$isincrement['name']}` {$piece}";
    }

    return $sqls;
}

function _db_build_index_sql($index) {
    $piece = '';
    $fields = implode('`,`', $index['fields']);
    if ('index' == $index['type']) {
        $piece .= " INDEX `{$index['name']}` (`{$fields}`)";
    }
    if ('unique' == $index['type']) {
        $piece .= "UNIQUE `{$index['name']}` (`{$fields}`)";
    }
    if ('primary' == $index['type']) {
        $piece .= "PRIMARY KEY (`{$fields}`)";
    }

    return $piece;
}

function _db_build_field_sql($field) {
    if (!empty($field['length'])) {
        $length = "({$field['length']})";
    } else {
        $length = '';
    }
    if (false !== strpos(strtolower($field['type']), 'int') || in_array(strtolower($field['type']), array('decimal', 'float', 'dobule'))) {
        $signed = empty($field['signed']) ? ' unsigned' : '';
    } else {
        $signed = '';
    }
    if (empty($field['null'])) {
        $null = ' NOT NULL';
    } else {
        $null = '';
    }
    if (isset($field['default'])) {
        $default = " DEFAULT '" . $field['default'] . "'";
    } else {
        $default = '';
    }
    if ($field['increment']) {
        $increment = ' AUTO_INCREMENT';
    } else {
        $increment = '';
    }

    return "{$field['type']}{$length}{$signed}{$null}{$default}{$increment}";
}

function db_table_schemas($table) {
    $dump = "DROP TABLE IF EXISTS {$table};\n";
    $sql = "SHOW CREATE TABLE {$table}";
    $row = pdo_fetch($sql);
    $dump .= $row['Create Table'];
    $dump .= ";\n\n";

    return $dump;
}

function db_table_insert_sql($tablename, $start, $size) {
    $data = '';
    $tmp = '';
    $start = intval($start);
    $size = intval($size);
    $sql = "SELECT * FROM {$tablename} LIMIT {$start},{$size}";
    $result = pdo_fetchall($sql);
    if (!empty($result)) {
        foreach ($result as $row) {
            $tmp .= '(';
            foreach ($row as $k => $v) {
                $value = str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $v);
                $tmp .= "'" . $value . "',";
            }
            $tmp = rtrim($tmp, ',');
            $tmp .= "),\n";
        }
        $tmp = rtrim($tmp, ",\n");
        $data .= "INSERT INTO {$tablename} VALUES \n{$tmp};\n";
        $datas = array(
                'data' => $data,
                'result' => $result,
        );

        return $datas;
    } else {
        return false;
    }
}
