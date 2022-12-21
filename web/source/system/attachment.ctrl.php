<?php
/**
 * BAE相关设置选项
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
load()->model('setting');
load()->model('attachment');
load()->func('file');

$dos = array('attachment', 'remote', 'buckets', 'oss', 'cos', 'qiniu', 'upload_remote');
$do = in_array($do, $dos) ? $do : 'remote';

if ('upload_remote' == $do) {
    if (!empty($_W['setting']['remote_complete_info']['type'])) {
        $_W['setting']['remote'] = $_W['setting']['remote_complete_info'];
        $result = file_dir_remote_upload(ATTACHMENT_ROOT . 'images');
        if (is_error($result)) {
            iajax(-1, $result['message']);
        } else {
            if (file_dir_exist_image(ATTACHMENT_ROOT . 'images')) {
                iajax(0, array('status' => 1));
            } else {
                iajax(0, array('status' => 0, 'message' => '完成'));
            }
        }
    } else {
        iajax(-1, '请先填写并开启远程附件设置');
    }
}

//远程附件
if ('remote' == $do) {
    $remote = empty($_W['setting']['remote_complete_info']) ? array() : $_W['setting']['remote_complete_info'];
    $remote_urls = array(
        'alioss' => array('old_url' => $remote['alioss']['url'] ?? ''),
        'qiniu' => array('old_url' => $remote['qiniu']['url'] ?? ''),
        'cos' => array('old_url' => $remote['cos']['url'] ?? ''),
    );

    if ($_W['ispost']) {
        $remote = array(
            'type' => intval($_GPC['type']),
            'alioss' => array(
                'key' => safe_gpc_string($_GPC['alioss']['key']),
                'secret' => strexists($_GPC['alioss']['secret'], '*') ? $_W['setting']['remote_complete_info']['alioss']['secret'] : safe_gpc_string($_GPC['alioss']['secret']),
                'bucket' => safe_gpc_string($_GPC['alioss']['bucket']),
                'internal' => safe_gpc_string($_GPC['alioss']['internal']),
            ),
            'qiniu' => array(
                'accesskey' => safe_gpc_string($_GPC['qiniu']['accesskey']),
                'secretkey' => strexists($_GPC['qiniu']['secretkey'], '*') ? $_W['setting']['remote_complete_info']['qiniu']['secretkey'] : safe_gpc_string($_GPC['qiniu']['secretkey']),
                'bucket' => safe_gpc_string($_GPC['qiniu']['bucket']),
                'url' => rtrim(safe_gpc_url($_GPC['qiniu']['url'], false), '/'),
            ),
            'cos' => array(
                'appid' => safe_gpc_string($_GPC['cos']['appid']),
                'secretid' => safe_gpc_string($_GPC['cos']['secretid']),
                'secretkey' => strexists(safe_gpc_string($_GPC['cos']['secretkey']), '*') ? $_W['setting']['remote_complete_info']['cos']['secretkey'] : safe_gpc_string($_GPC['cos']['secretkey']),
                'bucket' => safe_gpc_string($_GPC['cos']['bucket']),
                'local' => safe_gpc_string($_GPC['cos']['local']),
                'url' => rtrim(safe_gpc_url($_GPC['cos']['url'], false), '/'),
            ),
        );
        switch ($remote['type']) {
            case ATTACH_OSS:
                if ('' == trim($remote['alioss']['key'])) {
                    if ($_W['isajax']) {
                        iajax(-1, '阿里云OSS-Access Key ID不能为空');
                    }
                    itoast('阿里云OSS-Access Key ID不能为空');
                }
                if ('' == trim($remote['alioss']['secret'])) {
                    if ($_W['isajax']) {
                        iajax(-1, '阿里云OSS-Access Key Secret不能为空');
                    }
                    itoast('阿里云OSS-Access Key Secret不能为空');
                }
                $buckets = attachment_alioss_buctkets($remote['alioss']['key'], $remote['alioss']['secret']);
                if (is_error($buckets)) {
                    if ($_W['isajax']) {
                        iajax(-1, 'OSS-Access Key ID 或 OSS-Access Key Secret错误，请重新填写');
                    }
                    itoast('OSS-Access Key ID 或 OSS-Access Key Secret错误，请重新填写');
                }
                list($remote['alioss']['bucket'], $remote['alioss']['url']) = explode('@@', safe_gpc_string($_GPC['alioss']['bucket']));
                if (empty($buckets[$remote['alioss']['bucket']])) {
                    if ($_W['isajax']) {
                        iajax(-1, 'Bucket不存在或是已经被删除');
                    }
                    itoast('Bucket不存在或是已经被删除');
                }
                $remote['alioss']['url'] = 'http://' . $remote['alioss']['bucket'] . '.' . $buckets[$remote['alioss']['bucket']]['location'] . '.aliyuncs.com';
                $remote['alioss']['ossurl'] = $buckets[$remote['alioss']['bucket']]['location'] . '.aliyuncs.com';
                if (!empty($_GPC['custom']['url'])) {
                    $url = safe_gpc_url(trim($_GPC['custom']['url'], '/'), false);
                    if (!strexists($url, 'http://') && !strexists($url, 'https://')) {
                        $url = 'http://' . $url;
                    }
                    $remote['alioss']['url'] = $url;
                }
                attachment_replace_article_remote_url($remote_urls['alioss']['old_url'], $remote['alioss']['url']);
                break;
            case ATTACH_QINIU:
                if (empty($remote['qiniu']['accesskey'])) {
                    if ($_W['isajax']) {
                        iajax(-1, '请填写Accesskey.');
                    }
                    itoast('请填写Accesskey');
                }
                if (empty($remote['qiniu']['secretkey'])) {
                    if ($_W['isajax']) {
                        iajax(-1, '请填写secretkey.');
                    }
                    itoast('请填写secretkey');
                }
                if (empty($remote['qiniu']['bucket'])) {
                    if ($_W['isajax']) {
                        iajax(-1, '请填写bucket.');
                    }
                    itoast('请填写bucket');
                }
                if (empty($remote['qiniu']['url'])) {
                    if ($_W['isajax']) {
                        iajax(-1, '请填写url.');
                    }
                    itoast('请填写url');
                } else {
                    $remote['qiniu']['url'] = strexists($remote['qiniu']['url'], 'http') ? $remote['qiniu']['url'] : ('http://' . $remote['qiniu']['url']);
                }
                attachment_replace_article_remote_url($remote_urls['qiniu']['old_url'], $remote['qiniu']['url']);
                $auth = attachment_qiniu_auth($remote['qiniu']['accesskey'], $remote['qiniu']['secretkey'], $remote['qiniu']['bucket']);
                if (is_error($auth)) {
                    $message = $auth['message']['error'] == 'bad token' ? 'Accesskey或Secretkey填写错误， 请检查后重新提交' : 'bucket填写错误或是bucket所对应的存储区域选择错误，请检查后重新提交';
                    if ($_W['isajax']) {
                        iajax(-1, $message);
                    }
                    itoast($message);
                }
                break;
            case ATTACH_COS:
                if (empty($remote['cos']['appid'])) {
                    if ($_W['isajax']) {
                        iajax(-1, '请填写APPID');
                    }
                    itoast('请填写APPID');
                }
                if (empty($remote['cos']['secretid'])) {
                    if ($_W['isajax']) {
                        iajax(-1, '请填写SECRETID');
                    }
                    itoast('请填写SECRETID');
                }
                if (empty($remote['cos']['secretkey'])) {
                    if ($_W['isajax']) {
                        iajax(-1, '请填写SECRETKEY');
                    }
                    itoast('请填写SECRETKEY');
                }
                if (empty($remote['cos']['bucket'])) {
                    if ($_W['isajax']) {
                        iajax(-1, '请填写BUCKET');
                    }
                    itoast('请填写BUCKET');
                }
                $remote['cos']['bucket'] = str_replace("-{$remote['cos']['appid']}", '', trim($remote['cos']['bucket']));
                if (empty($remote['cos']['url'])) {
                    $remote['cos']['url'] = sprintf('https://%s-%s.cos%s.myqcloud.com', $remote['cos']['bucket'], $remote['cos']['appid'], $remote['cos']['local']);
                }
                $_W['setting']['remote']['cos'] = array();
                attachment_replace_article_remote_url($remote_urls['cos']['old_url'], $remote['cos']['url']);
                $auth = attachment_cos_auth($remote['cos']['bucket'], $remote['cos']['appid'], $remote['cos']['secretid'], $remote['cos']['secretkey'], $remote['cos']['local']);
    
                if (is_error($auth)) {
                    if ($_W['isajax']) {
                        iajax(-1, $auth['message']);
                    }
                    itoast($auth['message']);
                }
                break;
        }
        $_W['setting']['remote_complete_info'] = empty($_W['setting']['remote_complete_info']) || !is_array($_W['setting']['remote_complete_info']) ? array() : $_W['setting']['remote_complete_info'];
        $_W['setting']['remote_complete_info']['type'] = $remote['type'];
        $_W['setting']['remote_complete_info']['alioss'] = $remote['alioss'];
        $_W['setting']['remote_complete_info']['qiniu'] = $remote['qiniu'];
        $_W['setting']['remote_complete_info']['cos'] = $remote['cos'];
        setting_save($_W['setting']['remote_complete_info'], 'remote');
        if ($_W['isajax']) {
            iajax(0, '远程附件配置信息更新成功！');
        }
        itoast('远程附件配置信息更新成功！', url('system/attachment/remote'), 'success');
    }
    $bucket_datacenter = attachment_alioss_datacenters();
    $local_attachment = file_dir_exist_image(ATTACHMENT_ROOT . 'images');
    $cos_bucket_area = array(
        'ap-beijing-1' => '北京一区（已售罄）',
        'ap-beijing' => '北京',
        'ap-nanjing' => '南京',
        'ap-shanghai' => '上海',
        'ap-guangzhou' => '广州',
        'ap-chengdu' => '成都',
        'ap-chongqing' => '重庆',
        'ap-shenzhen-fsi' => '深圳金融',
        'ap-shanghai-fsi' => '上海金融',
        'ap-beijing-fsi' => '北京金融',
        'ap-hongkong' => '中国香港',
        'ap-singapore' => '新加坡',
        'ap-mumbai' => '孟买',
        'ap-seoul' => '首尔',
        'ap-bangkok' => '曼谷',
        'ap-tokyo' => '东京',
        'na-siliconvalley' => '硅谷',
        'na-ashburn' => '弗吉尼亚',
        'na-toronto' => '多伦多',
        'eu-frankfurt' => '法兰克福',
        'eu-moscow' => '莫斯科',
    );
    if ($_W['isajax']) {
        $message = array(
            'remote' => $remote,
            'bucket_datacenter' => $bucket_datacenter,
            'local_attachment' => $local_attachment,
            'cos_bucket_area' => $cos_bucket_area
        );
        iajax(0, $message);
    }
    template('system/attachment');
}

if ('buckets' == $do) {
    $key = safe_gpc_string($_GPC['key']);
    $secret = safe_gpc_string($_GPC['secret']);
    $buckets = attachment_alioss_buctkets($key, $secret);
    if (is_error($buckets)) {
        iajax(-1, '');
    }
    $bucket_datacenter = attachment_alioss_datacenters();
    $bucket = array();
    foreach ($buckets as $key => $value) {
        $value['bucket_key'] = $value['name'] . '@@' . $value['location'];
        $value['loca_name'] = $key . '@@' . $bucket_datacenter[$value['location']];
        $bucket[] = $value;
    }
    iajax(0, $bucket);
}

if ('oss' == $do) {
    $key = safe_gpc_string($_GPC['key']);
    $secret = strexists($_GPC['secret'], '*') ? $_W['setting']['remote_complete_info']['alioss']['secret'] : safe_gpc_string($_GPC['secret']);
    $bucket = empty($_GPC['bucket']) ? '' : safe_gpc_string($_GPC['bucket']);
    $buckets = attachment_alioss_buctkets($key, $secret);
    list($bucket, $url) = explode('@@', safe_gpc_string($_GPC['bucket']));
    $result = attachment_newalioss_auth($key, $secret, $bucket, safe_gpc_string($_GPC['internal']));
    if (is_error($result)) {
        iajax(-1, 'OSS-Access Key ID 或 OSS-Access Key Secret错误，请重新填写');
    }
    $ossurl = $buckets[$bucket]['location'] . '.aliyuncs.com';
    if (!empty($_GPC['url'])) {
        if (!strexists($_GPC['url'], 'http://') && !strexists($_GPC['url'], 'https://')) {
            $url = 'http://' . safe_gpc_string($_GPC['url']);
        } else {
            $url = safe_gpc_url($_GPC['url'], false);
        }
        $url = trim($url, '/') . '/';
    } else {
        $url = 'http://' . $bucket . '.' . $buckets[$bucket]['location'] . '.aliyuncs.com/';
    }
    load()->func('communication');
    $filename = 'favicon.ico';
    $response = ihttp_request($url . '/' . $filename, array(), array('CURLOPT_REFERER' => $_SERVER['SERVER_NAME']));
    if (is_error($response)) {
        iajax(-1, '配置失败，阿里云访问url错误');
    }
    if (200 != intval($response['code'])) {
        iajax(-1, '配置失败，阿里云访问url错误,请保证bucket为公共读取的');
    }
    $image = getimagesizefromstring($response['content']);
    if (!empty($image) && strexists($image['mime'], 'image')) {
        iajax(0, '配置成功');
    } else {
        iajax(-1, '配置失败，阿里云访问url错误');
    }
}

if ('qiniu' == $do) {
    $_GPC['secretkey'] = strexists($_GPC['secretkey'], '*') ? $_W['setting']['remote_complete_info']['qiniu']['secretkey'] : safe_gpc_string($_GPC['secretkey']);
    $auth = attachment_qiniu_auth(safe_gpc_string($_GPC['accesskey']), safe_gpc_string($_GPC['secretkey']), safe_gpc_string($_GPC['bucket']));
    if (is_error($auth)) {
        iajax(-1, '配置失败，请检查配置。注：请检查存储区域是否选择的是和bucket对应<br/>的区域', '');
    }
    load()->func('communication');
    $url = safe_gpc_url($_GPC['url'], false);
    $url = strexists($url, 'http') ? trim($url, '/') : 'http://' . trim($url, '/');
    $filename = 'favicon.ico';
    $response = ihttp_request($url . '/' . $filename, array(), array('CURLOPT_REFERER' => $_SERVER['SERVER_NAME']));
    if (is_error($response)) {
        iajax(-1, '配置失败，七牛访问url错误');
    }
    if (200 != intval($response['code'])) {
        iajax(-1, '配置失败，七牛访问url错误,请保证bucket为公共读取的');
    }
    $image = getimagesizefromstring($response['content']);
    if (!empty($image) && strexists($image['mime'], 'image')) {
        iajax(0, '配置成功');
    } else {
        iajax(-1, '配置失败，七牛访问url错误');
    }
}

if ('cos' == $do) {
    $url = safe_gpc_url($_GPC['url'], false);
    $appid = safe_gpc_string($_GPC['appid']);
    $secretid = safe_gpc_string($_GPC['secretid']);
    $local = safe_gpc_string($_GPC['local']);
    $secretkey = strexists($_GPC['secretkey'], '*') ? $_W['setting']['remote_complete_info']['cos']['secretkey'] : safe_gpc_string($_GPC['secretkey']);
    $bucket = str_replace("-{$appid}", '', safe_gpc_string($_GPC['bucket']));

    if (empty($url)) {
        $url = sprintf('https://%s-%s.cos.%s.myqcloud.com', $bucket, $appid, $local);
    }
    $url = rtrim($url, '/');
    $_W['setting']['remote']['cos'] = array();
    $auth = attachment_cos_auth($bucket, $appid, $secretid, $secretkey, $local);

    if (is_error($auth)) {
        iajax(-1, '配置失败，请检查配置' . $auth['message'], '');
    }
    load()->func('communication');
    $response = ihttp_request($url . '/favicon.ico', array(), array('CURLOPT_REFERER' => $_SERVER['SERVER_NAME']));
    if (is_error($response)) {
        iajax(-1, '配置失败，腾讯cos访问url错误');
    }
    if (200 != intval($response['code'])) {
        iajax(-1, '配置失败，腾讯cos访问url错误,请保证bucket为公共读取的');
    }
    $image = getimagesizefromstring($response['content']);
    if (!empty($image) && strexists($image['mime'], 'image')) {
        iajax(0, '配置成功');
    } else {
        iajax(-1, '配置失败，腾讯cos访问url错误');
    }
}
