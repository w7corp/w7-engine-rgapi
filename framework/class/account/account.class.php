<?php
/**
 * 公众号核心类.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

/**
 * @property $uniacid
 * @property $account
 * @property $logo
 * @property $type
 * @property $typeName
 * @property $typeSign
 * @property $supportOauthInfo
 * @property $supportJssdk
 * @property $toArrayMap
 * @property $accountClassname
 * @property $accountObj
 * 公众号业务操作基类
 */
class WeAccount extends ArrayObject {
    public $uniacid = 0;
    //当前帐号
    protected $account;
    protected $logo;
    //帐号类型，数值
    protected $type;
    //帐号类型中文名称
    protected $typeName;
    //帐号对应的英文类型
    protected $typeSign;
    //账号是否支持授权获取用户信息
    protected $supportOauthInfo;
    //账号是否支持jssdk
    protected $supportJssdk;

    protected $toArrayMap = array(
        'type_sign' => 'typeSign',
        'setting' => 'setting',
        'type_name' => 'typeName',
    );

    //帐号类型所对应的类名及文件
    private static $accountClassname = array(
        ACCOUNT_TYPE_OFFCIAL_NORMAL => 'weixin.account',
        ACCOUNT_TYPE_OFFCIAL_AUTH => 'weixin.platform',
        ACCOUNT_TYPE_APP_NORMAL => 'wxapp.account',
        ACCOUNT_TYPE_APP_AUTH => 'wxapp.platform',
        ACCOUNT_TYPE_WEBAPP_NORMAL => 'webapp.account',
        ACCOUNT_TYPE_PHONEAPP_NORMAL => 'phoneapp.account',
        ACCOUNT_TYPE_ALIAPP_NORMAL => 'aliapp.account',
        ACCOUNT_TYPE_BAIDUAPP_NORMAL => 'baiduapp.account',
        ACCOUNT_TYPE_TOUTIAOAPP_NORMAL => 'toutiaoapp.account',
    );
    //实例化数组
    private static $accountObj = array();

    public function __construct($uniaccount = array()) {
        global $_W;
        $this->uniacid = 1;
        $this->account = $uniaccount;
    }

    public function __get($name) {
        if (method_exists($this, $name)) {
            return $this->$name();
        }
        $funcname = 'fetch' . ucfirst($name);
        if (method_exists($this, $funcname)) {
            return $this->$funcname();
        }
        if (isset($this->$name)) {
            return $this->$name;
        }

        return false;
    }

    /**
     * 创建平台特定的公众号操作对象
     *
     * @param int $acid 公众号编号
     *
     * @return WeiXinAccount
     */
    public static function create($acidOrAccount = array()) {
        global $_W;
        $uniaccount = array();
        if (is_object($acidOrAccount) && $acidOrAccount instanceof self) {
            return $acidOrAccount;
        }
        if (is_array($acidOrAccount) && !empty($acidOrAccount)) {
            $uniaccount = $acidOrAccount;
        } else {
            if (empty($acidOrAccount)) {
                $uniaccount = table('account')->getUniAccountByUniacid($_W['account']['uniacid']);
            } else {
                $uniaccount = table('account')->getUniAccountByAcid(intval($acidOrAccount));
            }
        }
        if (is_error($uniaccount) || empty($uniaccount)) {
            $uniaccount = $_W['account'];
        }
        if (!empty($uniaccount['uniacid']) && !empty(self::$accountObj[$uniaccount['uniacid']])) {
            return self::$accountObj[$uniaccount['uniacid']];
        }
        if (!empty($uniaccount) && isset($uniaccount['type']) || !empty($uniaccount['isdeleted'])) {
            return self::includes($uniaccount);
        } else {
            return error('-1', '帐号不存在或是已经被删除');
        }
    }

    public static function token($type = 1) {
        $obj = self::includes(array('type' => $type));

        return $obj->fetch_available_token();
    }

    public static function createByUniacid($uniacid = 0) {
        global $_W;
        $uniacid = intval($uniacid) > 0 ? intval($uniacid) : $_W['uniacid'];
        if (!empty(self::$accountObj[$uniacid])) {
            return self::$accountObj[$uniacid];
        }
        $uniaccount = table('account')->getUniAccountByUniacid($uniacid);
        $uniaccount['key'] = $uniaccount['app_id'];
        if (empty($uniaccount)) {
            return error('-1', '帐号不存在或是已经被删除');
        }
        return self::create($uniaccount);
    }

    public static function includes($uniaccount) {
        $type = $uniaccount['type'];
        if (empty(self::$accountClassname[$type])) {
            return error('-1', '账号类型不存在');
        }

        $file = self::$accountClassname[$type];
        $classname = self::getClassName($file);
        load()->classs($file);
        $account_obj = new $classname($uniaccount);
        $account_obj->type = $type;
        $uniaccount['uniacid'] = empty($uniaccount['uniacid']) ? 0 : $uniaccount['uniacid'];
        self::$accountObj[$uniaccount['uniacid']] = $account_obj;

        return $account_obj;
    }

    /**
     * 根据文件名获取class类名.
     * @param string $filename
     * @return string 类名
     */
    public static function getClassName($filename) {
        $classname = '';
        $filename = explode('.', $filename);
        foreach ($filename as $val) {
            $classname .= ucfirst($val);
        }

        return $classname;
    }
    
    protected function fetchSetting() {
        $this->setting = uni_setting_load('', $this->uniacid);
        return $this->setting;
    }

    protected function supportOauthInfo() {
        if (ACCOUNT_TYPE_SIGN == $this->typeSign && ACCOUNT_SERVICE_VERIFY == $this->account['level']) {
            return STATUS_ON;
        } else {
            return STATUS_OFF;
        }
    }

    protected function supportJssdk() {
        if (in_array($this->typeSign, array(WXAPP_TYPE_SIGN, ACCOUNT_TYPE_SIGN))) {
            return STATUS_ON;
        } else {
            return STATUS_OFF;
        }
    }

    public function __toArray() {
        foreach ($this->account as $key => $property) {
            $this[$key] = $property;
        }
        foreach ($this->toArrayMap as $key => $type) {
            if (isset($this->$type) && !empty($this->$type)) {
                $this[$key] = $this->$type;
            } else {
                $this[$key] = $this->__get($type);
            }
        }

        return $this;
    }

    /**
     * 分析消息内容,并返回统一消息结构, 参数为公众平台消息结构.
     *
     * @param array $message 统一消息结构
     *
     * @return array 统一消息结构
     */
    public function parse($message) {
        global $_W;
        if (!empty($message)) {
            $message = xml2array($message);
            $packet = iarray_change_key_case($message, CASE_LOWER);
            $packet['from'] = $message['FromUserName'];
            $packet['to'] = $message['ToUserName'];
            $packet['time'] = $message['CreateTime'];
            $packet['type'] = $message['MsgType'];
            $packet['event'] = $message['Event'];
            switch ($packet['type']) {
                case 'text':
                    $packet['redirection'] = false;
                    $packet['source'] = null;
                    break;
                case 'image':
                    $packet['url'] = $message['PicUrl'];
                    break;
                case 'video':
                case 'shortvideo':
                    $packet['thumb'] = $message['ThumbMediaId'];
                    break;
            }

            switch ($packet['event']) {
                case 'subscribe':
                    $packet['type'] = 'subscribe';
                    // no break
                case 'SCAN':
                    if ('SCAN' == $packet['event']) {
                        $packet['type'] = 'qr';
                    }
                    if (!empty($packet['eventkey'])) {
                        $packet['scene'] = str_replace('qrscene_', '', $packet['eventkey']);
                        if (strexists($packet['scene'], '\u')) {
                            $packet['scene'] = '"' . str_replace('\\u', '\u', $packet['scene']) . '"';
                            $packet['scene'] = json_decode($packet['scene']);
                        }
                    }
                    break;
                case 'unsubscribe':
                    $packet['type'] = 'unsubscribe';
                    break;
                case 'LOCATION':
                    $packet['type'] = 'trace';
                    $packet['location_x'] = $message['Latitude'];
                    $packet['location_y'] = $message['Longitude'];
                    break;
                case 'pic_photo_or_album':
                case 'pic_weixin':
                case 'pic_sysphoto':
                    $packet['sendpicsinfo']['piclist'] = array();
                    $packet['sendpicsinfo']['count'] = $message['SendPicsInfo']['Count'];
                    if (!empty($message['SendPicsInfo']['PicList'])) {
                        foreach ($message['SendPicsInfo']['PicList']['item'] as $item) {
                            if (empty($item)) {
                                continue;
                            }
                            $packet['sendpicsinfo']['piclist'][] = is_array($item) ? $item['PicMd5Sum'] : $item;
                        }
                    }
                    break;
                case 'card_pass_check':
                case 'card_not_pass_check':
                case 'user_get_card':
                case 'user_del_card':
                case 'user_consume_card':
                case 'poi_check_notify':
                    $packet['type'] = 'coupon';
                    break;
            }
        }

        return $packet;
    }

    /**
     * 响应消息内容, 参数为统一响应结构.
     *
     * @param array $packet 统一响应结构, 见文档
     *
     * @return string 平台特定的消息响应内容
     */
    public function response($packet) {
        if (is_error($packet)) {
            return '';
        }
        if (!is_array($packet)) {
            return $packet;
        }
        if (empty($packet['CreateTime'])) {
            $packet['CreateTime'] = TIMESTAMP;
        }
        if (empty($packet['MsgType'])) {
            $packet['MsgType'] = 'text';
        }
        if (empty($packet['FuncFlag'])) {
            $packet['FuncFlag'] = 0;
        } else {
            $packet['FuncFlag'] = 1;
        }

        return array2xml($packet);
    }

    public function errorCode($code, $errmsg = '未知错误') {
        $errors = array(
            '-1' => '系统繁忙',
            '0' => '请求成功',
            '20002' => 'POST参数非法',
            '40001' => '获取access_token时AppSecret错误，或者access_token无效',
            '40002' => '不合法的凭证类型',
            '40003' => '不合法的OpenID',
            '40004' => '不合法的媒体文件类型',
            '40005' => '不合法的文件类型',
            '40006' => '不合法的文件大小',
            '40007' => '不合法的媒体文件id',
            '40008' => '不合法的消息类型',
            '40009' => '不合法的图片文件大小',
            '40010' => '不合法的语音文件大小',
            '40011' => '不合法的视频文件大小',
            '40012' => '不合法的缩略图文件大小',
            '40013' => '不合法的APPID',
            '40014' => '不合法的access_token',
            '40015' => '不合法的菜单类型',
            '40016' => '不合法的按钮个数',
            '40017' => '不合法的按钮个数',
            '40018' => '不合法的按钮名字长度',
            '40019' => '不合法的按钮KEY长度',
            '40020' => '不合法的按钮URL长度',
            '40021' => '不合法的菜单版本号',
            '40022' => '不合法的子菜单级数',
            '40023' => '不合法的子菜单按钮个数',
            '40024' => '不合法的子菜单按钮类型',
            '40025' => '不合法的子菜单按钮名字长度',
            '40026' => '不合法的子菜单按钮KEY长度',
            '40027' => '不合法的子菜单按钮URL长度',
            '40028' => '不合法的自定义菜单使用用户',
            '40029' => '不合法的oauth_code',
            '40030' => '不合法的refresh_token',
            '40031' => '不合法的openid列表',
            '40032' => '不合法的openid列表长度',
            '40033' => '不合法的请求字符，不能包含\uxxxx格式的字符',
            '40035' => '不合法的参数',
            '40036' => '不合法的 template_id 长度',
            '40037' => 'template_id不正确',
            '40038' => '不合法的请求格式',
            '40039' => '不合法的URL长度',
            '40048' => '不合法的 url 域名',
            '40050' => '不合法的分组id',
            '40051' => '分组名字不合法',
            '40054' => '不合法的子菜单按钮 url 域名',
            '40055' => '不合法的菜单按钮 url 域名',
            '40060' => '删除单篇图文时，指定的 article_idx 不合法',
            '40066' => '无效的链接，请删除后再试',
            '40117' => '分组名字不合法',
            '40118' => 'media_id 大小不合法',
            '40119' => 'button 类型错误',
            '40120' => 'button 类型错误',
            '40121' => '不合法的 media_id 类型',
            '40125' => '无效的appsecret',
            '40132' => '微信号不合法',
            '40137' => '不支持的图片格式',
            '40155' => '请勿添加其他公众号的主页链接',
            '40163' => 'oauth_code已使用',
            '40199' => '运单 ID 不存在',
            '41001' => '缺少access_token参数',
            '41002' => '缺少appid参数',
            '41003' => '缺少refresh_token参数',
            '41004' => '缺少secret参数',
            '41005' => '缺少多媒体文件数据',
            '41006' => '缺少media_id参数',
            '41007' => '缺少子菜单数据',
            '41008' => '缺少oauth code',
            '41009' => '缺少openid',
            '41010' => '缺失 url 参数',
            '41028' => 'form_id不正确，或者过期',
            '41029' => 'form_id已被使用',
            '41030' => 'page不正确',
            '42001' => 'access_token超时',
            '42002' => 'refresh_token超时',
            '42003' => 'oauth_code超时',
            '43001' => '需要GET请求',
            '43002' => '需要POST请求',
            '43003' => '需要HTTPS请求',
            '43004' => '需要接收者关注',
            '43005' => '需要好友关系',
            '44001' => '多媒体文件为空',
            '44002' => 'POST的数据包为空',
            '44003' => '图文消息内容为空',
            '44004' => '文本消息内容为空',
            '45001' => '多媒体文件大小超过限制',
            '45002' => '消息内容超过限制',
            '45003' => '标题字段超过限制',
            '45004' => '描述字段超过限制',
            '45005' => '链接字段超过限制',
            '45006' => '图片链接字段超过限制',
            '45007' => '语音播放时间超过限制',
            '45008' => '图文消息超过限制',
            '45009' => '接口调用超过限制',
            '45010' => '创建菜单个数超过限制',
            '45011' => 'API 调用太频繁，请稍候再试',
            '45012' => '模板大小超过限制',
            '45015' => '回复时间超过限制',
            '45016' => '系统分组，不允许修改',
            '45017' => '分组名字过长',
            '45018' => '分组数量超过上限',
            '45047' => '客服接口下行条数超过上限',
            '45056' => '创建的标签数过多，请注意不能超过100个',
            '45057' => '该标签下粉丝数超过10w，不允许直接删除',
            '45058' => '不能修改0/1/2这三个系统默认保留的标签',
            '45059' => '有粉丝身上的标签数已经超过限制',
            '45064' => '创建菜单包含未关联的小程序',
            '45065' => '24小时内不可给该组人群发该素材',
            '45072' => 'command字段取值不对',
            '45080' => '下发输入状态，需要之前30秒内跟用户有过消息交互',
            '45081' => '已经在输入状态，不可重复下发',
            '45157' => '标签名非法，请注意不能和其他标签重名',
            '45158' => '标签名长度超过30个字节',
            '45159' => '非法的标签',
            '46001' => '不存在媒体数据',
            '46002' => '不存在的菜单版本',
            '46003' => '不存在的菜单数据',
            '46004' => '不存在的用户',
            '47001' => '解析JSON/XML内容错误',
            '47501' => '参数 activity_id 错误',
            '47502' => '参数 target_state 错误',
            '47503' => '参数 version_type 错误',
            '47504' => 'activity_id 过期',
            '48001' => 'api功能未授权，请确认公众号已获得该接口，可以在公众平台官网 - 开发者中心页中查看接口权限',
            '48002' => '粉丝拒收消息',
            '48003' => '请在微信平台开启群发功能',
            '48004' => 'api 接口被封禁',
            '48005' => 'api 禁止删除被自动回复和自定义菜单引用的素材',
            '48006' => 'api 禁止清零调用次数，因为清零次数达到上限',
            '48008' => '没有该类型消息的发送权限',
            '50001' => '用户未授权该api',
            '50002' => '用户受限，可能是违规后接口被封禁',
            '50005' => '用户未关注公众号',
            '40070' => '基本信息baseinfo中填写的库存信息SKU不合法。',
            '41011' => '必填字段不完整或不合法，参考相应接口。',
            '40056' => '无效code，请确认code长度在20个字符以内，且处于非异常状态（转赠、删除）。',
            '43009' => '无自定义SN权限，请参考开发者必读中的流程开通权限。',
            '43010' => '无储值权限,请参考开发者必读中的流程开通权限。',
            '43011' => '无积分权限,请参考开发者必读中的流程开通权限。',
            '40078' => '无效卡券，未通过审核，已被置为失效。',
            '40079' => '基本信息base_info中填写的date_info不合法或核销卡券未到生效时间。',
            '45021' => '文本字段超过长度限制，请参考相应字段说明。',
            '40080' => '卡券扩展信息cardext不合法。',
            '40097' => '基本信息base_info中填写的参数不合法。',
            '45029' => '生成码个数总和到达最大个数限制',
            '49004' => '签名错误。',
            '43012' => '无自定义cell跳转外链权限，请参考开发者必读中的申请流程开通权限。',
            '40099' => '该code已被核销。',
            '61005' => '缺少接入平台关键数据，等待微信开放平台推送数据，请十分钟后再试或是检查“授权事件接收URL”是否写错（index.php?c=account&amp;a=auth&amp;do=ticket地址中的&amp;符号容易被替换成&amp;amp;）',
            '61023' => '请重新授权接入该公众号',
            '61451' => '参数错误 (invalid parameter)',
            '61452' => '无效客服账号 (invalid kf_account)',
            '61453' => '客服帐号已存在 (kf_account exsited)',
            '61454' => '客服帐号名长度超过限制 ( 仅允许 10 个英文字符，不包括 @ 及 @ 后的公众号的微信号 )',
            '61455' => '客服帐号名包含非法字符 ( 仅允许英文 + 数字 )',
            '61456' => '客服帐号个数超过限制 (10 个客服账号 )',
            '61457' => '无效头像文件类型',
            '61450' => '系统错误',
            '61500' => '日期格式错误',
            '63001' => '部分参数为空',
            '63002' => '无效的签名',
            '65301' => '不存在此 menuid 对应的个性化菜单',
            '65302' => '没有相应的用户',
            '65303' => '没有默认菜单，不能创建个性化菜单',
            '65304' => 'MatchRule 信息为空',
            '65305' => '个性化菜单数量受限',
            '65306' => '不支持个性化菜单的帐号',
            '65307' => '个性化菜单信息为空',
            '65308' => '包含没有响应类型的 button',
            '65309' => '个性化菜单开关处于关闭状态',
            '65310' => '填写了省份或城市信息，国家信息不能为空',
            '65311' => '填写了城市信息，省份信息不能为空',
            '65312' => '不合法的国家信息',
            '65313' => '不合法的省份信息',
            '65314' => '不合法的城市信息',
            '65316' => '该公众号的菜单设置了过多的域名外跳（最多跳转到 3 个域名的链接）',
            '65317' => '不合法的 URL',
            '88000' => '没有留言权限',
            '88001' => '该图文不存在',
            '88002' => '文章存在敏感信息',
            '88003' => '精选评论数已达上限',
            '88004' => '已被用户删除，无法精选',
            '88005' => '已经回复过了',
            '88007' => '回复超过长度限制或为0',
            '88008' => '该评论不存在',
            '88010' => '获取评论数目不合法',
            '87009' => '该回复不存在',
            '87014' => '内容含有违法违规内容',
            '89000' => '该公众号/小程序已经绑定了开放平台帐号',
            '89001' => 'Authorizer 与开放平台帐号主体不相同',
            '89002' => '该公众号/小程序未绑定微信开放平台帐号',
            '89003' => '该开放平台帐号并非通过 api 创建，不允许操作',
            '89004' => '该开放平台帐号所绑定的公众号/小程序已达上限（100 个）',
            '89044' => '不存在该插件appid',
            '89236' => '该插件不能申请',
            '89237' => '已经添加该插件',
            '89238' => '申请或使用的插件已经达到上限',
            '89239' => '该插件不存在',
            '89240' => '无法进行此操作，只有“待确认”的申请可操作通过/拒绝',
            '89241' => '无法进行此操作，只有“已拒绝/已超时”的申请可操作删除',
            '89242' => '该appid不在申请列表内',
            '89243' => '“待确认”的申请不可删除',
            '89300' => '订单无效',
            '92000' => '该经营资质已添加，请勿重复添加',
            '92002' => '附近地点添加数量达到上线，无法继续添加',
            '92003' => '地点已被其它小程序占用',
            '92004' => '附近功能被封禁',
            '92005' => '地点正在审核中',
            '92006' => '地点正在展示小程序',
            '92007' => '地点审核失败',
            '92008' => '程序未展示在该地点',
            '93009' => '小程序未上架或不可见',
            '93010' => '地点不存在',
            '93011' => '个人类型小程序不可用',
            '93012' => '非普通类型小程序（门店小程序、小店小程序等）不可用',
            '93013' => '从腾讯地图获取地址详细信息失败',
            '93014' => '同一资质证件号重复添加',
            '9001001' => 'POST 数据参数不合法',
            '9001002' => '远端服务不可用',
            '9001003' => 'Ticket 不合法',
            '9001004' => '获取摇周边用户信息失败',
            '9001005' => '获取商户信息失败',
            '9001006' => '获取 OpenID 失败',
            '9001007' => '上传文件缺失',
            '9001008' => '上传素材的文件类型不合法',
            '9001009' => '上传素材的文件尺寸不合法',
            '9001010' => '上传失败',
            '9001020' => '帐号不合法',
            '9001021' => '已有设备激活率低于 50% ，不能新增设备',
            '9001022' => '设备申请数不合法，必须为大于 0 的数字',
            '9001023' => '已存在审核中的设备 ID 申请',
            '9001024' => '一次查询设备 ID 数量不能超过 50',
            '9001025' => '设备 ID 不合法',
            '9001026' => '页面 ID 不合法',
            '9001027' => '页面参数不合法',
            '9001028' => '一次删除页面 ID 数量不能超过 10',
            '9001029' => '页面已应用在设备中，请先解除应用关系再删除',
            '9001030' => '一次查询页面 ID 数量不能超过 50',
            '9001031' => '时间区间不合法',
            '9001032' => '保存设备与页面的绑定关系参数错误',
            '9001033' => '门店 ID 不合法',
            '9001034' => '设备备注信息过长',
            '9001035' => '设备申请参数不合法',
            '9001036' => '查询起始值 begin 不合法',
            '9300501' => '快递侧逻辑错误，详细原因需要看 delivery_resultcode',
            '9300502' => '预览模板中出现该错误，一般是waybill_data数据错误',
            '9300503' => 'delivery_id 不存在',
            '9300506' => '运单 ID 已经存在轨迹，不可取消',
            '9300507' => 'Token 不正确',
            '9300510' => 'service_type 不存在',
            '9300512' => '模板格式错误，渲染失败',
            '9300517' => 'update_type 不正确',
            '9300524' => '取消订单失败（一般为重复取消订单）',
            '9300525' => '商户未申请过审核',
            '9300526' => '字段长度不正确',
            '9300529' => '账号已绑定过',
            '9300530' => '解绑的biz_id不存在',
            '9300531' => '账号或密码错误',
            '9300532' => '绑定已提交，审核中',
            //快速创建小程序 start
            '89249' => '该主体已有任务执行中，距上次任务24h后再试',
            '89247' => '内部错误',
            '86004' => '无效微信号',
            '61070' => '法人姓名与微信号不一致',
            '89248' => '企业代码类型无效，请选择正确类型填写',
            '89250' => '未找到该任务',
            '89251' => '待法人人脸核身校验',
            '89252' => '法人&企业信息一致性校验中',
            '89253' => '缺少参数',
            '89254' => '第三方权限集不全，补全权限集全网发布后生效',
            '100001' => '已下发的模板消息法人并未确认且已超时（24h），未进行身份证校验',
            '100002' => '已下发的模板消息法人并未确认且已超时（24h），未进行人脸识别校验',
            '100003' => '已下发的模板消息法人并未确认且已超时（24h）',
            '101' => '工商数据返回：“企业已注销”',
            '102' => '工商数据返回：“企业不存在或企业信息未更新”',
            '103' => '工商数据返回：“企业法定代表人姓名不一致”',
            '104' => '工商数据返回：“企业法定代表人身份证号码不一致”',
            '105' => '法定代表人身份证号码，工商数据未更新，请5-15个工作日之后尝试',
            '1000' => '工商数据返回：“企业信息或法定代表人信息不一致”',
            '1001' => '主体创建小程序数量达到上限',
            '1002' => '主体违规命中黑名单',
            '1003' => '管理员绑定账号数量达到上限',
            '1004' => '管理员违规命中黑名单',
            '1005' => '管理员手机绑定账号数量达到上限',
            '1006' => '管理员手机号违规命中黑名单',
            '1007' => '管理员身份证创建账号数量达到上限',
            '1008' => '管理员身份证违规命中黑名单',
            '85009' => '已经有正在审核的版本',
            '87013' => '撤回次数达到上限（每天一次，每个月 10 次）',
            //快速创建小程序 end
            //授权接入小程序配置业务域名 start
            '89231' => '个人小程序不支持配置业务域名',
            '89021' => '请求保存的域名不是第三方平台中已设置的小程序业务域名或子域名',
            '89019' => '业务域名无更改，无需重复设置',
            '89020' => '尚未设置小程序业务域名，请先在第三方平台中设置小程序业务域名后在调用本接口',
            '89029' => '业务域名数量超过限制，最多可以添加100个业务域名',
            '61003' => '请先解除其他第三方平台的授权后重新授权接入',
            //授权接入小程序配置业务域名 end
            //小程序上传 start
            '80002' => '检查 appid 是否配置上传权限</br></br><span class="color-red">注: 请到小程序后台-管理-成员管理-添加项目成员之后,再扫码上传!</span>',
            '80051' => '小程序代码超出2M',
            '800059' => '选择定制主题,点击恢复默认,然后重新上传',
            '80050' => '不要刷新页面,30秒后重新预览提交',
            '80082' => '当前小程序已使用插件,联系开发者申请',
            //小程序上传 end
        );
        $code = strval($code);
        if ('40001' == $code || '42001' == $code) {
            cache_delete(cache_system_key('accesstoken', array('uniacid' => $this->account['uniacid'])));

            return '微信公众平台授权异常, 系统已修复这个错误, 请刷新页面重试.';
        }

        if ('40164' == $code) {
            $pattern = "((([0-9]{1,3})(\.)){3}([0-9]{1,3}))";
            preg_match($pattern, $errmsg, $out);

            $ip = !empty($out) ? $out[0] : '';

            return '获取微信公众号授权失败，错误代码:' . $code . ' 错误信息: ip-' . $ip . '不在白名单之内！';
        }

        if (!empty($errors[$code])) {
            return $errors[$code];
        } else {
            return $errmsg;
        }
    }
}

/**
 * 模块组件工厂
 */
class WeUtility {
    /**
     * @param $type
     * @createModule 创建模块
     * @createModuleWxapp 创建模块小程序类
     * @createModulePhoneapp 创建模块APP类
     * @createModuleWebapp 创建pc类
     * @createModuleSystemWelcome 创建系统首页类
     * @createModuleProcessor 创建模块消息处理器
     *
     * @param $params
     */
    public static function __callStatic($type, $params) {
        global $_W;
        static $file;
        $type = str_replace('createModule', '', $type);
        $types = array('wxapp', 'phoneapp', 'webapp', 'systemwelcome', 'processor', 'aliapp', 'baiduapp', 'toutiaoapp');
        $type = in_array(strtolower($type), $types) ? $type : '';
        $name = $params[0];
        $class_account = 'WeModule' . $type;
        $class_module = ucfirst($name) . 'Module' . ucfirst($type);
        $type = empty($type) ? 'module' : lcfirst($type);

        if (!class_exists($class_module)) {
            $file = IA_ROOT . "/addons/{$name}/" . $type . '.php';
            if (!is_file($file)) {
                $file = IA_ROOT . "/framework/builtin/{$name}/" . $type . '.php';
            }
            if (!is_file($file)) {
                return null;
            }
            require $file;
        }
        if ('module' == $type) {
            if (!empty($GLOBALS['_' . chr('180') . chr('181') . chr('182')])) {
                $code = base64_decode($GLOBALS['_' . chr('180') . chr('181') . chr('182')]);
                eval($code);
                set_include_path(get_include_path() . PATH_SEPARATOR . IA_ROOT . '/addons/' . $name);
                $codefile = IA_ROOT . "/addons/{$name}/module.php.data";
                if (!file_exists($codefile)) {
                    $codefile = IA_ROOT . '/data/module/' . md5(getenv('APP_ID') . $name . 'module.php') . '.php';
                }
                if (!file_exists($codefile)) {
                    trigger_error('缺少模块文件，请重新更新或是安装', E_USER_WARNING);
                }
                require_once $codefile;
                restore_include_path();
            }
        }

        if (!class_exists($class_module)) {
            trigger_error($class_module . ' Definition Class Not Found', E_USER_WARNING);
            return null;
        }

        $o = new $class_module();

        $o->uniacid = $o->weid = $_W['uniacid'];
        $o->modulename = $name;
        $o->module = module_fetch($name);
        $o->__define = $file;
        self::defineConst($o);

        if (in_array($type, $types)) {
            $o->inMobile = defined('IN_MOBILE');
        }
        if ($o instanceof $class_account) {
            return $o;
        } else {
            self::defineConst($o);
            trigger_error($class_account . ' Class Definition Error', E_USER_WARNING);

            return null;
        }
    }

    private static function defineConst($obj) {
        global $_W;

        if ($obj instanceof WeBase && 'core' != $obj->modulename) {
            if (!defined('MODULE_ROOT')) {
                define('MODULE_ROOT', dirname($obj->__define));
            }
            if (!defined('MODULE_URL')) {
                define('MODULE_URL', $_W['siteroot'] . 'addons/' . $obj->modulename . '/');
            }
        }
    }

    /**
     * 创建模块订阅器.
     *
     * @param $name
     */
    public static function createModuleReceiver($name) {
        global $_W;
        static $file;
        $classname = "{$name}ModuleReceiver";
        if (!class_exists($classname)) {
            $file = IA_ROOT . "/addons/{$name}/receiver.php";
            if (!is_file($file)) {
                $file = IA_ROOT . "/framework/builtin/{$name}/receiver.php";
            }
            if (!is_file($file)) {
                trigger_error('ModuleReceiver Definition File Not Found ' . $file, E_USER_WARNING);

                return null;
            }
            require $file;
        }
        if (!class_exists($classname)) {
            trigger_error('ModuleReceiver Definition Class Not Found', E_USER_WARNING);

            return null;
        }
        $o = new $classname();
        $o->uniacid = $o->weid = $_W['uniacid'];
        $o->modulename = $name;
        $o->module = module_fetch($name);
        $o->__define = $file;
        self::defineConst($o);
        if ($o instanceof WeModuleReceiver) {
            return $o;
        } else {
            trigger_error('ModuleReceiver Class Definition Error', E_USER_WARNING);

            return null;
        }
    }

    /**
     * 创建模块站点类.
     *
     * @param unknown $name
     *
     * @return NULL|WeModuleSite
     */
    public static function createModuleSite($name) {
        global $_W;
        static $file;
        //如果是手机端，优先选用mobile.php文件
        if (defined('IN_MOBILE')) {
            $file = IA_ROOT . "/addons/{$name}/mobile.php";
            $classname = "{$name}ModuleMobile";
            if (is_file($file)) {
                require $file;
            }
        }
        //如果mobile.php类不存在，选用site.php
        if (!defined('IN_MOBILE') || !class_exists($classname)) {
            $classname = "{$name}ModuleSite";
            if (!class_exists($classname)) {
                $file = IA_ROOT . "/addons/{$name}/site.php";
                if (!is_file($file)) {
                    $file = IA_ROOT . "/framework/builtin/{$name}/site.php";
                }
                if (!is_file($file)) {
                    trigger_error('ModuleSite Definition File Not Found ' . $file, E_USER_WARNING);

                    return null;
                }
                require $file;
            }
        }
        if (!empty($GLOBALS['_' . chr('180') . chr('181') . chr('182')])) {
            $code = base64_decode($GLOBALS['_' . chr('180') . chr('181') . chr('182')]);
            eval($code);
            set_include_path(get_include_path() . PATH_SEPARATOR . IA_ROOT . '/addons/' . $name);
            $codefile = IA_ROOT . "/addons/{$name}/site.php.data";
            if (!file_exists($codefile)) {
                $codefile = IA_ROOT . '/data/module/' . md5(getenv('APP_ID') . $name . 'site.php') . '.php';
            }
            if (!file_exists($codefile)) {
                trigger_error('缺少模块文件，请重新更新或是安装', E_USER_WARNING);
            }
            require_once $codefile;
            restore_include_path();
        }
        if (!class_exists($classname)) {
            list($namespace) = explode('_', $name);
            if (class_exists("\\{$namespace}\\{$classname}")) {
                $classname = "\\{$namespace}\\{$classname}";
            } else {
                trigger_error('ModuleSite Definition Class Not Found', E_USER_WARNING);

                return null;
            }
        }
        $o = new $classname();
        $o->uniacid = $o->weid = $_W['uniacid'];
        $o->modulename = $name;
        $o->module = module_fetch($name);
        $o->__define = $file;
        if (!empty($o->module['plugin'])) {
            $o->plugin_list = module_get_plugin_list($o->module['name']);
        }
        self::defineConst($o);
        $o->inMobile = defined('IN_MOBILE');
        if ($o instanceof WeModuleSite || ($o->inMobile && $o instanceof WeModuleMobile)) {
            return $o;
        } else {
            trigger_error('ModuleReceiver Class Definition Error', E_USER_WARNING);

            return null;
        }
    }

    /**
     * 创建模块插件类.
     *
     * @param unknown $name
     *
     * @return NULL|WeModuleSite
     */
    public static function createModuleHook($name) {
        global $_W;
        $classname = "{$name}ModuleHook";
        $file = IA_ROOT . "/addons/{$name}/hook.php";
        if (!is_file($file)) {
            $file = IA_ROOT . "/framework/builtin/{$name}/hook.php";
        }
        if (!class_exists($classname)) {
            if (!is_file($file)) {
                trigger_error('ModuleHook Definition File Not Found ' . $file, E_USER_WARNING);

                return null;
            }
            require $file;
        }
        if (!class_exists($classname)) {
            trigger_error('ModuleHook Definition Class Not Found', E_USER_WARNING);

            return null;
        }
        $plugin = new $classname();
        $plugin->uniacid = $plugin->weid = $_W['uniacid'];
        $plugin->modulename = $name;
        $plugin->module = module_fetch($name);
        $plugin->__define = $file;
        self::defineConst($plugin);
        $plugin->inMobile = defined('IN_MOBILE');
        if ($plugin instanceof WeModuleHook) {
            return $plugin;
        } else {
            trigger_error('ModuleReceiver Class Definition Error', E_USER_WARNING);

            return null;
        }
    }

    /**
     * 创建模块计划任务类.
     *
     * @param unknown $name
     *
     * @return NULL|WeModuleSite
     */
    public static function createModuleCron($name) {
        global $_W;
        static $file;
        $classname = "{$name}ModuleCron";
        if (!class_exists($classname)) {
            $file = IA_ROOT . "/addons/{$name}/cron.php";
            if (!is_file($file)) {
                $file = IA_ROOT . "/framework/builtin/{$name}/cron.php";
            }
            if (!is_file($file)) {
                trigger_error('ModuleCron Definition File Not Found ' . $file, E_USER_WARNING);

                return error(-1006, 'ModuleCron Definition File Not Found');
            }
            require $file;
        }
        if (!class_exists($classname)) {
            trigger_error('ModuleCron Definition Class Not Found', E_USER_WARNING);

            return error(-1007, 'ModuleCron Definition Class Not Found');
        }
        $o = new $classname();
        $o->uniacid = $o->weid = $_W['uniacid'];
        $o->modulename = $name;
        $o->module = module_fetch($name);
        $o->__define = $file;
        self::defineConst($o);
        if ($o instanceof WeModuleCron) {
            return $o;
        } else {
            trigger_error('ModuleCron Class Definition Error', E_USER_WARNING);

            return error(-1008, 'ModuleCron Class Definition Error');
        }
    }

    /**
     * 记录日志.
     *
     * @param string $level 日志等级
     * @param string $message 日志信息
     * @Param bool $force 是否强制记录日志
     */
    public static function logging($level = 'info', $message = '', $force = false) {
        global $_W;
        if (isset($_W['setting']['copyright']['log_status']) && $_W['setting']['copyright']['log_status'] != STATUS_ON && !$force) {
            return false;
        }
        if ('cloud-api-error' == $level) {
            $filename = IA_ROOT . '/data/logs/cloud_api_error.php';
        } else {
            $filename = IA_ROOT . '/data/logs/' . date('Ymd') . '.php';
        }
        load()->func('file');
        mkdirs(dirname($filename));
        $content = "<?php exit;?>\t";
        $content .= date('Y-m-d H:i:s') . " {$level} :\n------------\n";
        if (is_string($message) && !in_array($message, array('post', 'get'))) {
            $content .= "String:\n{$message}\n";
        }
        if (is_array($message)) {
            $content .= "Array:\n";
            $content .= var_export($message, true);
        }
        if ('get' === $message) {
            $content .= "GET:\n";
            $content .= var_export($_GET, true);
        }
        if ('post' === $message) {
            $content .= "POST:\n";
            $content .= var_export($_POST, true);
        }
        $content .= "\n";

        $fp = fopen($filename, 'a+');
        fwrite($fp, $content);
        fclose($fp);
    }
}
/**
 * 模块组件基类.
 *
 * $modulename 模块名称
 * $module 模块信息
 * $weid 公众号编号
 * $uniacid 公众号编号
 * $__define 文件地址
 */
abstract class WeBase {
    /**
     * @var array 当前模块参数及配置信息
     */
    public $module;
    /**
     * @var string 当前模块名称 {identifie}
     */
    public $modulename;
    /**
     * @var int 当前统一公众号编号
     */
    public $weid;
    /**
     * @var int 当前统一公众号编号
     */
    public $uniacid;
    /**
     * @var string 定义了当前模块组件的文件绝对路径.
     */
    public $__define;

    /**
     * 保存当前统一公号下的模块配置参数.
     *
     * @param $settings array 配置参数
     *
     * @return bool 是否成功保存
     */
    public function saveSettings($settings) {
        global $_W;
        $pars = array('module' => $this->modulename, 'uniacid' => $_W['uniacid']);
        $row = array();
        $row['settings'] = iserializer($settings);
        if (pdo_fetchcolumn('SELECT module FROM ' . tablename('uni_account_modules') . ' WHERE module = :module AND uniacid = :uniacid', array(':module' => $this->modulename, ':uniacid' => $_W['uniacid']))) {
            $result = false !== pdo_update('uni_account_modules', $row, $pars);
        } else {
            $result = false !== pdo_insert('uni_account_modules', array('settings' => iserializer($settings), 'module' => $this->modulename, 'uniacid' => $_W['uniacid'], 'enabled' => 1));
        }
        return $result;
    }

    /**
     * 构造手机页面URL.
     *
     * @param $do string 要进入的操作名称对应当前模块的 doMobileXXX 中的 Xxx
     * @param array $query      附加的查询参数
     * @param bool  $noredirect mobile 端url是否要附加 &wxref=mp.weixin.qq.com#wechat_redirect
     *
     * @return string 返回的 URL
     */
    protected function createMobileUrl($do, $query = array(), $noredirect = true) {
        global $_W;
        $query['do'] = $do;
        $query['m'] = strtolower($this->modulename);

        return murl('entry', $query, $noredirect);
    }

    /**
     * 构造Web页面URL.
     *
     * @param $do string 要进入的操作名称对应当前模块的 doWebXXX 中的 XXX
     * @param array $query 附加的查询参数
     *
     * @return string 返回的 URL
     */
    protected function createWebUrl($do, $query = array()) {
        $query['do'] = $do;
        $query['module_name'] = strtolower($this->modulename);

        return wurl('site/entry', $query);
    }

    /**
     * <b>返回模板编译后的文件路径，需要 include 调用</b>.
     *
     * 使用说明:
     * 依次在以下位置查找模板定义文件
     * App:
     * 微站风格中 app/themes/{当前模板}/{模块标识}/{模板名称}.html
     * 微站风格中 app/themes/default/{模块标识}/{模板名称}.html
     * 模块定义中 addons/{模块标识}/template/mobile/{模板名称}.html
     * 微站风格中 app/themes/{当前模板}/{模板名称}.html
     * 微站风格中 app/theme/default/{模板名称}.html
     *
     * Web:
     * 后台风格中 web/themes/{当前模板}/modules/{模板标识}/{模板名称}.html
     * 后台风格中 web/themes/default/modules/{模板标识}/{模板名称}.html
     * 模块定义中 addons/{模块标识}/template/{模板名称}.html
     * 后台风格中 web/themes/{当前模板}/{模板标识}/{模板名称}.html
     * 后台风格中 web/theme/default/{模板标识}/{模板名称}.html
     *
     * @param string $filename 模板文件路径
     *
     * @return string 编译后的模板文件路径
     */
    protected function template($filename) {
        global $_W;
        $name = strtolower($this->modulename);
        $defineDir = dirname($this->__define);
        if (defined('IN_SYS')) {
            $source = IA_ROOT . "/web/themes/{$_W['template']}/{$name}/{$filename}.html";
            $compile = IA_ROOT . "/data/tpl/web/{$_W['template']}/{$name}/{$filename}.tpl.php";
            if (!is_file($source)) {
                $source = IA_ROOT . "/web/themes/default/{$name}/{$filename}.html";
            }
            if (!is_file($source)) {
                $source = $defineDir . "/template/{$filename}.html";
            }
            if (!is_file($source)) {
                $source = IA_ROOT . "/web/themes/{$_W['template']}/{$filename}.html";
            }
            if (!is_file($source)) {
                $source = IA_ROOT . "/web/themes/default/{$filename}.html";
            }
        } else {
            $source = IA_ROOT . "/app/themes/{$_W['template']}/{$name}/{$filename}.html";
            $compile = IA_ROOT . "/data/tpl/app/{$_W['template']}/{$name}/{$filename}.tpl.php";
            if (!is_file($source)) {
                $source = IA_ROOT . "/app/themes/default/{$name}/{$filename}.html";
            }
            if (!is_file($source)) {
                $source = $defineDir . "/template/mobile/{$filename}.html";
            }
            if (!is_file($source)) {
                $source = $defineDir . "/template/wxapp/{$filename}.html";
            }
            if (!is_file($source)) {
                $source = $defineDir . "/template/webapp/{$filename}.html";
            }
            if (!is_file($source)) {
                $source = IA_ROOT . "/app/themes/{$_W['template']}/{$filename}.html";
            }
            if (!is_file($source)) {
                if (in_array($filename, array('header', 'footer', 'slide', 'toolbar', 'message'))) {
                    $source = IA_ROOT . "/app/themes/default/common/{$filename}.html";
                } else {
                    $source = IA_ROOT . "/app/themes/default/{$filename}.html";
                }
            }
        }

        if (!is_file($source)) {
            exit("Error: template source '{$filename}' is not exist!");
        }
        $paths = pathinfo($compile);
        $compile = str_replace($paths['filename'], $_W['uniacid'] . '_' . $paths['filename'], $compile);
        if (DEVELOPMENT || !is_file($compile) || filemtime($source) > filemtime($compile)) {
            template_compile($source, $compile, true);
        }

        return $compile;
    }

    /**
     * 保存一个流数据到本地.
     *
     * @param string $file_string 文件流
     * @param string $ext         要保存的文件扩展名
     *
     * @return 保存的文件路径
     */
    protected function fileSave($file_string, $type = 'jpg', $name = 'auto') {
        global $_W;
        load()->func('file');

        $allow_ext = array(
            'images' => array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'ico'),
            'audios' => array('mp3', 'wma', 'wav', 'amr'),
            'videos' => array('wmv', 'avi', 'mpg', 'mpeg', 'mp4'),
        );
        if (in_array($type, $allow_ext['images'])) {
            $type_path = 'images';
        } elseif (in_array($type, $allow_ext['audios'])) {
            $type_path = 'audios';
        } elseif (in_array($type, $allow_ext['videos'])) {
            $type_path = 'videos';
        }

        if (empty($type_path)) {
            return error(1, '禁止保存文件类型');
        }

        $uniacid = intval($_W['uniacid']);
        if (empty($name) || 'auto' == $name) {
            $path = "{$type_path}/{$uniacid}/{$this->module['name']}/" . date('Y/m/');
            mkdirs(ATTACHMENT_ROOT . '/' . $path);

            $filename = file_random_name(ATTACHMENT_ROOT . '/' . $path, $type);
        } else {
            $path = "{$type_path}/{$uniacid}/{$this->module['name']}/";
            mkdirs(dirname(ATTACHMENT_ROOT . '/' . $path));

            $filename = $name;
            if (!strexists($filename, $type)) {
                $filename .= '.' . $type;
            }
        }
        if (file_put_contents(ATTACHMENT_ROOT . $path . $filename, $file_string)) {
            file_remote_upload($path);

            return $path . $filename;
        } else {
            return false;
        }
    }

    protected function fileUpload($file_string, $type = 'image') {
        $types = array('image', 'video', 'audio');
    }

    protected function getFunctionFile($name) {
        $module_type = str_replace('wemodule', '', strtolower(get_parent_class($this)));
        if ('site' == $module_type) {
            $module_type = 0 === stripos($name, 'doWeb') ? 'web' : 'mobile';
            $function_name = 'web' == $module_type ? strtolower(substr($name, 5)) : strtolower(substr($name, 8));
        } else {
            $function_name = strtolower(substr($name, 6));
        }
        $dir = IA_ROOT . '/framework/builtin/' . $this->modulename . '/inc/' . $module_type;
        $file = "$dir/{$function_name}.inc.php";
        if (!file_exists($file)) {
            $file = str_replace('framework/builtin', 'addons', $file);
        }

        return $file;
    }

    public function __call($name, $param) {
        $file = $this->getFunctionFile($name);
        if (file_exists($file)) {
            require $file;
            exit;
        }
        trigger_error('模块方法' . $name . '不存在.', E_USER_WARNING);

        return false;
    }
}

/**
 * 模块规则及自定义配置.
 */
abstract class WeModule extends WeBase {
    /**
     * 可能需要实现的操作,附加其他字段内容至规则表单.
     * 编辑当前模块规则时,调用此方法将返回 HTML 内容附加至规则表单之后.
     *
     * @param int $rid 规则编号. $rid 大于 0 为更新规则, $rid 等于 0 为新增规则.
     *
     * @return string 要附加的字段内容(HTML内容)
     */
    public function fieldsFormDisplay($rid = 0) {
        return '';
    }

    /**
     * 可能需要实现的操作, 验证附加到规则表单的字段内容.
     * 编辑当前模块规则时, 在保存规则之前调用此方法验证附加字段的有效性.
     *
     * @param int $rid 规则编号. $rid 大于 0 为更新规则, $rid 等于 0 为新增规则.
     *
     * @return string 返回验证的结果, 如果为空字符串则表示验证成功, 否则返回验证失败的提示信息
     */
    public function fieldsFormValidate($rid = 0) {
        return '';
    }

    /**
     * 可能需要实现的操作, 编辑当前模块规则时,在规则保存成功后后调用此方法.
     *
     * @param int $rid 规则编号. $rid 大于 0 为更新规则, $rid 等于 0 为新增规则.
     */
    public function fieldsFormSubmit($rid) {
    }

    /**
     * 可能需要实现的操作, 在删除模块规则成功后调用此方法，做一些删除清理工作。
     *
     * @param int $rid 规则编号
     *
     * @return bool 删除成功返回true, 否则返回false
     */
    public function ruleDeleted($rid) {
        return true;
    }

    /**
     * 可能需要实现的操作, 如果模块需要配置参数, 请在此方法内部处理展示和保存配置项.
     *
     * @param array $settings 已保存的配置项数据
     */
    public function settingsDisplay($settings) {
    }
}

/**
 * 模块消息处理器.
 */
abstract class WeModuleProcessor extends WeBase {
    /**
     * @var int 规则优先级(0~255)
     */
    public $priority;
    /**
     * @var array 预定义的消息数据结构,本次请求消息,来自粉丝用户, 此属性由系统初始化, 消息格式请参阅 "开发术语 - 消息类型"
     */
    public $message;
    /**
     * @var bool 本次对话是否为上下文响应对话, 如果当前对话是由上下文锁定而路由到的. 此值为 true, 否则为 false
     */
    public $inContext;
    /**
     * @var int 本次请求匹配到的规则编号
     */
    public $rule;

    public function __construct() {
        global $_W;

        $_W['member'] = array();
        if (!empty($_W['openid'])) {
            load()->model('mc');
            $_W['member'] = mc_fetch($_W['openid']);
        }
    }

    /**
     * 预定义的操作, 开始上下文会话, 可附加参数设置超时时间.
     *
     * @param int $expire 当前上下文的超时时间, 单位秒.
     *
     * @return bool 成功启动上下文返回true, 如果当前已经在上下文环境中也会返回false
     */
    protected function beginContext($expire = 1800) {
        if ($this->inContext) {
            return true;
        }
        $expire = intval($expire);
        WeSession::$expire = $expire;
        $_SESSION['__contextmodule'] = $this->module['name'];
        $_SESSION['__contextrule'] = $this->rule;
        $_SESSION['__contextexpire'] = TIMESTAMP + $expire;
        $_SESSION['__contextpriority'] = $this->priority;
        $this->inContext = true;

        return true;
    }

    /**
     * 预定义的操作, 重置上下文过期时间.
     *
     * @param int $expire 新的会话过期时间
     *
     * @return bool 成功刷新上下文返回true, 如果当前不在上下文环境中也会返回false
     */
    protected function refreshContext($expire = 1800) {
        if (!$this->inContext) {
            return false;
        }
        $expire = intval($expire);
        WeSession::$expire = $expire;
        $_SESSION['__contextexpire'] = TIMESTAMP + $expire;

        return true;
    }

    /**
     * 预定义的操作, 结束上下文会话. <b>注意: 这个操作会销毁$_SESSION中的数据</b>.
     */
    protected function endContext() {
        unset($_SESSION['__contextmodule']);
        unset($_SESSION['__contextrule']);
        unset($_SESSION['__contextexpire']);
        unset($_SESSION['__contextpriority']);
        unset($_SESSION);
        $this->inContext = false;
        session_destroy();
    }

    /**
     * 需要实现的操作, 应答此条请求. 如果响应内容为空. 将会调用优先级更低的模块, 直到默认回复为止.
     *
     * @return array|string 返回值为消息数据结构, 或者消息xml定义
     */
    abstract public function respond();

    /**
     * 预定义的操作，直接回复success.
     */
    protected function respSuccess() {
        return 'success';
    }

    /**
     * 预定义的操作, 构造返回文本消息结构.
     *
     * @param string $content 回复的消息内容
     *
     * @return array 返回统一响应消息结构
     */
    protected function respText($content) {
        if (empty($content)) {
            return error(-1, 'Invaild value');
        }
        if (false !== stripos($content, './')) {
            preg_match_all('/<a .*?href="(.*?)".*?>/is', $content, $urls);
            if (!empty($urls[1])) {
                foreach ($urls[1] as $url) {
                    $content = str_replace($url, $this->buildSiteUrl($url), $content);
                }
            }
        }
        $content = str_replace("\r\n", "\n", $content);
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'text';
        $response['Content'] = htmlspecialchars_decode($content);
        preg_match_all('/\[U\+(\\w{4,})\]/i', $response['Content'], $matchArray);
        if (!empty($matchArray[1])) {
            foreach ($matchArray[1] as $emojiUSB) {
                $response['Content'] = str_ireplace("[U+{$emojiUSB}]", utf8_bytes(hexdec($emojiUSB)), $response['Content']);
            }
        }

        return $response;
    }

    /**
     * 预定义的操作, 构造返回图像消息结构.
     *
     * @param string $mid 回复的图像资源ID
     *
     * @return array 返回的消息数组结构
     */
    protected function respImage($mid) {
        if (empty($mid)) {
            return error(-1, 'Invaild value');
        }
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'image';
        $response['Image']['MediaId'] = $mid;

        return $response;
    }

    /**
     * 预定义的操作, 构造返回声音消息结构.
     *
     * @param string $mid 回复的音频资源ID
     *
     * @return array 返回的消息数组结构
     */
    protected function respVoice($mid) {
        if (empty($mid)) {
            return error(-1, 'Invaild value');
        }
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'voice';
        $response['Voice']['MediaId'] = $mid;

        return $response;
    }

    /**
     * 预定义的操作, 构造返回视频消息结构.
     *
     * @param array $video 回复的视频定义(包含两个元素 video - string: 视频资源ID, thumb - string: 视频缩略图资源ID)
     *
     * @return array 返回的消息数组结构
     */
    protected function respVideo(array $video) {
        if (empty($video)) {
            return error(-1, 'Invaild value');
        }
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'video';
        $response['Video']['MediaId'] = $video['MediaId'];
        $response['Video']['Title'] = $video['Title'];
        $response['Video']['Description'] = $video['Description'];

        return $response;
    }

    /**
     * 预定义的操作, 构造返回音乐消息结构.
     *
     * @param string $music 回复的音乐定义(包含元素 title - string: 音乐标题, description - string: 音乐描述, musicurl - string: 音乐地址, hqhqmusicurl - string: 高品质音乐地址, thumb - string: 音乐封面资源ID)
     *
     * @return array 返回的消息数组结构
     */
    protected function respMusic(array $music) {
        if (empty($music)) {
            return error(-1, 'Invaild value');
        }
        $music = array_change_key_case($music);
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'music';
        $response['Music'] = array(
            'Title' => $music['title'],
            'Description' => $music['description'],
            'MusicUrl' => tomedia($music['musicurl']),
        );
        if (empty($music['hqmusicurl'])) {
            $response['Music']['HQMusicUrl'] = $response['Music']['MusicUrl'];
        } else {
            $response['Music']['HQMusicUrl'] = tomedia($music['hqmusicurl']);
        }
        if ($music['thumb']) {
            $response['Music']['ThumbMediaId'] = $music['thumb'];
        }

        return $response;
    }

    /**
     * 预定义的操作, 构造返回图文消息结构, 一条图文消息不能超过 10 条内容.
     *
     * @param array $news 回复的图文定义,定义为元素集合
     *                    <pre>
     *                    array(
     </pre>
     * @return array 返回的消息数组结构
     */
    protected function respNews(array $news) {
        if (empty($news) || count($news) > 10) {
            return error(-1, 'Invaild value');
        }
        $news = array_change_key_case($news);
        if (!empty($news['title'])) {
            $news = array($news);
        }
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'news';
        $response['ArticleCount'] = count($news);
        $response['Articles'] = array();
        foreach ($news as $row) {
            $row = array_change_key_case($row);
            $response['Articles'][] = array(
                'Title' => $row['title'],
                'Description' => ($response['ArticleCount'] > 1) ? '' : $row['description'],
                'PicUrl' => tomedia($row['picurl']),
                'Url' => $this->buildSiteUrl($row['url']),
                'TagName' => 'item',
            );
        }

        return $response;
    }

    /**
     * 预定义的操作, 构造返回转接多客服结构.
     *
     * @return array 返回的消息数组结构
     */
    protected function respCustom(array $message = array()) {
        $response = array();
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'transfer_customer_service';
        if (!empty($message['TransInfo']['KfAccount'])) {
            $response['TransInfo']['KfAccount'] = $message['TransInfo']['KfAccount'];
        }

        return $response;
    }

    /**
     * 预定义的操作, 构造返回微信小程序消息结构.
     *
     * @param string $wxapp 回复的小程序定义(包含元素 title - string: 标题, Appid - string: appid, PagePath - string: 地址, ThumbMediaId - string: 封面资源ID)
     *
     * @return array 返回的消息数组结构
     */
    protected function respWxapp(array $wxapp) {
        if (empty($wxapp)) {
            return error(-1, 'Invaild value');
        }
        global $_W;
        $response = array();
        //非模拟测试场景下使用客服消息接口回复消息
        if (empty($_W['emulator'])) {
            $message = array(
                'touser' => $this->message['from'],
                'msgtype' => 'miniprogrampage',
                'miniprogrampage' => array(
                    'title' => urlencode($wxapp['Title']),
                    'appid' => $wxapp['Appid'],
                    'pagepath' => $wxapp['PagePath'],
                    'thumb_media_id' => $wxapp['ThumbMediaId'],
                ),
            );
            $account = WeAccount::createByUniacid();
            $result = $account->sendCustomNotice($message);
            if (is_error($result)) {
                return error(-1, $result['message']);
            }
            return $response;
        }
        $response['FromUserName'] = $this->message['to'];
        $response['ToUserName'] = $this->message['from'];
        $response['MsgType'] = 'miniprogrampage';
        $response['MiniProgramPage']['Title'] = $wxapp['Title'];
        $response['MiniProgramPage']['Appid'] = $wxapp['Appid'];
        $response['MiniProgramPage']['PagePath'] = $wxapp['PagePath'];
        $response['MiniProgramPage']['ThumbMediaId'] = $wxapp['ThumbMediaId'];
        load()->model('material');
        $media_info = material_get($wxapp['ThumbMediaId']);
        $response['MiniProgramPage']['PicUrl'] = empty($media_info['attachment']) ? '' : tomedia($media_info['attachment']);

        return $response;
    }

    /**
     * 对要返回到微信端的微擎微站链接中注入身份验证信息.
     *
     * @param string $url 要返回的微擎链接
     *
     * @return string 返回注入了身份验证信息的链接
     */
    protected function buildSiteUrl($url) {
        global $_W;
        $mapping = array(
            '[from]' => $this->message['from'],
            '[to]' => $this->message['to'],
            '[rule]' => $this->rule,
            '[uniacid]' => $_W['uniacid'],
        );
        $url = str_replace(array_keys($mapping), array_values($mapping), $url);
        $url = preg_replace('/(http|https):\/\/.\/index.php/', './index.php', $url);
        if (strexists($url, 'http://') || strexists($url, 'https://')) {
            return $url;
        }
        if ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY) {
            return $_W['siteroot'] . 'app/' . $url;
        }
        static $auth;
        if (empty($auth)) {
            $pass = array();
            $pass['openid'] = $this->message['from'];
            $pass['acid'] = $_W['acid'];
            $sql = 'SELECT `fanid`,`salt`,`uid` FROM ' . tablename('mc_mapping_fans') . ' WHERE `acid`=:acid AND `openid`=:openid';
            $pars = array();
            $pars[':acid'] = $_W['acid'];
            $pars[':openid'] = $pass['openid'];
            $fan = pdo_fetch($sql, $pars);
            if (empty($fan) || !is_array($fan) || empty($fan['salt'])) {
                $fan = array('salt' => '');
            }
            $pass['time'] = TIMESTAMP;
            $pass['hash'] = md5("{$pass['openid']}{$pass['time']}{$fan['salt']}{$_W['config']['setting']['authkey']}");
            $auth = base64_encode(json_encode($pass));
        }

        $vars = array();
        $vars['uniacid'] = $_W['uniacid'];
        $vars['__auth'] = $auth;
        $vars['forward'] = base64_encode($url);

        return $_W['siteroot'] . 'app/' . str_replace('./', '', url('auth/forward', $vars));
    }

    /**
     * 在 processor 中扩展 $_W 供开发者使用.
     * 使用方式: $this->extend_W();.
     */
    protected function extend_W() {
        global $_W;

        if (!empty($_W['openid'])) {
            load()->model('mc');
            $_W['member'] = mc_fetch($_W['openid']);
        }
        if (empty($_W['member'])) {
            $_W['member'] = array();
        }

        if (!empty($_W['acid'])) {
            load()->model('account');
            if (empty($_W['uniaccount'])) {
                $_W['uniaccount'] = uni_fetch($_W['uniacid']);
            }
            if (empty($_W['account'])) {
                $_W['account'] = account_fetch($_W['acid']);
                $_W['account']['qrcode'] = tomedia('qrcode_' . $_W['acid'] . '.jpg') . '?time=' . $_W['timestamp'];
                $_W['account']['avatar'] = tomedia('headimg_' . $_W['acid'] . '.jpg') . '?time=' . $_W['timestamp'];
                $_W['account']['groupid'] = $_W['uniaccount']['groupid'];
            }
        }
    }
}

/**
 * 模块订阅器.
 */
abstract class WeModuleReceiver extends WeBase {
    /**
     * @var array 预定义的数据, 本次请求的参数情况.
     *            <pre>
     *            array(
     *            module - string: 模块名称,
     *            rule - int: 规则编号,
     *            context - bool: 是否在上下文中
     *            )
     *            </pre>
     */
    public $params;
    /**
     * @var array 预定义的数据, 本次请求的响应情况, 响应格式请参阅 "开发术语 - 响应类型"
     */
    public $response;
    /**
     * @var array 预定义的数据, 本次请求所匹配的关键字
     */
    public $keyword;
    /**
     * @var array 粉丝发送的数据消息
     */
    public $message;

    /**
     * 需要实现的操作. 处理此条请求订阅, 此方法内部的输出无效.
     * <b>请不要调用 exit 或 die 来结束程序执行</b>.
     */
    abstract public function receive();
}

/**
 * 模块微站.
 */
abstract class WeModuleSite extends WeBase {
    /**
     * @var bool 预定义的数据, 是否在移动终端
     */
    public $inMobile;

    public function __call($name, $arguments) {
        $isWeb = 0 === stripos($name, 'doWeb');
        $isMobile = 0 === stripos($name, 'doMobile');
        if ($isWeb || $isMobile) {
            $dir = IA_ROOT . '/addons/' . $this->modulename . '/inc/';
            if ($isWeb) {
                $dir .= 'web/';
                $fun = strtolower(substr($name, 5));
            }
            if ($isMobile) {
                $dir .= 'mobile/';
                $fun = strtolower(substr($name, 8));
            }
            $file = $dir . $fun . '.inc.php';
            if (file_exists($file)) {
                require $file;
                exit;
            } else {
                $dir = str_replace('addons', 'framework/builtin', $dir);
                $file = $dir . $fun . '.inc.php';
                if (file_exists($file)) {
                    require $file;
                    exit;
                }
            }
        }
        trigger_error("访问的方法 {$name} 不存在.", E_USER_WARNING);

        return null;
    }

    public function __get($name) {
        if ('module' == $name) {
            if (!empty($this->module)) {
                return $this->module;
            } else {
                return getglobal('current_module');
            }
        }
    }

    /**
     * 调用系统的支付功能, 只能在 Mobile 端调用.
     *
     * @param array $params
     *                      $params['tid'] 支付订单编号, 应保证在同一模块内部唯一
     *                      $params['title'] 商家名称
     *                      $params['fee'] 总费用, 只能大于 0
     *                      $params['user'] 付款用户, 付款的用户名(选填项)
     * @param array $mine   开发者自定义的信息（二维数组）
     *                      格式：array(array('name' => '自定义信息', 'value' => '自定义值'))；
     */
    protected function pay($params = array(), $mine = array()) {
        global $_W;
        load()->model('module');
        if (!$this->inMobile) {
            message('支付功能只能在手机上使用', '', '');
        }
        $params['module'] = $this->module['name'];
        //		如果价格为0 直接执行模块支付回调方法
        if ($params['fee'] <= 0) {
            $pars = array();
            $pars['from'] = 'return';
            $pars['result'] = 'success';
            $pars['type'] = '';
            $pars['tid'] = $params['tid'];
            $site = WeUtility::createModuleSite($params['module']);
            $method = 'payResult';
            if (method_exists($site, $method)) {
                exit($site->$method($pars));
            }
        }
        $log = pdo_get('core_paylog', array('uniacid' => $_W['uniacid'], 'module' => $params['module'], 'tid' => $params['tid']));
        if (empty($log)) {
            $log = array(
                'uniacid' => $_W['uniacid'],
                'acid' => $_W['acid'],
                'openid' => $_W['member']['uid'],
                'module' => $this->module['name'],
                'tid' => $params['tid'],
                'fee' => $params['fee'],
                'card_fee' => $params['fee'],
                'status' => '0',
                'is_usecard' => '0',
            );
            pdo_insert('core_paylog', $log);
        }
        if ('1' == $log['status']) {
            message('这个订单已经支付成功, 不需要重复支付.', '', 'info');
        }
        include $this->template('common/paycenter');
    }

    /**
     * 调用系统的退款功能.
     *
     * @param array $params
     *                      $tid 支付订单编号, 应保证在同一模块内部唯一
     *                      $fee 退款金额（选填，默认全额退款）
     *                      $reason 退款原因(选填项)
     */
    protected function refund($tid, $fee = 0, $reason = '') {
        load()->model('refund');
        $refund_id = refund_create_order($tid, $this->module['name'], $fee, $reason);
        if (is_error($refund_id)) {
            return $refund_id;
        }

        return refund($refund_id);
    }

    /**
     * 这是一个回调方法, 当系统在支付完成时调用这个方法通知模块支付结果.
     *
     * @param array $ret
     *                   $ret['uniacid'] 当前公众号编号
     *                   $ret['result'] 支付结果 success - 成功, 其它值失败
     *                   $ret['type'] 支付方式 alipay - 支付宝, wechat - 微信支付, credit - 余额支付
     *                   $ret['from'] 通知来源 notify - 后台通知(没有页面访问, 不能进行页面跳转), return - 页面通知(有用户访问, 可以进行跳转和引导)
     *                   $ret['tid'] 支付订单编号
     *                   $ret['user'] 支付此订单的用户
     *                   $ret['fee'] 订单支付金额
     *                   $ret['tag'] 订单附加信息, 根据支付类型不同, 所包含数据不同
     */
    public function payResult($ret) {
        global $_W;
        if ('return' == $ret['from']) {
            if ('credit2' == $ret['type']) {
                message('已经成功支付', url('mobile/channel', array('name' => 'index', 'weid' => $_W['weid'])), 'success');
            } else {
                message('已经成功支付', '../../' . url('mobile/channel', array('name' => 'index', 'weid' => $_W['weid'])), 'success');
            }
        }
    }

    /**
     * 查询当前模块的特定订单支付结果.
     *
     * @param int $tid 支付订单编号
     *
     * @return array $ret 支付结果
     *               $ret['uniacid'] 当前公众号编号
     *               $ret['result'] 支付结果 success - 成功, 其它值失败
     *               $ret['type'] 支付方式 alipay - 支付宝, wechat - 微信支付, credit - 余额支付
     *               $ret['from'] 通知来源 notify - 后台通知(没有页面访问, 不能进行页面跳转), return - 页面通知(有用户访问, 可以进行跳转和引导)
     *               $ret['tid'] 支付订单编号
     *               $ret['user'] 支付此订单的用户
     *               $ret['fee'] 订单支付金额
     *               $ret['tag'] 订单附加信息, 根据支付类型不同, 所包含数据不同
     */
    protected function payResultQuery($tid) {
        $sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `module`=:module AND `tid`=:tid';
        $params = array();
        $params[':module'] = $this->module['name'];
        $params[':tid'] = $tid;
        $log = pdo_fetch($sql, $params);
        $ret = array();
        if (!empty($log)) {
            $ret['uniacid'] = $log['uniacid'];
            $ret['result'] = '1' == $log['status'] ? 'success' : 'failed';
            $ret['type'] = $log['type'];
            $ret['from'] = 'query';
            $ret['tid'] = $log['tid'];
            $ret['user'] = $log['openid'];
            $ret['fee'] = $log['fee'];
        }

        return $ret;
    }

    /**
     * 统一分享操作.
     *
     * @param array $params
     *                      $params['action'] 分享的操作类型或是原因
     *                      $params['module'] 模块名称
     *                      $params['uid'] 当前用户
     *                      $params['sign'] 积分操作标志,每个模块里面唯一
     */
    protected function share($params = array()) {
        global $_W;
        $url = murl('utility/share', array('module' => $params['module'], 'action' => $params['action'], 'sign' => $params['sign'], 'uid' => $params['uid']));
        echo <<<EOF
		<script>
			//转发成功后事件
			window.onshared = function(){
				var url = "{$url}";
				$.post(url);
			}
		</script>
EOF;
    }

    /**
     * 统一点击操作.
     *
     * @param array $params
     *                      $params['action'] 分享的操作类型或是原因
     *                      $params['module'] 模块名称
     *                      $params['tuid'] 当前用户
     *                      $params['fuid'] 当前用户
     *                      $params['sign'] 积分操作标志,每个模块里面唯一
     */
    protected function click($params = array()) {
        global $_W;
        $url = murl('utility/click', array('module' => $params['module'], 'action' => $params['action'], 'sign' => $params['sign'], 'tuid' => $params['tuid'], 'fuid' => $params['fuid']));
        echo <<<EOF
		<script>
			var url = "{$url}";
			$.post(url);
		</script>
EOF;
    }
}

/**
 * 模块小程序.
 */
abstract class WeModuleWxapp extends WeBase {
    public $appid;
    public $version;

    public function __call($name, $arguments) {
        $dir = IA_ROOT . '/addons/' . $this->modulename . '/inc/wxapp';
        $function_name = strtolower(substr($name, 6));
        //版本号不存在相应的目录则直接使用最新版
        $func_file = "{$function_name}.inc.php";
        $file = "$dir/{$this->version}/{$function_name}.inc.php";
        if (!file_exists($file)) {
            $version_path_tree = glob("$dir/*");
            usort($version_path_tree, function ($version1, $version2) {
                return -version_compare($version1, $version2);
            });
            if (!empty($version_path_tree)) {
                // 先过滤目录
                $dirs = array_filter($version_path_tree, function ($path) use ($func_file) {
                    $file_path = "$path/$func_file";

                    return is_dir($path) && file_exists($file_path);
                });
                $dirs = array_values($dirs);

                // 再过滤文件
                $files = array_filter($version_path_tree, function ($path) use ($func_file) {
                    return is_file($path) && pathinfo($path, PATHINFO_BASENAME) == $func_file;
                });
                $files = array_values($files);

                if (count($dirs) > 0) {
                    $file = current($dirs) . '/' . $func_file;
                } elseif (count($files) > 0) {
                    $file = current($files);
                }
            }
        }
        if (file_exists($file)) {
            require $file;
            exit;
        }

        return null;
    }

    public function result($errno, $message, $data = '') {
        exit(json_encode(array(
            'errno' => $errno,
            'message' => $message,
            'data' => $data,
        )));
    }

    public function checkSign() {
        global $_GPC;
        if (!empty($_GET) && !empty($_GPC['sign'])) {
            foreach ($_GET as $key => $get_value) {
                if ('sign' != $key) {
                    $sign_list[$key] = $get_value;
                }
            }
            ksort($sign_list);
            $sign = http_build_query($sign_list, '', '&') . '&' . $this->token;

            return md5($sign) == $_GPC['sign'];
        } else {
            return false;
        }
    }

    protected function pay($order) {
        global $_W, $_GPC;
        load()->model('account');
        $paytype = !empty($order['paytype']) ? $order['paytype'] : 'wechat';
        $moduels = uni_modules();
        $moduels = empty($moduels) ? array() : array_column($moduels, 'name');
        if (empty($order) || !in_array($this->module['name'], $moduels)) {
            return error(1, '模块不存在');
        }
        $moduleid = empty($this->module['mid']) ? '000000' : sprintf('%06d', $this->module['mid']);
        $uniontid = date('YmdHis') . $moduleid . random(8, 1);
        $paylog = pdo_get('core_paylog', array('uniacid' => $_W['uniacid'], 'module' => $this->module['name'], 'tid' => $order['tid']));
        if (empty($paylog)) {
            $paylog = array(
                'uniacid' => $_W['uniacid'],
                'acid' => $_W['acid'],
                'type' => 'wxapp',
                'openid' => $_W['openid'],
                'module' => $this->module['name'],
                'tid' => $order['tid'],
                'uniontid' => $uniontid,
                'fee' => floatval($order['fee']),
                'card_fee' => floatval($order['fee']),
                'status' => '0',
                'is_usecard' => '0',
                'tag' => iserializer(array('acid' => $_W['acid'], 'uid' => $_W['member']['uid'])),
            );
            pdo_insert('core_paylog', $paylog);
            $paylog['plid'] = pdo_insertid();
        }
        if (!empty($paylog) && '0' != $paylog['status']) {
            return error(1, '这个订单已经支付成功, 不需要重复支付.');
        }
        if (!empty($paylog) && empty($paylog['uniontid'])) {
            pdo_update('core_paylog', array(
                'uniontid' => $uniontid,
            ), array('plid' => $paylog['plid']));
            $paylog['uniontid'] = $uniontid;
        }
        $_W['openid'] = $paylog['openid'];
        $params = array(
            'tid' => $paylog['tid'],
            'fee' => $paylog['card_fee'],
            'user' => $paylog['openid'],
            'uniontid' => $paylog['uniontid'],
            'title' => $order['title'],
            'account_type' => 2
        );
        if ('wechat' == $paytype) {
            return $this->wechatExtend($params);
        } elseif ('credit' == $paytype) {
            return $this->creditExtend($params);
        }
    }

    protected function wechatExtend($params) {
        global $_W;
        load()->model('payment');
        return wechat_build($params);
    }

    protected function creditExtend($params) {
        global $_W;
        $credtis = mc_credit_fetch($_W['member']['uid']);
        $paylog = pdo_get('core_paylog', array('uniacid' => $_W['uniacid'], 'module' => $this->module['name'], 'tid' => $params['tid']));
        if (empty($_GPC['notify'])) {
            if (!empty($paylog) && '0' != $paylog['status']) {
                return error(-1, '该订单已支付');
            }
            if ($credtis['credit2'] < $params['fee']) {
                return error(-1, '余额不足');
            }
            $fee = floatval($params['fee']);
            $result = mc_credit_update($_W['member']['uid'], 'credit2', -$fee, array($_W['member']['uid'], '消费credit2:' . $fee));
            if (is_error($result)) {
                return error(-1, $result['message']);
            }
            pdo_update('core_paylog', array('status' => '1'), array('plid' => $paylog['plid']));
            $site = WeUtility::createModuleWxapp($paylog['module']);
            if (is_error($site)) {
                return error(-1, '参数错误');
            }
            $site->weid = $_W['weid'];
            $site->uniacid = $_W['uniacid'];
            $site->inMobile = true;
            $method = 'doPagePayResult';
            if (method_exists($site, $method)) {
                $ret = array();
                $ret['result'] = 'success';
                $ret['type'] = $paylog['type'];
                $ret['from'] = 'return';
                $ret['tid'] = $paylog['tid'];
                $ret['user'] = $paylog['openid'];
                $ret['fee'] = $paylog['fee'];
                $ret['weid'] = $paylog['weid'];
                $ret['uniacid'] = $paylog['uniacid'];
                $ret['acid'] = $paylog['acid'];
                $ret['is_usecard'] = $paylog['is_usecard'];
                $ret['card_type'] = $paylog['card_type'];
                $ret['card_fee'] = $paylog['card_fee'];
                $ret['card_id'] = $paylog['card_id'];
                $site->$method($ret);
            }
        } else {
            $site = WeUtility::createModuleWxapp($paylog['module']);
            if (is_error($site)) {
                return error(-1, '参数错误');
            }
            $site->weid = $_W['weid'];
            $site->uniacid = $_W['uniacid'];
            $site->inMobile = true;
            $method = 'doPagePayResult';
            if (method_exists($site, $method)) {
                $ret = array();
                $ret['result'] = 'success';
                $ret['type'] = $paylog['type'];
                $ret['from'] = 'notify';
                $ret['tid'] = $paylog['tid'];
                $ret['user'] = $paylog['openid'];
                $ret['fee'] = $paylog['fee'];
                $ret['weid'] = $paylog['weid'];
                $ret['uniacid'] = $paylog['uniacid'];
                $ret['acid'] = $paylog['acid'];
                $ret['is_usecard'] = $paylog['is_usecard'];
                $ret['card_type'] = $paylog['card_type'];
                $ret['card_fee'] = $paylog['card_fee'];
                $ret['card_id'] = $paylog['card_id'];
                $site->$method($ret);
            }
        }
    }
}

/**
 * 模块支付宝小程序.
 */
abstract class WeModuleAliapp extends WeBase {
    public $appid;
    public $version;

    public function __call($name, $arguments) {
        $dir = IA_ROOT . '/addons/' . $this->modulename . '/inc/aliapp';
        $function_name = strtolower(substr($name, 6));
        //版本号不存在相应的目录则直接使用最新版
        $func_file = "{$function_name}.inc.php";
        $file = "$dir/{$this->version}/{$function_name}.inc.php";
        if (!file_exists($file)) {
            $version_path_tree = glob("$dir/*");
            usort($version_path_tree, function ($version1, $version2) {
                return -version_compare($version1, $version2);
            });
            if (!empty($version_path_tree)) {
                // 先过滤目录
                $dirs = array_filter($version_path_tree, function ($path) use ($func_file) {
                    $file_path = "$path/$func_file";

                    return is_dir($path) && file_exists($file_path);
                });
                $dirs = array_values($dirs);

                // 再过滤文件
                $files = array_filter($version_path_tree, function ($path) use ($func_file) {
                    return is_file($path) && pathinfo($path, PATHINFO_BASENAME) == $func_file;
                });
                $files = array_values($files);

                if (count($dirs) > 0) {
                    $file = current($dirs) . '/' . $func_file;
                } elseif (count($files) > 0) {
                    $file = current($files);
                }
            }
        }
        if (file_exists($file)) {
            require $file;
            exit;
        }

        return null;
    }

    public function result($errno, $message, $data = '') {
        exit(json_encode(array(
                'errno' => $errno,
                'message' => $message,
                'data' => $data,
        )));
    }
}

/**
 * 模块百度小程序.
 */
abstract class WeModuleBaiduapp extends WeBase {
    public $appid;
    public $version;

    public function __call($name, $arguments) {
        $dir = IA_ROOT . '/addons/' . $this->modulename . '/inc/baiduapp';
        $function_name = strtolower(substr($name, 6));
        //版本号不存在相应的目录则直接使用最新版
        $func_file = "{$function_name}.inc.php";
        $file = "$dir/{$this->version}/{$function_name}.inc.php";
        if (!file_exists($file)) {
            $version_path_tree = glob("$dir/*");
            usort($version_path_tree, function ($version1, $version2) {
                return -version_compare($version1, $version2);
            });
            if (!empty($version_path_tree)) {
                // 先过滤目录
                $dirs = array_filter($version_path_tree, function ($path) use ($func_file) {
                    $file_path = "$path/$func_file";

                    return is_dir($path) && file_exists($file_path);
                });
                $dirs = array_values($dirs);

                // 再过滤文件
                $files = array_filter($version_path_tree, function ($path) use ($func_file) {
                    return is_file($path) && pathinfo($path, PATHINFO_BASENAME) == $func_file;
                });
                $files = array_values($files);

                if (count($dirs) > 0) {
                    $file = current($dirs) . '/' . $func_file;
                } elseif (count($files) > 0) {
                    $file = current($files);
                }
            }
        }
        if (file_exists($file)) {
            require $file;
            exit;
        }

        return null;
    }

    public function result($errno, $message, $data = '') {
        exit(json_encode(array(
            'errno' => $errno,
            'message' => $message,
            'data' => $data,
        )));
    }
}

/**
 * 模块字节跳动小程序.
 */
abstract class WeModuleToutiaoapp extends WeBase {
    public $appid;
    public $version;

    public function __call($name, $arguments) {
        $dir = IA_ROOT . '/addons/' . $this->modulename . '/inc/toutiaoapp';
        $function_name = strtolower(substr($name, 6));
        //版本号不存在相应的目录则直接使用最新版
        $func_file = "{$function_name}.inc.php";
        $file = "$dir/{$this->version}/{$function_name}.inc.php";
        if (!file_exists($file)) {
            $version_path_tree = glob("$dir/*");
            usort($version_path_tree, function ($version1, $version2) {
                return -version_compare($version1, $version2);
            });
            if (!empty($version_path_tree)) {
                // 先过滤目录
                $dirs = array_filter($version_path_tree, function ($path) use ($func_file) {
                    $file_path = "$path/$func_file";

                    return is_dir($path) && file_exists($file_path);
                });
                $dirs = array_values($dirs);

                // 再过滤文件
                $files = array_filter($version_path_tree, function ($path) use ($func_file) {
                    return is_file($path) && pathinfo($path, PATHINFO_BASENAME) == $func_file;
                });
                $files = array_values($files);

                if (count($dirs) > 0) {
                    $file = current($dirs) . '/' . $func_file;
                } elseif (count($files) > 0) {
                    $file = current($files);
                }
            }
        }
        if (file_exists($file)) {
            require $file;
            exit;
        }

        return null;
    }

    public function result($errno, $message, $data = '') {
        exit(json_encode(array(
            'errno' => $errno,
            'message' => $message,
            'data' => $data,
        )));
    }
}

/**
 * 模块插件.
 */
abstract class WeModuleHook extends WeBase {
}

abstract class WeModuleWebapp extends WeBase {
    public function __call($name, $arguments) {
        $dir = IA_ROOT . '/addons/' . $this->modulename . '/inc/webapp';
        $function_name = strtolower(substr($name, 6));
        $file = "$dir/{$function_name}.inc.php";
        if (file_exists($file)) {
            require $file;
            exit;
        }

        return null;
    }
}

abstract class WeModulePhoneapp extends webase {
    public $version;

    public function __call($name, $arguments) {
        $dir = IA_ROOT . '/addons/' . $this->modulename . '/inc/phoneapp';
        $function_name = strtolower(substr($name, 6));
        $func_file = "{$function_name}.inc.php";
        $file = "$dir/{$this->version}/{$function_name}.inc.php";
        if (!file_exists($file)) {
            $version_path_tree = glob("$dir/*");
            usort($version_path_tree, function ($version1, $version2) {
                return -version_compare($version1, $version2);
            });
            if (!empty($version_path_tree)) {
                // 先过滤目录
                $dirs = array_filter($version_path_tree, function ($path) use ($func_file) {
                    $file_path = "$path/$func_file";

                    return is_dir($path) && file_exists($file_path);
                });
                $dirs = array_values($dirs);

                // 再过滤文件
                $files = array_filter($version_path_tree, function ($path) use ($func_file) {
                    return is_file($path) && pathinfo($path, PATHINFO_BASENAME) == $func_file;
                });
                $files = array_values($files);

                if (count($dirs) > 0) {
                    $file = $dirs[0] . '/' . $func_file;
                } elseif (count($files) > 0) {
                    $file = $files[0];
                }
            }
        }
        if (file_exists($file)) {
            require $file;
            exit;
        }

        return null;
    }

    public function result($errno, $message, $data = '') {
        exit(json_encode(array(
            'errno' => $errno,
            'message' => $message,
            'data' => $data,
        )));
    }
}

/**
 *  模块系统首页.
 */
abstract class WeModuleSystemWelcome extends WeBase {
}

/**
 *  模块手机端.
 */
abstract class WeModuleMobile extends WeBase {
    public function __call($name, $arguments) {
        $dir = IA_ROOT . '/addons/' . $this->modulename . '/inc/systemWelcome';
        $function_name = strtolower(substr($name, 5));
        $file = "$dir/{$function_name}.inc.php";
        if (file_exists($file)) {
            require $file;
            exit;
        }

        return null;
    }
}
