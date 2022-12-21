<?php
/**
 * 函数版本兼容.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

if (!function_exists('json_encode')) {
    function json_encode($value) {
        static $jsonobj;
        if (!isset($jsonobj)) {
            load()->library('json');
            $jsonobj = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        }

        return $jsonobj->encode($value);
    }
}

if (!function_exists('json_decode')) {
    function json_decode($jsonString) {
        static $jsonobj;
        if (!isset($jsonobj)) {
            load()->library('json');
            $jsonobj = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        }

        return $jsonobj->decode($jsonString);
    }
}

if (!function_exists('http_build_query')) {
    function http_build_query($formdata, $numeric_prefix = null, $arg_separator = null) {
        if (!is_array($formdata)) {
            return false;
        }
        if (null == $arg_separator) {
            $arg_separator = '&';
        }

        return http_build_recursive($formdata, $arg_separator);
    }
    function http_build_recursive($formdata, $separator, $key = '', $prefix = '') {
        $rlt = '';
        foreach ($formdata as $k => $v) {
            if (is_array($v)) {
                if ($key) {
                    $rlt .= http_build_recursive($v, $separator, $key . '[' . $k . ']', $prefix);
                } else {
                    $rlt .= http_build_recursive($v, $separator, $k, $prefix);
                }
            } else {
                if ($key) {
                    $rlt .= $prefix . $key . '[' . urlencode($k) . ']=' . urldecode($v) . '&';
                } else {
                    $rlt .= $prefix . urldecode($k) . '=' . urldecode($v) . '&';
                }
            }
        }

        return $rlt;
    }
}

if (!function_exists('file_put_contents')) {
    function file_put_contents($file, $string) {
        $fp = @fopen($file, 'w') or exit("Can not open $file");
        flock($fp, LOCK_EX);
        $stringlen = @fwrite($fp, $string);
        flock($fp, LOCK_UN);
        @fclose($fp);

        return $stringlen;
    }
}

if (!function_exists('getimagesizefromstring')) {
    function getimagesizefromstring($string_data) {
        $uri = 'data://application/octet-stream;base64,' . base64_encode($string_data);

        return getimagesize($uri);
    }
}

/*
 * 兼容 <5.4.0 版本，json_encode() 中文转为unicode编码问题。添加 JSON_UNESCAPED_UNICODE 常量
 */
if (!defined('JSON_UNESCAPED_UNICODE')) {
    define('JSON_UNESCAPED_UNICODE', 256);
}

if (!function_exists('hex2bin')) {
    function hex2bin($str) {
        $sbin = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i += 2) {
            $sbin .= pack('H*', substr($str, $i, 2));
        }

        return $sbin;
    }
}

if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $charset = '') {
        return istrlen($string, $charset);
    }
}

/*
 * php5.4以上版本要求session hanlder继承此接口
 */
if (!interface_exists('SessionHandlerInterface')) {
    interface SessionHandlerInterface {
    }
}

/*
 * php-fpm环境下，此函数可以快速响应数据，后续代码将在后台运行
 */
if (!function_exists('fastcgi_finish_request')) {
    function fastcgi_finish_request() {
        return error(-1, 'Not npm or fast cgi');
    }
}

if (!function_exists('openssl_decrypt')) {
    function openssl_decrypt($ciphertext_dec, $method, $key, $options, $iv) {
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($module, $key, $iv);
        $decrypted = mdecrypt_generic($module, $ciphertext_dec);
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);

        return $decrypted;
    }
}
if (!function_exists('openssl_encrypt')) {
    function openssl_encrypt($text, $method, $key, $options, $iv) {
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($module, $key, $iv);
        $encrypted = mcrypt_generic($module, $text);
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);

        return $encrypted;
    }
}
//array_column()函数只支持PHP版本5.5以上
if (!function_exists('array_column')) {
    function array_column($input, $columnKey, $indexKey = null) {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = array();

        foreach ((array) $input as $key => $row) {
            if ($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
            } else {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
            }
            if (!$indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }
            $result[$key] = $tmp;
        }

        return $result;
    }
}
//boolval要求至少php5.5以上
if (!function_exists('boolval')) {
    function boolval($val) {
        return (bool) $val;
    }
}
