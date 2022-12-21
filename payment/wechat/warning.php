<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/payment/wechat/warning.php : v 1184e30b9b78 : 2015/09/06 10:57:13 : RenChao $
 */
require '../../source/bootstrap.inc.php';
$input = file_get_contents('php://input');
/*
$input = "
<xml>
<AppId><![CDATA[wxa0ab09ff1cd1b49b]]></AppId>
<ErrorType>1001</ErrorType>
<Description><![CDATA[错误描述]]></Description>
<AlarmContent><![CDATA[错误详情]]></AlarmContent>
<TimeStamp>1393860740</TimeStamp>
<AppSignature><![CDATA[f8164781a303f4d5a944a2dfc68411a8c7e4fbea]]></AppSignature>
<SignMethod><![CDATA[sha1]]></SignMethod>
</xml>
";
*/
if (preg_match('/(\<\!DOCTYPE|\<\!ENTITY)/i', $input)) {
    exit('fail');
}
libxml_disable_entity_loader(true);
$obj = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
if ($obj instanceof SimpleXMLElement && !empty($obj->FeedBackId)) {
    $data = array(
        'appid' => trim($obj->AppId),
        'timestamp' => trim($obj->TimeStamp),
        'errortype' => trim($obj->ErrorType),
        'description' => trim($obj->Description),
        'alarmcontent' => trim($obj->AlarmContent),
        'appsignature' => trim($obj->AppSignature),
        'signmethod' => trim($obj->SignMethod),
    );
    require '../../framework/bootstrap.inc.php';
    WeUtility::logging('pay-warning', $input);
}
exit('success');
