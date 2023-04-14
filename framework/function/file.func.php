<?php
/**
 * 文件操作.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 将数据写入文件.
 *
 * @param string $filename
 *                         文件名称
 * @param string $data
 *                         写入数据
 *
 * @return bool
 */
function file_write($filename, $data) {
    global $_W;
    $filename = ATTACHMENT_ROOT . '/' . $filename;
    mkdirs(dirname($filename));
    file_put_contents($filename, $data);
    @chmod($filename, $_W['config']['setting']['filemode']);

    return is_file($filename);
}
function file_read($filename) {
    $filename = ATTACHMENT_ROOT . '/' . $filename;
    if (!is_file($filename)) {
        return false;
    }

    return file_get_contents($filename);
}
/**
 * 将文件移动至目标位置.
 *
 * @param string $filename
 *                         移动的文件
 * @param string $dest
 *                         移动的目标位置
 *
 * @return bool
 */
function file_move($filename, $dest) {
    global $_W;
    mkdirs(dirname($dest));
    if (is_uploaded_file($filename)) {
        move_uploaded_file($filename, $dest);
    } else {
        rename($filename, $dest);
    }
    @chmod($filename, $_W['config']['setting']['filemode']);

    return is_file($dest);
}

/**
 * 获取指定目录下所有文件路径.
 *
 * @param string $path    文件夹目录
 * @param array  $include 指定获取子目录
 *
 * @return array
 */
function file_tree($path, $include = array()) {
    $files = array();
    if (!empty($include)) {
        $ds = glob($path . '/{' . implode(',', $include) . '}', GLOB_BRACE);
    } else {
        $ds = glob($path . '/*');
    }
    if (is_array($ds)) {
        foreach ($ds as $entry) {
            if (is_file($entry)) {
                $files[] = $entry;
            }
            if (is_dir($entry)) {
                $rs = file_tree($entry);
                foreach ($rs as $f) {
                    $files[] = $f;
                }
            }
        }
    }

    return $files;
}

/**
 * 获取指定目录下一定数量文件的文件路径.
 *
 * @param string $path       文件夹目录
 * @param array  $limit      获取文件数量
 * @param array  $file_count 已获取文件数量
 *
 * @return array
 */
function file_tree_limit($path, $limit = 0, $acquired_files_count = 0) {
    $files = array();
    if (is_dir($path)) {
        if ($dir = opendir($path)) {
            while (false !== ($file = readdir($dir))) {
                if (in_array($file, array('.', '..'))) {
                    continue;
                }
                if (is_file($path . '/' . $file)) {
                    $files[] = $path . '/' . $file;
                    ++$acquired_files_count;
                    if ($limit > 0 && $acquired_files_count >= $limit) {
                        closedir($dir);

                        return $files;
                    }
                }
                if (is_dir($path . '/' . $file)) {
                    $rs = file_tree_limit($path . '/' . $file, $limit, $acquired_files_count);
                    foreach ($rs as $f) {
                        $files[] = $f;
                        ++$acquired_files_count;
                        if ($limit > 0 && $acquired_files_count >= $limit) {
                            closedir($dir);

                            return $files;
                        }
                    }
                }
            }
            closedir($dir);
        }
    }

    return $files;
}

/**
 * 判断指定目录下是否存在图片.
 *
 * @param string $path 文件夹目录
 *
 * @return boolean
 */
function file_dir_exist_image($path) {
    if (is_dir($path)) {
        if ($dir = opendir($path)) {
            while (false !== ($file = readdir($dir))) {
                if (in_array($file, array('.', '..'))) {
                    continue;
                }
                if (is_file($path . '/' . $file) && $path != (ATTACHMENT_ROOT . 'images') && file_is_image($path . '/' . $file)) {
                    if (0 === strpos($path, ATTACHMENT_ROOT)) {
                        $attachment = str_replace(ATTACHMENT_ROOT . 'images/', '', $path . '/' . $file);
                        list($file_account) = explode('/', $attachment);
                        if ('global' == $file_account) {
                            continue;
                        }
                    }
                    closedir($dir);

                    return true;
                }
                if (is_dir($path . '/' . $file) && file_dir_exist_image($path . '/' . $file)) {
                    closedir($dir);

                    return true;
                }
            }
            closedir($dir);
        }
    }

    return false;
}

/**
 * 递归创建目录.
 *
 * @param string $path
 *                     目录
 *
 * @return bool
 */
function mkdirs($path) {
    if (!is_dir($path)) {
        mkdirs(dirname($path));
        mkdir($path);
    }

    return is_dir($path);
}

/**
 * 复制指定目录下所有文件到新目录.
 *
 * @param string $src
 *                       原始文件夹
 * @param string $des
 *                       目标文件夹
 * @param array  $filter
 *                       需要过滤的文件类型
 */
function file_copy($src, $des, $filter) {
    $dir = opendir($src);
    @mkdir($des);
    while (false !== ($file = readdir($dir))) {
        if (('.' != $file) && ('..' != $file)) {
            if (is_dir($src . '/' . $file)) {
                file_copy($src . '/' . $file, $des . '/' . $file, $filter);
            } elseif (!in_array(substr($file, strrpos($file, '.') + 1), $filter)) {
                copy($src . '/' . $file, $des . '/' . $file);
            }
        }
    }
    closedir($dir);
}

/**
 * 删除目录.
 *
 * @param string $path
 *                      目录位置
 * @param bool   $clean
 *                      true: 不删除目录，仅删除目录内文件; false: 整个目录全部删除
 *
 * @return bool
 */
function rmdirs($path, $clean = false) {
    if (!is_dir($path)) {
        return false;
    }
    $files = glob($path . '/*');
    if ($files) {
        foreach ($files as $file) {
            is_dir($file) ? rmdirs($file) : @unlink($file);
        }
    }

    return $clean ? true : @rmdir($path);
}

/**
 * 上传文件到附件目录.
 *
 * @param array  $file
 *                         上传的文件信息
 * @param string $type
 *                         文件保存类型
 * @param string $name
 *                         保存的文件名,不含后缀.(未指定则自动生成文件名，指定则是从附件目录开始的完整相对路径)
 * @param string $compress 是否压缩
 *
 * @return array 错误信息 error 或 array('success' => bool，'path' => 保存路径（从附件目录开始的完整相对路径）)
 */
function file_upload($file, $type = 'image', $name = '', $compress = false) {
    load()->model('system');
    $harmtype = array('asp', 'php', 'jsp', 'js', 'css', 'php3', 'php4', 'php5', 'ashx', 'aspx', 'exe', 'cgi');
    if (empty($file)) {
        return error(-1, '没有上传内容');
    }
    if (!in_array($type, array('image', 'thumb', 'voice', 'video', 'audio'))) {
        return error(-2, '未知的上传类型');
    }
    global $_W;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    $setting = setting_load('upload');
    $setting['upload'] = !empty($setting['upload']) ? $setting['upload'] : $_W['setting']['upload'];
    switch ($type) {
        case 'image':
        case 'thumb':
            $allowExt = array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'ico');
            $limit = $setting['upload']['image']['limit'] ?? '5000';
            break;
        case 'voice':
        case 'audio':
            $allowExt = array('mp3', 'wma', 'wav', 'amr');
            $limit = $setting['upload']['audio']['limit'] ?? '5000';
            break;
        case 'video':
            $allowExt = array('rm', 'rmvb', 'wmv', 'avi', 'mpg', 'mpeg', 'mp4');
            $limit = $setting['upload']['audio']['limit'] ?? '5000';
            break;
    }
    $type_setting = in_array($type, array('image', 'thumb')) ? 'image' : 'audio';
    $setting = $_W['setting']['upload'][$type_setting];
    if (!empty($setting['extentions'])) {
        $allowExt = $setting['extentions'];
    }
    if (!in_array(strtolower($ext), $allowExt) || in_array(strtolower($ext), $harmtype)) {
        return error(-3, '不允许上传此类文件');
    }
    if (!empty($limit) && $limit * 1024 < filesize($file['tmp_name'])) {
        return error(-4, "上传的文件超过大小限制，请上传小于 {$limit}k 的文件");
    }

    $result = array();
    if (empty($name) || 'auto' == $name) {
        $uniacid = intval($_W['uniacid']);
        $path = "{$type}s/{$uniacid}/" . date('Y/m/');
        mkdirs(ATTACHMENT_ROOT . '/' . $path);
        $filename = file_random_name(ATTACHMENT_ROOT . '/' . $path, $ext);

        $result['path'] = $path . $filename;
    } else {
        mkdirs(dirname(ATTACHMENT_ROOT . '/' . $name));
        if (!strexists($name, $ext)) {
            $name .= '.' . $ext;
        }
        $result['path'] = $name;
    }

    $save_path = ATTACHMENT_ROOT . '/' . $result['path'];

    //处理ios设备图片显示旋转
    $image = '';
    if (isset($setting['zip_percentage']) && $setting['zip_percentage'] == 100 && system_check_php_ext('exif')) {
        $exif = exif_read_data($file['tmp_name']);
        if (!empty($exif['THUMBNAIL']['Orientation'])) {
            $image = imagecreatefromstring(file_get_contents($file['tmp_name']));
            switch ($exif['THUMBNAIL']['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
                default:
                    $image = imagerotate($image, 0, 0);
                    break;
            }
        }
    }
    if (empty($image)) {
        $newimage = file_move($file['tmp_name'], $save_path);
    } else {
        $newimage = imagejpeg($image, $save_path);
        imagedestroy($image);
    }
    if (empty($newimage)) {
        return error(-1, '文件上传失败, 请将 attachment 目录权限先777 <br> (如果777上传失败,可尝试将目录设置为755)');
    }

    if ('image' == $type && $compress) {
        //设置清晰度
        file_image_quality($save_path, $save_path, $ext);
    }

    $result['success'] = true;

    return $result;
}
function file_wechat_upload($file, $type = 'image', $name = '') {
    $harmtype = array('asp', 'php', 'jsp', 'js', 'css', 'php3', 'php4', 'php5', 'ashx', 'aspx', 'exe', 'cgi');
    if (empty($file)) {
        return error(-1, '没有上传内容');
    }
    if (!in_array($type, array('image', 'thumb', 'voice', 'video', 'audio'))) {
        return error(-2, '未知的上传类型');
    }

    global $_W;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    if (in_array(strtolower($ext), $harmtype)) {
        return error(-3, '不允许上传此类文件');
    }

    $result = array();
    if (empty($name) || 'auto' == $name) {
        $uniacid = intval($_W['uniacid']);
        $path = "{$type}s/{$uniacid}/" . date('Y/m/');
        mkdirs(ATTACHMENT_ROOT . '/' . $path);
        $filename = file_random_name(ATTACHMENT_ROOT . '/' . $path, $ext);
        $result['path'] = $path . $filename;
    } else {
        mkdirs(dirname(ATTACHMENT_ROOT . '/' . $name));
        if (!strexists($name, $ext)) {
            $name .= '.' . $ext;
        }
        $result['path'] = $name;
    }
    $save_path = ATTACHMENT_ROOT . '/' . $result['path'];
    if (!file_move($file['tmp_name'], $save_path)) {
        return error(-1, '保存上传文件失败');
    }

    if ('image' == $type) {
        //设置清晰度
        file_image_quality($save_path, $save_path, $ext);
    }
    $result['success'] = true;

    return $result;
}

/**
 * 上传图片到远程服务器，需要外部自行处理成功和失败时删除原图的操作.
 * @param string $filename 图片的相对路径从attachment开始
 * @param bool $auto_delete_local 是否自动删除本地文件
 * @return bool|error
 */
function file_remote_upload($filename, $auto_delete_local = true) {
    global $_W;
    if (empty($_W['setting']['remote']['type'])) {
        return false;
    }
    if ($_W['setting']['remote']['type'] == ATTACH_OSS) {
        load()->library('oss');
        load()->model('attachment');
        $buckets = attachment_alioss_buctkets($_W['setting']['remote']['alioss']['key'], $_W['setting']['remote']['alioss']['secret']);
        $host_name = $_W['setting']['remote']['alioss']['internal'] ? '-internal.aliyuncs.com' : '.aliyuncs.com';
        $endpoint = 'http://' . $buckets[$_W['setting']['remote']['alioss']['bucket']]['location'] . $host_name;
        try {
            $ossClient = new \OSS\OssClient($_W['setting']['remote']['alioss']['key'], $_W['setting']['remote']['alioss']['secret'], $endpoint);
            $ossClient->uploadFile($_W['setting']['remote']['alioss']['bucket'], $filename, ATTACHMENT_ROOT . $filename);
        } catch (\OSS\Core\OssException $e) {
            return error(1, $e->getMessage());
        }
        if ($auto_delete_local) {
            file_delete($filename);
        }
    } elseif ($_W['setting']['remote']['type'] == ATTACH_QINIU) {
        load()->library('qiniu');
        $auth = new Qiniu\Auth($_W['setting']['remote']['qiniu']['accesskey'], $_W['setting']['remote']['qiniu']['secretkey']);
        $config = new Qiniu\Config();
        $uploadmgr = new Qiniu\Storage\UploadManager($config);
        // 构造上传策略，覆盖已有文件
        $putpolicy = Qiniu\base64_urlSafeEncode(json_encode(array(
            'scope' => $_W['setting']['remote']['qiniu']['bucket'] . ':' . $filename,
        )));
        $uploadtoken = $auth->uploadToken($_W['setting']['remote']['qiniu']['bucket'], $filename, 3600, $putpolicy);
        list($ret, $err) = $uploadmgr->putFile($uploadtoken, $filename, ATTACHMENT_ROOT . '/' . $filename);
        if ($auto_delete_local) {
            file_delete($filename);
        }
        if (null !== $err) {
            return error(1, '远程附件上传失败，请检查配置并重新上传');
        } else {
            return true;
        }
    } elseif ($_W['setting']['remote']['type'] == ATTACH_COS) {
        load()->library('cosv5');
        try {
            $bucket = $_W['setting']['remote']['cos']['bucket'] . '-' . $_W['setting']['remote']['cos']['appid'];
            $cosClient = new Qcloud\Cos\Client(
                array(
                    'region' => $_W['setting']['remote']['cos']['local'],
                    'credentials' => array(
                        'secretId' => $_W['setting']['remote']['cos']['secretid'],
                        'secretKey' => $_W['setting']['remote']['cos']['secretkey']))
            );
            $cosClient->Upload($bucket, $filename, fopen(ATTACHMENT_ROOT . $filename, 'rb'));
            if ($auto_delete_local) {
                file_delete($filename);
            }
        } catch (\Exception $e) {
            return error(1, $e->getMessage());
        }
    }
    return true;
}

/**
 * 上传目录下的图片到远程服务器并删除本地图片.
 *
 * @param string $dir_path 目录路径
 * @param string $limit    上传数量限制
 *
 * @return true|error
 */
function file_dir_remote_upload($dir_path, $limit = 200) {
    global $_W;
    if (empty($_W['setting']['remote']['type'])) {
        return error(1, '未开启远程附件');
    }
    $dir_path = safe_gpc_path($dir_path);
    if (!empty($dir_path)) {
        $local_attachment = file_tree_limit($dir_path, $limit);
    } else {
        $local_attachment = array();
    }
    if (is_array($local_attachment) && !empty($local_attachment)) {
        foreach ($local_attachment as $attachment) {
            $filename = str_replace(ATTACHMENT_ROOT, '', $attachment);
            list($image_dir, $file_account) = explode('/', $filename);
            if ('global' == $file_account || !file_is_image($attachment)) {
                continue;
            }
            $result = file_remote_upload($filename);
            if (is_error($result)) {
                return $result;
            }
        }
    }

    return true;
}

/**
 * 获取指定某目录下指定后缀的随机文件名.
 *
 * @param string $dir
 *                    目录的绝对路径
 * @param string $ext
 *                    文件后缀名
 *
 * @return string 随机文件名称
 */
function file_random_name($dir, $ext) {
    do {
        $filename = random(30) . '.' . $ext;
    } while (file_exists($dir . $filename));

    return $filename;
}

/**
 * 删除系统附件.
 *
 * @param string $file
 *
 * @return bool
 */
function file_delete($file) {
    if (empty($file)) {
        return false;
    }
    $file = safe_gpc_path($file);
    $file_extension = pathinfo($file, PATHINFO_EXTENSION);
    if (in_array($file_extension, array('php', 'html', 'js', 'css', 'ttf', 'otf', 'eot', 'svg', 'woff'))) {
        return false;
    }

    if (file_exists($file)) {
        @unlink($file);
    }
    if (file_exists(ATTACHMENT_ROOT . '/' . $file)) {
        @unlink(ATTACHMENT_ROOT . '/' . $file);
    }

    return true;
}
function file_remote_delete($file) {
    global $_W;
    $result = true;
    if (empty($file)) {
        return $result;
    }
    if (!empty($_W['setting']['remote']['type'])) {
        switch ($_W['setting']['remote']['type']) {
            case ATTACH_OSS:
                load()->model('attachment');
                load()->library('oss');
                $buckets = attachment_alioss_buctkets($_W['setting']['remote']['alioss']['key'], $_W['setting']['remote']['alioss']['secret']);
                $endpoint = 'http://' . $buckets[$_W['setting']['remote']['alioss']['bucket']]['location'] . '.aliyuncs.com';
                try {
                    $ossClient = new \OSS\OssClient($_W['setting']['remote']['alioss']['key'], $_W['setting']['remote']['alioss']['secret'], $endpoint);
                    $ossClient->deleteObject($_W['setting']['remote']['alioss']['bucket'], $file);
                } catch (\OSS\Core\OssException $e) {
                    $result = error(1, '删除oss远程文件失败');
                }
                break;
            case ATTACH_QINIU:
                load()->library('qiniu');
                $auth = new Qiniu\Auth($_W['setting']['remote']['qiniu']['accesskey'], $_W['setting']['remote']['qiniu']['secretkey']);
                $bucketMgr = new Qiniu\Storage\BucketManager($auth);
                $error = $bucketMgr->delete($_W['setting']['remote']['qiniu']['bucket'], $file);
                if ($error instanceof Qiniu\Http\Error) {
                    if (612 != $error->code()) {
                        $result = error(1, '删除七牛远程文件失败');
                    }
                }
                break;
            case ATTACH_COS:
                load()->library('cosv5');
                try {
                    $key = $file;
                    $bucket = $_W['setting']['remote']['cos']['bucket'] . '-' . $_W['setting']['remote']['cos']['appid'];
                    $cosClient = new Qcloud\Cos\Client(
                        array(
                            'region' => $_W['setting']['remote']['cos']['local'],
                            'credentials' => array(
                                'secretId' => $_W['setting']['remote']['cos']['secretid'],
                                'secretKey' => $_W['setting']['remote']['cos']['secretkey']))
                    );
                    $cosClient->deleteObjects(array(
                        'Bucket' => $bucket,
                        'Objects' => array(array('Key' => $key))
                    ));
                } catch (\Exception $e) {
                    $result = error(1, '删除cos远程文件失败');
                }
                break;
        }
    }
    return $result;
}

/**
 * 图像缩略处理
 * 可处理图像类型jpg和png
 * 如果原图像宽度小于指定宽度, 直接复制到目标地址
 * 如果原图像宽度大于指定宽度, 按比例缩放至指定宽度后保存至目标地址
 *
 * @param string $srcfile
 *                        原图像地址
 * @param string $desfile
 *                        新图像地址
 * @param int    $width
 *                        缩放宽度
 *
 * @return mixed string:缩略图地址; error:调用缩略方法失败;
 */
function file_image_thumb($srcfile, $desfile = '', $width = 0) {
    global $_W;
    load()->classs('image');
    if (0 == intval($width)) {
        load()->model('setting');
        $width = intval($_W['setting']['upload']['image']['width']);
    }
    if (empty($desfile)) {
        $ext = pathinfo($srcfile, PATHINFO_EXTENSION);
        $srcdir = dirname($srcfile);
        do {
            $desfile = $srcdir . '/' . random(30) . ".{$ext}";
        } while (file_exists($desfile));
    }

    $des = dirname($desfile);
    // 创建存放目录
    if (!file_exists($des)) {
        if (!mkdirs($des)) {
            return error('-1', '创建目录失败');
        }
    } elseif (!is_writable($des)) {
        return error('-1', '目录无法写入');
    }
    // 缩略宽度 大于图片本身宽度 不进行缩略
    $org_info = @getimagesize($srcfile);
    if ($org_info) {
        if (0 == $width || $width > $org_info[0]) {
            copy($srcfile, $desfile);

            return str_replace(ATTACHMENT_ROOT . '/', '', $desfile);
        }
    }
    // 源图像的宽高比
    $scale_org = $org_info[0] / $org_info[1];
    // 缩放后的高
    $height = $width / $scale_org;
    $desfile = Image::create($srcfile)->resize($width, $height)->saveTo($desfile);
    if (!$desfile) {
        return false;
    }

    return str_replace(ATTACHMENT_ROOT . '/', '', $desfile);
}

/**
 * 图像裁切处理
 * 可处理图像类型jpg和png
 * 如果原图像宽度小于指定宽度(高度), 不处理宽度(高度)
 * 如果原图像宽度大于指定宽度(高度), 则按照裁剪位置裁切指定宽度(高度)
 * 将裁切成功的图像保存至目标地址
 *
 * @param string $src
 *                         原图像地址
 * @param string $desfile
 *                         新图像地址
 * @param int    $width
 *                         要裁切的宽度
 * @param int    $height
 *                         要裁切的高度
 * @param int    $position
 *                         开始裁切的位置, 按照九宫格1-9指定位置
 *
 * @return bool|array 指示裁切成功或裁切失败原因
 */
function file_image_crop($src, $desfile, $width = 400, $height = 300, $position = 1) {
    load()->classs('image');
    $des = dirname($desfile);
    // 创建存放目录
    if (!file_exists($des)) {
        if (!mkdirs($des)) {
            return error('-1', '创建目录失败');
        }
    } elseif (!is_writable($des)) {
        return error('-1', '目录无法写入');
    }

    return Image::create($src)
        ->crop($width, $height, $position)
        ->saveTo($desfile);
}

/**
 * 文件扫描.
 *
 * @param string $filepath
 *                            目录名称
 * @param int    $subdir
 *                            是否搜索子目录
 * @param string $ex
 *                            搜索扩展
 * @param int    $isdir
 *                            是否只搜索目录
 * @param int    $md5
 *                            是否生成MD5验证码
 * @param int    $enforcement
 *
 * @return array
 */
function file_lists($filepath, $subdir = 1, $ex = '', $isdir = 0, $md5 = 0, $enforcement = 0) {
    static $file_list = array();
    if ($enforcement) {
        $file_list = array();
    }
    $flags = $isdir ? GLOB_ONLYDIR : 0;
    $list = glob($filepath . '*' . (!empty($ex) && empty($subdir) ? '.' . $ex : ''), $flags);
    if (!empty($ex)) {
        $ex_num = strlen($ex);
    }
    foreach ($list as $k => $v) {
        $v = str_replace('\\', '/', $v);
        $v1 = str_replace(IA_ROOT . '/', '', $v);
        if ($subdir && is_dir($v)) {
            file_lists($v . '/', $subdir, $ex, $isdir, $md5);
            continue;
        }
        if (!empty($ex) && strtolower(substr($v, -$ex_num, $ex_num)) == $ex) {
            if ($md5) {
                $file_list[$v1] = md5_file($v);
            } else {
                $file_list[] = $v1;
            }
            continue;
        } elseif (!empty($ex) && strtolower(substr($v, -$ex_num, $ex_num)) != $ex) {
            unset($list[$k]);
            continue;
        }
    }

    return $file_list;
}

/**
 * 获取远程素材.
 *
 * @param string $url
 *                      文件地址
 * @param int    $limit
 *                      文件大小限制（单位：KB）。默认为：系统的图片大小设置
 * @param string $path
 *                      文件保存路径。默认为：系统附件目录 "images/{$uniacid}/Y/m/文件名";
 *
 * @return string 文件path
 */
function file_remote_attach_fetch($url, $limit = 0, $path = '') {
    global $_W;
    $url = trim($url);
    if (empty($url)) {
        return error(-1, '文件地址不存在');
    }
    $get_headers = file_media_content_type($url);
    if (empty($get_headers)) {
        return error(-1, '提取资源失败, 资源文件类型错误.');
    } else {
        $ext = $get_headers['ext'];
        $type = $get_headers['type'];
    }

    if (empty($path)) {
        $path = $type . "/{$_W['uniacid']}/" . date('Y/m/');
    } else {
        $path = parse_path($path);
    }
    if (!$path) {
        return error(-1, '提取文件失败: 上传路径配置有误.');
    }

    if (!is_dir(ATTACHMENT_ROOT . $path)) {
        if (!mkdirs(ATTACHMENT_ROOT . $path, 0700, true)) {
            return error(-1, '提取文件失败: 权限不足.');
        }
    }
    
    $resp = ihttp_get($url);
    if (is_error($resp)) {
        return error(-1, '提取文件失败, 错误信息: ' . $resp['message']);
    }
    if (200 != intval($resp['code'])) {
        return error(-1, '提取文件失败: 未找到该资源文件.');
    }
    /* 文件大小过滤 */
    if (!$limit) {
        if ('images' == $type) {
            $limit = $_W['setting']['upload']['image']['limit'] * 1024;
        } else {
            $limit = $_W['setting']['upload']['audio']['limit'] * 1024;
        }
    } else {
        $limit = $limit * 1024;
    }
    if (intval($resp['headers']['Content-Length']) > $limit) {
        return error(-1, '上传的媒体文件过大(' . sizecount($resp['headers']['Content-Length']) . ' > ' . sizecount($limit));
    }
    $filename = file_random_name(ATTACHMENT_ROOT . $path, $ext);
    $pathname = $path . $filename;
    $fullname = ATTACHMENT_ROOT . $pathname;
    if (false == file_put_contents($fullname, $resp['content'])) {
        return error(-1, '提取失败.');
    }

    return $pathname;
}

/**
 * 获取素材类型及扩展名.
 *
 * @param $url
 *
 * @return array|bool
 */
function file_media_content_type($url) {
    $file_header = iget_headers($url, 1);
    if (empty($url) || !is_array($file_header)) {
        return false;
    }
    switch ($file_header['Content-Type']) {
        case 'application/x-jpg':
        case 'image/jpg':
        case 'image/jpeg':
            $ext = 'jpg';
            $type = 'images';
            break;
        case 'image/png':
            $ext = 'png';
            $type = 'images';
            break;
        case 'image/gif':
            $ext = 'gif';
            $type = 'images';
            break;
        case 'video/mp4':
        case 'video/mpeg4':
            $ext = 'mp4';
            $type = 'videos';
            break;
        case 'video/x-ms-wmv':
            $ext = 'wmv';
            $type = 'videos';
            break;
        case 'audio/mpeg':
            $ext = 'mp3';
            $type = 'audios';
            break;
        case 'audio/mp4':
            $ext = 'mp4';
            $type = 'audios';
            break;
        case 'audio/x-ms-wma':
            $ext = 'wma';
            $type = 'audios';
            break;
        default:
            return false;
            break;
    }

    return array('ext' => $ext, 'type' => $type);
}

/**
 * 获取系统支持的素材类型.
 *
 * @param $type 图片和音视频
 *
 * @return array
 */
function file_allowed_media($type) {
    global $_W;
    if (!in_array($type, array('image', 'audio'))) {
        return array();
    }
    if (empty($_W['setting']['upload'][$type]['extention']) || !is_array($_W['setting']['upload'][$type]['extention'])) {
        return $_W['config']['upload'][$type]['extentions'];
    }

    return $_W['setting']['upload'][$type]['extention'];
}

function file_is_image($url) {
    global $_W;
    $allowed_media = file_allowed_media('image');

    if ('//' == substr($url, 0, 2)) {
        $url = 'http:' . $url;
    }
    //对于本地图片先转换绝对路径，因为部分客户服务器配置问题，网络图片访问不了（这块兼容一下）
    if (0 == strpos($url, $_W['siteroot'] . 'attachment/')) {
        $url = str_replace($_W['siteroot'] . 'attachment/', ATTACHMENT_ROOT, $url);
    }
    $lower_url = strtolower($url);
    if (('http://' == substr($lower_url, 0, 7)) || ('https://' == substr($lower_url, 0, 8))) {
        $analysis_url = parse_url($lower_url);
        $preg_str = '/.*(\.' . implode('|\.', $allowed_media) . ')$/';
        if (!empty($analysis_url['query']) || !preg_match($preg_str, $lower_url) || !preg_match($preg_str, $analysis_url['path'])) {
            return false;
        }
        $img_headers = file_media_content_type($url);
        if (empty($img_headers) || !in_array($img_headers['ext'], $allowed_media)) {
            return false;
        }
    }

    $info = igetimagesize($url);
    if (!is_array($info)) {
        return false;
    }

    return true;
}

/**
 * 清晰度设置 如果是上传文件的话 就直接把临时目录覆盖为新的.
 *
 * @param $src //原始目录
 * @param $to_path //目标目录
 * @param int $quality //清晰度
 * @param $ext //图片类型
 *
 * @since version
 */
function file_image_quality($src, $to_path, $ext) {
    load()->classs('image');
    global $_W;
    //不压缩
    if ('gif' == strtolower($ext)) {
        return;
    }
    $quality = intval($_W['setting']['upload']['image']['zip_percentage']);
    if ($quality <= 0 || $quality >= 100) {
        return;
    }

    //	//大于5M不压缩
    if (filesize($src) / 1024 > 5120) {
        return;
    }

    $result = Image::create($src, $ext)->saveTo($to_path, $quality);

    return $result;
}
