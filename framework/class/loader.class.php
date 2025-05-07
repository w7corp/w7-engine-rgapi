<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

/**
 * @return Loader
 */
function load() {
    static $loader;
    if (empty($loader)) {
        $loader = new Loader();
    }

    return $loader;
}

/**
 * 加载一个表抽象对象
 * @param string $name 服务名称
 * @return We7Table 表模型
 */
function table($name) {
    $table_classname = '\\We7\\Table\\';
    $subsection_name = explode('_', $name);
    if (1 == count($subsection_name)) {
        $table_classname .= ucfirst($subsection_name[0]) . '\\' . ucfirst($subsection_name[0]);
    } else {
        foreach ($subsection_name as $key => $val) {
            if (0 == $key) {
                $table_classname .= ucfirst($val) . '\\';
            } else {
                $table_classname .= ucfirst($val);
            }
        }
    }
    return new $table_classname();
}

/**
 * php文件加载器.
 * @method bool func($name)
 * @method bool model($name)
 * @method bool classs($name)
 * @method bool web($name)
 * @method bool app($name)
 * @method bool library($name)
 */
class Loader {
    private $cache = array();
    private $singletonObject = array();
    private $libraryMap = array(
        'agent' => 'agent/agent.class',
        'captcha' => 'captcha/captcha.class',
        'pdo' => 'pdo/PDO.class',
        'qrcode' => 'qrcode/phpqrcode',
        'pinyin' => 'pinyin/pinyin',
        'pkcs7' => 'pkcs7/pkcs7Encoder',
        'json' => 'json/JSON',
        'phpmailer' => 'phpmailer/PHPMailerAutoload',
        'oss' => 'alioss/autoload',
        'qiniu' => 'qiniu/autoload',
        'cosv5' => 'cosv5/index',
        'wechatpay-v3' => 'wechatpay-guzzle-middleware/index',
        'wechatpayv3' => 'wechatpay/index',
    );
    private $loadTypeMap = array(
        'func' => '/framework/function/%s.func.php',
        'model' => '/framework/model/%s.mod.php',
        'classs' => '/framework/class/%s.class.php',
        'library' => '/framework/library/%s.php',
        'table' => '/framework/table/%s.table.php',
        'web' => '/web/common/%s.func.php',
        'app' => '/app/common/%s.func.php',
    );
    private $accountMap = array(
        'pay' => 'pay/pay',
        'account' => 'account/account',
        'weixin.account' => 'account/weixin.account',
        'weixin.platform' => 'account/weixin.platform',
        'aliapp.account' => 'account/aliapp.account',
        'baiduapp.account' => 'account/baiduapp.account',
        'toutiaoapp.account' => 'account/toutiaoapp.account',
        'phoneapp.account' => 'account/phoneapp.account',
        'webapp.account' => 'account/webapp.account',
        'wxapp.account' => 'account/wxapp.account',
        'wxapp.platform' => 'account/wxapp.platform',
    );

    public function __construct() {
        $this->registerAutoload();
    }

    public function registerAutoload() {
        spl_autoload_register(array($this, 'autoload'));
    }

    public function autoload($class) {
        $section = array(
            'Table' => '/framework/table/',
        );
        //兼容旧版load()方式加载类
        $classmap = array(
            'We7Table' => 'table',
        );
        if (isset($classmap[$class])) {
            load()->classs($classmap[$class]);
        } elseif (preg_match('/^[0-9a-zA-Z\-\\\\_]+$/', $class)
            && (0 === stripos($class, 'We7') || 0 === stripos($class, '\We7'))
            && false !== stripos($class, '\\')) {
            $group = explode('\\', $class);
            $path = IA_ROOT . $section[$group[1]];
            unset($group[0]);
            unset($group[1]);
            $file_path = $path . implode('/', $group) . '.php';
            if (is_file($file_path)) {
                include $file_path;
            }
            //如果没有找到表，默认路由到Core命名空间，兼容之前命名不标准
            $file_path = $path . 'Core/' . implode('', $group) . '.php';
            if (is_file($file_path)) {
                include $file_path;
            }
        }
    }

    public function __call($type, $params) {
        global $_W;
        $name = $cachekey = array_shift($params);

        $accountMapKey = array_search($name, $this->accountMap);
        if (!empty($accountMapKey)) {
            $name = $cachekey = $accountMapKey;
        }

        if (!empty($this->cache[$type]) && isset($this->cache[$type][$cachekey])) {
            return true;
        }
        if (empty($this->loadTypeMap[$type])) {
            return true;
        }
        //第三方库文件因为命名差异，支持定义别名
        if ('library' == $type && !empty($this->libraryMap[$name])) {
            $name = $this->libraryMap[$name];
        }
        if ('classs' == $type && !empty($this->accountMap[$name])) {
            //兼容升级写法，后续直接去掉if判断
            $filename = sprintf($this->loadTypeMap[$type], $this->accountMap[$name]);
            if (file_exists(IA_ROOT . $filename)) {
                $name = $this->accountMap[$name];
            }
        }
        $file = sprintf($this->loadTypeMap[$type], $name);
        if (file_exists(IA_ROOT . $file)) {
            include IA_ROOT . $file;
            $this->cache[$type][$cachekey] = true;
        }

        return true;
    }

    /**
     * 获取一个服务单例，目录是在framework/class目录下.
     * @param unknown $name
     */
    public function singleton($name) {
        if (isset($this->singletonObject[$name])) {
            return $this->singletonObject[$name];
        }
        $this->singletonObject[$name] = $this->object($name);

        return $this->singletonObject[$name];
    }

    /**
     * 获取一个服务对象，目录是在framework/class目录下.
     * @param unknown $name
     */
    public function object($name) {
        $this->classs(strtolower($name));
        if (class_exists($name)) {
            return new $name();
        } else {
            return false;
        }
    }
}
