<?php
defined('IN_IA') or exit('Access Denied');

$config = array();

$config['db']['master']['host'] = getenv('MYSQL_HOST');
$config['db']['master']['username'] = getenv('MYSQL_USERNAME');
$config['db']['master']['password'] = getenv('MYSQL_PASSWORD');
$config['db']['master']['port'] = getenv('MYSQL_PORT') ?: 3306;
$config['db']['master']['database'] = getenv('MYSQL_DATABASE');
$config['db']['master']['charset'] = 'utf8';
$config['db']['master']['pconnect'] = 0;
$config['db']['master']['tablepre'] = 'ims_';

$config['db']['slave_status'] = false;
$config['db']['slave']['1']['host'] = '';
$config['db']['slave']['1']['username'] = '';
$config['db']['slave']['1']['password'] = '';
$config['db']['slave']['1']['port'] = '';
$config['db']['slave']['1']['database'] = '';
$config['db']['slave']['1']['charset'] = 'utf8';
$config['db']['slave']['1']['pconnect'] = 0;
$config['db']['slave']['1']['tablepre'] = 'ims_';
$config['db']['slave']['1']['weight'] = 0;

$config['db']['common']['slave_except_table'] = array('core_sessions');

// --------------------------  CONFIG COOKIE  --------------------------- //
$config['cookie']['pre'] = getenv('PROJECT_COOKIE_KEY');
$config['cookie']['domain'] = '';
$config['cookie']['path'] = '/';

// --------------------------  CONFIG SETTING  --------------------------- //
$config['setting']['charset'] = 'utf-8';
$config['setting']['cache'] = getenv('PROJECT_CACHE');
$config['setting']['timezone'] = 'Asia/Shanghai';
$config['setting']['memory_limit'] = '256M';
$config['setting']['filemode'] = 0644;
$config['setting']['authkey'] = getenv('PROJECT_AUTH_KEY');
$config['setting']['founder'] = '1';
$config['setting']['development'] = getenv('APP_DEBUG');
$config['setting']['referrer'] = 0;

// --------------------------  CONFIG UPLOAD  --------------------------- //
$config['upload']['image']['extentions'] = array('gif', 'jpg', 'jpeg', 'png');
$config['upload']['image']['limit'] = 5000;
$config['upload']['attachdir'] = 'attachment';
$config['upload']['audio']['extentions'] = array('mp3');
$config['upload']['audio']['limit'] = 5000;

// --------------------------  CONFIG MEMCACHE  --------------------------- //
$config['setting']['memcache']['server'] = '';
$config['setting']['memcache']['port'] = 11211;
$config['setting']['memcache']['pconnect'] = 1;
$config['setting']['memcache']['timeout'] = 30;

// --------------------------  CONFIG REDIS  --------------------------- //
$config['setting']['redis']['server'] = getenv('REDIS_HOST');
$config['setting']['redis']['port'] = getenv('REDIS_PORT');
$config['setting']['redis']['pconnect'] = 1;
$config['setting']['redis']['timeout'] = 30;
$config['setting']['redis']['auth'] = getenv('REDIS_PASSWORD');

// --------------------------  CONFIG PROXY  --------------------------- //
$config['setting']['proxy']['host'] = '';
$config['setting']['proxy']['auth'] = '';

$config['config']['setting']['allow_origin'] = getenv('ALLOW_ORIGIN');
$config['setting']['local_develop'] = getenv('LOCAL_DEVELOP');
if (getenv('V3_API_DOMAIN')) {
    define('V3_API_DOMAIN', getenv('V3_API_DOMAIN'));
}
if (getenv('CARD_NAVIGATE_MODULE_NAME')) {
    define('CARD_NAVIGATE_MODULE_NAME', getenv('CARD_NAVIGATE_MODULE_NAME'));
}
