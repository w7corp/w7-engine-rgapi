<?php
/**
 * 远程附件
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

function attachment_set_attach_url() {
    global $_W;
    if (empty($_W['setting']['remote_complete_info'])) {
        $_W['setting']['remote_complete_info'] = empty($_W['setting']['remote']) ? '' : $_W['setting']['remote'];
    }
    $attach_url = $_W['attachurl_local'] = $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/';
    if (!empty($_W['setting']['remote']['type'])) {
        if ($_W['setting']['remote']['type'] == ATTACH_OSS) {
            $attach_url = $_W['attachurl_remote'] = $_W['setting']['remote']['alioss']['url'] . '/';
        } elseif ($_W['setting']['remote']['type'] == ATTACH_QINIU) {
            $attach_url = $_W['attachurl_remote'] = $_W['setting']['remote']['qiniu']['url'] . '/';
        } elseif ($_W['setting']['remote']['type'] == ATTACH_COS) {
            $attach_url = $_W['attachurl_remote'] = $_W['setting']['remote']['cos']['url'] . '/';
        }
    }
    return $attach_url;
}

function attachment_alioss_datacenters() {
    $bucket_datacenter = array(
        'oss-cn-hangzhou' => '杭州数据中心',
        'oss-cn-qingdao' => '青岛数据中心',
        'oss-cn-beijing' => '北京数据中心',
        'oss-cn-hongkong' => '香港数据中心',
        'oss-cn-shenzhen' => '深圳数据中心',
        'oss-cn-shanghai' => '上海数据中心',
        'oss-us-west-1' => '美国硅谷数据中心',
    );
    return $bucket_datacenter;
}

function attachment_newalioss_auth($key, $secret, $bucket, $internal = false) {
    load()->library('oss');
    $buckets = attachment_alioss_buctkets($key, $secret);
    $host = $internal ? '-internal.aliyuncs.com' : '.aliyuncs.com';
    $url = 'http://' . $buckets[$bucket]['location'] . $host;
    $filename = 'favicon.ico';
    try {
        $ossClient = new \OSS\OssClient($key, $secret, $url);
        $ossClient->uploadFile($bucket, $filename, IA_ROOT . '/web/resource/images/' . $filename);
    } catch (\OSS\Core\OssException $e) {
        return error(1, $e->getMessage());
    }
    return 1;
}

function attachment_alioss_buctkets($key, $secret) {
    load()->library('oss');
    $url = 'http://oss-cn-beijing.aliyuncs.com';
    try {
        $ossClient = new \OSS\OssClient($key, $secret, $url);
    } catch (\OSS\Core\OssException $e) {
        return error(1, $e->getMessage());
    }
    try {
        $bucketlistinfo = $ossClient->listBuckets();
    } catch (OSS\OSS_Exception $e) {
        return error(1, $e->getMessage());
    }
    $bucketlistinfo = $bucketlistinfo->getBucketList();
    $bucketlist = array();
    foreach ($bucketlistinfo as &$bucket) {
        $bucketlist[$bucket->getName()] = array('name' => $bucket->getName(), 'location' => $bucket->getLocation());
    }
    return $bucketlist;
}

function attachment_qiniu_auth($key, $secret, $bucket) {
    load()->library('qiniu');
    $auth = new Qiniu\Auth($key, $secret);
    $token = $auth->uploadToken($bucket);
    $config = new Qiniu\Config();
    $uploadmgr = new Qiniu\Storage\UploadManager($config);
    list($ret, $err) = $uploadmgr->putFile($token, 'favicon.ico', IA_ROOT . '/web/resource/images/favicon.ico');
    if ($err !== null) {
        $err = (array)$err;
        $err = (array)array_pop($err);
        $err = json_decode($err['body'], true);
        return error(-1, $err);
    } else {
        return true;
    }
}
function attachment_cos_auth($bucket, $appid, $key, $secret, $bucket_local = '') {
    if (!is_numeric($appid)) {
        return error(-1, '传入appid值不合法, 请重新输入');
    }
    if (!preg_match('/^[a-zA-Z0-9]{36}$/', $key)) {
        return error(-1, '传入secretid值不合法，请重新传入');
    }
    if (!preg_match('/^[a-zA-Z0-9]{32}$/', $secret)) {
        return error(-1, '传入secretkey值不合法，请重新传入');
    }
    load()->library('cosv5');
    try {
        $cosClient = new Qcloud\Cos\Client(
            array(
                'region' => $bucket_local,
                'credentials' => array(
                    'secretId' => $key,
                    'secretKey' => $secret))
        );
        $cosClient->Upload($bucket . '-' . $appid, 'favicon.ico', fopen(IA_ROOT . '/web/resource/images/favicon.ico', 'rb'));
    } catch (\Exception $e) {
        return error(-1, $e->getMessage());
    }
    return true;
}

/**
 * 更换远程附件的 url 时，修改系统文章的图片url
 * @param $old_url
 * @param $new_url
 * @return mixed
 */
function attachment_replace_article_remote_url($old_url, $new_url) {
    if (empty($old_url) || empty($new_url) || $old_url == $new_url) {
        return false;
    }
    $content_exists = pdo_get('article_news', array('content LIKE' => "%{$old_url}%"));
    if (!empty($content_exists)) {
        $update_sql = "UPDATE " . tablename('article_news') . " SET `content`=REPLACE(content, :old_url, :new_url)";
        return pdo_query($update_sql, array(':old_url' => $old_url, ':new_url' => $new_url));
    }
}

/**
 * 递归图片分组
 * @param array $group_data 数组
 * @param int $pid 父级ID
 * @return array 返回树状数组
 */
function attachment_recursion_group($group_data = array(), $pid = 0) {
    if (empty($group_data)) {
        return array();
    }
    $return_data = array();
    foreach ($group_data as $key => $group_data_value) {
        if ($group_data_value['pid'] == $pid) {
            $return_data[$group_data_value['id']] = $group_data_value;
            $sub_group = attachment_recursion_group($group_data, $group_data_value['id']);
            if (0 == $pid) {
                $return_data[$group_data_value['id']]['sub_group'] = !empty($sub_group) ? $sub_group : array();
            }
        }
    }
    return $return_data;
}
