<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.w7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

load()->func('file');
load()->model('material');
load()->model('attachment');
load()->model('module');

if (!in_array($do, array('upload', 'fetch', 'browser', 'delete', 'image', 'module', 'video', 'voice', 'news', 'keyword',
    'networktowechat', 'networktolocal', 'tolocal', 'wechat_upload',
    'group_list', 'add_group', 'change_group', 'del_group', 'move_to_group', 'change_name'))) {
    if ($_W['isajax']) {
        iajax(-1, 'Access Denied');
    }
    exit('Access Denied');
}
$result = array(
    'error' => 1,
    'message' => '',
    'data' => '',
);

error_reporting(0);
$type = safe_gpc_string($_GPC['upload_type']);
$type = in_array($type, array('image', 'audio', 'video')) ? $type : 'image';
$option = array();
$option = array_elements(array('uploadtype', 'dest_dir'), $_POST);
$option['width'] = intval($option['width']);
$module_name = empty($_GPC['module_name']) ? '' : safe_gpc_string($_GPC['module_name']);

$dest_dir = safe_gpc_string($_GPC['dest_dir']);
if (preg_match('/^[a-zA-Z0-9_\/]{0,50}$/', $dest_dir)) {
    $dest_dir = trim($dest_dir, '/');
    $pieces = explode('/', $dest_dir);
    if (count($pieces) > 3) {
        $dest_dir = '';
    }
} else {
    $dest_dir = '';
}
$module_upload_dir = '';
if ('' != $dest_dir) {
    $module_upload_dir = sha1($dest_dir);
}

$setting = $_W['setting']['upload'][$type];
$uniacid = $_W['uniacid'];

if ($uniacid == 0 && !empty($_W['isfounder'])) {
    $setting['folder'] = "{$type}s/global/";
    if (!empty($dest_dir)) {
        $setting['folder'] .= '' . $dest_dir . '/';
    }
} else {
    $setting['folder'] = "{$type}s/{$uniacid}";
    if (empty($dest_dir)) {
        $setting['folder'] .= '/' . date('Y/m/');
    } else {
        $setting['folder'] .= '/' . $dest_dir . '/';
    }
}
if ('fetch' == $do) {
    $url = safe_gpc_url($_GPC['url'], false);
    if (!file_is_image($url)) {
        die(json_encode(array('message' => '??????????????????')));
    }
    $resp = ihttp_get($url);
    if (is_error($resp)) {
        $result['message'] = '??????????????????, ????????????: ' . $resp['message'];
        die(json_encode($result));
    }
    if (200 != intval($resp['code'])) {
        $result['message'] = '??????????????????: ????????????????????????.';
        die(json_encode($result));
    }
    $ext = '';
    if ('image' == $type) {
        switch ($resp['headers']['Content-Type']) {
            case 'application/x-jpg':
            case 'image/jpeg':
                $ext = 'jpg';
                break;
            case 'image/png':
                $ext = 'png';
                break;
            case 'image/gif':
                $ext = 'gif';
                break;
            default:
                $result['message'] = '??????????????????, ????????????????????????.';
                die(json_encode($result));
                break;
        }
    } else {
        $result['message'] = '??????????????????, ?????????????????????.';
        die(json_encode($result));
    }
    $size = intval($resp['headers']['Content-Length']);
    if ($size > $setting['limit'] * 1024) {
        $result['message'] = '???????????????????????????(' . sizecount($size) . ' > ' . sizecount($setting['limit'] * 1024);
        die(json_encode($result));
    }
    $originname = pathinfo($url, PATHINFO_BASENAME);
    $filename = file_random_name(ATTACHMENT_ROOT . '/' . $setting['folder'], $ext);
    $pathname = $setting['folder'] . $filename;
    $fullname = ATTACHMENT_ROOT . '/' . $pathname;
    if (false == file_put_contents($fullname, $resp['content'])) {
        $result['message'] = '????????????.';
        die(json_encode($result));
    }
}

if ('upload' == $do) {
    if (empty($_FILES['file']['name'])) {
        $result['message'] = '????????????, ??????????????????????????????';
        iajax(-1, $result['message']);
    }
    if ($_FILES['file']['error'] != 0) {
        $result['message'] = '????????????, ?????????.????????????' . $_FILES['file']['error'];
        iajax(-1, $result['message']);
    }
    $originname = safe_gpc_string($_FILES['file']['name']);
    if (empty($originname)) {
        iajax(-1, '?????????????????????????????????????????????????????????????????????');
    }
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    $size = intval($_FILES['file']['size']);
    $filename = file_random_name(ATTACHMENT_ROOT . '/' . $setting['folder'], $ext);
    $zip_percentage = empty($_W['setting']['upload']['image']['zip_percentage']) ? false : true;
    $file = file_upload($_FILES['file'], $type, $setting['folder'] . $filename, $zip_percentage);
    if (is_error($file)) {
        $result['message'] = $file['message'];
        iajax(-1, $result['message']);
    }
    $pathname = $file['path'];
    $fullname = ATTACHMENT_ROOT . '/' . $pathname;
}

if ('fetch' == $do || 'upload' == $do) {
    if ('image' == $type) {
        $thumb = empty($setting['thumb']) || 'gif' == $ext ? 0 : 1;
        $width = intval($setting['width']);
        if (isset($option['thumb'])) {
            $thumb = empty($option['thumb']) ? 0 : 1;
        }
        if ($thumb && $width <= 0) {
            $width = 800;
        }
        if (isset($option['width']) && !empty($option['width'])) {
            $width = intval($option['width']);
        }
        if (1 == $thumb && $width > 0) {
            $thumbnail = file_image_thumb($fullname, '', $width);
            @unlink($fullname);
            if (is_error($thumbnail)) {
                $result['message'] = $thumbnail['message'];
                iajax(-1, $result);
            } else {
                $filename = pathinfo($thumbnail, PATHINFO_BASENAME);
                $pathname = $thumbnail;
                $fullname = ATTACHMENT_ROOT . '/' . $pathname;
            }
        }
    }
    $group_id = safe_gpc_int($_GPC['group_id']);
    $module_info = table('modules')->getByName($module_name);
    if (in_array($type, array('image', 'thumb')) && $group_id <= 0 && !empty($module_info)) {
        $group_exist = table('core_attachment_group')
            ->where(array('type' => 0, 'name' => $module_info['title']))
            ->searchWithUniacidOrUid($uniacid, $_W['uid'])
            ->getcolumn('id');
        if (empty($group_exist)) {
            table('core_attachment_group')
                ->fill(array(
                    'name' => $module_info['title'],
                    'uniacid' => $uniacid,
                    'uid' => $_W['uid'],
                    'type' => 0
                ))
                ->save();
            $group_id = pdo_insertid();
        } else {
            $group_id = $group_exist;
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
        'group_id' => $group_id,
    );
    if ('image' == $type) {
        $size = getimagesize($fullname);
        $info['width'] = $size[0];
        $info['height'] = $size[1];
    } else {
        $size = filesize($fullname);
        $info['size'] = sizecount($size);
    }
    if (!empty($_W['setting']['remote']['type'])) {
        $remotestatus = file_remote_upload($pathname);
        if (is_error($remotestatus)) {
            file_delete($pathname);
            iajax(-1, $remotestatus['message']);
        } else {
            $info['url'] = tomedia($pathname);
        }
    }
    pdo_insert('core_attachment', array(
        'uniacid' => $uniacid,
        'uid' => $_W['uid'],
        'filename' => $originname,
        'attachment' => $pathname,
        'type' => 'image' == $type ? 1 : ('audio' == $type || 'voice' == $type ? 2 : 3),
        'createtime' => TIMESTAMP,
        'module_upload_dir' => $module_upload_dir,
        'group_id' => intval($_GPC['group_id']),
    ));
    $info['state'] = 'SUCCESS';
    die(json_encode($info));
}

if ('change_name' == $do) {
    $id = intval($_GPC['id']);
    $core_attachment_table = table('core_attachment');
    $condition = array('id' => $id);
    if (empty($uniacid)) {
        $condition['uid'] = $_W['uid'];
    } else {
        $condition['uniacid'] = $uniacid;
    }
    $new_filename = safe_gpc_string($_GPC['new_filename']);
    $data = array('filename' => $new_filename);
    $result = $core_attachment_table->where($condition)->fill($data)->save();
    if ($result) {
        iajax(0, '???????????????');
    } else {
        iajax(-1, '????????????!');
    }
}

if ('delete' == $do) {
    $id = $_GPC['id'];
    if (!is_array($id)) {
        $id = array(intval($id));
    }
    $id = safe_gpc_array($id);
    $core_attachment_table = table('core_attachment');
    $core_attachment_table->searchWithId($id);
    if (empty($uniacid)) {
        $core_attachment_table->searchWithUid($_W['uid']);
        $_W['setting']['remote'] = $_W['setting']['remote_complete_info'];
    } else {
        $core_attachment_table->searchWithUniacid($uniacid);
        $uni_remote_setting = uni_setting_load('remote');
        if (!empty($uni_remote_setting['remote']['type'])) {
            $_W['setting']['remote'] = $uni_remote_setting['remote'];
        }
    }
    $attachments = $core_attachment_table->getall();
    $delete_ids = array();
    foreach ($attachments as $media) {
        if (!empty($_W['setting']['remote']['type'])) {
            $status = file_remote_delete($media['attachment']);
        } else {
            $status = file_delete($media['attachment']);
        }
        if (is_error($status)) {
            iajax(1, $status['message']);
            exit;
        }
        $delete_ids[] = $media['id'];
    }

    pdo_delete('core_attachment', array('id' => $delete_ids, 'uniacid' => $uniacid));
    iajax(0, '????????????');
}

$limit = array();
$limit['temp'] = array(
    'image' => array(
        'ext' => array('jpg', 'logo'),
        'size' => 1024 * 1024,
        'errmsg' => '?????????????????????jpg/logo??????,??????????????????1M',
    ),
    'voice' => array(
        'ext' => array('amr', 'mp3'),
        'size' => 2048 * 1024,
        'errmsg' => '?????????????????????amr/mp3??????,??????????????????2M',
    ),
    'video' => array(
        'ext' => array('mp4'),
        'size' => 10240 * 1024,
        'errmsg' => '?????????????????????mp4??????,??????????????????10M',
    ),
    'thumb' => array(
        'ext' => array('jpg', 'logo'),
        'size' => 64 * 1024,
        'errmsg' => '????????????????????????jpg/logo??????,??????????????????64K',
    ),
);
$limit['perm'] = array(
    'image' => array(
        'ext' => array('bmp', 'png', 'jpeg', 'jpg', 'gif'),
        'size' => 2048 * 1024,
        'max' => 100000,
        'errmsg' => '?????????????????????bmp/png/jpeg/jpg/gif??????,??????????????????2M',
    ),
    'voice' => array(
        'ext' => array('mp3', 'wma', 'wav', 'amr'),
        'size' => 2048 * 1024,
        'max' => 1000,
        'errmsg' => '?????????????????????mp3/wma/wav/amr??????,??????????????????2M,???????????????60???',
    ),
    'video' => array(
        'ext' => array('mp4'),
        'size' => 10240 * 1024,
        'max' => 1000,
        'errmsg' => '?????????????????????mp4??????,??????????????????10M',
    ),
    'thumb' => array(
        'ext' => array('jpg'),
        'size' => 64 * 1024,
        'max' => 1000,
        'errmsg' => '????????????????????????jpg??????,??????????????????64KB',
    ),
);

$limit['file_upload'] = array(
    'image' => array(
        'ext' => array('jpg'),
        'size' => 1024 * 1024,
        'max' => -1,
        'errmsg' => '???????????????jpg??????,??????????????????1M',
    ),
);
if ('wechat_upload' == $do) {
    $type = safe_gpc_string($_GPC['upload_type']);
    $mode = safe_gpc_string($_GPC['mode']);
    if ('image' == $type || 'thumb' == $type) {
        $type = 'image';
    }
    if ('audio' == $type) {
        $type = 'voice';
    }

    $setting['folder'] = "{$type}s/{$_W['uniacid']}" . '/' . date('Y/m/');

    if ('perm' == $mode) {
        $now_count = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('wechat_attachment') . ' WHERE uniacid = :aid AND model = :model AND type = :type', array(':aid' => $_W['uniacid'], ':model' => $mode, ':type' => $type));
        if ($now_count >= $limit['perm'][$type]['max']) {
            $result['message'] = '????????????????????????,?????????????????????????????????';
            iajax(-1, $result['message']);
        }
    }

    if (empty($mode) || empty($type) || !$_W['acid']) {
        $result['message'] = '??????????????????';
        iajax(-1, $result['message']);
    }

    if (empty($_FILES['file']['name'])) {
        $result['message'] = '????????????, ??????????????????????????????';
        iajax(-1, $result['message']);
    }

    if ($_FILES['file']['error'] != 0) {
        $result['message'] = '????????????, ?????????.';
        iajax(-1, $result['message']);
    }

    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    $size = intval($_FILES['file']['size']);
    $originname = $_FILES['file']['name'];

    if (!in_array($ext, $limit[$mode][$type]['ext']) || ($size > $limit[$mode][$type]['size'])) {
        $result['message'] = $limit[$mode][$type]['errmsg'];
        iajax(-1, $result['message']);
    }

    $filename = file_random_name(ATTACHMENT_ROOT . '/' . $setting['folder'], $ext);

    $file = file_wechat_upload($_FILES['file'], $type, $setting['folder'] . $filename, true);

    if (is_error($file)) {
        $result['message'] = $file['message'];
        iajax(-1, $result['message']);
    }

    $pathname = $file['path'];
    $fullname = ATTACHMENT_ROOT . '/' . $pathname;
    $acc = WeAccount::createByUniacid();
    if ('perm' == $mode || 'temp' == $mode) {
        if ('video' != $type) {
            $result = $acc->uploadMediaFixed($pathname, $type);
        } else {
            $result = $acc->uploadVideoFixed($originname, $originname, $pathname);
        }
    }
    if (is_error($result)) {
        file_delete($pathname);
        iajax(-1, $result['message']);
    }

    if ('image' == $type || 'thumb' == $type) {
        $file['path'] = file_image_thumb($fullname, '', 300);
    }

    if (!empty($_W['setting']['remote']['type']) && !empty($file['path'])) {
        $remotestatus = file_remote_upload($file['path']);
        if (is_error($remotestatus)) {
            file_delete($pathname);
            if ('image' == $type || 'thumb' == $type) {
                file_delete($file['path']);
            }
            $result['error'] = 0;
            $result['message'] = '?????????????????????????????????????????????????????????';
            iajax(-1, $result['message']);
        } else {
            file_delete($pathname);
            if ('image' == $type || 'thumb' == $type) {
                file_delete($file['path']);
            }
        }
    }

    $insert = array(
        'uniacid' => $_W['uniacid'],
        'acid' => $_W['acid'],
        'uid' => $_W['uid'],
        'filename' => $originname,
        'attachment' => $file['path'],
        'media_id' => $result['media_id'],
        'type' => $type,
        'model' => $mode,
        'createtime' => TIMESTAMP,
        'module_upload_dir' => $module_upload_dir,
        'group_id' => intval($_GPC['group_id']),
    );
    if ('image' == $type || 'thumb' == $type) {
        $size = getimagesize($fullname);
        $insert['width'] = $size[0];
        $insert['height'] = $size[1];
        if ('perm' == $mode) {
            $insert['tag'] = $content['url'];
        }
        if (!empty($insert['tag'])) {
            $insert['attachment'] = $content['url'];
        }
        $result['width'] = $size[0];
        $result['hieght'] = $size[1];
    }
    if ('video' == $type) {
        $insert['tag'] = iserializer(array('title' => $originname, 'url' => ''));
    }

    if (in_array($type, array('video', 'image')) && 'perm' == $mode) {
        if (!is_error($result)) {
            pdo_insert('wechat_attachment', $insert);
        }
    } else {
        pdo_insert('wechat_attachment', $insert);
    }

    $result['type'] = $type;
    $result['url'] = tomedia($file['path']);

    if ('image' == $type || 'thumb' == $type) {
        @unlink($fullname);
    }
    $result['mode'] = $mode;
    die(json_encode($result));
}

$type = safe_gpc_string($_GPC['type']);
$resourceid = intval($_GPC['resource_id']);
$uid = intval($_W['uid']);
$acid = intval($_W['acid']);
$url = safe_gpc_url($_GPC['url']);
$isnetwork_convert = !empty($url);
$islocal = 'local' == safe_gpc_string($_GPC['local']);

if ('keyword' == $do) {
    $keyword = safe_gpc_string(addslashes($_GPC['keyword']));
    $pindex = empty($_GPC['page']) ? 1 : max(1, intval($_GPC['page']));
    $psize = 24;
    $condition = array('uniacid' => $uniacid, 'status' => 1);
    if (!empty($keyword)) {
        $condition['content like'] = '%' . $keyword . '%';
    }

    $keyword_lists = pdo_getslice('rule_keyword', $condition, array($pindex, $psize), $total, array(), 'id');
    $result = array(
        'items' => $keyword_lists,
        'pager' => pagination($total, $pindex, $psize, '', array('before' => '2', 'after' => '3', 'ajaxcallback' => 'null', 'isajax' => 1)),
    );
    iajax(0, $result);
}
if ('module' == $do) {
    $enable_modules = array();
    $is_user_module = intval($_GPC['user_module']);
    $uid = empty($_GPC['uid']) || !is_numeric($_GPC['uid']) ? $_W['uid'] : intval($_GPC['uid']);
    $module_uniacid = empty($_GPC['module_uniacid']) || !is_numeric($_GPC['module_uniacid']) ? $_W['uniacid'] : intval($_GPC['module_uniacid']);
    $have_cover = 'true' == safe_gpc_string($_GPC['cover']) ? true : false;
    $account_all_type = uni_account_type();
    $module_type = in_array($_GPC['mtype'], array_keys(uni_account_type_sign())) ? safe_gpc_string($_GPC['mtype']) : '';
    if ($is_user_module) {
        $installedmodulelist = user_modules($uid);
    } else {
        $installedmodulelist = uni_modules_by_uniacid($module_uniacid);
    }

    foreach ($installedmodulelist as $k => $value) {
        if ('system' == $value['type']) {
            unset($installedmodulelist[$k]);
            continue;
        }

        $continue = false;
        foreach ($account_all_type as $account_type) {
            if ($module_type == $account_type['type_sign'] && $value[$account_type['module_support_name']] != $account_type['module_support_value']) {
                $continue = true;
                break;
            }
        }
        if ($continue) {
            unset($installedmodulelist[$k]);
            continue;
        }

        if ($have_cover) {
            $module_entries = module_entries($value['name'], array('cover'));
            if (empty($module_entries)) {
                unset($installedmodulelist[$k]);
                continue;
            }
        }

        $installedmodulelist[$k]['official'] = empty($value['issystem']) && (strexists($value['author'], 'WeEngine Team') || strexists($value['author'], '????????????'));
    }
    foreach ($installedmodulelist as $name => $module) {
        if ($module['issystem']) {
            $path = '/framework/builtin/' . $module['name'];
        } else {
            $path = '../addons/' . $module['name'];
        }
        $cion = $path . '/icon.jpg';
        if (!file_exists($cion)) {
            $cion = './resource/images/nopic-small.jpg';
        }
        $module['icon'] = $cion;
        $enable_modules[] = $module;
    }
    $result = array('items' => $enable_modules, 'pager' => '');
    iajax(0, $result);
}

if ('video' == $do || 'voice' == $do) {
    $server = $islocal ? MATERIAL_LOCAL : MATERIAL_WEXIN;
    $page_index = empty($_GPC['page']) ? 1 : max(1, intval($_GPC['page']));
    $page_size = 10;
    $keyword = safe_gpc_string($_GPC['keyword']);
    $order = safe_gpc_string($_GPC['order']);
    $conditions = array();
    if (!empty($keyword)) {
        $conditions['filename LIKE'] = "%$keyword%";
    }
    if (!empty($order)) {
        if (in_array($order, array('asc', 'desc'))) {
            $order = 'id ' . $order;
        }
        if (in_array($order, array('filename_asc', 'filename_desc'))) {
            $order = $order == 'filename_asc' ? 'asc' : 'desc';
            $order = 'filename ' . $order;
        }
    }
    $material_news_list = material_list($do, $server, array('page_index' => $page_index, 'page_size' => $page_size, 'conditions' => $conditions, 'order' => $order));
    $material_list = $material_news_list['material_list'];
    $pager = $material_news_list['page'];
    foreach ($material_list as &$item) {
        $item['url'] = tomedia($item['attachment']);
        unset($item['uid']);
    }
    $result = array(
        'list' => $material_list,
        'page' => $page_index,
        'page_size' => $page_size,
        'total' => $material_news_list['total'],
        'pager' => $pager,
        'items' => $material_list,
        'pager' => $pager,
    );
    iajax(0, $result);
}

if ('news' == $do) {
    $page_size = 24;
    $type = safe_gpc_string($_GPC['type']);
    $server = $islocal ? MATERIAL_LOCAL : MATERIAL_WEXIN;
    $page_index = empty($_GPC['page']) ? 1 : max(1, intval($_GPC['page']));
    $search = addslashes($_GPC['keyword']);
    $material_news_list = material_news_list($server, $search, array('page_index' => $page_index, 'page_size' => $page_size), $type);

    $material_list = array_values($material_news_list['material_list']);
    $pager = $material_news_list['page'];
    $result = array('items' => $material_list, 'pager' => $pager);
    iajax(0, $result);
}

if ('tolocal' == $do || 'towechat' == $do) {
    if (!in_array($type, array('news', 'image', 'video', 'voice'))) {
        iajax(1, '?????????????????????');
    }
}

if ('networktolocal' == $do) {
    $type = safe_gpc_string($_GPC['type']);
    $image_url = safe_gpc_url($_GPC['url'], false);
    if (!in_array($type, array('image', 'video'))) {
        $type = 'image';
    }

    $material = material_network_to_local($image_url, $uniacid, $uid, $type);
    if (is_error($material)) {
        iajax(1, $material['message']);
    }
    iajax(0, $material);
}

if ('tolocal' == $do) {
    if ('news' == $type) {
        $material = material_news_to_local($resourceid);
    } else {
        $material = material_to_local($resourceid, $uniacid, $uid, $type);
    }
    if (is_error($material)) {
        iajax(1, $material['message']);
    }
    iajax(0, $material);
}

if ('networktowechat' == $do) {
    $type = safe_gpc_string($_GPC['type']);
    if (!in_array($type, array('image', 'video'))) {
        $type = 'image';
    }
    $url_host = parse_url($url, PHP_URL_HOST);
    $is_ip = preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $url_host);
    if ($is_ip) {
        iajax(1, '?????????????????????IP?????????');
    }
    $material = material_network_to_wechat($url, $uniacid, $uid, $acid, $type);
    if (is_error($material)) {
        iajax(1, $material['message']);
    }
    iajax(0, $material);
}

$is_local_image = ($islocal ? true : false);

if ('add_group' == $do) {
    $table = table('core_attachment_group');
    $fields = array(
        'uid' => $_W['uid'],
        'uniacid' => $uniacid,
        'name' => safe_gpc_string($_GPC['name']),
        'type' => $is_local_image ? 0 : 1,
    );
    if (!empty($_GPC['pid'])) {
        $fields['pid'] = safe_gpc_int($_GPC['pid']);
    }
    $table->fill($fields);
    $result = $table->save();
    if (is_error($result)) {
        iajax($result['errno'], $result['message']);
    }
    iajax(0, array('id' => pdo_insertid()));
}

if ('change_group' == $do) {
    $table = table('core_attachment_group');
    $type = $is_local_image ? 0 : 1;
    $name = safe_gpc_string($_GPC['name']);
    $id = intval($_GPC['id']);
    $table->searchWithUniacidOrUid($uniacid, $_W['uid']);
    $updated = $table->where('type', $type)
        ->fill('name', $name)
        ->where('id', $id)->save();
    iajax($updated ? 0 : 1, $updated ? '????????????' : '????????????');
}

if ('del_group' == $do) {
    $table = table('core_attachment_group');
    $type = $is_local_image ? 0 : 1;
    $id = intval($_GPC['group_id']);
    $table->searchWithUniacidOrUid($uniacid, $_W['uid']);
    $deleted = $table->where('type', $type)->where('id', $id)->delete();
    iajax($deleted ? 0 : 1, $deleted ? '????????????' : '????????????');
}

if ('move_to_group' == $do) {
    $group_id = intval($_GPC['group_id']);
    $ids = safe_gpc_array($_GPC['id']);

    if ($is_local_image) {
        $table = table('core_attachment');
    } else {
        $table = table('wechat_attachment');
    }
    $updated = $table->where('id', $ids)->where('uniacid', $uniacid)->fill('group_id', $group_id)->save();

    iajax($updated ? 0 : -1, $updated ? '????????????' : '????????????');
}

if ('image' == $do) {
    $year = intval($_GPC['year']);
    $month = intval($_GPC['month']);
    $page = empty($_GPC['page']) ? 1 : max(1, intval($_GPC['page']));
    $groupid = safe_gpc_int($_GPC['group_id']);
    $keyword = safe_gpc_string($_GPC['keyword']);
    $order = safe_gpc_string($_GPC['order']);
    $page_size = 15;
    if ($islocal) {
        $attachment_table = table('core_attachment');
    } else {
        $attachment_table = table('wechat_attachment');
    }
    $attachment_table->searchWithUniacid($uniacid);
    $attachment_table->searchWithUploadDir($module_upload_dir);

    if (empty($uniacid)) {
        $attachment_table->searchWithUid($_W['uid']);
    }
    if ($groupid > 0) {
        $attachment_table->searchWithGroupId($groupid);
    }

    if (0 == $groupid) {
        $attachment_table->searchWithGroupId(-1);
    }

    if ($year || $month) {
        $start_time = strtotime("{$year}-{$month}-01");
        $end_time = strtotime('+1 month', $start_time);
        $attachment_table->searchWithTime($start_time, $end_time);
    }
    if ($islocal) {
        $attachment_table->searchWithType(ATTACH_TYPE_IMAGE);
    } else {
        $attachment_table->searchWithType(ATTACHMENT_IMAGE);
    }

    if (!empty($keyword)) {
        $attachment_table->where('filename LIKE', "%$keyword%");
    }

    if (!empty($order)) {
        if (in_array($order, array('asc', 'desc'))) {
            $attachment_table->orderby('id', $order);
        }
        if (in_array($order, array('filename_asc', 'filename_desc'))) {
            $order = $order == 'filename_asc' ? 'asc' : 'desc';
            $attachment_table->orderby('filename', $order);
        }
    }

    $attachment_table->searchWithPage($page, $page_size);

    $list = $attachment_table->getall();
    $total = $attachment_table->getLastQueryTotal();
    if (!empty($list)) {
        foreach ($list as &$meterial) {
            if ($islocal) {
                if ($uniacid == 0) {
                    $meterial['url'] = to_global_media($meterial['attachment']);
                } else {
                    $meterial['url'] = tomedia($meterial['attachment']);
                }
                unset($meterial['uid']);
            } else {
                if (!empty($_W['setting']['remote']['type'])) {
                    $meterial['attach'] = tomedia($meterial['attachment']);
                } else {
                    $meterial['attach'] = tomedia($meterial['attachment'], true);
                }
                $meterial['url'] = $meterial['attach'];
            }
        }
    }

    $pager = pagination($total, $page, $page_size, '', $context = array('before' => 5, 'after' => 4, 'isajax' => $_W['isajax']));
    //pager???items?????????angular?????????????????????list??????????????????????????????????????????????????????????????????????????????????????????
    $result = array(
        'list' => $list,
        'total' => $total,
        'page' => $page,
        'page_size' => $page_size,
        'pager' => $pager,
        'items' => $list,
    );
    iajax(0, $result);
}

/*
 *  ??????????????????
 */
if ('group_list' == $do) {
    $query = table('core_attachment_group')->where('type', $is_local_image ? 0 : 1);
    $list = attachment_recursion_group($query->getall());
    iajax(0, $list);
}
