<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

function template_compat($filename) {
    static $mapping = array(
        'home/home' => 'index',
        'header' => 'common/header',
        'footer' => 'common/footer',
        'slide' => 'common/slide',
    );
    if (!empty($mapping[$filename])) {
        return $mapping[$filename];
    }
    return '';
}

function template_design($html) {
    if (empty($html)) {
        return '';
    }
    $html = str_replace(array('<?', '<%', '<?php', '{php'), '_', $html);
    $html = preg_replace('/<\s*?script.*[\n\f\r\t\v]*.*(src|language)+/i', '_', $html);

    $script_start = '<sc<x>ript type="text/ja<x>vasc<x>ript">';
    $script_end = '</sc<x>ript>';

    $count_down_script = <<<EOF
$(document).ready(function(){\r\n\t\t\t\t\tsetInterval(function(){\r\n\t\t\t\t\t\tvar timer = $('.timer');\r\n\t\t\t\t\t\tfor (var i = 0; i < timer.length; i++) {\r\n\t\t\t\t\t\t\tvar dead = $(timer.get(i)).attr('data');\r\n\t\t\t\t\t\t\tvar deadtime = dead.replace(/-/g,'/');\r\n\t\t\t\t\t\t\tdeadtime = new Date(deadtime).getTime();\r\n\t\t\t\t\t\t\tvar nowtime = Date.parse(Date());\r\n\t\t\t\t\t\t\tvar diff = deadtime - nowtime > 0 ? deadtime - nowtime : 0;\r\n\t\t\t\t\t\t\tvar res = {};\r\n\t\t\t\t\t\t\tres.day = parseInt(diff / (24 * 60 * 60 * 1000));\r\n\t\t\t\t\t\t\tres.hour = parseInt(diff / (60 * 60 * 1000) % 24);\r\n\t\t\t\t\t\t\tres.min = parseInt(diff / (60 * 1000) % 60);\r\n\t\t\t\t\t\t\tres.sec = parseInt(diff / 1000 % 60);\r\n\t\t\t\t\t\t\t$('.timer[data="'+dead+'"] .day').text(res.day);\r\n\t\t\t\t\t\t\t$('.timer[data="'+dead+'"] .hours').text(res.hour);\r\n\t\t\t\t\t\t\t$('.timer[data="'+dead+'"] .minutes').text(res.min);\r\n\t\t\t\t\t\t\t$('.timer[data="'+dead+'"] .seconds').text(res.sec);\r\n\t\t\t\t\t\t};\r\n\t\t\t\t\t}, 1000);\r\n\t\t\t\t});
EOF;
    $add_num_acript = <<<EOF
$(document).ready(function() {\r\n\t\t\t\t\tvar patt = new RegExp('c=home&a=page');\r\n\t\t\t\t\tif (patt.exec(window.location.href)) {\r\n\t\t\t\t\t\t$.post(window.location.href, {'do' : 'getnum'}, function(data) {\r\n\t\t\t\t\t\t\tif (data.message.errno == 0) {\r\n\t\t\t\t\t\t\t\t$('.counter-num').text(data.message.message.goodnum);\r\n\t\t\t\t\t\t\t}\r\n\t\t\t\t\t\t}, 'json');\r\n\t\t\t\t\t\t$(".app-good .element").click(function() {\r\n\t\t\t\t\t\t\tvar id=GetQueryString("id");\r\n\t\t\t\t\t\t\tif(id !=null && id.toString().length>=1 && localStorage.havegood != id){\r\n\t\t\t\t\t\t\t\t$.post(window.location.href, {'do': 'addnum'}, function(data) {\r\n\t\t\t\t\t\t\t\t\tif (data.message.errno == 0) {\r\n\t\t\t\t\t\t\t\t\t\tvar now = $('.counter-num').text();\r\n\t\t\t\t\t\t\t\t\t\tnow = parseInt(now)+1;\r\n\t\t\t\t\t\t\t\t\t\t$('.counter-num').text(now);\r\n\t\t\t\t\t\t\t\t\t\tlocalStorage.havegood = id;\r\n\t\t\t\t\t\t\t\t\t}\r\n\t\t\t\t\t\t\t\t}, 'json');\r\n\t\t\t\t\t\t\t}\r\n\t\t\t\t\t\t});\r\n\t\t\t\t\t\tfunction GetQueryString(name){\r\n\t\t\t\t\t\t\tvar reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");\r\n\t\t\t\t\t\t\tvar r = window.location.search.substr(1).match(reg);\r\n\t\t\t\t\t\t\tif(r!=null)return  unescape(r[2]); return null;\r\n\t\t\t\t\t\t}\t\t\t\t\t\t\r\n\t\t\t\t\t};\r\n\t\t\t\t});
EOF;
    if (strexists($html, $script_start . $add_num_acript . $script_end)) {
        $html = str_replace($script_start . $add_num_acript . $script_end, '<script type="text/javascript">' . $add_num_acript . '</script>', $html);
    }

    if (strexists($html, $script_start . $count_down_script . $script_end)) {
        $html = str_replace($script_start . $count_down_script . $script_end, '<script type="text/javascript">' . $count_down_script . '</script>', $html);
    }

    $link_error = '<li<x>nk href="./resource/components/swiper/swiper.min.css" rel="stylesheet">';
    if (strexists($html, $link_error)) {
        $html = str_replace($link_error, '<link href="./resource/components/swiper/swiper.min.css" rel="stylesheet">', $html);
    }
    $svg_start = 'xm<x>lns="http://www.w3.org/2000/svg" xm<x>lns:xli<x>nk="http://www.w3.org/1999/xli<x>nk"';
    $svg_end = 'xm<x>l:space="preserve" preserveAspectRatio="none">';
    if (strexists($html, $svg_start)) {
        $html = str_replace($svg_start, 'xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"', $html);
    }
    if (strexists($html, $svg_end)) {
        $html = str_replace($svg_end, 'xml:space="preserve" preserveAspectRatio="none">', $html);
    }

    return $html;
}

function template_page($id, $flag = TEMPLATE_DISPLAY) {
    global $_W;
    $page = pdo_get('site_page', array('id' => $id));
    if (empty($page)) {
        return error(1, 'Error: Page is not found');
    }
    if (empty($page['html'])) {
        return '';
    }

    $page['params'] = json_decode($page['params'], true);
    $GLOBALS['title'] = htmlentities($page['title'], ENT_QUOTES, 'UTF-8');
    $GLOBALS['_share'] = array('desc' => $page['description'], 'title' => $page['title'], 'imgUrl' => tomedia($page['params']['0']['params']['thumb'] ?? ''));

    $compile = IA_ROOT . "/data/tpl/app/{$id}.{$_W['template']}.tpl.php";
    $path = dirname($compile);
    if (!is_dir($path)) {
        load()->func('file');
        mkdirs($path);
    }
    $content = template_design($page['html']);

    $GLOBALS['bottom_menu'] = $page['params'][0]['property'][0]['params']['bottom_menu'] ? true : false;
    file_put_contents($compile, $content);
    switch ($flag) {
        case TEMPLATE_DISPLAY:
        default:
            extract($GLOBALS, EXTR_SKIP);
            template('common/header');
            include $compile;
            template('common/footer');
            break;
        case TEMPLATE_FETCH:
            extract($GLOBALS, EXTR_SKIP);
            ob_clean();
            ob_start();
            include $compile;
            $contents = ob_get_contents();
            ob_clean();
            return $contents;
            break;
        case TEMPLATE_INCLUDEPATH:
            return $compile;
            break;
    }
}

function template($filename, $flag = TEMPLATE_DISPLAY) {
    global $_W, $_GPC;
    $source = IA_ROOT . "/app/themes/{$_W['template']}/{$filename}.html";
    $compile = IA_ROOT . "/data/tpl/app/{$_W['template']}/{$filename}.tpl.php";
    if (!is_file($source)) {
        $compatFilename = template_compat($filename);
        if (!empty($compatFilename)) {
            return template($compatFilename, $flag);
        }
    }
    if (!is_file($source)) {
        $source = IA_ROOT . "/app/themes/default/{$filename}.html";
        $compile = IA_ROOT . "/data/tpl/app/default/{$filename}.tpl.php";
    }

    if (!is_file($source)) {
        exit("Error: template source '{$filename}' is not exist!");
    }
    $paths = pathinfo($compile);
    $_GPC['t'] = empty($_GPC['t']) ? 0 : intval($_GPC['t']);
    $compile = str_replace($paths['filename'], $_W['uniacid'] . '_' . $_GPC['t'] . '_' . $paths['filename'], $compile);

    if (DEVELOPMENT || !is_file($compile) || filemtime($source) > filemtime($compile)) {
        template_compile($source, $compile);
    }
    switch ($flag) {
        case TEMPLATE_DISPLAY:
        default:
            extract($GLOBALS, EXTR_SKIP);
            include $compile;
            break;
        case TEMPLATE_FETCH:
            extract($GLOBALS, EXTR_SKIP);
            ob_clean();
            ob_start();
            include $compile;
            $contents = ob_get_contents();
            ob_clean();
            return $contents;
            break;
        case TEMPLATE_INCLUDEPATH:
            return $compile;
            break;
    }
}

function template_compile($from, $to) {
    global $_W;
    $path = dirname($to);
    if (!is_dir($path)) {
        load()->func('file');
        mkdirs($path);
    }
    $content = template_parse(file_get_contents($from));
    file_put_contents($to, $content);
}

function template_parse($str) {
    load()->model('mc');
    $check_repeat_template = array(
        "'common\\/header'",
        "'common\\/footer'",
    );
    foreach ($check_repeat_template as $template) {
        if (preg_match_all('/{template\s+' . $template . '}/', $str, $match) > 1) {
            $replace = stripslashes($template);
            $str = preg_replace('/{template\s+' . $template . '}/i', '<?php (!empty($this) && $this instanceof WeModuleSite) ? (include $this->template(' . $replace . ', TEMPLATE_INCLUDEPATH)) : (include template(' . $replace . ', TEMPLATE_INCLUDEPATH));?>', $str, 1);
            $str = preg_replace('/{template\s+' . $template . '}/i', '', $str);
        }
    }
    $str = preg_replace('/<!--{(.+?)}-->/s', '{$1}', $str);
    $str = preg_replace('/{template\s+(.+?)}/', '<?php (!empty($this) && $this instanceof WeModuleSite) ? (include $this->template($1, TEMPLATE_INCLUDEPATH)) : (include template($1, TEMPLATE_INCLUDEPATH));?>', $str);
    $str = preg_replace('/{php\s+(.+?)}/', '<?php $1?>', $str);
    $str = preg_replace('/{if\s+(.+?)}/', '<?php if($1) { ?>', $str);
    $str = preg_replace('/{else}/', '<?php } else { ?>', $str);
    $str = preg_replace('/{else ?if\s+(.+?)}/', '<?php } else if($1) { ?>', $str);
    $str = preg_replace('/{\/if}/', '<?php } ?>', $str);
    $str = preg_replace('/{loop\s+(\S+)\s+(\S+)}/', '<?php if(is_array($1)) { foreach($1 as $2) { ?>', $str);
    $str = preg_replace('/{loop\s+(\S+)\s+(\S+)\s+(\S+)}/', '<?php if(is_array($1)) { foreach($1 as $2 => $3) { ?>', $str);
    $str = preg_replace('/{\/loop}/', '<?php } } ?>', $str);
    $str = preg_replace('/{(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)}/', '<?php echo $1;?>', $str);
    $str = preg_replace('/{(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\[\]\'\"\$]*)}/', '<?php echo $1;?>', $str);
    $str = preg_replace('/{url\s+(\S+)}/', '<?php echo url($1);?>', $str);
    $str = preg_replace('/{url\s+(\S+)\s+(array\(.+?\))}/', '<?php echo url($1, $2);?>', $str);
    $str = preg_replace('/{media\s+(\S+)}/', '<?php echo tomedia($1);?>', $str);
    $str = preg_replace_callback('/{data\s+(.+?)}/s', "moduledata", $str);
    $str = preg_replace_callback('/{hook\s+(.+?)}/s', "template_modulehook_parser", $str);
    $str = preg_replace('/{\/data}/', '<?php } } ?>', $str);
    $str = preg_replace('/{\/hook}/', '<?php ; ?>', $str);
    $str = preg_replace_callback('/<\?php([^\?]+)\?>/s', "template_addquote", $str);
    $str = preg_replace('/{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)}/s', '<?php echo $1;?>', $str);
    $str = str_replace('{##', '{', $str);
    $str = str_replace('##}', '}', $str);

    $module_name = empty($GLOBALS['_W']['current_module']['name']) ? '' : $GLOBALS['_W']['current_module']['name'];
    $business_stat_script = "</script><script type=\"text/javascript\" src=\"{$GLOBALS['_W']['siteroot']}app/index.php?i=" . mc_current_real_uniacid() . "&c=utility&a=visit&do=showjs&m={$module_name}\">";
    if (!empty($GLOBALS['_W']['setting']['remote']['type'])) {
        $str = str_replace('</body>', "<script>var imgs = document.getElementsByTagName('img');for(var i=0, len=imgs.length; i < len; i++){imgs[i].onerror = function() {if (!this.getAttribute('check-src') && (this.src.indexOf('http://') > -1 || this.src.indexOf('https://') > -1)) {this.src = this.src.indexOf('{$GLOBALS['_W']['attachurl_local']}') == -1 ? this.src.replace('{$GLOBALS['_W']['attachurl_remote']}', '{$GLOBALS['_W']['attachurl_local']}') : this.src.replace('{$GLOBALS['_W']['attachurl_local']}', '{$GLOBALS['_W']['attachurl_remote']}');this.setAttribute('check-src', true);}}};{$business_stat_script}</script></body>", $str);
    } else {
        $str = str_replace('</body>', "<script>;{$business_stat_script}</script></body>", $str);
    }
    $str = "<?php defined('IN_IA') or exit('Access Denied');?>" . $str;
    return $str;
}

function template_addquote($matchs) {
    $code = "<?php {$matchs[1]}?>";
    $code = preg_replace('/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\](?![a-zA-Z0-9_\-\.\x7f-\xff\[\]]*[\'"])/s', "['$1']", $code);
    return str_replace('\\\"', '\"', $code);
}

/**
 *
 * 此处变量为系统定义+自定义，系统定义在下方列出，如果模块有特殊参数需要
 * 则可在标签上自定义参数，在对应的func中使用即可。
 *
 * func - 指定获取数据的函数，此函数定义在模块目录下的model.php文件中
 * module - 指定获取数据的模块。
 * assign - 指定该标签得到数据后，存入的变量名称。如果为空则存在与func同名的变量中，方便在下方的代码中使用。
 * item - 指定循环体内的迭代时的变量名。相当于`foreach ($foo as $i => $row)` 中 $row变量。
 * limit - 指定获取变量时条数。
 * return - 为true时，获取到数据后直接循环输出，为false时，获取到数据后作为变量返回。
 *
 * @return string
 */
function moduledata($params = '') {
    if (empty($params[1])) {
        return '';
    }
    $params = explode(' ', $params[1]);
    if (empty($params)) {
        return '';
    }
    $data = array();
    foreach ($params as $row) {
        $row = explode('=', $row);
        $data[$row[0]] = str_replace(array("'", '"'), '', $row[1]);
        $row[1] = urldecode($row[1]);
    }
    $funcname = $data['func'];
    $assign = !empty($data['assign']) ? $data['assign'] : $funcname;
    $item = !empty($data['item']) ? $data['item'] : 'row';
    $data['limit'] = !empty($data['limit']) ? $data['limit'] : 10;
    if (empty($data['return']) || $data['return'] == 'false') {
        $return = false;
    } else {
        $return = true;
    }
    $data['index'] = !empty($data['index']) ? $data['index'] : 'iteration';
    if (!empty($data['module'])) {
        $modulename = $data['module'];
    } else {
        list($modulename) = explode('_', $data['func']);
    }
    $data['multiid'] = empty($_GET['t']) ? 0 : intval($_GET['t']);
    $data['uniacid'] = intval($_GET['i']);
    $data['acid'] = empty($_GET['j']) ? 0 : intval($_GET['j']);

    if (empty($modulename) || empty($funcname)) {
        return '';
    }
    $variable = var_export($data, true);
    $variable = preg_replace("/'(\\$[a-zA-Z_\x7f-\xff\[\]\']*?)'/", '$1', $variable);
    $php = "<?php \${$assign} = modulefunc('$modulename', '{$funcname}', {$variable}); ";
    if (empty($return)) {
        $php .= "if(is_array(\${$assign})) { \$i=0; foreach(\${$assign} as \$i => \${$item}) { \$i++; \${$item}['{$data['index']}'] = \$i; ";
    }
    $php .= "?>";
    return $php;
}

function modulefunc($modulename, $funcname, $params) {
    static $includes;

    $includefile = '';
    if (!function_exists($funcname)) {
        if (!isset($includes[$modulename])) {
            if (!file_exists(IA_ROOT . '/addons/' . $modulename . '/model.php')) {
                return '';
            } else {
                $includes[$modulename] = true;
                include_once IA_ROOT . '/addons/' . $modulename . '/model.php';
            }
        }
    }

    if (function_exists($funcname)) {
        return call_user_func_array($funcname, array($params));
    } else {
        return array();
    }
}

function template_modulehook_parser($params = array()) {
    load()->model('module');
    if (empty($params[1])) {
        return '';
    }
    $params = explode(' ', $params[1]);
    if (empty($params)) {
        return '';
    }
    $plugin = array();
    foreach ($params as $row) {
        $row = explode('=', $row);
        $plugin[$row[0]] = str_replace(array("'", '"'), '', $row[1]);
        $row[1] = urldecode($row[1]);
    }
    $plugin_info = module_fetch($plugin['module']);
    if (empty($plugin_info)) {
        return false;
    }

    if (empty($plugin['return']) || $plugin['return'] == 'false') {
        //$plugin['return'] = false;
    } else {
        //$plugin['return'] = true;
    }
    if (empty($plugin['func']) || empty($plugin['module'])) {
        return false;
    }

    if (defined('IN_SYS')) {
        $plugin['func'] = "hookWeb{$plugin['func']}";
    } else {
        $plugin['func'] = "hookMobile{$plugin['func']}";
    }

    $plugin_module = WeUtility::createModuleHook($plugin_info['name']);
    if (method_exists($plugin_module, $plugin['func']) && $plugin_module instanceof WeModuleHook) {
        $hookparams = var_export($plugin, true);
        if (!empty($hookparams)) {
            $hookparams = preg_replace("/'(\\$[a-zA-Z_\x7f-\xff\[\]\']*?)'/", '$1', $hookparams);
        } else {
            $hookparams = 'array()';
        }
        $php = "<?php \$plugin_module = WeUtility::createModuleHook('{$plugin_info['name']}');call_user_func_array(array(\$plugin_module, '{$plugin['func']}'), array('params' => {$hookparams})); ?>";
        return $php;
    } else {
        $php = "<!--模块 {$plugin_info['name']} 不存在嵌入点 {$plugin['func']}-->";
        return $php;
    }
}
