## 初始化Api
```php
$api = new \W7\Sdk\Module\Api("站点Appid", "站点App Sercet", "应用关联Appid", "号码类型", "BASE_URI");
```
> 号码类型使用可以使用Account类下的常量

### 获取关联的号码
```php
$api->getAccountList();
```
返回如下：
```
Array
(
    [0] => Array
        (
            [name] => 号码名称
            [type] => 号码类型
            [app_id] => 号码APPID
            [logo_url] => Logo的URL
        )

)
```
### 获取号码的AccessToken
```php
$api->app()->getAccessToken();
```
返回如下：
```
Array
(
    [access_token] => AccessToken
    [expires_in] => 有效期(秒)
)
```
### 微信小程序登录
```php
$api->app()->jsCode2Session($js_code);
```
返回如下：
```
Array
(
    [openid] => 用户唯一标识
    [session_key] => 会话密钥
    [unionid] => 用户在开放平台的唯一标识符，若当前小程序已绑定到微信开放平台帐号下会返回，详见 UnionID 机制说明。
    [expires_in] => 有效期(秒)
)
```
### 微信公众号授权登录
```php
$api->app()->snsOauthAccessToken($code);
```
返回如下：
```
Array
(
    [access_token] => 网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
    [expires_in] =>  access_token接口调用凭证超时时间，单位（秒）
    [refresh_token] => 用户刷新access_token
    [openid] => 用户唯一标识，请注意，在未关注公众号时，用户访问公众号的网页，也会产生一个用户和公众号唯一的OpenID
    [scope] => 用户授权的作用域，使用逗号（,）分隔
    [is_snapshotuser] => 是否为快照页模式虚拟账号，只有当用户是快照页模式虚拟账号时返回，值为1
    [unionid] => 用户统一标识（针对一个微信开放平台帐号下的应用，同一用户的 unionid 是唯一的），只有当scope为"snsapi_userinfo"时返回
)
```
## 微信支付
```php
$pay = $api->wechatPay("异步通知地址，可为空");
```

### Native下单
SDK方法说明：
```php
/**
 * Native下单
 *
 * @param string $description  商品描述
 * @param string $out_trade_no 商户系统内部订单号，只能是数字、大小写字母_-*且在同一个商户号下唯一
 * @param int    $total        订单总金额，单位为分。
 * @param array  $other        其他非必填参数
 * 
 * @return ResponseInterface|ApiResponse
 */
```
示例：
```php
$pay->payTransactionsNative("测试支付", "20250205100100000", 1);
```
### JSAPI下单
方法说明：
```php
/**
 * JSAPI下单
 *
 * @param string $description  商品描述
 * @param string $out_trade_no 商户系统内部订单号，只能是数字、大小写字母_-*且在同一个商户号下唯一
 * @param int    $total        订单总金额，单位为分。
 * @param string $openid       用户在直连商户appid下的唯一标识。
 * @param array  $other        其他非必填参数
 *
 * @return ApiResponse|ResponseInterface
 */
```
示例：
```php
$pay->payTransactionsJsapi("测试支付", "20250205100100000", 1, 'wx52s1fq9kgf3h5t89');
```
### 申请退款
方法说明：
```php
/**
 * 申请退款
 *
 * @param string $out_refund_no  商户系统内部的退款单号，商户系统内部唯一，只能是数字、大小写字母_-|*@ ，同一退款单号多次请求只退一笔。
 * @param int    $refund         退款金额，单位为分，只能为整数，不能超过原订单支付金额。
 * @param int    $total          原支付交易的订单总金额，单位为分，只能为整数。
 * @param string $transaction_id 原支付交易对应的微信订单号
 * @param string $out_trade_no   原支付交易对应的商户订单号
 * @param array  $other          其他非必填参数
 *
 * @return ApiResponse|ResponseInterface
 */
```
示例：
```php
// transaction_id和out_trade_no二选一，必须有一个
$pay->refund("refund20250205100100000", 1, 1,'20230201121854308820');
```
## 支付宝支付
```php
$pay = $api->aliPay("异步通知地址，可为空");
```
### 电脑网站支付
```php
/**
 * 电脑网站支付
 *
 * @param string    $subject        订单标题
 * @param string    $out_trade_no   交易创建时传入的商户订单号
 * @param float|int $amount         订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
 * @param string    $return_url     支付成功后同步跳转的页面，是一个http/https开头的字符串
 * @param array     $other          其他非必填参数
 *
 * @return ApiResponse|ResponseInterface
 */
```
```php
$pay->payForPc('订单标题', '123456789', 0.01);
```
### 手机网站支付
```php
/**
 * 手机网站支付
 *
 * @param string    $subject       订单标题
 * @param string    $out_trade_no  交易创建时传入的商户订单号
 * @param float|int $amount        订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
 * @param string    $quit_url      用户付款中途退出返回商户网站的地址
 * @param string    $return_url    支付成功后同步跳转的页面，是一个http/https开头的字符串
 * @param array     $other         其他非必填参数
 *
 * @return ApiResponse|ResponseInterface
 */
```
```php
$pay->payForWap('订单标题', '123456789', 0.01,"https://w7.cc");
```
### 交易退款
```php
/**
 * 交易退款
 *
 * @param string    $out_trade_no 交易创建时传入的商户订单号
 * @param float|int $amount       需要退款的金额，该金额不能大于订单金额，单位为元，支持两位小数
 * @param array     $other        其他非必填参数
 *
 * @return ApiResponse|ResponseInterface
 */
```
```php
$pay->refund('123456789', 0.01);
```
## 号码同步通知

当号码删除，停用，恢复的时候，平台会向应用关联中填写的Url发起post json通知，通知内容如下：
```json
{
  "account_app_id":"号码的App_id，如未填这里是空字符串",
  "account_type":"号码类型,整数",
  "app_id":"应用关联app_id",
  "data":[
    "附加数据，目前为空"
  ],
  "event":"事件类型：recycle:停用,restore:恢复,delete:删除"
}
```
