<?php

/**
 * MemCached缓存.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

function cache_memcached() {
    global $_W;
    static $memcacheobj;
    if (!extension_loaded('memcached')) {
        return error(1, 'Class Memcached is not found');
    }
    if (empty($memcacheobj)) {
        $config = $_W['config']['setting']['memcached'];
        $memcacheobj = new Memcached();
        $connect = $memcacheobj->addServer(
            $config['server'],
            !empty($config['port']) ? $config['port'] : 11211,
            !empty($config['weight']) ? $config['weight'] : 1
        );
        if (isset($config['username']) && isset($config['password'])) {
            $memcacheobj->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $memcacheobj->setSaslAuthData($config['username'], $config['password']);
        }
        if (!$connect) {
            return error(-1, 'Memcached is not in work');
        }
    }

    return $memcacheobj;
}

/**
 * 取出缓存的单条数据.
 * @param 缓存键名 ，多个层级或分组请使用:隔开
 * @return mixed
 */
function cache_read($key) {
    $memcache = cache_memcached();
    if (is_error($memcache)) {
        return $memcache;
    }
    $result = $memcache->get(cache_prefix($key));
    return $result;
}

/**
 * 检索缓存中指定层级或分组的所有缓存.
 * @param 缓存分组
 * @return mixed
 */
function cache_search($key) {
    return cache_read(cache_prefix($key));
}

/**
 * 将值序列化并写入缓存.
 * @param string $key
 * @param mixed $value
 * @param int $ttl
 * @return mixed
 */
function cache_write($key, $value, $ttl = 0) {
    $memcache = cache_memcached();
    if (is_error($memcache)) {
        return $memcache;
    }
    if ($memcache->set(cache_prefix($key), $value, $ttl)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 删除某个键的缓存数据.
 * @param string $key
 * @return mixed
 */
function cache_delete($key) {
    $memcache = cache_memcached();
    if (is_error($memcache)) {
        return $memcache;
    }
    $cache_relation_keys = cache_relation_keys($key);
    if (is_error($cache_relation_keys)) {
        return $cache_relation_keys;
    }
    if (is_array($cache_relation_keys) && !empty($cache_relation_keys)) {
        foreach ($cache_relation_keys as $key) {
            $cache_info = cache_load($key);
            if (!empty($cache_info)) {
                $origins_cache_key = $key;
                $result = $memcache->delete(cache_prefix($key));
                unset($GLOBALS['_W']['cache'][$origins_cache_key]);
                if (!$result) {
                    return error(1, '缓存: ' . $key . ' 删除失败！');
                }
            }
        }
    }

    return true;
}

/**
 * 清空缓存指定前缀或所有数据.
 * @param string $prefix
 */
function cache_clean() {
    $memcache = cache_memcached();
    if (is_error($memcache)) {
        return $memcache;
    }
    if ($memcache->flush()) {
        unset($GLOBALS['_W']['cache']);
        return true;
    } else {
        return false;
    }
}

function cache_prefix($key) {
    return $GLOBALS['_W']['config']['setting']['authkey'] . $key;
}
