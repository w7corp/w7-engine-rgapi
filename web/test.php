<?php
//1.php8兼容问题，较突出的有：
//    1.{} 改成 []，这个也导致微擎提供的加密代码解密失败。
//       报错：“Array and string offset access syntax with curly braces is no longer supported” （例子：点爆圣诞抢红包）
//    2.参数位置问题，有默认值的参数需放在后面。
//       报错：“Required parameter $default follows optional parameter $condition”，
//2.扩展问题：(镜像支持，记得咱们之前搞过)
//    1.sg11(例子：崛企智慧酒店单商户版 -> ewei_hotel， 惠花卡->hc_card)
//    2.ionCube(例子：壹佰智慧轻站 -> yb_guanwang)
//3.兼容问题
//    1.一些系统函数，如permission_check_account_user，user_is_vice_founder  数量多，建议开发者兼容
//    2.有一些表没有用就删除了，可是有些开发者使用了这些删除的表。表数量变化：138->51张。有删除的表，有修改的表（如uni_account,users,account）
//      (例子：上门预约服务小程序->xg_o2o)    建议开发者兼容
//    3.号码信息结构调整(原来的用法如：$_W['account']->account['name'],$_W['account']['name'])
//    4.小程序上传及小程序的version_id，现在没了

//Muu云课堂V2 --正常进入后台
//微课堂V2--正常进入后台
//社群空间站--正常进入后台
//牛了个牛通关版--正常进入后台
//九块九进群--正常进入后台
//惠花生活--部分正常，部分不正常，扩展没有（2.1）
//上门预约服务小程序--进不去后台，表不兼容(3.2)
//壹佰智慧轻站--进不去后台，扩展没有（2.2）
//崛企智慧酒店单商户版--进不去后台，扩展没有（2.1）
//点爆圣诞抢红包--进不去后台，代码加密问题（1.1）

//小程序测试
//微课堂V2------------无法测试，需要直播插件（申请不上）
//Muu云课堂V2---------需无法测试，要直播插件（申请不上）
//壹佰智慧轻站---------无法测试，代码包里的前端包不完整
//牛了个牛通关版-------可以正常运行（有app_id和app_secret请求微信接口,改动了下源码后正常）
//上门预约服务小程序----无法测试，进不去，表兼容问题
//惠花生活-------------无法测试，原2.0系统和2.0独立系统都报错
//崛企智慧酒店单商户版--只支持公众号
//九块九进群----------只支持公众号
//社群空间站----------只支持公众号
//点爆圣诞抢红包------只支持公众号
define('IN_SYS', true);
require __DIR__ . '/../framework/bootstrap.inc.php';
require IA_ROOT . '/web/common/bootstrap.sys.inc.php';

$entris = module_entries('fy_lessonv2');
echo "<pre>";
print_r($entris);
echo "</pre>";
exit;
