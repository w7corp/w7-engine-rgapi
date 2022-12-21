<?php
/**
 * 公共函数.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 比较字符串int类型大小.
 * @param string $version1
 * @param string $version2
 * @return int
 */
function ver_compare($version1, $version2) {
    $version1 = str_replace('.', '', $version1);
    $version2 = str_replace('.', '', $version2);
    $oldLength = istrlen($version1);
    $newLength = istrlen($version2);
    if (is_numeric($version1) && is_numeric($version2)) {
        if ($oldLength > $newLength) {
            $version2 .= str_repeat('0', $oldLength - $newLength);
        }
        if ($newLength > $oldLength) {
            $version1 .= str_repeat('0', $newLength - $oldLength);
        }
        $version1 = intval($version1);
        $version2 = intval($version2);
    }

    return version_compare($version1, $version2);
}

function iget_headers($url, $format = 0) {
    $result = @get_headers($url, $format);
    if (empty($result)) {
        stream_context_set_default(array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
            ),
        ));
        $result = get_headers($url, $format);
    }

    return $result;
}

function igetimagesize($filename, $imageinfo = array()) {
    $result = @getimagesize($filename, $imageinfo);
    if (empty($result)) {
        $file_content = ihttp_request($filename);
        $content = $file_content['content'];
        $result = getimagesize('data://image/jpeg;base64,' . base64_encode($content), $imageinfo);
    }

    return $result;
}

/**
 * 反转义字符串或数组中的 \.
 * @param mixed $var
 * @return mixed $var
 */
function istripslashes($var) {
    if (is_array($var)) {
        foreach ($var as $key => $value) {
            $var[stripslashes($key)] = istripslashes($value);
        }
    } else {
        $var = stripslashes($var);
    }

    return $var;
}

/**
 * 转义字符串或数组中的的HTML.
 * @param mixed $var
 * @return mixed $var
 */
function ihtmlspecialchars($var) {
    if (is_array($var)) {
        foreach ($var as $key => $value) {
            $var[htmlspecialchars($key)] = ihtmlspecialchars($value);
        }
    } else {
        $var = str_replace('&amp;', '&', htmlspecialchars($var, ENT_QUOTES));
    }

    return $var;
}

/**
 * 写入cookie值
 * @param string $key    名称
 * @param string $value  值
 * @param int    $expire 生命周期
 *
 * @return boolean
 */
function isetcookie($key, $value, $expire = 0, $httponly = false) {
    global $_W;
    $expire = 0 != $expire ? (TIMESTAMP + $expire) : 0;
    if (!empty($_W['ishttps'])) {
        header('Set-Cookie: ' . ($_W['config']['cookie']['pre'] . $key . '=' . rawurlencode($value))
            . (!empty($expire) ? ('; expires=' . $expire) : '')
            . (!empty($_W['config']['cookie']['path']) ? ('; Path=' . $_W['config']['cookie']['path']) : '')
            . (!empty($_W['config']['cookie']['domain']) ? ('; Domain=' . $_W['config']['cookie']['domain']) : '')
            . '; SameSite=None; Secure'
            . (!$httponly ? '' : '; HttpOnly'), false);
    } else {
        $secure = (!empty($_SERVER['SERVER_PORT']) && 443 == $_SERVER['SERVER_PORT']) ? 1 : 0;
        setcookie($_W['config']['cookie']['pre'] . $key, $value, $expire, $_W['config']['cookie']['path'], $_W['config']['cookie']['domain'], $secure, $httponly);
    }
    return true;
}

/**
 * 获取cookie值
 * @param $key 要获取的cookie名称
 * @return mixed
 */
function igetcookie($key) {
    global $_W;
    $key = $_W['config']['cookie']['pre'] . $key;

    return isset($_COOKIE[$key]) ? $_COOKIE[$key] : '';
}

/**
 * 获取客户端IP.
 * @return string
 */
function getip() {
    static $ip = '';
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
        $ip = $_SERVER['HTTP_CDN_SRC_IP'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
        foreach ($matches[0] as $xip) {
            if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                $ip = $xip;
                break;
            }
        }
    }
    if (preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $ip)) {
        return $ip;
    } else {
        return '127.0.0.1';
    }
}

/**
 * 获取Token.
 * @param stirng $specialadd 附加字串
 * @return string
 */
function token($specialadd = '') {
    global $_W;
    if (!defined('IN_MOBILE')) {
        $key = complex_authkey();

        return substr(md5($key . $specialadd), 8, 8);
    } else {
        if (!empty($_SESSION['token'])) {
            $count = count($_SESSION['token']) - 5;
            asort($_SESSION['token']);
            foreach ($_SESSION['token'] as $k => $v) {
                if (TIMESTAMP - $v > 300 || $count > 0) {
                    unset($_SESSION['token'][$k]);
                    --$count;
                }
            }
        }
        $key = substr(random(20), 0, 4);
        $_SESSION['token'][$key] = TIMESTAMP;

        return $key;
    }
}

/**
 * 获取随机字符串.
 * @param number $length  字符串长度
 * @param bool   $numeric 是否为纯数字
 * @return string
 */
function random($length, $numeric = false) {
    $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    if ($numeric) {
        $hash = '';
    } else {
        $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
        --$length;
    }
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; ++$i) {
        $hash .= $seed[mt_rand(0, $max)];
    }

    return $hash;
}

/**
 * 提交来源检查.
 * @param string $var      表单Submit名称
 * @param bool   $allowget 是否通过检查
 * @return boolean
 */
function checksubmit($var = 'submit', $allowget = false) {
    global $_W, $_GPC;
    if (empty($_GPC[$var])) {
        return false;
    }
    if (defined('IN_SYS')) {
        if ($allowget || (($_W['ispost'] && !empty($_W['token']) && $_W['token'] == $_GPC['token']) && (empty($_SERVER['HTTP_REFERER']) || preg_replace("/https?:\/\/([^\:\/]+).*/i", '\\1', $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", '\\1', $_SERVER['HTTP_HOST'])))) {
            return true;
        }
    } else {
        if (empty($_W['isajax']) && empty($_SESSION['token'][$_GPC['token']])) {
            exit('<script type="text/javascript">history.go(-1);</script>');
        } else {
            unset($_SESSION['token'][$_GPC['token']]);
        }

        return true;
    }

    return false;
}

function complex_authkey() {
    global $_W;
    $key = empty($_W['setting']['site']) ? array() : (array) $_W['setting']['site'];
    $key['authkey'] = $_W['config']['setting']['authkey'];

    return implode('', $key);
}
function checkcaptcha($code) {
    global $_GPC;
    session_start();
    $key = complex_authkey();
    $codehash = md5(strtolower($code) . $key);
    if (!empty($_GPC['__code']) && ($codehash == $_SESSION['__code'] || $codehash == igetcookie('__code'))) {
        $return = true;
    } else {
        $return = false;
    }
    $_SESSION['__code'] = '';
    isetcookie('__code', '');

    return $return;
}

/**
 * 获取完整数据表名.
 * @param string $table 数据表名
 * @return string
 */
function tablename($table) {
    if (empty($GLOBALS['_W']['config']['db']['master'])) {
        return "`{$GLOBALS['_W']['config']['db']['tablepre']}{$table}`";
    }

    return "`{$GLOBALS['_W']['config']['db']['master']['tablepre']}{$table}`";
}

/**
 * 该函数从一个数组中取得若干元素。
 * 该函数测试（传入）数组的每个键值是否在（目标）数组中已定义；
 * 如果一个键值不存在，该键值所对应的值将被置为FALSE，
 * 或者你可以通过传入的第3个参数来指定默认的值。
 * @param array $keys    需要筛选的键名列表
 * @param array $src     要进行筛选的数组
 * @param mixed $default 如果原数组未定义某个键，则使用此默认值返回
 * @return array
 */
function array_elements($keys, $src, $default = false) {
    $return = array();
    if (!is_array($keys)) {
        $keys = array($keys);
    }
    foreach ($keys as $key) {
        if (isset($src[$key])) {
            $return[$key] = $src[$key];
        } else {
            $return[$key] = $default;
        }
    }

    return $return;
}

/**
 * 根据键值对数组排序.
 * @param array  $array 需要排序的数组
 * @param string $keys  用来排序的键名
 * @param string $type  排序规则
 * @return array
 */
function iarray_sort($array, $keys, $type = 'asc') {
    $keysvalue = $new_array = array();
    foreach ($array as $k => $v) {
        $keysvalue[$k] = $v[$keys];
    }
    if ('asc' == $type) {
        asort($keysvalue);
    } else {
        arsort($keysvalue);
    }
    reset($keysvalue);
    foreach ($keysvalue as $k => $v) {
        $new_array[$k] = $array[$k];
    }

    return $new_array;
}

/**
 * 判断给定参数是否位于区间内或将参数转换为区间内的数.
 * @param string $num        输入参数
 * @param int    $downline   区间下限
 * @param int    $upline     区间上限
 * @param bool   $returnNear 输入参数处理方式
 * @return mixed
 */
function range_limit($num, $downline, $upline, $returnNear = true) {
    $num = intval($num);
    $downline = intval($downline);
    $upline = intval($upline);
    if ($num < $downline) {
        return empty($returnNear) ? false : $downline;
    } elseif ($num > $upline) {
        return empty($returnNear) ? false : $upline;
    } else {
        return empty($returnNear) ? true : $num;
    }
}

/**
 * JSON编码,加上转义操作,适合于JSON入库.
 * @param string $value
 * @param int    $options
 * @return mixed
 */
function ijson_encode($value, $options = 0) {
    if (empty($value)) {
        return false;
    }
    if (version_compare(PHP_VERSION, '5.4.0', '<') && JSON_UNESCAPED_UNICODE == $options) {
        $str = json_encode($value);
        $json_str = preg_replace_callback("#\\\u([0-9a-f]{4})#i", function ($matchs) {
            return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
        }, $str);
    } else {
        $json_str = json_encode($value, $options);
    }

    return addslashes($json_str);
}

/**
 * 获取字符串序列化结果.
 * @param mixed $value
 * @return string
 */
function iserializer($value) {
    return serialize($value);
}

/**
 * 获取序列化字符的反序列化结果.
 * @param string $value
 * @return mixed
 */
function iunserializer($value) {
    if (empty($value)) {
        return array();
    }
    if (is_array($value)) {
        return $value;
    }
    if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
        $result = unserialize($value, array('allowed_classes' => false));
    } else {
        $result = unserialize($value);
    }
    return $result;
}

/**
 * 判断是否为base64加密字符串.
 * @param string $str
 * @return boolean
 */
function is_base64($str) {
    if (!is_string($str)) {
        return false;
    }

    return $str == base64_encode(base64_decode($str));
}

/**
 * 判断是否为序列化字符串.
 * @param mixed $data
 * @param bool  $strict
 * @return boolean
 */
function is_serialized($data, $strict = true) {
    if (!is_string($data)) {
        return false;
    }
    $data = trim($data);
    if ('N;' == $data) {
        return true;
    }
    if (strlen($data) < 4) {
        return false;
    }
    if (':' !== $data[1]) {
        return false;
    }
    if ($strict) {
        $lastc = substr($data, -1);
        if (';' !== $lastc && '}' !== $lastc) {
            return false;
        }
    } else {
        $semicolon = strpos($data, ';');
        $brace = strpos($data, '}');
        // Either ; or } must exist.
        if (false === $semicolon && false === $brace) {
            return false;
        }
        // But neither must be in the first X characters.
        if (false !== $semicolon && $semicolon < 3) {
            return false;
        }
        if (false !== $brace && $brace < 4) {
            return false;
        }
    }
    $token = $data[0];
    switch ($token) {
        case 's':
            if ($strict) {
                if ('"' !== substr($data, -2, 1)) {
                    return false;
                }
            } elseif (false === strpos($data, '"')) {
                return false;
            }
        // or else fall through
        // no break
        case 'a':
            return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
        case 'O':
            return false;
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';

            return (bool) preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
    }

    return false;
}

/**
 * 获取Web端URL地址
 * @param string $segment 路由参数
 * @param array  $params  附加参数
 * @param boolean $contain_domain 是否包含域名
 * @return string
 */
function wurl($segment, $params = array(), $contain_domain = false) {
    global $_W, $_GPC;
    if (empty($params)) {
        $params = array();
    }
    $cad = explode('/', $segment);
    $controller = empty($cad[0]) ? '' : $cad[0];
    $action = empty($cad[1]) ? '' : $cad[1];
    $do = empty($cad[2]) ? '' : $cad[2];
    if ($contain_domain) {
        $url = $_W['siteroot'] . 'web/index.php?';
    } else {
        $url = './index.php?';
    }
    if (!empty($controller)) {
        $url .= "c={$controller}&";
    }
    if (!empty($action)) {
        $url .= "a={$action}&";
    }
    if (!empty($do)) {
        $url .= "do={$do}&";
    }
    if (!empty($params)) {
        $queryString = http_build_query($params, '', '&');
        $url .= $queryString;
    }

    return $url;
}

if (!function_exists('murl')) {
    /**
     * 获取Mobile端URL地址
     *
     * @param string $segment    路由参数
     * @param array  $params     附加参数
     * @param bool   $noredirect 是否追加微信URl后缀
     */
    function murl($segment, $params = array(), $noredirect = true, $addhost = false) {
        global $_W;
        $cad = explode('/', $segment);
        $controller = empty($cad[0]) ? '' : $cad[0];
        $action = empty($cad[1]) ? '' : $cad[1];
        $do = empty($cad[2]) ? '' : $cad[2];
        if (!empty($addhost)) {
            $url = $_W['siteroot'] . 'app/';
        } else {
            $url = './';
        }
        $str = '';
        if (!empty($_W['account']) && $_W['account']['type'] == ACCOUNT_TYPE_WEBAPP_NORMAL) {
            $str .= '&a=webapp';
        }
        if (!empty($_W['account']) && $_W['account']['type'] == ACCOUNT_TYPE_PHONEAPP_NORMAL) {
            $str .= '&a=phoneapp';
        }
        $url .= "index.php?i={$_W['uniacid']}{$str}&";
        if (!empty($controller)) {
            $url .= "c={$controller}&";
        }
        if (!empty($action)) {
            $url .= "a={$action}&";
        }
        if (!empty($do)) {
            $url .= "do={$do}&";
        }
        if (!empty($params)) {
            $queryString = http_build_query($params, '', '&');
            $url .= $queryString;
            if (false === $noredirect) {
                //加上后，表单提交无值
                $url .= '&wxref=mp.weixin.qq.com#wechat_redirect';
            }
        }

        return $url;
    }
}

/**
 * 获取分页导航HTML.
 * @param int    $total     总记录数
 * @param int    $pageIndex 当前页码
 * @param int    $pageSize  每页显示条数
 * @param string $url       要生成的 url 格式，页码占位符请使用 *，如果未写占位符，系统将自动生成
 * @param array  $context
 * @return string
 */
function pagination($total, $pageIndex, $pageSize = 15, $url = '', $context = array('before' => 5, 'after' => 4, 'ajaxcallback' => '', 'callbackfuncname' => '')) {
    global $_W;
    $pdata = array(
        'tcount' => 0,
        'tpage' => 0,
        'cindex' => 0,
        'findex' => 0,
        'pindex' => 0,
        'nindex' => 0,
        'lindex' => 0,
        'options' => '',
    );
    if (empty($context['before'])) {
        $context['before'] = 5;
    }
    if (empty($context['after'])) {
        $context['after'] = 4;
    }

    if (!empty($context['ajaxcallback'])) {
        $context['isajax'] = true;
    }

    if (!empty($context['callbackfuncname'])) {
        $callbackfunc = $context['callbackfuncname'];
    }

    $pdata['tcount'] = $total;
    $pdata['tpage'] = (empty($pageSize) || $pageSize < 0) ? 1 : ceil($total / $pageSize);
    if ($pdata['tpage'] <= 1) {
        return '';
    }
    $cindex = $pageIndex;
    $cindex = min($cindex, $pdata['tpage']);
    $cindex = max($cindex, 1);
    $pdata['cindex'] = $cindex;
    $pdata['findex'] = 1;
    $pdata['pindex'] = $cindex > 1 ? $cindex - 1 : 1;
    $pdata['nindex'] = $cindex < $pdata['tpage'] ? $cindex + 1 : $pdata['tpage'];
    $pdata['lindex'] = $pdata['tpage'];

    if (!empty($context['isajax'])) {
        if (empty($url)) {
            $url = $_W['script_name'] . '?' . http_build_query($_GET);
        }
        $pdata['faa'] = 'href="javascript:;" page="' . $pdata['findex'] . '" ' . ($callbackfunc ? 'ng-click="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['findex'] . '\', this);"' : '');
        $pdata['paa'] = 'href="javascript:;" page="' . $pdata['pindex'] . '" ' . ($callbackfunc ? 'ng-click="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['pindex'] . '\', this);"' : '');
        $pdata['naa'] = 'href="javascript:;" page="' . $pdata['nindex'] . '" ' . ($callbackfunc ? 'ng-click="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['nindex'] . '\', this);"' : '');
        $pdata['laa'] = 'href="javascript:;" page="' . $pdata['lindex'] . '" ' . ($callbackfunc ? 'ng-click="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['lindex'] . '\', this);"' : '');
    } else {
        if ($url) {
            $pdata['faa'] = 'href="?' . str_replace('*', $pdata['findex'], $url) . '"';
            $pdata['paa'] = 'href="?' . str_replace('*', $pdata['pindex'], $url) . '"';
            $pdata['naa'] = 'href="?' . str_replace('*', $pdata['nindex'], $url) . '"';
            $pdata['laa'] = 'href="?' . str_replace('*', $pdata['lindex'], $url) . '"';
        } else {
            $_GET['page'] = $pdata['findex'];
            $pdata['faa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
            $_GET['page'] = $pdata['pindex'];
            $pdata['paa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
            $_GET['page'] = $pdata['nindex'];
            $pdata['naa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
            $_GET['page'] = $pdata['lindex'];
            $pdata['laa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
        }
    }

    $html = '<div><ul class="pagination pagination-centered">';
    $html .= "<li><a {$pdata['faa']} class=\"pager-nav\">首页</a></li>";
    empty($callbackfunc) && $html .= "<li><a {$pdata['paa']} class=\"pager-nav\">&laquo;上一页</a></li>";

    //页码算法：前5后4，不足10位补齐
    if (!$context['before'] && 0 != $context['before']) {
        $context['before'] = 5;
    }
    if (!$context['after'] && 0 != $context['after']) {
        $context['after'] = 4;
    }

    if (0 != $context['after'] && 0 != $context['before']) {
        $range = array();
        $range['start'] = max(1, $pdata['cindex'] - $context['before']);
        $range['end'] = min($pdata['tpage'], $pdata['cindex'] + $context['after']);
        if ($range['end'] - $range['start'] < $context['before'] + $context['after']) {
            $range['end'] = min($pdata['tpage'], $range['start'] + $context['before'] + $context['after']);
            $range['start'] = max(1, $range['end'] - $context['before'] - $context['after']);
        }
        for ($i = $range['start']; $i <= $range['end']; ++$i) {
            if (!empty($context['isajax'])) {
                $aa = 'href="javascript:;" page="' . $i . '" ' . ($callbackfunc ? 'ng-click="' . $callbackfunc . '(\'' . $url . '\', \'' . $i . '\', this);"' : '');
            } else {
                if ($url) {
                    $aa = 'href="?' . str_replace('*', $i, $url) . '"';
                } else {
                    $_GET['page'] = $i;
                    $aa = 'href="?' . http_build_query($_GET) . '"';
                }
            }
            if (!empty($context['isajax'])) {
                $html .= ($i == $pdata['cindex'] ? '<li class="active">' : '<li>') . "<a {$aa}>" . $i . '</a></li>';
            } else {
                $html .= ($i == $pdata['cindex'] ? '<li class="active"><a href="javascript:;">' . $i . '</a></li>' : "<li><a {$aa}>" . $i . '</a></li>');
            }
        }
    }

    if ($pdata['cindex'] < $pdata['tpage']) {
        empty($callbackfunc) && $html .= "<li><a {$pdata['naa']} class=\"pager-nav\">下一页&raquo;</a></li>";
        $html .= "<li><a {$pdata['laa']} class=\"pager-nav\">尾页</a></li>";
    }
    $html .= '</ul></div>';

    return $html;
}

/**
 * 获取附件的HTTP绝对路径.
 * @param string $src        附件地址
 * @param bool   $local_path 是否直接返回本地图片路径
 * @param bool   $is_cache   是否读取缓存
 * @return string
 */
function tomedia($src, $local_path = false, $is_cahce = false) {
    global $_W;
    $src = trim($src);
    if (empty($src)) {
        return '';
    }
    if ($is_cahce) {
        $src .= '?v=' . time();
    }

    if (strexists($src, 'c=utility&a=wxcode&do=image&attach=')) {
        return $src;
    }

    $t = strtolower($src);
    if (strexists($t, '//mmbiz.qlogo.cn') || strexists($t, '//mmbiz.qpic.cn')) {
        $url = url('utility/wxcode/image', array('attach' => $src));

        return $_W['siteroot'] . 'web' . ltrim($url, '.');
    }

    if ('//' == substr($src, 0, 2)) {
        return 'http:' . $src;
    }
    if (('http://' == substr($src, 0, 7)) || ('https://' == substr($src, 0, 8))) {
        return $src;
    }

    if (strexists($src, 'addons/')) {
        return $_W['siteroot'] . substr($src, strpos($src, 'addons/'));
    }
    if (strexists($src, 'app/themes/')) {
        return $_W['siteroot'] . substr($src, strpos($src, 'app/themes/'));
    }
    //如果远程地址中包含本地host也检测是否远程图片
    if (strexists($src, $_W['siteroot']) && !strexists($src, '/addons/')) {
        $urls = parse_url($src);
        $src = $t = substr($urls['path'], strpos($urls['path'], 'images'));
    }
    $uni_remote_setting = uni_setting_load('remote');
    //全局未设置远程附件，帐号内设置远程附件的情况要考虑在内，否则帐号内不显示图片，即第二个“||”判断
    if ($local_path || empty($_W['setting']['remote']['type']) && (empty($_W['uniacid']) || !empty($_W['uniacid']) && empty($uni_remote_setting['remote']['type'])) || file_exists(IA_ROOT . '/' . $_W['config']['upload']['attachdir'] . '/' . $src)) {
        $src = $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/' . $src;
    } else {
        if (!empty($uni_remote_setting['remote']['type'])) {
            if (ATTACH_OSS == $uni_remote_setting['remote']['type']) {
                $src = $uni_remote_setting['remote']['alioss']['url'] . '/' . $src;
            } elseif (ATTACH_QINIU == $uni_remote_setting['remote']['type']) {
                $src = $uni_remote_setting['remote']['qiniu']['url'] . '/' . $src;
            } elseif (ATTACH_COS == $uni_remote_setting['remote']['type']) {
                $src = $uni_remote_setting['remote']['cos']['url'] . '/' . $src;
            }
        } else {
            $src = $_W['attachurl_remote'] . $src;
        }
    }
    return $src;
}

/*
 * 根据全局远程附件设置获取附件的HTTP绝对路径
 * @param string $src 附件地址
 * @return string
 */
function to_global_media($src) {
    global $_W;
    if (empty($src)) {
        return '';
    }
    $lower_src = strtolower($src);
    if (('http://' == substr($lower_src, 0, 7)) || ('https://' == substr($lower_src, 0, 8)) || ('//' == substr($lower_src, 0, 2))) {
        return $src;
    }
    $remote = setting_load('remote');
    $remote = empty($remote) ? array() : $remote['remote'];
    if (empty($remote['type']) || file_exists(IA_ROOT . '/' . $_W['config']['upload']['attachdir'] . '/' . $src)) {
        $src = $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/' . $src;
    } else {
        if (ATTACH_OSS == $remote['type']) {
            $attach_url = $remote['alioss']['url'] . '/';
        } elseif (ATTACH_QINIU == $remote['type']) {
            $attach_url = $remote['qiniu']['url'] . '/';
        } elseif (ATTACH_COS == $remote['type']) {
            $attach_url = $remote['cos']['url'] . '/';
        }
        $src = $attach_url . $src;
    }

    return $src;
}

/**
 * 构造错误数组.
 * @param int    $errno   错误码，0为无任何错误
 * @param string $message 错误信息
 * @return array
 */
function error($errno, $message = '') {
    return array(
        'errno' => $errno,
        'message' => $message,
    );
}

/**
 * 检测数组是否产生错误.
 * @param mixed $data
 * @return boolean
 */
function is_error($data) {
    if (empty($data) || !is_array($data) || !array_key_exists('errno', $data) || (array_key_exists('errno', $data) && 0 == $data['errno'])) {
        return false;
    } else {
        return true;
    }
}

/**
 * 检测敏感词.
 * @param $string
 * @return bool
 */
function detect_sensitive_word($string) {
    $setting = setting_load('sensitive_words');
    if (empty($setting['sensitive_words'])) {
        return false;
    }
    $sensitive_words = $setting['sensitive_words'];
    $blacklist = '/' . implode('|', $sensitive_words) . '/';
    if (preg_match($blacklist, $string, $matches)) {
        return $matches[0];
    }

    return false;
}
/**
 * 获取引用页地址
 * @param string $default 默认地址
 * @return string
 */
function referer($default = '') {
    global $_GPC, $_W;
    $_SERVER['HTTP_REFERER'] = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
    $_W['referer'] = !empty($_GPC['referer']) ? $_GPC['referer'] : $_SERVER['HTTP_REFERER'];
    $_W['referer'] = '?' == substr($_W['referer'], -1) ? substr($_W['referer'], 0, -1) : $_W['referer'];

    if (strpos($_W['referer'], 'member.php?act=login')) {
        $_W['referer'] = $default;
    }

    $_W['referer'] = str_replace('&amp;', '&', $_W['referer']);
    $reurl = parse_url($_W['referer']);

    if (!empty($reurl['host']) && !in_array($reurl['host'], array($_SERVER['HTTP_HOST'], 'www.' . $_SERVER['HTTP_HOST'])) && !in_array($_SERVER['HTTP_HOST'], array($reurl['host'], 'www.' . $reurl['host']))) {
        $_W['referer'] = $_W['siteroot'];
    } elseif (empty($reurl['host'])) {
        $_W['referer'] = $_W['siteroot'] . './' . $_W['referer'];
    }

    return strip_tags($_W['referer']);
}

/**
 * 判断字符串是否包含子串.
 * @param string $string 在该字符串中进行查找
 * @param string $find   需要查找的字符串
 * @return boolean
 */
function strexists($string, $find) {
    return !(false === strpos($string, $find));
}

/**
 * 截取|替换字符串.
 * @param string $string  对该字符串进行截取
 * @param int    $length  指定截取长度
 * @param bool   $havedot 超出指定长度的字符是否用 '...' 显示
 * @param string $charset 字符编码
 * @return string
 */
function cutstr($string, $length, $havedot = false, $charset = '') {
    global $_W;
    if (empty($charset)) {
        $charset = $_W['charset'];
    }
    if ('gbk' == strtolower($charset)) {
        $charset = 'gbk';
    } else {
        $charset = 'utf8';
    }
    if (istrlen($string, $charset) <= $length) {
        return $string;
    }
    if (function_exists('mb_strcut')) {
        $string = mb_substr($string, 0, $length, $charset);
    } else {
        $pre = '{%';
        $end = '%}';
        $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), $string);

        $strlen = strlen($string);
        $n = $tn = $noc = 0;
        if ('utf8' == $charset) {
            while ($n < $strlen) {
                $t = ord($string[$n]);
                if (9 == $t || 10 == $t || (32 <= $t && $t <= 126)) {
                    $tn = 1;
                    ++$n;
                    ++$noc;
                } elseif (194 <= $t && $t <= 223) {
                    $tn = 2;
                    $n += 2;
                    ++$noc;
                } elseif (224 <= $t && $t <= 239) {
                    $tn = 3;
                    $n += 3;
                    ++$noc;
                } elseif (240 <= $t && $t <= 247) {
                    $tn = 4;
                    $n += 4;
                    ++$noc;
                } elseif (248 <= $t && $t <= 251) {
                    $tn = 5;
                    $n += 5;
                    ++$noc;
                } elseif (252 == $t || 253 == $t) {
                    $tn = 6;
                    $n += 6;
                    ++$noc;
                } else {
                    ++$n;
                }
                if ($noc >= $length) {
                    break;
                }
            }
            if ($noc > $length) {
                $n -= $tn;
            }
            $strcut = substr($string, 0, $n);
        } else {
            while ($n < $strlen) {
                $t = ord($string[$n]);
                if ($t > 127) {
                    $tn = 2;
                    $n += 2;
                    ++$noc;
                } else {
                    $tn = 1;
                    ++$n;
                    ++$noc;
                }
                if ($noc >= $length) {
                    break;
                }
            }
            if ($noc > $length) {
                $n -= $tn;
            }
            $strcut = substr($string, 0, $n);
        }
        $string = str_replace(array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
    }

    if ($havedot) {
        $string = $string . '...';
    }

    return $string;
}

/**
 * 获取字符串长度.
 * @param string $string
 * @param string $charset
 * @return int
 */
function istrlen($string, $charset = '') {
    global $_W;
    if (empty($charset)) {
        $charset = $_W['charset'];
    }
    if ('gbk' == strtolower($charset)) {
        $charset = 'gbk';
    } else {
        $charset = 'utf8';
    }
    if (function_exists('mb_strlen') && extension_loaded('mbstring')) {
        return mb_strlen($string, $charset);
    } else {
        $n = $noc = 0;
        $strlen = strlen($string);

        if ('utf8' == $charset) {
            while ($n < $strlen) {
                $t = ord($string[$n]);
                if (9 == $t || 10 == $t || (32 <= $t && $t <= 126)) {
                    ++$n;
                    ++$noc;
                } elseif (194 <= $t && $t <= 223) {
                    $n += 2;
                    ++$noc;
                } elseif (224 <= $t && $t <= 239) {
                    $n += 3;
                    ++$noc;
                } elseif (240 <= $t && $t <= 247) {
                    $n += 4;
                    ++$noc;
                } elseif (248 <= $t && $t <= 251) {
                    $n += 5;
                    ++$noc;
                } elseif (252 == $t || 253 == $t) {
                    $n += 6;
                    ++$noc;
                } else {
                    ++$n;
                }
            }
        } else {
            while ($n < $strlen) {
                $t = ord($string[$n]);
                if ($t > 127) {
                    $n += 2;
                    ++$noc;
                } else {
                    ++$n;
                    ++$noc;
                }
            }
        }

        return $noc;
    }
}

/**
 * 获取表情字符串HTML.
 * @param string $message 表情字符串
 * @param string $size    表情图片大小
 * @return string
 */
function emotion($message = '', $size = '24px') {
    $emotions = array(
        '/::)', '/::~', '/::B', '/::|', '/:8-)', '/::<', '/::$', '/::X', '/::Z', "/::'(",
        '/::-|', '/::@', '/::P', '/::D', '/::O', '/::(', '/::+', '/:--b', '/::Q', '/::T',
        '/:,@P', '/:,@-D', '/::d', '/:,@o', '/::g', '/:|-)', '/::!', '/::L', '/::>', '/::,@',
        '/:,@f', '/::-S', '/:?', '/:,@x', '/:,@@', '/::8', '/:,@!', '/:!!!', '/:xx', '/:bye',
        '/:wipe', '/:dig', '/:handclap', '/:&-(', '/:B-)', '/:<@', '/:@>', '/::-O', '/:>-|',
        '/:P-(', "/::'|", '/:X-)', '/::*', '/:@x', '/:8*', '/:pd', '/:<W>', '/:beer', '/:basketb',
        '/:oo', '/:coffee', '/:eat', '/:pig', '/:rose', '/:fade', '/:showlove', '/:heart',
        '/:break', '/:cake', '/:li', '/:bome', '/:kn', '/:footb', '/:ladybug', '/:shit', '/:moon',
        '/:sun', '/:gift', '/:hug', '/:strong', '/:weak', '/:share', '/:v', '/:@)', '/:jj', '/:@@',
        '/:bad', '/:lvu', '/:no', '/:ok', '/:love', '/:<L>', '/:jump', '/:shake', '/:<O>', '/:circle',
        '/:kotow', '/:turn', '/:skip', '/:oY', '/:#-0', '/:hiphot', '/:kiss', '/:<&', '/:&>',
    );
    foreach ($emotions as $index => $emotion) {
        $message = str_replace($emotion, '<img style="width:' . $size . ';vertical-align:middle;" src="http://res.mail.qq.com/zh_CN/images/mo/DEFAULT2/' . $index . '.gif" />', $message);
    }

    return $message;
}

/**
 * 字符串加密或解密.
 * @param string $string    要加密或解密的字符串
 * @param string $operation 操作类型 'ENCODE' 或 'DECODE'
 * @param string $key       加密密钥或解密密钥
 * @param int    $expiry    过期时间, 秒为单位
 * @return string
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    if (empty($string)) {
        return '';
    }
    $ckey_length = 4;
    $key = md5('' != $key ? $key : $GLOBALS['_W']['config']['setting']['authkey']);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ('DECODE' == $operation ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = 'DECODE' == $operation ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; ++$i) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; ++$i) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; ++$i) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ('DECODE' == $operation) {
        if ((0 == substr($result, 0, 10) || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

/**
 * 格式化显示文件大小.
 * @param int $size 文件原始大小
 * @param $unit boolean 是否显示单位
 * @return string
 */
function sizecount($size, $unit = false) {
    if ($size >= 1073741824) {
        $size = round($size / 1073741824 * 100) / 100 . ' GB';
    } elseif ($size >= 1048576) {
        $size = round($size / 1048576 * 100) / 100 . ' MB';
    } elseif ($size >= 1024) {
        $size = round($size / 1024 * 100) / 100 . ' KB';
    } else {
        $size = $size . ' Bytes';
    }
    if ($unit) {
        $size = preg_replace('/[^0-9\.]/', '', $size);
    }

    return $size;
}

/**
 *字节数转成 bit.
 * @param string $str 字节数
 * @return float
 */
function bytecount($str) {
    $unit = strtolower(substr($str, -1));
    if ('b' == $unit) {
        $str = substr($str, 0, -1);
    }
    if ('k' == $unit) {
        return floatval($str) * 1024;
    }
    if ('m' == $unit) {
        return floatval($str) * 1048576;
    }
    if ('g' == $unit) {
        return floatval($str) * 1073741824;
    }
}

/**
 * 获取数组的XML结构.
 * @param array $arr   要转换的数组
 * @param int   $level 节点层级, 1 为 Root
 * @return string
 */
function array2xml($arr, $level = 1) {
    $s = 1 == $level ? '<xml>' : '';
    foreach ($arr as $tagname => $value) {
        if (is_numeric($tagname)) {
            $tagname = $value['TagName'];
            unset($value['TagName']);
        }
        if (!is_array($value)) {
            $s .= "<{$tagname}>" . (!is_numeric($value) ? '<![CDATA[' : '') . $value . (!is_numeric($value) ? ']]>' : '') . "</{$tagname}>";
        } else {
            $s .= "<{$tagname}>" . array2xml($value, $level + 1) . "</{$tagname}>";
        }
    }
    $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);

    return 1 == $level ? $s . '</xml>' : $s;
}
/**
 * 转化一个xml结构成为数组.
 * @param string $xml
 */
function xml2array($xml) {
    if (empty($xml)) {
        return array();
    }
    $result = array();
    $xmlobj = isimplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xmlobj instanceof SimpleXMLElement) {
        $result = json_decode(json_encode($xmlobj), true);
        if (is_array($result)) {
            return $result;
        } else {
            return '';
        }
    } else {
        return $result;
    }
}

/**
 * 获取当前文件的相对路径
 * @return mixed|string
 */
function scriptname() {
    $script_name = basename($_SERVER['SCRIPT_FILENAME']);
    if (basename($_SERVER['SCRIPT_NAME']) === $script_name) {
        $script_name = $_SERVER['SCRIPT_NAME'];
    } else {
        if (basename($_SERVER['PHP_SELF']) === $script_name) {
            $script_name = $_SERVER['PHP_SELF'];
        } else {
            if (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $script_name) {
                $script_name = $_SERVER['ORIG_SCRIPT_NAME'];
            } else {
                if (false !== ($pos = strpos($_SERVER['PHP_SELF'], '/'))) {
                    $script_name = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $script_name;
                } else {
                    if (isset($_SERVER['DOCUMENT_ROOT']) && 0 === strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT'])) {
                        $script_name = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));
                    } else {
                        $script_name = 'unknown';
                    }
                }
            }
        }
    }

    return $script_name;
}

/**
 * 将unicode编码值转换为utf-8编码字符.
 */
function utf8_bytes($cp) {
    if ($cp > 0x10000) {
        // 4 bytes
        return	chr(0xF0 | (($cp & 0x1C0000) >> 18)) .
            chr(0x80 | (($cp & 0x3F000) >> 12)) .
            chr(0x80 | (($cp & 0xFC0) >> 6)) .
            chr(0x80 | ($cp & 0x3F));
    } elseif ($cp > 0x800) {
        // 3 bytes
        return	chr(0xE0 | (($cp & 0xF000) >> 12)) .
            chr(0x80 | (($cp & 0xFC0) >> 6)) .
            chr(0x80 | ($cp & 0x3F));
    } elseif ($cp > 0x80) {
        // 2 bytes
        return	chr(0xC0 | (($cp & 0x7C0) >> 6)) .
            chr(0x80 | ($cp & 0x3F));
    } else {
        // 1 byte
        return chr($cp);
    }
}

function media2local($media_id, $all = false) {
    global $_W;
    load()->model('material');
    $data = material_get($media_id);
    if (!is_error($data)) {
        $data['attachment'] = tomedia($data['attachment'], true);
        if (!$all) {
            return $data['attachment'];
        }

        return $data;
    } else {
        return '';
    }
}

function aes_decode($message, $encodingaeskey = '', $appid = '') {
    $key = base64_decode($encodingaeskey . '=');

    $ciphertext_dec = base64_decode($message);
    $iv = substr($key, 0, 16);
    //php7不支持mcrypt,换成openssl
    $decrypted = openssl_decrypt($ciphertext_dec, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    $pad = ord(substr($decrypted, -1));
    if ($pad < 1 || $pad > 32) {
        $pad = 0;
    }
    $result = substr($decrypted, 0, (strlen($decrypted) - $pad));
    if (strlen($result) < 16) {
        return '';
    }
    $content = substr($result, 16, strlen($result));
    $len_list = unpack('N', substr($content, 0, 4));
    $contentlen = $len_list[1];
    $content = substr($content, 4, $contentlen);
    $from_appid = substr($content, 4);
    if (!empty($appid) && $appid != $from_appid) {
        return '';
    }

    return $content;
}

function aes_encode($message, $encodingaeskey = '', $appid = '') {
    $key = base64_decode($encodingaeskey . '=');
    $text = random(16) . pack('N', strlen($message)) . $message . $appid;

    $iv = substr($key, 0, 16);

    $block_size = 32;
    $text_length = strlen($text);
    //计算需要填充的位数
    $amount_to_pad = $block_size - ($text_length % $block_size);
    if (0 == $amount_to_pad) {
        $amount_to_pad = $block_size;
    }
    //获得补位所用的字符
    $pad_chr = chr($amount_to_pad);
    $tmp = '';
    for ($index = 0; $index < $amount_to_pad; ++$index) {
        $tmp .= $pad_chr;
    }
    $text = $text . $tmp;
    //加密，php7不支持mcrypt,换成openssl
    $encrypted = openssl_encrypt($text, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    //加密后的消息
    $encrypt_msg = base64_encode($encrypted);

    return $encrypt_msg;
}

/**
 *  aes_pkcs7解密函数.
 * @param $encrypt_data 待解密文件（ 经过 base64_encode 编码 ）
 * @param $key 解密key
 * @param bool $iv 偏移量 （经过 base64_encode 编码 ）
 * @return array
 */
function aes_pkcs7_decode($encrypt_data, $key, $iv = false) {
    load()->library('pkcs7');
    $encrypt_data = base64_decode($encrypt_data);
    if (!empty($iv)) {
        $iv = base64_decode($iv);
    }
    $pc = new Prpcrypt($key);
    $result = $pc->decrypt($encrypt_data, $iv);
    if (0 != $result[0]) {
        return error($result[0], '解密失败');
    }

    return $result[1];
}

/*
 * 重新封装 isimplexml_load_string 函数。解决安全问题
 * */
function isimplexml_load_string($string, $class_name = 'SimpleXMLElement', $options = 0, $ns = '', $is_prefix = false) {
    libxml_disable_entity_loader(true);
    if (preg_match('/(\<\!DOCTYPE|\<\!ENTITY)/i', $string)) {
        return false;
    }
    $string = preg_replace('/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f\\x7f]/', '', $string); //过滤xml中的控制字符
    return simplexml_load_string($string, $class_name, $options, $ns, $is_prefix);
}
/*
 * 修复&nbsp;在utf8编码下被转换成黑块的坑
 */
function ihtml_entity_decode($str) {
    $str = str_replace('&nbsp;', '#nbsp;', $str);

    return str_replace('#nbsp;', '&nbsp;', html_entity_decode(urldecode($str)));
}
/**
 * 变更数据组键值为小写，支持多维数据.
 * @param unknown $arr
 * @param number  $stat
 * @return multitype:|multitype:unknown
 */
function iarray_change_key_case($array, $case = CASE_LOWER) {
    if (!is_array($array) || empty($array)) {
        return array();
    }
    $array = array_change_key_case($array, $case);
    foreach ($array as $key => $value) {
        if (empty($value) && is_array($value)) {
            $array[$key] = '';
        }
        if (!empty($value) && is_array($value)) {
            $array[$key] = iarray_change_key_case($value, $case);
        }
    }

    return $array;
}

/**
 * 过滤GET,POST传入的路径中的危险字符.
 * @param string $path
 * @return bool | string 正常返回路径，否则返回空
 */
function parse_path($path) {
    $danger_char = array('../', '{php', '<?php', '<%', '<?', '..\\', '\\\\', '\\', '..\\\\', '%00', '\0', '\r');
    foreach ($danger_char as $char) {
        if (strexists($path, $char)) {
            return false;
        }
    }

    return $path;
}

/**
 * 文件大小.
 * @param string $dir 文件的路径
 * @return int $size 文件大小
 */
function dir_size($dir) {
    $size = 0;
    if (is_dir($dir)) {
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ('.' != $entry && '..' != $entry) {
                if (is_dir("{$dir}/{$entry}")) {
                    $size += dir_size("{$dir}/{$entry}");
                } else {
                    $size += filesize("{$dir}/{$entry}");
                }
            }
        }
        closedir($handle);
    }

    return $size;
}

/**
 * 获取字符串的大写英文首字母.
 * @param unknown $str
 * @return string
 */
function get_first_pinyin($str) {
    static $pinyin;
    $first_char = '';
    $str = trim($str);
    if (empty($str)) {
        return $first_char;
    }
    if (empty($pinyin)) {
        load()->library('pinyin');
        $pinyin = new Pinyin_Pinyin();
    }
    $first_char = $pinyin->get_first_char($str);

    return $first_char;
}

/**
 * 过滤字符串中的emoji表情（微信昵称过滤）.
 */
function strip_emoji($nickname) {
    $clean_text = '';
    // Match Emoticons
    $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
    $clean_text = preg_replace($regexEmoticons, '_', $nickname);
    // Match Miscellaneous Symbols and Pictographs
    $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
    $clean_text = preg_replace($regexSymbols, '_', $clean_text);
    // Match Transport And Map Symbols
    $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
    $clean_text = preg_replace($regexTransport, '_', $clean_text);
    // Match Miscellaneous Symbols
    $regexMisc = '/[\x{2600}-\x{26FF}]/u';
    $clean_text = preg_replace($regexMisc, '_', $clean_text);
    // Match Dingbats
    $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
    $clean_text = preg_replace($regexDingbats, '_', $clean_text);

    $clean_text = str_replace("'", '_', $clean_text);
    $clean_text = str_replace('"', '_', $clean_text);
    $clean_text = str_replace('“', '_', $clean_text);
    $clean_text = str_replace('゛', '_', $clean_text);
    $search = array(' ', '　', "\n", "\r", "\t");
    $replace = array('_', '_', '_', '_', '_');

    return str_replace($search, $replace, $clean_text);
}

/**
 * 把一个可能包含emoji的字符串中的unicode码转换为实际的emoji.
 * @param string $string
 */
function emoji_unicode_decode($string) {
    preg_match_all('/\[U\+(\\w{4,})\]/i', $string, $match);
    if (!empty($match[1])) {
        foreach ($match[1] as $emojiUSB) {
            $string = str_ireplace("[U+{$emojiUSB}]", utf8_bytes(hexdec($emojiUSB)), $string);
        }
    }

    return $string;
}

function emoji_unicode_encode($string) {
    $ranges = array(
        '\\\\ud83c[\\\\udf00-\\\\udfff]', // U+1F300 to U+1F3FF
        '\\\\ud83d[\\\\udc00-\\\\ude4f]', // U+1F400 to U+1F64F
        '\\\\ud83d[\\\\ude80-\\\\udeff]',  // U+1F680 to U+1F6FF
    );
    preg_match_all('/' . implode('|', $ranges) . '/i', $string, $match);
    print_r($match);
    exit;
}

/*
 *  指定开头的字符串
 * @param $haystack 原始字符串
 * @param $needles 开头字符串
 * @return bool
 */
if (!function_exists('starts_with')) {
    function starts_with($haystack, $needles) {
        foreach ((array) $needles as $needle) {
            if ('' != $needle && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }
}

function ierror_page($message, $redirect_url = '') {
    $image_url = !empty($redirect_url) ? '../web/resource/images/loading.gif' : '//cdn.w7.cc/ued/jump/image/jump-logo.png';
    $html = '<!DOCTYPE html>
		<html lang="zh-cn">
			<head>
				<meta charset="utf-8">
				<meta http-equiv="X-UA-Compatible" content="IE=edge">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title>错误页提示</title>
			</head>
			<style>
				html,body,.jump{height:100vh;width:100vw;overflow:hidden;background-color:#fff}.jump{position:relative;text-align:center}.center-box{margin:280px auto 0;height:230px;width:600px;display:inline-block;text-align:center}.jump-content{font-size:18px;line-height:30px;color:#666;font-weight:300}.jump-tips{font-size:14px;line-height:30px;color:#999}
			</style>
			<body>
				<div class="jump">
					<div class="center-box">
						<img src="' . $image_url . '" alt="" style="margin-bottom:10px;width:40px">
						<div class="jump-content">' . $message . '</div>
					</div>
				</div>
			</body>';
    if (!empty($redirect_url)) {
        $html .= '<script>
			window.onload = function() {
				var count = 3
				var i = setInterval(function() {
					count--
					clearInterval(i)
					location.href = "' . $redirect_url . '";
					},2000)
			}
			</script>';
    }
    $html .= '</html>';

    return $html;
}

function iconsole_log($data) {
    echo '<script>';
    echo 'console.log(' . json_encode($data) . ')';
    echo '</script>';
}

/**
 * 判断给定字符串是否是三段版本号
 * @param $string
 * @return bool
 */
function str_is_version($string) {
    $string_array = explode('.', $string);
    $sum = 0;
    foreach ($string_array as $item) {
        if (!is_numeric($item)) {
            continue;
        }
        $sum++;
    }
    if ($sum != 3) {
        return false;
    }
    return true;
}
load()->func('safe');
