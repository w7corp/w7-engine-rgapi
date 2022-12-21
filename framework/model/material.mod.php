<?php
defined('IN_IA') or exit('Access Denied');
load()->func('file');

/**
 * 生成本地图文素材
 * @param array $post_data
 * @return int 本地图文素材ID
 */
function material_news_set($data, $attach_id, $type = 'news') {
    global $_W;
    $attach_id = intval($attach_id);
    foreach ($data as $key => $news) {
        if (empty($news['title']) ||
            (!empty($news['thumb']) && !parse_path($news['thumb'])) ||
            (!empty($news['url']) && !parse_path($news['url'])) ||
            (!empty($news['content_source_url']) && !parse_path($news['content_source_url']))
        ) {
            return error('-1', '参数有误');
        }
        if (!material_url_check($news['content_source_url']) ||
            (!empty($news['thumb']) && !material_url_check($news['thumb'])) ||
            (!empty($news['url']) && !material_url_check($news['url']))
        ) {
            return error('-3', '提交链接参数不合法');
        }
        if (empty($news['digest']) && !empty($news['content'])) {
            $content = str_replace(array('<br>', '&nbsp;'), array("\n", ' '), ihtml_entity_decode($news['content']));
            $content = strip_tags($content, '<a>');
            $news['digest'] = empty($content) ? '' : cutstr($content, 54);
        }
        $post_news[] = array(
            'id' => intval($news['id']),
            'uniacid' => $_W['uniacid'],
            'thumb_url' => $news['thumb'],
            'title' => addslashes($news['title']),
            'author' => addslashes($news['author']),
            'digest' => addslashes($news['digest']),
            'content' => safe_gpc_html(htmlspecialchars_decode($news['content'], ENT_QUOTES)),
            'url' => empty($news['url']) ? '' : $news['url'],
            'show_cover_pic' => intval($news['show_cover_pic']),
            'displayorder' => intval($key),
            'thumb_media_id' => empty($news['media_id']) ? '' : addslashes($news['media_id']),
            'content_source_url' => $news['content_source_url'],
        );
    }
    if (!empty($attach_id)) {
        $wechat_attachment = pdo_get('wechat_attachment', array(
            'id' => $attach_id,
            'uniacid' => $_W['uniacid']
        ));
        if (empty($wechat_attachment)) {
            return error('-2', '编辑素材不存在');
        }
        $wechat_attachment['model'] = 'local';
        pdo_update('wechat_attachment', $wechat_attachment, array('id' => $attach_id, 'uniacid' => $_W['uniacid']));
        pdo_delete('wechat_news', array('attach_id' => $attach_id, 'uniacid' => $_W['uniacid']));
        foreach ($post_news as $id => $news) {
            $news['attach_id'] = $attach_id;
            unset($news['id']);
            pdo_insert('wechat_news', $news);
        }
        cache_delete(cache_system_key('material_reply', array('attach_id' => $attach_id)));
    } else {
        $wechat_attachment = array(
            'uniacid' => $_W['uniacid'],
            'acid' => $_W['acid'],
            'media_id' => '',
            'type' => $type,
            'model' => 'local',
            'createtime' => TIMESTAMP,
            'article_id' => '',
            'publish_status' => -1
        );
        pdo_insert('wechat_attachment', $wechat_attachment);
        $attach_id = pdo_insertid();
        foreach ($post_news as $news) {
            unset($news['id']);
            $news['attach_id'] = $attach_id;
            $news['url'] = '';
            $news['thumb_media_id'] = '';
            if (strexists($news['thumb_url'], 'c=utility&a=wxcode&do=image&attach=')) {
                $local_attachment = material_network_image_to_local($news['thumb_url'], $_W['uniacid'], $_W['uid']);
                $news['thumb_url'] = $local_attachment['url'];
            }
            pdo_insert('wechat_news', $news);
        }
    }
    return $attach_id;
}

/**
 * 获取素材
 * @param array $material 素材的id或者mediaid
 * @return array() 素材内容
 */
function material_get($attach_id) {
    if (empty($attach_id)) {
        return error(1, "素材id参数不能为空");
    }
    if (is_numeric($attach_id)) {
        $material = table('wechat_attachment')->getById($attach_id);
    } else {
        $media_id = trim($attach_id);
        $material = table('wechat_attachment')->getByMediaId($media_id);
    }
    if (!empty($material)) {
        if (in_array($material['type'], array('news', 'draft'))) {
            $news = table('wechat_news')->getAllByAttachId($material['id']);
            if (!empty($news)) {
                foreach ($news as &$news_row) {
                    $news_row['content_source_url'] = $news_row['content_source_url'];
                    $news_row['thumb_url'] = tomedia($news_row['thumb_url']);
                    preg_match_all('/src=[\'\"]?([^\'\"]*)[\'\"]?/i', $news_row['content'], $match);
                    if (!empty($match[1])) {
                        foreach ($match[1] as $val) {
                            if ((strexists($val, 'http://') || strexists($val, 'https://')) && (strexists($val, 'mmbiz.qlogo.cn') || strexists($val, 'mmbiz.qpic.cn'))) {
                                $news_row['content'] = str_replace($val, tomedia($val), $news_row['content']);
                            }
                        }
                    }
                    $news_row['content'] = str_replace('data-src', 'src', $news_row['content']);
                }
                unset($news_row);
            } else {
                return error('1', '素材不存在');
            }
            $material['news'] = $news;
        } elseif ($material['type'] == 'image') {
            $material['url'] = $material['attachment'];
            $material['attachment'] = tomedia($material['attachment']);
        }
        return $material;
    } else {
        return error('1', "素材不存在");
    }
}

/**
 * 构造素材回复消息结构
 * @param array $material 素材的id
 * @return array() 回复消息结构
 */
function material_build_reply($attach_id) {
    if (empty($attach_id)) {
        return error(1, "素材id参数不能为空");
    }
    $cachekey = cache_system_key('material_reply', array('attach_id' => $attach_id));
    $reply = cache_load($cachekey);
    if (!empty($reply)) {
        return $reply;
    }
    $reply_material = material_get($attach_id);
    $reply = array();
    if (in_array($reply_material['type'], array('news', 'draft'))) {
        if (!empty($reply_material['news'])) {
            foreach ($reply_material['news'] as $material) {
                $reply[] = array(
                    'title' => $material['title'],
                    'description' => $material['digest'],
                    'picurl' => $material['thumb_url'],
                    'url' => !empty($material['url']) ? $material['url'] : $material['content_source_url'],
                );
            }
        }
    }
    cache_write($cachekey, $reply, CACHE_EXPIRE_MIDDLE);
    return $reply;
}

/**
 *将内容中通过tomeida()转义的微信图片代理地址替换成微信图片原生地址
 * @param $content string 待处理的图文内容
 */
function material_strip_wechat_image_proxy($content) {
    global $_W;
    $match_wechat = array();
    $content = htmlspecialchars_decode($content);
    preg_match_all('/<img.*src=[\'"](.*)[\'"].*\/?>/iU', $content, $match_wechat);
    if (!empty($match_wechat[1])) {
        foreach ($match_wechat[1] as $val) {
            $wechat_thumb_url = urldecode(str_replace($_W['siteroot'] . 'web/index.php?c=utility&a=wxcode&do=image&attach=', '', $val));
            $content = str_replace($val, $wechat_thumb_url, $content);
        }
    }
    return $content;
}

/**
 * 获取内容中所有非微信图片的图片地址
 * @param $content string 待处理的内容
 * @param $images array 内容中所有图片的地址
 */
function material_get_image_url($content) {
    global $_W;
    $content = htmlspecialchars_decode($content);
    $match = array();
    $images = array();
    preg_match_all('/<img.*src=[\'"](.*\.(?:png|jpg|jpeg|jpe|gif))[\'"].*\/?>/iU', $content, $match);
    if (!empty($match[1])) {
        foreach ($match[1] as $val) {
            if ((strexists($val, 'http://') || strexists($val, 'https://')) && !strexists($val, 'mmbiz.qlogo.cn') && !strexists($val, 'mmbiz.qpic.cn')) {
                $images[] = $val;
            } else {
                if (strexists($val, './attachment/images/')) {
                    $images[] = tomedia($val);
                }
            }
        }
    }
    return $images;
}

/**
 * 替换图文素材内容中图片url地址（把非微信url替换成微信url）
 * @param $content string 待处理的图文内容
 */
function material_parse_content($content) {
    global $_W;
    $content = material_strip_wechat_image_proxy($content);
    $images = material_get_image_url($content);
    if (!empty($images)) {
        foreach ($images as $image) {
            $thumb = file_remote_attach_fetch(tomedia($image), 1024, 'material/images');
            if (is_error($thumb)) {
                return $thumb;
            }
            $thumb = ATTACHMENT_ROOT . $thumb;
            $account_api = WeAccount::createByUniacid();
            $result = $account_api->uploadNewsThumb($thumb);
            if (is_error($result)) {
                return $result;
            } else {
                $content = str_replace($image, $result, $content);
            }
        }
    }
    return $content;
}
/**
 * 目前图片、音频、视频素材上传都用这个方法
 * 上传素材文件到微信，获取mediaId
 * @param string $url
 *
 */
function material_local_upload_by_url($url, $type = 'images') {
    global $_W;
    $account_api = WeAccount::createByUniacid();
    if (! empty($_W['setting']['remote']['type'])) {
        $remote_file_url = tomedia($url);
        $filepath = file_remote_attach_fetch($remote_file_url, 0, '');
        if (is_error($filepath)) {
            return $filepath;
        }
        $filepath = ATTACHMENT_ROOT . $filepath;
    } else {
        if (strexists(parse_url($url, PHP_URL_PATH), '/attachment/')) {
            $url = substr(parse_url($url, PHP_URL_PATH), strpos(parse_url($url, PHP_URL_PATH), '/attachment/') + strlen('/attachment/'));
        }
        $filepath = ATTACHMENT_ROOT . $url;
    }
    $filesize = filesize($filepath);
    $filesize = sizecount($filesize, true);
    if ($filesize > 10 && $type == 'videos') {
        return error(-1, '要转换的微信素材视频不能超过10M');
    }
    return $account_api->uploadMediaFixed($filepath, $type);
}

/**
 * 同步本地素材到微信
 * @param int $material_id
 * @return array
 */
function material_local_upload($material_id) {
    global $_W;
    $type_arr = array('1' => 'images', '2' => 'voices', '3' => 'videos');
    $material = pdo_get('core_attachment', array('uniacid' => $_W['uniacid'], 'id' => $material_id));
    if (empty($material)) {
        return error('-1', '同步素材不存在或已删除');
    }
    return material_local_upload_by_url($material['attachment'], $type_arr[$material['type']]);
}

/**
 * 验证输入内容是否为合法链接
 * @param $url
 * @return boolean
 */
function material_url_check($url) {
    if (empty($url)) {
        return true;
    } else {
        $pattern = "/^((https|http|tel):\/\/|\.\/index.php)[^\s]+/i";
        return preg_match($pattern, trim($url));
    }
}

function material_news_list($server = '', $search = '', $page = array('page_index' => 1, 'page_size' => 24), $type = 'news') {
    global $_W;
    $wechat_news_table = table('wechat_news');
    $wechat_attachment_table = table('wechat_attachment');
    $material_list = array();
    $total = 0;
    $type = in_array($type, array('news', 'draft', 'publish')) ? $type : 'news';
    if (empty($search)) {
        $wechat_attachment_table->searchWithUniacid($_W['uniacid']);
        if ('publish' == $type) {
            $wechat_attachment_table->searchWithArticleId(array('article_id !=' => ''));
            $type = 'draft';
        } else {
            $wechat_attachment_table->searchWithArticleId(array('article_id' => ''));
        }
        $wechat_attachment_table->searchWithType($type);
        if (!empty($server) && in_array($server, array('local', 'perm'))) {
            $wechat_attachment_table->searchWithModel($server);
        }
        $wechat_attachment_table->searchWithPage($page['page_index'], $page['page_size']);
        $news_list = $wechat_attachment_table->orderby('createtime DESC')->getall();
        $total = $wechat_attachment_table->getLastQueryTotal();

        if (! empty($news_list)) {
            foreach ($news_list as $news) {
                $news['items'] = $wechat_news_table->getAllByAttachId($news['id']);
                $material_list[$news['id']] = $news;
            }
        }
    } else {
        $wechat_news_table->searchKeyword("%$search%");
        $wechat_news_table->searchWithUniacid($_W['uniacid']);
        $search_attach_id = $wechat_news_table->getall();

        if (!empty($search_attach_id)) {
            foreach ($search_attach_id as $news) {
                if (isset($material_list[$news['attach_id']]) && !empty($material_list[$news['attach_id']])) {
                    continue;
                }
                $wechat_attachment = $wechat_attachment_table->getById($news['attach_id']);
                if (empty($wechat_attachment)) {
                    continue;
                }
                if ('publish' == $type) {
                    if ('draft' != $wechat_attachment['type'] || empty($wechat_attachment['article_id'])) {
                        continue;
                    }
                } else {
                    if ($wechat_attachment['type'] != $type || !empty($wechat_attachment['article_id'])) {
                        continue;
                    }
                }
                $material_list[$news['attach_id']] = $wechat_attachment;
                $material_list[$news['attach_id']]['items'] = $wechat_news_table->getAllByAttachId($news['attach_id']);
            }
        }
    }

    // 转换微信图片地址
    foreach ($material_list as $key => &$news) {
        if (isset($news['items']) && is_array($news['items'])) {
            if (empty($news['items'][0])) {
                $news['items'] = array_values($news['items']);
            }
            foreach ($news['items'] as &$item) {
                $item['digest'] = htmlspecialchars($item['digest']);
                $item['thumb_url'] = tomedia($item['thumb_url']);
            }
        }
    }
    unset($news_list);
    $pager = pagination($total, $page['page_index'], $page['page_size'], '', $context = array('before' => 5, 'after' => 4, 'isajax' => $_W['isajax']));
    $material_news = array('material_list' => $material_list, 'page' => $pager);
    return $material_news;
}

function material_list($type = '', $server = '', $page = array('page_index' => 1, 'page_size' => 24)) {
    global $_W;
    $tables = array(MATERIAL_LOCAL => 'core_attachment', MATERIAL_WEXIN => 'wechat_attachment');
    $conditions['uniacid'] = $_W['uniacid'];
    $table = $tables[$server];
    switch ($type) {
        case 'voice':
            $conditions['type'] = $server == MATERIAL_LOCAL ? ATTACH_TYPE_VOICE : 'voice';
            break;
        case 'video':
            $conditions['type'] = $server == MATERIAL_LOCAL ? ATTACH_TYPE_VEDIO : 'video';
            break;
        default:
            $conditions['type'] = $server == MATERIAL_LOCAL ? ATTACH_TYPE_IMAGE : 'image';
            break;
    }
    $order = 'createtime DESC';
    if (!empty($page['conditions']) && is_array($page['conditions'])) {
        $conditions = array_merge($conditions, $page['conditions']);
    }
    if (!empty($page['order'])) {
        $order = $page['order'];
    }
    if ($server == 'local') {
        $material_list = pdo_getslice($table, $conditions, array($page['page_index'], $page['page_size']), $total, array(), '', $order);
    } else {
        $conditions['model'] = MATERIAL_WEXIN;
        $material_list = pdo_getslice($table, $conditions, array($page['page_index'], $page['page_size']), $total, array(), '', $order);
        if ($type == 'video') {
            foreach ($material_list as &$row) {
                $row['tag'] = $row['tag'] == '' ? array() : iunserializer($row['tag']);
                if (empty($row['filename'])) {
                    $row['filename'] = $row['tag']['title'];
                }
            }
            unset($row);
        }
    }
    $pager = pagination($total, $page['page_index'], $page['page_size'], '', $context = array('before' => 5, 'after' => 4, 'isajax' => $_W['isajax']));
    $material_news = array('material_list' => $material_list, 'page' => $pager);
    return $material_news;
}

/**
 *  微信图文转本地
 * @param $resourceid
 * @return array|int
 */
function material_news_to_local($attach_id) {
    // 如果是 news 类型
    $material = material_get($attach_id);
    if (is_error($material)) {
        return $material;
    }
    $attach_id = material_news_set($material['news'], $attach_id);
    if (is_error($attach_id)) {
        return $attach_id;
    }
    $material['items'] = $material['news'];// 前台用的items
    return $material;
}

/**
 *  图片 视频  语音转为 本地
 * @param $resourceid
 * @param $uniacid
 * @param $uid
 * @param $type
 * @return array|string
 */
function material_to_local($resourceid, $uniacid, $uid, $type = 'image') {
    $material = material_get($resourceid);
    if (is_error($material)) {
        return $material;
    }
    return material_network_image_to_local($material['attachment'], $uniacid, $uid);
}

/**
 *  网络图片转本地
 * @param $url
 * @param $uniacid
 * @param $uid
 * @param int $type
 * @return array|string
 */
function material_network_image_to_local($url, $uniacid, $uid) {
    return material_network_to_local($url, $uniacid, $uid, 'image');
}

/**
 *  网络资源转本地 支持视频 图片
 * @param $url
 * @param $uniacid
 * @param $uid
 * @param int $type
 * @return array|string
 */
function material_network_to_local($url, $uniacid, $uid, $type = 'image') {
    global $_W;
    $path = file_remote_attach_fetch($url); //网络转本地图片路径
    if (is_error($path)) {
        return $path;
    }
    if (!empty($_W['setting']['remote']['type'])) {
        $remotestatus = file_remote_upload($path);
        if (is_error($remotestatus)) {
            return $remotestatus;
        } else {
            file_delete($path);
        }
    }
    $filename = pathinfo($path, PATHINFO_FILENAME);
    $data = array('uniacid' => $uniacid, 'uid' => $uid,
        'filename' => $filename,
        'attachment' => $path,
        'type' => $type == 'image' ? ATTACH_TYPE_IMAGE : ($type == 'audio' || $type == 'voice' ? ATTACH_TYPE_VOICE : ATTACH_TYPE_VEDIO),
        'createtime' => TIMESTAMP
    );
    pdo_insert('core_attachment', $data);
    $id = pdo_insertid();
    $data['id'] = $id;
    $data['url'] = tomedia($path);
    return $data;
}

/**
 *  本地图片 视频 语音 转换为微信 资源
 * @param $attach_id
 * @param $uniacid
 * @param $uid
 * @param $acid
 */
function material_to_wechat($attach_id, $uniacid, $uid, $acid, $type = 'image') {
    $result = material_local_upload($attach_id); //本地资源上传到服务器
    if (is_error($result)) {
        return $result;
    }
    $tag = $result['url'];
    if ($type == 'video') {
        $tag = serialize(array('title' => '网络视频', 'description' => '网络视频'));
    }
    $data = array('uniacid' => $uniacid, 'uid' => $uid, 'acid' => $acid,
        'media_id' => $result['media_id'],
        'attachment' => $result['url'],
        'type' => $type,
        'tag' => $tag,
        'model' => 'perm',
        'createtime' => TIMESTAMP
    );
    pdo_insert('wechat_attachment', $data);
    $id = pdo_insertid();
    $data['url'] = tomedia($result['url']);
    $data['id'] = $id;
    return $data;
}

/**
 *  网络视频  图片上传到微信
 */
function material_network_to_wechat($url, $uniacid, $uid, $acid, $type = 'image') {
    $local = material_network_to_local($url, $uniacid, $uid, $type); //网络图片先转为本地资源
    if (is_error($local)) {
        return $local;
    }
    return material_to_wechat($local['id'], $uniacid, $uid, $acid, $type);
}
