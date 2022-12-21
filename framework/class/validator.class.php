<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * User: fanyk
 * Date: 2017/10/30
 * Time: 11:24.
 */

/**
 * @see
 * @since 1.6.2
 * @since version
 */

/*
 * @example
$url = null;// 'https://www.baidu.com/&ssd=as&as=../asd../\asdad\\sdff..//asdas..sdf&script=123';
    $file = __DIR__.'/test_1x.php';
    $validor = new Validator(
        array(
            'data_url'=>$url,
            'data_int'=>3232,
            'data_file'=>$file,
            'data_array'=>array(1,2,3),
            'data_email'=>'sdjkd@qqcom',
            'data_string'=>'3',
            'data_ip'=> '1.25.55.55133',
            'data_in'=> '2',
            'data_notin'=>'4',
            'data_between'=>3,
            'data_same'=>'3',
            'data_date'=>'2017-11-22',
            'data_after'=>'2017-11-20',
            'data_before'=>'2017-11-23',
            'data_bool'=>'2232',
            'data_sms'=>'32'
            ),
        array(
        'data_url'=>'required|url',
        'data_int'=>'min:3233|max:90',
        'data_file'=>'file|min:8|max:3',
        'data_array'=>'array|size:3',
        'data_email'=>'email',
        'data_string'=>'required|string',
        'data_ip'=>'ip',
        'data_between'=>'between:5,10',
        'data_same'=> 'same:data_string'	,
        'data_date'=>'date',
        'data_after'=>'after:2017-11-21',
        'data_before'=>'before:data_date',
        'data_in'=>'in:3,4,5',
        'data_notin'=>array(array('name'=>'notin', 'params'=>array('3', '4', '7'))),
        'data_bool'=>'bool',
        'data_sms' => 'required|sms|size:5',

    ),array(
        'data_notin.notin'=>'字段内容必须不在 3,4,7 内',
        'data_same'=>'字段必须和data_string字段一致',
        'data_sms'=>'短信验证码不正确'
    ));
    $validor->addRule('sms', function($key, $value, $params, $validor){
        return false;

    });
    $validor->valid();
    var_dump($validor->errors());
*/

class Validator {
    const IMG = 'jpg, jepg, png, gif, bmp'; //常量只能是字符串
    const IMG_MIMETYPE = 'image/jpeg,image/jpeg,image/png,image/gif,image/bmp';

    private $defaults = array(
        'required' => ':attribute 必须填写',
        'integer' => ':attribute必须是整数',
        'int' => ':attribute必须是整数',
        'numeric' => ':attribute必须是数字',
        'string' => ':attribute必须是字符串',
        'json' => ':attribute 必须是json',
        'array' => ':attribute必须是数组',
        'min' => ':attribute不能小于%s',
        'max' => ':attribute不能大于%s',
        'between' => ':attribute 必须在 %s %s 范围内',
        'size' => ':attribute 大小必须是 %s',
        'url' => ':attribute不是有效的url', //url //不带参数默认过滤127 172 10开头的ip 预防ssrf
        'email' => ':attribute不是有效的邮箱',
        'mobile' => ':attribute不是有效的手机号',
        'file' => ':attribute必须是一个文件',
        'image' => ':attribute必须是一个图片',
        'ip' => ':attribute不是有效的ip',
        'in' => ':attribute 必须在 %s 内',
        'notin' => ':attribute 不在 %s 内',
        'date' => ':attribute 必须是有效的日期',
        'after' => ':attribute 日期不能小于 %s',
        'before' => ':attribute 日期不能大于 %s',
        'regex' => ':attribute 不是有效的数据', //regex:pattern
        'same' => ':attribute 和 %s 不一致', //some:field
        'bool' => ':attribute 必须是bool值',
        'path' => ':attribute 不是有效的路径',
    );
    /**
     * 自定义校验.
     *
     * @var array
     *
     * @since version
     */
    private $custom = array();
    /**
     *  验证规则.
     *
     * @var array
     */
    private $rules = array();
    /**
     *  验证失败后的消息.
     *
     * @var array
     */
    private $messages = array();
    /**
     * @var array 数据
     *
     * @since version
     */
    private $data = array();

    /** 所有的错误消息
     * @var array
     *
     * @since version
     */
    private $errors = array();

    public function __construct($data, $rules = array(), $messages = array()) {
        $this->data = $data;
        $this->rules = $this->parseRule($rules);
        $this->messages = $messages;
    }

    public static function create($data, $rules, array $messages = array()) {
        return new self($data, $rules, $messages);
    }

    /**
     * 添加规则.
     *
     * @param $key
     * @param string|array('name','params','callable'=>null)
     *
     * @since version
     */
    public function addRule($name, callable $callable) {
        if (!$name) {
            throw new InvalidArgumentException('无效的参数');
        }
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('无效的callable 对象');
        }
        $this->custom[$name] = $callable;
    }

    /**
     *  是否验证失败.
     *
     * @return bool
     */
    public function isError() {
        return 0 !== count($this->errors);
    }

    /**
     * 所有的错误.
     *
     * @return array
     *
     * @since version
     */
    public function error() {
        return $this->errors;
    }

    /**
     * 错误消息.
     *
     * @return string
     */
    public function message() {
        $init = array();
        $errmsg = array_reduce($this->error(), function ($result, $value) {
            return array_merge($result, array_values($value));
        }, $init);

        return implode(',', array_values($errmsg));
    }

    public function getData() {
        return $this->data;
    }

    /**
     * 解析rule.
     *
     * @param $rules
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    protected function parseRule(array $rules) {
        $result = array();
        if (0 == count($rules)) {
            throw new InvalidArgumentException('无效的rules');
        }
        foreach ($rules as $key => $rule) {
            $result[$key] = $this->parseSingleRule($rule);
        }

        return $result;
    }

    /**
     *  解析单个规则.
     *
     * @param $value
     *
     * @return mixed
     */
    protected function parseSingleRule($value) {
        if (is_string($value)) {
            $rules = explode('|', $value);
            $result = array();
            foreach ($rules as $dataKey => $rule) {
                $kv = explode(':', $rule);
                $params = array();
                if (count($kv) > 1) {
                    $params = explode(',', $kv[1]);
                }
                $result[] = array('name' => $kv[0], 'params' => $params);
            }

            return $result;
        }
        if (is_array($value)) {
            // 规范数据
            $value = array_map(function ($item) {
                if (is_string($item)) {
                    $name_params = explode(':', $item);
                    $params = array();
                    if (count($name_params) > 1) {
                        $params = explode(',', $name_params[1]);
                    }

                    return array('name' => $name_params[0], 'params' => $params);
                }
                if (!is_array($item)) {
                    throw new InvalidArgumentException('无效的rule参数');
                }
                $newitem = $item;
                if (!isset($item['name'])) {
                    $newitem = array();
                    $newitem['name'] = $newitem[0];
                    $newitem['params'] = count($item) > 1 ? $item[1] : array();
                }

                return $newitem;
            }, $value);

            return $value;
        }
        throw new InvalidArgumentException('无效的rule配置项');
    }

    private function getRules($key) {
        return isset($this->rules[$key]) ? $this->rules[$key] : array();
    }

    public function valid() {
        $this->errors = array();
        foreach ($this->data as $key => $value) {
            $rules = $this->getRules($key);
            foreach ($rules as $rule) {
                $this->doValid($key, $value, $rule);
            }
        }

        return $this->isError() ? error(1, $this->message()) : error(0);
    }

    /**
     *  单条验证
     *
     * @param $callback
     * @param $key
     * @param null  $value
     * @param array $params
     */
    private function doSingle($callback, $dataKey, $value, $rule) {
        $valid = call_user_func($callback, $dataKey, $value, $rule['params']);
        if (!$valid) {
            $this->errors[$dataKey][$rule['name']] = $this->getMessage($dataKey, $rule);

            return false;
        }

        return true;
    }

    /**
     *  自定义验证
     *
     * @param $callback
     * @param $dataKey
     * @param $value
     * @param $rule
     *
     * @since version
     */
    private function doCustom($callback, $dataKey, $value, $rule) {
        $valid = call_user_func($callback, $dataKey, $value, $rule['params'], $this);
        if (!$valid) {
            $this->errors[$dataKey][$rule['name']] = $this->getMessage($dataKey, $rule);

            return false;
        }

        return true;
    }

    /***
     *  获取验证的回调函数
     * @param $rule
     *
     * @return array|null
     *
     * @since version
     */
    private function doValid($dataKey, $value, $rule) {
        $ruleName = $rule['name'];
        if (isset($this->defaults[$ruleName])) {
            $callback = array($this, 'valid' . ucfirst($ruleName));

            return $this->doSingle($callback, $dataKey, $value, $rule);
        }
        if (isset($this->custom[$ruleName])) {
            $callback = $this->custom[$ruleName];

            return $this->doCustom($callback, $dataKey, $value, $rule, $this);
        }
        throw new InvalidArgumentException('valid' . $rule['name'] . ' 方法未定义');
    }

    /**
     *  获取值
     *
     * @param $key
     *
     * @return mixed|null
     */
    private function getValue($key) {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    protected function getMessage($dataKey, $rule) {
        $message = $this->getErrorMessage($dataKey, $rule['name']);
        if ($message) {
            $message = str_replace(':attribute', $dataKey, $message);
            $message = vsprintf($message, $rule['params']); //sprintf($message, $rule['params']);
        }

        return $message;
    }

    protected function getErrorMessage($dataKey, $ruleName) {
        $dr = $dataKey . '.' . $ruleName;
        if ($this->messages[$dr]) {
            return $this->messages[$dr];
        }
        if (isset($this->messages[$dataKey])) {
            return $this->messages[$dataKey];
        }

        return isset($this->defaults[$ruleName]) ? $this->defaults[$ruleName] : '错误';
    }

    /**
     *  验证参数必须.
     *
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public function validRequired($key, $value, $params) {
        if (is_null($value)) {
            return false;
        }

        if (is_array($value)) {
            return 0 != count($value);
        }

        if (is_string($value)) {
            return '' !== $value;
        }

        return true;
    }

    public function validInteger($key, $value, $params) {
        return false !== filter_var($value, FILTER_VALIDATE_INT);
    }

    public function validInt($key, $value, $params) {
        return $this->validInteger($key, $value, $params);
    }

    public function validNumeric($key, $value, $params) {
        return is_numeric($value);
    }

    public function validString($key, $value, $params) {
        return is_string($value);
    }

    public function validJson($key, $value, $params) {
        if (!is_scalar($value) && !method_exists($value, '__toString')) {
            return false;
        }

        json_decode($value);

        return JSON_ERROR_NONE === json_last_error();
    }

    /**
     *  校验数组.
     *
     * @param $key
     * @param $value
     *
     * @return bool
     *
     * @since version
     */
    public function validArray($key, $value, $params) {
        return is_array($value);
    }

    /**
     *  校验文件.
     *
     * @param $key
     * @param $value
     *
     * @return bool
     *
     * @since version
     */
    public function validFile($key, $value, $params) {
        return is_file($value);
    }

    public function validImage($key, $value, $params) {
        return $this->isImage($value);
    }

    public function validEmail($key, $value, $params) {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public function validMobile($key, $value, $params) {
        return $this->validRegex($key, $value, array('/^1[34578]\d{9}$/'));
    }

    /**
     * 正则验证
     *
     * @param $key
     * @param $value
     * @param $params
     *
     * @return int
     */
    public function validRegex($key, $value, $params) {
        $this->checkParams(1, $params, 'regex');

        return preg_match($params[0], $value);
    }

    /**
     *  验证ip是否正确.
     *
     * @param $key
     * @param $value
     *
     * @return bool|mixed
     */
    public function validIp($key, $value, $params) {
        if (!is_null($value)) {
            return filter_var($value, FILTER_VALIDATE_IP);
        }

        return false;
    }

    /**
     *  大小校验.
     *
     * @param $key
     * @param $value
     * @param $params
     *
     * @return bool
     */
    public function validSize($key, $value, $params) {
        $this->checkParams(1, $params, 'size');

        return $this->getSize($key, $value) == $params[0];
    }

    /**
     *  校验max.
     *
     * @param $key
     */
    public function validMax($key, $value, $params) {
        $this->checkParams(1, $params, 'max');
        $size = $this->getSize($key, $value);

        return $size <= $params[0];
    }

    /**
     *  校验max.
     *
     * @param $key
     * @param $value
     * @param $params
     *
     * @return bool
     */
    public function validMin($key, $value, $params) {
        $this->checkParams(1, $params, 'min');
        $size = $this->getSize($key, $value);

        return $size >= $params[0];
    }

    public function validUrl($key, $value, $params) {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }
        /**
         * @var array
         */
        $parseData = parse_url($value);
        $scheme = $parseData['scheme'];
        $allowSchemes = array('http', 'https');
        if (!in_array($scheme, $allowSchemes)) { //只能http https
            return false;
        }
        if (!isset($parseData['host'])) {
            return false;
        }
        $host = $parseData['host'];
        if (strexists($host, '@')) {
            return false;
        }
        $pattern = '/^(10|172|192|127)/'; //不允许本机ip
        if (preg_match($pattern, $host)) {
            return false;
        }

        return parse_path($value);
    }

    public function validDate($key, $value, $params) {
        return $this->checkDate($value);
    }

    public function validIn($key, $value, $params) {
        if (is_array($params)) {
            return in_array($value, $params, true);
        }

        return false;
    }

    public function validNotin($key, $value, $params) {
        return !$this->validIn($key, $value, $params);
    }

    /**
     *  验证和另一个字段是否相等.
     *
     * @param $key
     * @param $value
     * @param $params
     *
     * @return bool
     *
     * @since version
     */
    public function validSame($key, $value, $params) {
        $this->checkParams(1, $params, 'same');
        $otherField = $params[0];
        $otherValue = isset($this->data[$otherField]) ? $this->data[$otherField] : null;

        return (is_string($value) || is_numeric($value)) && $value === $otherValue;
    }

    public function validBetween($key, $value, $params) {
        $this->checkParams(2, $params, 'between');
        $size = $this->getSize($key, $value);

        return $size >= $params[0] && $size <= $params[1];
    }

    /**
     *  在指定日期之后.
     *
     * @param $key
     * @param $value
     * @param $params
     *
     * @return bool
     *
     * @since version
     */
    public function validAfter($key, $value, $params) {
        $this->checkParams(1, $params, 'afterdate');
        $date = $params[0]; //检查参数是否是日期 或者 指定字段
        return $this->compareDate($value, $date, '>');
    }

    /**
     * 在指定日期之前.
     *
     * @param $key
     * @param $value
     * @param $params
     *
     * @return bool
     *
     * @since version
     */
    public function validBefore($key, $value, $params) {
        $this->checkParams(1, $params, 'beforedate');
        $date = $params[0]; //检查参数是否是日期 或者 指定字段
        return $this->compareDate($value, $date, '<');
    }

    private function compareDate($value, $param, $operator = '=') {
        if (!$this->checkDate($param)) {
            $param = $this->getValue($param);
        }
        if ($this->checkDate($value) && $this->checkDate($param)) {
            $currentTime = $this->getDateTimestamp($value);
            $paramTime = $this->getDateTimestamp($param);

            return $this->compare($currentTime, $paramTime, $operator);
        }

        return false;
    }

    /**
     * Validate that an attribute is a boolean.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    public function validBool($key, $value, $params) {
        $acceptable = array(true, false, 0, 1, '0', '1');

        return in_array($value, $acceptable, true);
    }

    /**
     *  验证路径是否有非法字符.
     *
     * @param $key
     * @param $value
     * @param $params
     *
     * @return bool|string
     */
    public function validPath($key, $value, $params) {
        return parse_path($value);
    }

    protected function getSize($key, $value) {
        if (is_numeric($value)) {
            return $value;
        } elseif (is_array($value)) {
            return count($value);
        } elseif (is_file($value)) {
            return filesize($value) / 1024;
        } elseif ($value instanceof SplFileInfo) {
            return $value->getSize() / 1024;
        } elseif (is_string($value)) {
            return mb_strlen($value);
        }

        return false;
    }

    private function isImage($value) {
        if (is_file($value)) {
            $filename = $value;
            if ($value instanceof SplFileInfo) {
                $filename = $value->getFilename();
            }
            if (is_string($filename)) {
                $pathinfo = pathinfo($filename);
                $extension = strtolower($pathinfo['extension']);

                return !empty($extension) && in_array($extension, array('jpg', 'jpeg', 'gif', 'png'));
            }
        }

        return false;
    }

    private function mimeTypeIsImage($mimeType) {
        $imgMimeType = explode(',', static::IMG_MIMETYPE);

        return in_array($mimeType, $imgMimeType);
    }

    /**
     *  检测是否是日期
     *
     * @param $date
     *
     * @return bool
     *
     * @since version
     */
    private function checkDate($value) {
        if ($value instanceof DateTimeInterface) {
            return true;
        }
        if ((!is_string($value) && !is_numeric($value)) || false === strtotime($value)) {
            return false;
        }
        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    private function checkParams($count, $params, $ruleName) {
        if (count($params) != $count) {
            throw new InvalidArgumentException("$ruleName 参数个数必须为 $count 个");
        }
    }

    private function getDateTimestamp($date) {
        return $date instanceof DateTimeInterface ? $date->getTimestamp() : strtotime($date);
    }

    /**
     * Determine if a comparison passes between the given values.
     *
     * @param mixed  $first
     * @param mixed  $second
     * @param string $operator
     *
     * @return bool
     */
    protected function compare($first, $second, $operator) {
        switch ($operator) {
            case '<':
                return $first < $second;
            case '>':
                return $first > $second;
            case '<=':
                return $first <= $second;
            case '>=':
                return $first >= $second;
            case '=':
                return $first == $second;
            default:
                throw new InvalidArgumentException();
        }
    }
}
