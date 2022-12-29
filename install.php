<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.w7.cc/ for more details.
 */
ini_set('display_errors', 0);
error_reporting(0);
set_time_limit(0);

ob_start();
define('IA_INSTALL_ROOT', str_replace("\\", '/', dirname(__FILE__)));
define('INSTALL_VERSION', 'notapp');
define('ERROR_LOG_FILE', './data/logs/error_log.php');
set_error_handler("handleError");

$actions = array('environment', 'install', 'login');
$action = !empty($_GET['step']) ? $_GET['step'] : '';
$action = in_array($action, $actions) ? $action : '';

$is_https = $_SERVER['SERVER_PORT'] == 443 ||
(!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ||
!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https' ||
!empty($_SERVER['HTTP_X_CLIENT_SCHEME']) && strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) == 'https' ||
!empty($_SERVER['HTTP_X_CLIENT_PROTO']) && strtolower($_SERVER['HTTP_X_CLIENT_PROTO']) == 'https'
    ? true : false;
$sitepath = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
$sitepath = str_replace('/install.php', '', $sitepath);
$siteroot = htmlspecialchars(($is_https ? 'https://' : 'http://') . (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $sitepath);

if ($action == 'environment') {
    $server['upload'] = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknow';
    $server['upload'] = strtolower($server['upload']);
    if ($server['upload'] == 'unknow' || !strstr($server['upload'], 'm')) {
        $ret['upload']['failed'] = true;
        $ret['upload']['name'] = '上传限制';
        $ret['upload']['result'] = $server['upload'];
    }
    if (version_compare(PHP_VERSION, '7.2.0') == -1) {
        $ret['version']['failed'] = true;
        $ret['version']['name'] = 'PHP版本';
        $ret['version']['result'] = PHP_VERSION . '（最低要求7.2.0）';
    }
    if (version_compare(PHP_VERSION, '7.0.0') == -1 && version_compare(PHP_VERSION, '5.6.0') >= 0) {
        $ret['always_populate_raw_post_data']['failed'] = @ini_get('always_populate_raw_post_data') != '-1';
        $ret['always_populate_raw_post_data']['name'] = 'always_populate_raw_post_data配置';
        $ret['always_populate_raw_post_data']['result'] = @ini_get('always_populate_raw_post_data');
        $ret['always_populate_raw_post_data']['handle'] = 'https://market.w7.cc/IndependentEngine';
    }
    $ret['fopen']['ok'] = @ini_get('allow_url_fopen') && function_exists('fsockopen');
    if (!$ret['fopen']['ok']) {
        $ret['fopen']['failed'] = true;
        $ret['fopen']['name'] = 'fopen';
        $ret['fopen']['result'] = '不支持fopen';
    }
    if (!$is_https) {
        $ret['https']['failed'] = true;
        $ret['https']['name'] = '是否支持https';
        $ret['https']['result'] = '不支持';
    }
    $ret['dom']['ok'] = class_exists('DOMDocument');
    if (!$ret['dom']['ok']) {
        $ret['dom']['failed'] = true;
        $ret['dom']['name'] = 'DOMDocument';
        $ret['dom']['result'] = '没有启用DOMDocument';
    }
    
    $ret['session']['ok'] = ini_get('session.auto_start');
    if (!empty($ret['session']['ok']) && strtolower($ret['session']['ok']) == 'on') {
        $ret['session']['failed'] = true;
        $ret['session']['name'] = 'session.auto_start开启';
        $ret['session']['result'] = '系统session.auto_start开启';
    }
    
    $ret['asp_tags']['ok'] = ini_get('asp_tags');
    if (!empty($ret['asp_tags']['ok']) && strtolower($ret['asp_tags']['ok']) == 'on') {
        $ret['asp_tags']['failed'] = true;
        $ret['asp_tags']['name'] = 'asp_tags';
        $ret['asp_tags']['result'] = 'asp_tags开启状态';
    }
    
    $ret['root']['ok'] = local_writeable(IA_INSTALL_ROOT);
    if (!$ret['root']['ok']) {
        $ret['root']['failed'] = true;
        $ret['root']['name'] = '本地目录写入';
        $ret['root']['result'] = '本地目录无法写入';
    }
    $ret['data']['ok'] = local_writeable(IA_INSTALL_ROOT . '/data');
    if (!$ret['data']['ok']) {
        $ret['data']['failed'] = true;
        $ret['data']['name'] = 'data目录写入';
        $ret['data']['result'] = 'data目录无法写入';
    }
    
    foreach (we7_need_extension() as $extension) {
        $if_ok = extension_loaded($extension);
        if (!$if_ok) {
            $ret[$extension]['failed'] = true;
            $ret[$extension]['name'] = $extension . '扩展';
            $ret[$extension]['result'] = '不支持' . $extension;
        }
    }
    
    $result = array();
    foreach ($ret as $key => $value) {
        if (version_compare(PHP_VERSION, '7.0.0') >= 0 && in_array($key, array('mcrypt', 'always_populate_raw_post_data'))) {
            continue;
        }
        if (!empty($value['failed'])) {
            $value['handle'] = !empty($value['handle']) ? $value['handle'] : 'https://market.w7.cc/IndependentEngine';
            $result[] = $value;
        }
    }
    if (empty($result)) {
        exit(we7_error(0, 'success'));
    } else {
        exit(we7_error(434, $result));
    }
}
if ($action == 'install') {
    //1.数据库
    if (!file_exists(IA_INSTALL_ROOT . '/data/db.lock')) {
        $database_result = we7_db();
        if ($database_result !== true) {
            exit(we7_error(420, $database_result));
        }
        touch(IA_INSTALL_ROOT . '/data/db.lock');
    }
    //2.初始化
    if (!file_exists(IA_INSTALL_ROOT . '/data/install.lock')) {
        we7_finish();
        @unlink(IA_INSTALL_ROOT . '/data/logs/data.json');
    }

    touch(IA_INSTALL_ROOT . '/data/install.lock');
    exit(we7_error(0));
}

if ($action == 'login') {
    @unlink(IA_INSTALL_ROOT . '/data/db.lock');
    @unlink(IA_INSTALL_ROOT . '/data/logs/error_log.php');
    @unlink(IA_INSTALL_ROOT . '/data/logs/install-' . date('Ymd') . '.php');
    exit(we7_error(0));
}

function handleError($code, $description, $file = null, $line = null) {
    list($error, $log) = map_error_code($code);
    $data = array(
        'date' => date('Y-m-d H:i:s', time()),
        'level' => $log,
        'code' => $code,
        'error' => $error,
        'description' => $description,
        'file' => $file,
        'line' => $line,
        'message' => $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']'
    );
    return file_log($data);
}

function file_log($logData, $fileName = ERROR_LOG_FILE) {
    if (!is_dir('data/logs')) {
        local_mkdirs('data/logs');
    }
    $fh = fopen($fileName, 'a+');
    if (is_array($logData)) {
        $logData = print_r($logData, 1);
    }
    $logData = '<?php exit;?>' . PHP_EOL . $logData;
    $status = fwrite($fh, $logData);
    fclose($fh);
    return (bool)$status;
}

function map_error_code($code) {
    $error = $log = null;
    switch ($code) {
        case E_PARSE:
        case E_ERROR:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            $error = 'Fatal Error';
            $log = LOG_ERR;
            break;
        case E_WARNING:
        case E_USER_WARNING:
        case E_COMPILE_WARNING:
        case E_RECOVERABLE_ERROR:
            $error = 'Warning';
            $log = LOG_WARNING;
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $error = 'Notice';
            $log = LOG_NOTICE;
            break;
        case E_STRICT:
            $error = 'Strict';
            $log = LOG_NOTICE;
            break;
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            $error = 'Deprecated';
            $log = LOG_NOTICE;
            break;
        default:
            break;
    }
    return array($error, $log);
}

function local_writeable($dir) {
    $writeable = 0;
    if (!is_dir($dir)) {
        @mkdir($dir, 0777);
    }
    if (is_dir($dir)) {
        if ($fp = fopen("$dir/test.txt", 'w')) {
            fclose($fp);
            unlink("$dir/test.txt");
            $writeable = 1;
        } else {
            $writeable = 0;
        }
    }
    return $writeable;
}

function local_mkdirs($path) {
    if (!is_dir($path)) {
        local_mkdirs(dirname($path));
        mkdir($path);
    }
    return is_dir($path);
}

function local_run($sql, $link, $db) {
    if (!isset($sql) || empty($sql)) {
        return;
    }
    
    $sql = str_replace("\r", "\n", str_replace(' ims_', ' ' . $db['prefix'], $sql));
    $sql = str_replace("\r", "\n", str_replace(' `ims_', ' `' . $db['prefix'], $sql));
    $ret = array();
    $num = 0;
    foreach (explode(";\n", trim($sql)) as $query) {
        $ret[$num] = '';
        $queries = explode("\n", trim($query));
        foreach ($queries as $query) {
            $ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0] . $query[1] == '--') ? '' : $query;
        }
        $num++;
    }
    unset($sql);
    foreach ($ret as $query) {
        $query = trim($query);
        if ($query) {
            $link->exec($query);
            if ($link->errorCode() != '00000') {
                $errorInfo = $link->errorInfo();
                trigger_error($errorInfo[0] . ": " . $errorInfo[2], E_USER_WARNING);
                exit($query);
            }
        }
    }
}

function local_create_sql($schema, $local_create_sql) {
    $pieces = explode('_', $schema['charset']);
    $charset = $pieces[0];
    $engine = $local_create_sql ? $schema['engine'] : 'MyISAM';
    $sql = "CREATE TABLE IF NOT EXISTS `{$schema['tablename']}` (\n";
    foreach ($schema['fields'] as $value) {
        if (!empty($value['length'])) {
            $length = "({$value['length']})";
        } else {
            $length = '';
        }
        
        $signed = empty($value['signed']) ? ' unsigned' : '';
        if (empty($value['null'])) {
            $null = ' NOT NULL';
        } else {
            $null = '';
        }
        if (isset($value['default'])) {
            $default = " DEFAULT '" . $value['default'] . "'";
        } else {
            $default = '';
        }
        if ($value['increment']) {
            $increment = ' AUTO_INCREMENT';
        } else {
            $increment = '';
        }
        
        $sql .= "`{$value['name']}` {$value['type']}{$length}{$signed}{$null}{$default}{$increment},\n";
    }
    foreach ($schema['indexes'] as $value) {
        $fields = implode('`,`', $value['fields']);
        if ($value['type'] == 'index') {
            $sql .= "KEY `{$value['name']}` (`{$fields}`),\n";
        }
        if ($value['type'] == 'unique') {
            $sql .= "UNIQUE KEY `{$value['name']}` (`{$fields}`),\n";
        }
        if ($value['type'] == 'primary') {
            $sql .= "PRIMARY KEY (`{$fields}`),\n";
        }
    }
    $sql = rtrim($sql);
    $sql = rtrim($sql, ',');
    
    $sql .= "\n) ENGINE=$engine DEFAULT CHARSET=$charset;\n\n";
    return $sql;
}

function we7_need_extension() {
    return array('zip', 'pdo', 'pdo_mysql', 'openssl', 'gd', 'mbstring', 'mcrypt', 'curl');
}

/**
 * @param $link PDO
 * @param $method
 * @param $sql
 * @return false|mixed
 */
function we7_pdo($link, $method, $sql) {
    if (empty($link) || empty($method) || empty($sql)) {
        return false;
    }
    if (!($link instanceof PDO)) {
        trigger_error('$link不是有效的数据库连接:' . (string)$link);
        return false;
    }
    $statement = $link->$method($sql);
    if ($link->errorCode() != '00000') {
        $errorInfo = $link->errorInfo();
        trigger_error($errorInfo[0] . ": " . $errorInfo[2], E_USER_WARNING);
        return false;
    }
    if ($statement instanceof PDOStatement) {
        $result = $statement->fetch();
        if ($statement->errorCode() != '00000') {
            $errorInfo = $statement->errorInfo();
            trigger_error($errorInfo[0] . ": " . $errorInfo[2], E_USER_WARNING);
            return false;
        }
    } else {
        $result = $statement;
    }
    return $result;
}

/**
 * 创建数据库
 * @return bool|string
 */
function we7_db() {
    global $is_https;
    define('IN_IA', true);
    require IA_INSTALL_ROOT . '/data/config.php';
    $db = array(
        'server' => $config['db']['master']['host'],
        'port' => $config['db']['master']['port'],
        'username' => $config['db']['master']['username'],
        'password' => $config['db']['master']['password'],
        'prefix' => $config['db']['master']['tablepre'],
        'name' => $config['db']['master']['database'],
    );

    $error = '';
    try {
        $link = new PDO("mysql:host={$db['server']};port={$db['port']}", $db['username'], $db['password']); 	// dns可以没有dbname
        we7_pdo($link, 'exec', "SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
        we7_pdo($link, 'exec', "SET sql_mode=''");
        we7_pdo($link, 'query', "CREATE DATABASE IF NOT EXISTS `{$db['name']}`;");
        $databases_if_exists = we7_pdo($link, 'query', "SHOW DATABASES LIKE '{$db['name']}';");
        if (empty($databases_if_exists)) {
            $error = "数据库不存在且创建数据库失败.";
        }
        we7_pdo($link, 'exec', "USE `{$db['name']}`;");
        $tables = we7_pdo($link, 'query', "SHOW TABLES LIKE '{$db['prefix']}%';");
        if (!empty($tables)) {
            return '您的数据库不为空，请重新建立数据库或是清空该数据库或更改表前缀！';
        }
    } catch (PDOException $e) {
        trigger_error($e->getCode() . ':' . $e->getMessage());
        $error = $e->getMessage();
        if (strpos($error, 'Access denied for user') !== false) {
            $error = '您的数据库访问用户名或是密码错误.';
        } elseif (strpos($error, 'No such file or directory') !== false) {
            $error = '无法连接数据库,请检查数据库是否正常.详情:' . $error;
        } else {
            $error = iconv('gbk', 'utf8', $error);
        }
    }
    if (!empty($error)) {
        return $error;
    }

    $link = new PDO("mysql:dbname={$db['name']};host={$db['server']};port={$db['port']}", $db['username'], $db['password']);
    we7_pdo($link, 'exec', "SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
    we7_pdo($link, 'exec', "SET sql_mode=''");
    
    $dbfile = IA_INSTALL_ROOT . '/data/db-1.x.php';
    if (file_exists(IA_INSTALL_ROOT . '/index.php') &&
        is_dir(IA_INSTALL_ROOT . '/web') &&
        file_exists($dbfile)) {
        $dat = require $dbfile;
        if (empty($dat) || !is_array($dat)) {
            return '安装包不正确, 数据安装脚本缺失.';
        }
        
        $support_innodb = false;
        $engines = $link->query("SHOW ENGINES;");
        $all_engines = $engines->fetchAll();
        foreach ($all_engines as $engine) {
            if (strtolower($engine['Engine']) == 'innodb' && in_array(strtolower($engine['Support']), array('default', 'yes'))) {
                $support_innodb = true;
            }
        }
        
        foreach ($dat['schemas'] as $schema) {
            $sql = local_create_sql($schema, $support_innodb);
            local_run($sql, $link, $db);
        }
        foreach ($dat['datas'] as $data) {
            local_run($data, $link, $db);
        }
    } else {
        return '安装包不正确.';
    }
    
    return true;
}

/**
 * 重建站点缓存
 * @return bool
 */
function we7_finish() {
    global $_W;
    define('IN_SYS', true);
    require IA_INSTALL_ROOT . '/framework/bootstrap.inc.php';
    require IA_INSTALL_ROOT . '/web/common/bootstrap.sys.inc.php';
    $_W['uid'] = $_W['isfounder'] = 1;
    load()->model('cache');
    cache_build_setting();
    return true;
}

function we7_error($num, $message = 'success') {
    $num = intval($num);
    return json_encode(array('errno' => $num, 'data' => $message));
}

header('content-type:text/html;charset=utf-8');
echo '<!DOCTYPE html>
<html lang=en>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" href="https://cdn.w7.cc/favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="//at.alicdn.com/t/font_588424_lhuur4vvl0a.css">
  <title>独立系统安装</title>
  <script>
        window.__MICRO_APP_PUBLIC_PATH__ = "https://cdn.w7.cc/ued/we7-install/' . INSTALL_VERSION . '/";
  </script>
  <link href="//cdn.w7.cc/ued/we7-install/' . INSTALL_VERSION . '/css/chunk-vendors.css?v=' . time() . '" rel="stylesheet">
  <link href="//cdn.w7.cc/ued/we7-install/' . INSTALL_VERSION . '/css/app.css?v=' . time() . '" rel="stylesheet">
</head>
<body>
  <div id=app></div>
  <script src="//cdn.w7.cc/ued/we7-install/' . INSTALL_VERSION . '/js/chunk-vendors.we7.js?v=' . time() . '"></script>
  <script src="//cdn.w7.cc/ued/we7-install/' . INSTALL_VERSION . '/js/app.we7.js?v=' . time() . '"></script></body>
</html>';
