<?php
/**
 * 提供系统安全获取传入值
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 从GPC中获取一个数字.
 *
 * @param unknown $value
 * @param string  $default
 */
function safe_gpc_int($value, $default = 0) {
    //如果包含小数点，优先按float对待
    //否则一律按int对待
    if (false !== strpos($value, '.')) {
        $value = floatval($value);
        $default = floatval($default);
    } else {
        $value = intval($value);
        $default = intval($default);
    }

    if (empty($value) && $default != $value) {
        $value = $default;
    }

    return $value;
}

/**
 * 检测某个值是否在某一数组中
 * @param $value 要检测的值
 * @param array $allow 要检测的数组
 * @param string $default 如果$value不在$allow中，返回的默认值
 * @return string
 */
function safe_gpc_belong($value, $allow = array(), $default = '') {
    if (empty($allow)) {
        return $default;
    }
    if (in_array($value, $allow, true)) {
        return $value;
    } else {
        return $default;
    }
}

/**
 * 字符串安全规则-纯字母
 * @param $value
 * @return bool
 */
function safe_rule_alpha($value) {
    return is_scalar($value) && 1 === preg_match('/^[A-Za-z]+$/', (string)$value);
}

/**
 * 字符串安全规则-字母和数字
 * @param $value
 * @return bool
 */
function safe_rule_alpha_num($value) {
    return is_scalar($value) && 1 === preg_match('/^[A-Za-z0-9]+$/', (string)$value);
}

/**
 * 字符串安全规则-字母、数字，以及破折号 (-) 和下划线 ( _ )
 * @param $value
 * @return bool
 */
function safe_rule_alpha_dash($value) {
    return is_scalar($value) && 1 === preg_match('/^[A-Za-z0-9\-_]+$/', (string)$value);
}

/**
 * 字符串安全规则-中文、字母、数字、破折号、下划线及常用标点
 * @param $value
 * @return bool
 */
function safe_rule_chs_alpha_dash($value) {
    return is_scalar($value) && 1 === preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\(\)（）\/\-\_\.\s\*,，。;；—:：#!！@$+～=]+$/u', (string)$value);
}

function safe_rule_url($value) {
    return is_scalar($value) && 1 === preg_match('/^((ht|f)tps?):\/\/([\w-]+(\.[\w-]+)*\/?)+([\x{4e00}-\x{9fa5}\w\-\.,@?^=%&:\/~\+#\[\]]*)?$/u', (string)$value);
}

function safe_rule_path($value) {
    return is_scalar($value) && 1 === preg_match('/^(\.\/|\/|[a-zA-Z0-9])[a-zA-Z0-9\-\.\/]+/', (string)$value);
}

function safe_rule_mobile($value) {
    return is_scalar($value) && 1 === preg_match(REGULAR_MOBILE, (string)$value);
}

function safe_rule_color($value) {
    return is_scalar($value) && 1 === preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', (string)$value);
}
/**
 * 转换一个安全字符串.
 * @param mixed  $value 要转换的字符串
 * @param string $default 默认值
 * @param string $rule 验证规则
 * @return string
 */
function safe_gpc_string($value, $default = '', $rule = '') {
    if (empty($value)) {
        return $default;
    }
    $rule = in_array($rule, array('path', 'url', 'mobile', 'alpha', 'alpha_num', 'alpha_dash', 'color', 'chs_alpha_dash')) ? $rule : 'chs_alpha_dash';
    switch ($rule) {
        case 'path':
            $value = safe_rule_path($value) ? $value : $default;
            break;
        case 'url':
            $value = safe_rule_url($value) ? $value : $default;
            break;
        case 'mobile':
            $value = safe_rule_mobile($value) ? $value : $default;
            break;
        case 'alpha':
            $value = safe_rule_alpha($value) ? $value : $default;
            break;
        case 'alpha_num':
            $value = safe_rule_alpha_num($value) ? $value : $default;
            break;
        case 'alpha_dash':
            $value = safe_rule_alpha_dash($value) ? $value : $default;
            break;
        case 'color':
            $value = safe_rule_color($value) ? $value : $default;
            break;
        case 'chs_alpha_dash':
            $value = safe_rule_chs_alpha_dash($value) ? $value : $default;
            break;
    }

    return trim($value);
}

/**
 * 转换一个安全路径.
 * @param string $value
 * @param string $default
 * @return string
 */
function safe_gpc_path($value, $default = '') {
    if (starts_with($value, 'http') || starts_with($value, 'ftp')) {
        $path = safe_gpc_string($value, '', 'url');
    } else {
        $path = safe_gpc_string($value, '', 'path');
    }
    $path = str_replace(array('..', '..\\', '\\\\', '\\', '..\\\\'), '', $path);

    if (empty($path) || $path != $value) {
        $path = $default;
    }

    return $path;
}

/**
 * 转换一个安全的字符串型数组.
 * @param unknown $value
 * @param array   $default
 */
function safe_gpc_array($value, $default = array()) {
    if (empty($value) || !is_array($value)) {
        return $default;
    }
    foreach ($value as &$row) {
        if (is_numeric($row) && (2147483647 > $row && -2147483648 < $row)) {
            $row = $row;
        } elseif (is_array($row)) {
            $row = safe_gpc_array($row, $default);
        } elseif (0 === strpos($row, 'http') || 0 === strpos($row, './')) {
            $row = safe_gpc_url($row, false);
        } else {
            $row = safe_gpc_string($row);
        }
    }

    return $value;
}

/**
 * 转换一个安全的布尔值
 * @param mixed $value
 * @return boolean
 */
function safe_gpc_boolean($value) {
    return boolval($value);
}

/**
 * 转换一个安全HTML数据.
 */
function safe_gpc_html($value, $default = '') {
    if (empty($value) || !is_string($value)) {
        return $default;
    }
    $value = safe_bad_str_replace($value);

    $value = safe_remove_xss($value);
    if (empty($value) && $value != $default) {
        $value = $default;
    }

    return $value;
}

function safe_gpc_sql($value, $operator = 'ENCODE', $default = '') {
    if (empty($value) || !is_string($value)) {
        return $default;
    }
    $value = trim(strtolower($value));

    $badstr = array(
        '_', '%', "'", chr(39),
        'select', 'join', 'union',
        'where', 'insert', 'delete',
        'update', 'like', 'drop',
        'create', 'modify', 'rename',
        'alter', 'cast',
    );
    $newstr = array(
        '\_', '\%', "''", '&#39;',
        'sel&#101;ct"', 'jo&#105;n', 'un&#105;on',
        'wh&#101;re', 'ins&#101;rt', 'del&#101;te',
        'up&#100;ate', 'lik&#101;', 'dro&#112',
        'cr&#101;ate', 'mod&#105;fy', 'ren&#097;me"',
        'alt&#101;r', 'ca&#115;',
    );

    if ('ENCODE' == $operator) {
        $value = str_replace($badstr, $newstr, $value);
    } else {
        $value = str_replace($newstr, $badstr, $value);
    }

    return $value;
}

/**
 * 转换一个安全URL.
 * @param string $value $_GPC中的值
 * @param bool   $strict_domain 是否严格限制只能为当前域下的URL
 * @param string $default
 */
function safe_gpc_url($value, $strict_domain = true, $default = '') {
    global $_W;
    if (empty($value) || !is_string($value)) {
        return $default;
    }
    $value = urldecode($value);

    if (starts_with($value, './') || starts_with($value, 'images/')) {
        return $value;
    }
    $value = safe_gpc_string($value, '', 'url');
    if ($strict_domain) {
        if (starts_with($value, array($_W['siteroot'], 'https://console.w7.cc', 'http://console.w7.cc'))) {
            return $value;
        }
        return $default;
    }
    if (starts_with($value, 'http') || starts_with($value, '//')) {
        return $value;
    }
    return $default;
}

/**
 *  去掉可能造成xss攻击的字符.
 * @param $val $string 需处理的字符串
 */
function safe_remove_xss($val) {
    $val = preg_replace('/([\x0e-\x19])/', '', $val);
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';

    for ($i = 0; $i < strlen($search); ++$i) {
        $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val);
        $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val);
    }
    preg_match_all('/href=[\'|\"](.*?)[\'|\"]|src=[\'|\"](.*?)[\'|\"]/i', $val, $matches);
    $url_list = array_merge($matches[1], $matches[2]);
    $encode_url_list = array();
    if (!empty($url_list)) {
        foreach ($url_list as $key => $url) {
            $val = str_replace($url, 'we7_' . $key . '_we7placeholder', $val);
            $encode_url_list[] = $url;
        }
    }
    $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'frameset', 'ilayer', 'bgsound', 'base');
    $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload', '@import');
    $ra = array_merge($ra1, $ra2);
    $found = true;
    while (true == $found) {
        $val_before = $val;
        for ($i = 0; $i < sizeof($ra); ++$i) {
            $pattern = '/';
            for ($j = 0; $j < strlen($ra[$i]); ++$j) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                    $pattern .= '|';
                    $pattern .= '|(&#0{0,8}([9|10|13]);)';
                    $pattern .= ')*';
                }
                $pattern .= $ra[$i][$j];
            }
            $pattern .= '/i';
            $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2);
            $val = preg_replace($pattern, $replacement, $val);
            if ($val_before == $val) {
                $found = false;
            }
        }
    }
    if (!empty($encode_url_list) && is_array($encode_url_list)) {
        foreach ($encode_url_list as $key => $url) {
            $val = str_replace('we7_' . $key . '_we7placeholder', $url, $val);
        }
    }

    return $val;
}

function safe_bad_str_replace($string) {
    if (empty($string)) {
        return '';
    }
    $badstr = array("\0", '%00', '%3C', '%3E', '<?', '<%', '<?php', '{php', '{if', '{loop', '../', '%0D%0A');
    $newstr = array('_', '_', '&lt;', '&gt;', '_', '_', '_', '_', '_', '_', '.._', '_');
    $string = str_replace($badstr, $newstr, $string);

    return $string;
}

/**
 * 检测密码强度.
 * @param $password
 * @return array|bool
 */
function safe_check_password($password) {
    global $_W;
    if (empty($_W['setting']['register']['safe'])) {
        $regex = '/[a-zA-Z0-9~!@#\$\^\&\*\(\)]{6,16}/';
        $message = '密码为6-16个字符，只允许大写字母、小写字母、数字及特殊字符：~!@#$^&*()';
    } else {
        $regex = '/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])[a-zA-Z0-9~!@#\$\^\&\*\(\)]{8,16}/';
        $message = '密码为8-16个字符，至少1个大写字母、1个小写字母和1个数字，可包含特殊字符:~!@#$^&*()，其他字符不被允许';
    }
    preg_match($regex, $password, $out);
    if (empty($out)) {
        return error(-1, $message);
    } else {
        if ($out[0] != $password) {
            return error(-1, $message);
        }
        return $password;
    }
}
