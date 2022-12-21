<?php

/**
 * 文件缓存.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');
/*
 * 缓存函数四个，分别用于写入，读取，删除，清空缓存。暂时采用文件缓冲，期待使用memcache或mysql内存缓冲
 */
load()->func('file');
define('CACHE_FILE_PATH', IA_ROOT . '/data/cache/');
/**
 * 获取文件缓存数据.
 *
 * @param string $key     缓存文件名称(key)
 * @param string $dir     缓存文件目录
 * @param bool   $include 获取缓存方式
 *
 * @return mixed
 */
function cache_read($key, $dir = '', $include = true) {
    $key = str_replace(':', '@', $key);
    $key = CACHE_FILE_PATH . $key;
    if (!is_file($key)) {
        return array();
    }

    return $include ? include $key : file_get_contents($key);
}
/**
 * 设置文件缓存数据.
 *
 * @param string $key  缓存文件名称(key)
 * @param mixed  $data 缓存数据
 * @param string $dir  缓存文件目录
 *
 * @return boolean
 */
function cache_write($key, $data, $dir = '') {
    global $_W;
    if (empty($key) || !isset($data)) {
        return false;
    }
    $key = str_replace(':', '@', $key);
    if (!is_string($data)) {
        $data = "<?php \r\ndefined('IN_IA') or exit('Access Denied');\r\nreturn " . var_export($data, true) . ';';
    }
    $key = CACHE_FILE_PATH . $key;
    mkdirs(dirname($key));
    file_put_contents($key, $data);
    @chmod($key, $_W['config']['setting']['filemode']);

    return is_file($key);
}
/**
 * 删除指定缓存文件.
 *
 * @param string $key 缓存文件名称(key)
 * @param string $dir 缓存文件目录
 *
 * @return boolean
 */
function cache_delete($key, $dir = '') {
    $cache_relation_keys = cache_relation_keys($key);
    if (is_error($cache_relation_keys)) {
        return $cache_relation_keys;
    }
    if (is_array($cache_relation_keys) && !empty($cache_relation_keys)) {
        foreach ($cache_relation_keys as $key) {
            $cache_info = cache_load($key);
            if (!empty($cache_info)) {
                $key = str_replace(':', '@', $key);
                $key = CACHE_FILE_PATH . $key;
                $result = file_delete($key);
                if (!$result) {
                    return error(1, '缓存: ' . $key . ' 删除失败!');
                }
            }
        }
    }

    return true;
}

/**
 * 清空所有缓存文件.
 *
 * @param string $dir 缓存文件目录
 *
 * @return boolean
 */
function cache_clean($dir = '') {
    return rmdirs(CACHE_FILE_PATH, true);
}
