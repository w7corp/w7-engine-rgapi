## 初始化Api
```php
$api = new \W7\Sdk\Module\Api("应用关联Appid", "应用关联App Sercet", "号码类型","BASE_URI");
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
            [aes_key] => 号码加密AESKEY
            [token] => 号码TOKEN
            [logo_url] => Logo的URL
        )

)
```
### 获取号码的AccessToken
```php
$api->app->getAccessToken();
```
返回如下：
```
Array
(
    [access_token] => AccessToken
    [expires_in] => 有效期(秒)
)
```