<?php
/**
 * 附件管理
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->func('file');

$do = in_array($do, array('upload', 'delete')) ? $do : 'upload';
$type = in_array($_GPC['type'], array('image', 'audio', 'video')) ? $_GPC['type'] : 'image';

if ($do == 'delete') {
    $result = array('error' => 1, 'message' => '');
    $id = intval($_GPC['id']);
    if (empty($id)) {
        return message($result, '', 'ajax');
    }

    $attachment = table('core_attachment')
        ->select(array('attachment', 'uniacid', 'uid'))
        ->where(array(
            'id' => $id,
            'uniacid' => $_W['uniacid']
        ))
        ->get();
    if (empty($attachment)) {
        return message(error(1, '文件不存在或已删除！'), '', 'ajax');
    }
    if (empty($_W['openid']) || (!empty($_W['fans']) && $attachment['uid'] != $_W['fans']['from_user']) || (!empty($_W['member']) && $attachment['uid'] != $_W['member']['uid'])) {
        return message(error(1, '没有权限删除文件！'), '', 'ajax');
    }

    $uni_remote_setting = uni_setting_load('remote');
    if (!empty($uni_remote_setting['remote']['type'])) {
        $_W['setting']['remote'] = $uni_remote_setting['remote'];
    }
    if ($_W['setting']['remote']['type']) {
        $result = file_remote_delete($attachment['attachment']);
    } else {
        $result = file_delete($attachment['attachment']);
    }
    if (!is_error($result)) {
        table('core_attachment')
            ->where(array(
                'id' => $id,
                'uniacid' => $_W['uniacid']
            ))
            ->delete();
    }
    if (!is_error($result)) {
        return message(error('0'), '', 'ajax');
    } else {
        return message(error(1, $result['message']), '', 'ajax');
    }
}

if ($do == 'upload') {
    if (empty($_FILES['file']['tmp_name'])) {
        $binaryfile = file_get_contents('php://input', 'r');
        if (!empty($binaryfile)) {
            mkdirs(ATTACHMENT_ROOT . '/temp');
            $tempfilename = random(5);
            $tempfile = ATTACHMENT_ROOT . '/temp/' . $tempfilename;
            if (file_put_contents($tempfile, $binaryfile)) {
                $mime = explode('/', $_GPC['mimetype']);
                $_FILES['file'] = array(
                    'name' => $tempfilename . '.' . $mime[1],
                    'tmp_name' => $tempfile,
                    'error' => 0,
                );
            }
        }
    }
    $result = array(
        'jsonrpc' => '2.0',
        'id' => 'id',
        'error' => array('code' => 1, 'message' => ''),
    );
    if (empty($_FILES['file']['name'])) {
        $result['error']['message'] = '上传失败, 请选择要上传的文件！';
        die(json_encode($result));
    }
    if ($_FILES['file']['error'] != 0) {
        $result['error']['message'] = '上传失败, 请重试.错误码：' . $_FILES['file']['error'];
        die(json_encode($result));
    }
    $originname = safe_gpc_string($_FILES['file']['name']);
    if (empty($originname)) {
        $result['error']['message'] = '文件名只允许中文、字母、数字及常用标点的组合！';
        die(json_encode($result));
    }

    //兼容安卓手机上传附件 $_FILES['file']['name'] 没有后缀
    $name = explode('.', $_FILES['file']['name']);
    if (empty($name[1]) && !empty($_GPC['type'])) {
        $mime = explode('/', $_GPC['type']);
        $_FILES['file']['name'] .= $mime[1];
    }

    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    $size = intval($_FILES['file']['size']);
    $setting = $_W['setting']['upload'][$type];
    $zip_percentage = empty($_W['setting']['upload']['image']['zip_percentage']) ? false : true;
    $file = file_upload($_FILES['file'], $type, '', $zip_percentage);

    if (is_error($file)) {
        $result['error']['message'] = $file['message'];
        die(json_encode($result));
    }
    $pathname = $file['path'];
    $fullname = ATTACHMENT_ROOT . '/' . $pathname;

    if ('image' == $type) {
        $thumb = empty($setting['thumb']) || $ext == 'gif' ? 0 : 1; // 是否使用缩略
        $width = intval($setting['width']); // 缩略尺寸
        if ($thumb == 1 && $width > 0 && (!isset($_GPC['thumb']) || (isset($_GPC['thumb']) && !empty($_GPC['thumb'])))) {
            $thumbnail = file_image_thumb($fullname, '', $width);
            @unlink($fullname);
            if (is_error($thumbnail)) {
                $result['error']['message'] = $thumbnail['message'];
                die(json_encode($result));
            } else {
                $filename = pathinfo($thumbnail, PATHINFO_BASENAME);
                $pathname = $thumbnail;
                $fullname = ATTACHMENT_ROOT . '/' . $pathname;
            }
        }
    }

    $info = array(
        'name' => $originname,
        'ext' => $ext,
        'filename' => $pathname,
        'attachment' => $pathname,
        'url' => tomedia($pathname),
        'is_image' => 'image' == $type ? 1 : 0,
        'filesize' => filesize($fullname),
    );
    if ('image' == $type) {
        $size = getimagesize($fullname);
        $info['width'] = $size[0];
        $info['height'] = $size[1];
    } else {
        $size = filesize($fullname);
        $info['size'] = sizecount($size);
    }

    setting_load('remote');
    $uni_remote_setting = uni_setting_load('remote');
    if (!empty($uni_remote_setting['remote']['type'])) {
        $_W['setting']['remote'] = $uni_remote_setting['remote'];
    }
    if (!empty($_W['setting']['remote']['type'])) {
        $remotestatus = file_remote_upload($pathname);
        if (is_error($remotestatus)) {
            $result['error']['message'] = '远程附件上传失败，请检查配置并重新上传';
            file_delete($pathname);
            die(json_encode($result));
        } else {
            file_delete($pathname);
            $info['url'] = tomedia($pathname);
        }
    }

    table('core_attachment')
        ->fill(array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['uid'],
            'filename' => $_FILES['file']['name'],
            'attachment' => $pathname,
            'type' => 'image' == $type ? 1 : ('audio' == $type || 'voice' == $type ? 2 : 3),
            'createtime' => TIMESTAMP,
        ))
        ->save();
    $info['id'] = pdo_insertid();
    die(json_encode($info));
}
