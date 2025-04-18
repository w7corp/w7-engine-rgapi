<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/web/common/tpl.func.php : v 8b6dd7b5a696 : 2015/09/17 03:20:11 : yanghf $.
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 【表单控件】: 日期控件.
 *
 * @param string $name
 *                         表单名称
 * @param string $value
 *                         默认为当前日期时间
 * @param bool   $withtime
 *                         是否显示时间(时分),默认为不显示
 *
 * @return form input string
 */
function _tpl_form_field_date($name, $value = '', $withtime = false) {
    $s = '';
    $withtime = empty($withtime) ? false : true;
    if (!empty($value)) {
        $value = strexists($value, '-') ? strtotime($value) : $value;
    } else {
        $value = TIMESTAMP;
    }
    $value = ($withtime ? date('Y-m-d H:i:s', $value) : date('Y-m-d', $value));
    $s .= '<input type="text" name="' . $name . '"  value="' . $value . '" placeholder="请选择日期时间" readonly="readonly" class="datetimepicker form-control" style="padding-left:12px;" />';
    $s .= '
        <script type="text/javascript">
            require(["datetimepicker"], function(){
                    var option = {
                        lang : "zh",
                        step : 5,
                        timepicker : ' . (!empty($withtime) ? 'true' : 'false') . ',
                        closeOnDateSelect : true,
                        format : "Y-m-d' . (!empty($withtime) ? ' H:i"' : '"') . '
                    };
                $(".datetimepicker[name = \'' . $name . '\']").datetimepicker(option);
            });
        </script>';

    return $s;
}

/**
 * 【表单控件】: 系统链接选择器.
 *
 * @param string $name    表单input名称
 * @param string $value   表单input值
 * @param array  $options 选择器样式配置信息
 *
 * @return string
 */
function tpl_form_field_link($name, $value = '', $options = array()) {
    global $_GPC, $_W;
    if (!empty($options)) {
        foreach ($options as $key => $val) {
            $options .= $key . ':' . $val . ',';
        }
    }
    $s = '';
    if (!defined('TPL_INIT_LINK')) {
        $multiid = empty($_GPC['multiid']) ? 0 : intval($_GPC['multiid']);
        $s .= '
		<script type="text/javascript" src="https://api.map.baidu.com/api?v=2.0&ak=F51571495f717ff1194de02366bb8da9&s=1"></script>
        <script type="text/javascript">
            window.HOST_TYPE = "2";
            window.BMap_loadScriptTime = (new Date).getTime();
            function showLinkDialog(elm) {
                var ipt = $(elm).parent().parent().parent().prev();
                util.linkBrowser(function(href){
                    var multiid = "' . $multiid . '";
                    if (multiid) {
                        href = /(&)?t=/.test(href) ? href : href + "&t=" + multiid;
                    }
                    ipt.val(href);
                });
            }
            function newsLinkDialog(elm, page) {
                var ipt = $(elm).parent().parent().parent().prev();
                util.newsBrowser(function(href, page){
                    if (page != "" && page != undefined) {
                        newsLinkDialog(elm, page);
                        return false;
                    }
                    var multiid = "' . $multiid . '";
                    if (multiid) {
                        href = /(&)?t=/.test(href) ? href : href + "&t=" + multiid;
                    }
                    ipt.val(href);
                }, page);
            }
            function pageLinkDialog(elm, page) {
                var ipt = $(elm).parent().parent().parent().prev();
                util.pageBrowser(function(href, page){
                    if (page != "" && page != undefined) {
                        pageLinkDialog(elm, page);
                        return false;
                    }
                    var multiid = "' . $multiid . '";
                    if (multiid) {
                        href = /(&)?t=/.test(href) ? href : href + "&t=" + multiid;
                    }
                    ipt.val(href);
                }, page);
            }
            function articleLinkDialog(elm, page) {
                var ipt = $(elm).parent().parent().parent().prev();
                util.articleBrowser(function(href, page){
                    if (page != "" && page != undefined) {
                        articleLinkDialog(elm, page);
                        return false;
                    }
                    var multiid = "' . $multiid . '";
                    if (multiid) {
                        href = /(&)?t=/.test(href) ? href : href + "&t=" + multiid;
                    }
                    ipt.val(href);
                }, page);
            }
            function phoneLinkDialog(elm, page) {
                var ipt = $(elm).parent().parent().parent().prev();
                util.phoneBrowser(function(href, page){
                    if (page != "" && page != undefined) {
                        phoneLinkDialog(elm, page);
                        return false;
                    }
                    ipt.val(href);
                }, page);
            }
            function mapLinkDialog(elm) {
                var ipt = $(elm).parent().parent().parent().prev();
                util.map(elm, function(val){
                    var href = \'https://api.map.baidu.com/marker?location=\'+val.lat+\',\'+val.lng+\'&output=html&src=we7\';
                    var multiid = "' . $multiid . '";
                    if (multiid) {
                        href = /(&)?t=/.test(href) ? href : href + "&t=" + multiid;
                    }
                    ipt.val(href);
                });
            }
        </script>';
        define('TPL_INIT_LINK', true);
    }
    $s .= '
    <div class="input-group">
        <input type="text" value="' . $value . '" name="' . $name . '" class="form-control" autocomplete="off" style="' . ($options ? $options : 'width:525px') . '">
        <span class="input-group-btn">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button" aria-haspopup="true" aria-expanded="false">选择链接 <span class="caret"></span></button>
            <ul class="dropdown-menu">
                <li><a href="javascript:" data-type="system" onclick="showLinkDialog(this);">系统菜单</a></li>
                <li><a href="javascript:" data-type="page" onclick="pageLinkDialog(this);">微页面</a></li>
                <li><a href="javascript:" data-type="article" onclick="articleLinkDialog(this)">文章及分类</a></li>
                <li><a href="javascript:" data-type="news" onclick="newsLinkDialog(this)">图文回复</a></li>
                <li><a href="javascript:" data-type="map" onclick="mapLinkDialog(this)">一键导航</a></li>
                <li><a href="javascript:" data-type="phone" onclick="phoneLinkDialog(this)">一键拨号</a></li>
            </ul>
        </span>
    </div>
    ';

    return $s;
}

/**
 * 【表单控件】:.
 *
 * @param string $name  表单input名称
 * @param string $value 表单input值
 *
 * @return string
 */
function tpl_form_module_link($name) {
    $s = '';
    if (!defined('TPL_INIT_module')) {
        $s = '
        <script type="text/javascript">
            function showModuleLink(elm) {
                util.showModuleLink(function(href, permission) {
                    var ipt = $(elm).parent().prev();
                    var ipts = $(elm).parent().prev().prev();
                    ipt.val(href);
                    ipts.val(permission);
                });
            }
        </script>';
        define('TPL_INIT_module', true);
    }
    $s .= '
    <div class="input-group">
        <input type="text" class="form-control" name="permission" style="display: none">
        <input type="text" class="form-control" name="' . $name . '">
            <span class="input-group-btn">
                <a href="javascript:"  class="btn btn-default" onclick="showModuleLink(this)">选择链接</a>
            </span>
    </div>
    ';

    return $s;
}

/**
 * 【表单控件】: Emoji表情选择器.
 *
 * @param string $name  表单input名称
 * @param string $value 表单input值
 *
 * @return string
 */
function tpl_form_field_emoji($name, $value = '') {
    $s = '';
    if (!defined('TPL_INIT_EMOJI')) {
        $s = '
        <script type="text/javascript">
            function showEmojiDialog(elm) {
                var btn = $(elm);
                var spview = btn.parent().prev();
                var ipt = spview.prev();
                if(!ipt.val()){
                    spview.css("display","none");
                }
                util.emojiBrowser(function(emoji){
                    ipt.val("\\\" + emoji.find("span").text().replace("+", "").toLowerCase());
                    spview.show();
                    spview.find("span").removeClass().addClass(emoji.find("span").attr("class"));
                });
            }
        </script>';
        define('TPL_INIT_EMOJI', true);
    }
    $s .= '
    <div class="input-group" style="width: 500px;">
        <input type="text" value="' . $value . '" name="' . $name . '" class="form-control" autocomplete="off">
        <span class="input-group-addon" style="display:none"><span></span></span>
        <span class="input-group-btn">
            <button class="btn btn-default" type="button" onclick="showEmojiDialog(this);">选择表情</button>
        </span>
    </div>
    ';

    return $s;
}

/**
 * 【表单控件】: 拾色器 (获取 HTML 色彩代码).
 *
 * @param string $name  表单input名称
 * @param string $value 表单input值
 *
 * @return string
 */
function tpl_form_field_color($name, $value = '') {
    $s = '';
    if (!defined('TPL_INIT_COLOR')) {
        $s = '
        <script type="text/javascript">
            $(function(){
                $(".colorpicker").each(function(){
                    var elm = this;
                    util.colorpicker(elm, function(color){
                        $(elm).parent().prev().prev().val(color.toHexString());
                        $(elm).parent().prev().css("background-color", color.toHexString());
                    });
                });
                $(".colorclean").click(function(){
                    $(this).parent().prev().prev().val("");
                    $(this).parent().prev().css("background-color", "#FFF");
                });
            });
        </script>';
        define('TPL_INIT_COLOR', true);
    }
    $s .= '
        <div class="row row-fix">
            <div class="col-xs-8 col-sm-8" style="padding-right:0;">
                <div class="input-group">
                    <input class="form-control" type="text" name="' . $name . '" placeholder="请选择颜色" value="' . $value . '">
                    <span class="input-group-addon" style="width:35px;border-left:none;background-color:' . $value . '"></span>
                    <span class="input-group-btn">
                        <button class="btn btn-default colorpicker" type="button">选择颜色 <i class="fa fa-caret-down"></i></button>
                        <button class="btn btn-default colorclean" type="button"><span><i class="fa fa-remove"></i></span></button>
                    </span>
                </div>
            </div>
        </div>
        ';

    return $s;
}

/**
 * 【表单控件】: 系统图标选择器.
 *
 * @param string $name  表单input名称
 * @param string $value 表单input值
 *
 * @return string
 */
function tpl_form_field_icon($name, $value = '') {
    if (empty($value)) {
        $value = 'fa fa-external-link';
    }
    $s = '';
    if (!defined('TPL_INIT_ICON')) {
        $s = '
        <script type="text/javascript">
            function showIconDialog(elm) {
                var btn = $(elm);
                var spview = btn.parent().prev();
                var ipt = spview.prev();
                if(!ipt.val()){
                    spview.css("display","none");
                }
                util.iconBrowser(function(ico){
                    ipt.val(ico);
                    spview.show();
                    spview.find("i").attr("class","");
                    spview.find("i").addClass("fa").addClass(ico);
                });
            }
        </script>';
        define('TPL_INIT_ICON', true);
    }
    $s .= '
    <div class="input-group" style="width: 300px;">
        <input type="text" value="' . $value . '" name="' . $name . '" class="form-control" autocomplete="off">
        <span class="input-group-addon"><i class="' . $value . ' fa"></i></span>
        <span class="input-group-btn">
            <button class="btn btn-default" type="button" onclick="showIconDialog(this);">选择图标</button>
        </span>
    </div>
    ';

    return $s;
}

/**
 * 【表单控件】: 图片上传与选择控件.
 *
 * @param string $name    表单input名称
 * @param string $value   表单input值
 * @param string $default 默认显示的缩略图
 * @param array  $options 图片上传配置信息
 *                        <pre>
 *                        $options['width'] = '';
 *                        $options['height'] = '';
 *                        $options['global'] = '';// 是否显示 global 目录（公共目录）
 *                        $options['extras'] = array(
 *                        &nbsp;'image'=> 缩略图img标签的自定义属性及属性值 ,
 *                        &nbsp;'text'=> input 标签的自定义属性及属性值
 *                        )
 *                        </pre>
 *
 * @return string
 */
function tpl_form_field_image($name, $value = '', $default = '', $options = array()) {
    global $_W;
    if (empty($default)) {
        $default = './resource/images/nopic.jpg';
    }
    $val = $default;
    if (!empty($value)) {
        $val = tomedia($value);
    }
    if (defined('SYSTEM_WELCOME_MODULE')) {
        $options['uniacid'] = 0;
    }
    if (!empty($options['global'])) {
        $options['global'] = true;
        $val = to_global_media(empty($value) ? $default : $value);
    } else {
        $options['global'] = false;
    }
    if (empty($options['class_extra'])) {
        $options['class_extra'] = '';
    }
    if (isset($options['dest_dir']) && !empty($options['dest_dir'])) {
        if (!preg_match('/^\w+([\/]\w+)?$/i', $options['dest_dir'])) {
            exit('图片上传目录错误,只能指定最多两级目录,如: "we7_store","we7_store/d1"');
        }
    }
    $options['direct'] = true;
    $options['multiple'] = false;
    if (isset($options['thumb'])) {
        $options['thumb'] = !empty($options['thumb']);
    }
    $options['fileSizeLimit'] = intval($GLOBALS['_W']['setting']['upload']['image']['limit']) * 1024;
    $s = '';
    if (!defined('TPL_INIT_IMAGE')) {
        $s = '
        <script type="text/javascript">
            function showImageDialog(elm, opts, options) {
                require(["util"], function(util){
                    var btn = $(elm);
                    var ipt = btn.parent().prev();
                    var val = ipt.val();
                    var img = ipt.parent().next().children();
                    options = ' . str_replace('"', '\'', json_encode($options)) . ';
                    util.image(val, function(url){
                        if(url.url){
                            if(img.length > 0){
                                img.get(0).src = url.url;
                            }
                            ipt.val(url.attachment);
                            ipt.attr("filename",url.filename);
                            ipt.attr("url",url.url);
                        }
                        if(url.media_id){
                            if(img.length > 0){
                                img.get(0).src = url.url;
                            }
                            ipt.val(url.media_id);
                        }
                    }, options);
                });
            }
            function deleteImage(elm){
                $(elm).prev().attr("src", "./resource/images/nopic.jpg");
                $(elm).parent().prev().find("input").val("");
            }
        </script>';
        define('TPL_INIT_IMAGE', true);
    }

    $s .= '
        <div class="input-group ' . $options['class_extra'] . '">
            <input type="text" name="' . $name . '" value="' . $value . '"' . (!empty($options['extras']['text']) ? $options['extras']['text'] : '') . ' class="form-control" autocomplete="off">
            <span class="input-group-btn">
                <button class="btn btn-default" type="button" onclick="showImageDialog(this);">选择图片</button>
            </span>
        </div>
        <div class="input-group ' . $options['class_extra'] . '" style="margin-top:.5em;">
            <img src="' . $val . '" onerror="this.src=\'' . $default . '\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail" ' . (!empty($options['extras']['image']) ? $options['extras']['image'] : '') . ' width="150" />
            <em class="close" style="position:absolute; top: 0px; right: -14px;" title="删除这张图片" onclick="deleteImage(this)">×</em>
        </div>';

    return $s;
}

/**
 * 批量上传图片.
 *
 * @param string $name    表单input名称
 * @param array  $value   附件路径信息
 * @param array  $options 自定义图片上传路径
 *
 * @return string
 */
function tpl_form_field_multi_image($name, $value = array(), $options = array()) {
    global $_W;
    $options['multiple'] = true;
    $options['direct'] = false;
    $options['fileSizeLimit'] = intval($GLOBALS['_W']['setting']['upload']['image']['limit']) * 1024;
    if (isset($options['dest_dir']) && !empty($options['dest_dir'])) {
        if (!preg_match('/^\w+([\/]\w+)?$/i', $options['dest_dir'])) {
            exit('图片上传目录错误,只能指定最多两级目录,如: "we7_store","we7_store/d1"');
        }
    }
    if (defined('SYSTEM_WELCOME_MODULE')) {
        $options['uniacid'] = 0;
    }
    $s = '';
    if (!defined('TPL_INIT_MULTI_IMAGE')) {
        $s = '
<script type="text/javascript">
    function uploadMultiImage(elm) {
        var name = $(elm).next().val();
        util.image( "", function(urls){
            $.each(urls, function(idx, url){
                $(elm).parent().parent().next().append(\'<div class="multi-item"><img onerror="this.src=\\\'./resource/images/nopic.jpg\\\'; this.title=\\\'图片未找到.\\\'" src="\'+url.url+\'" class="img-responsive img-thumbnail"><input type="hidden" name="\'+name+\'[]" value="\'+url.attachment+\'"><em class="close" title="删除这张图片" onclick="deleteMultiImage(this)">×</em></div>\');
            });
        }, ' . json_encode($options) . ');
    }
    function deleteMultiImage(elm){
        $(elm).parent().remove();
    }
</script>';
        define('TPL_INIT_MULTI_IMAGE', true);
    }

    $s .= <<<EOF
<div class="input-group">
    <input type="text" class="form-control" readonly="readonly" value="" placeholder="批量上传图片" autocomplete="off">
    <span class="input-group-btn">
        <button class="btn btn-default" type="button" onclick="uploadMultiImage(this);">选择图片</button>
        <input type="hidden" value="{$name}" />
    </span>
</div>
<div class="input-group multi-img-details">
EOF;
    if (is_array($value) && count($value) > 0) {
        foreach ($value as $row) {
            $s .= '
<div class="multi-item">
    <img src="' . tomedia($row) . '" onerror="this.src=\'./resource/images/nopic.jpg\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail">
    <input type="hidden" name="' . $name . '[]" value="' . $row . '" >
    <em class="close" title="删除这张图片" onclick="deleteMultiImage(this)">×</em>
</div>';
        }
    }
    $s .= '</div>';

    return $s;
}

/**
 * 【表单控件】: 音乐选择与上传.
 *
 * @param string $name    表单input名称
 * @param string $value   表单input值
 * @param array  $options 表单中input附加信息
 *
 * @return string
 */
function tpl_form_field_audio($name, $value = '', $options = array()) {
    if (!is_array($options)) {
        $options = array();
    }
    $options['direct'] = true;
    $options['multiple'] = false;
    $options['fileSizeLimit'] = intval($GLOBALS['_W']['setting']['upload']['audio']['limit']) * 1024;
    $s = '';
    if (!defined('TPL_INIT_AUDIO')) {
        $s = '
<script type="text/javascript">
    function showAudioDialog(elm, base64options, options) {
        require(["util"], function(util){
            var btn = $(elm);
            var ipt = btn.parent().prev();
            var val = ipt.val();
            util.audio(val, function(url){
                if(url && url.attachment && url.url){
                    btn.prev().show();
                    ipt.val(url.attachment);
                    ipt.attr("filename",url.filename);
                    ipt.attr("url",url.url);
                    setAudioPlayer();
                }
                if(url && url.media_id){
                    ipt.val(url.media_id);
                }
            }, "" , ' . json_encode($options) . ');
        });
    }

    function setAudioPlayer(){
        require(["jquery.jplayer"], function(){
            $(function(){
                $(".audio-player").each(function(){
                    $(this).prev().find("button").eq(0).click(function(){
                        var src = $(this).parent().prev().val();
                        if($(this).find("i").hasClass("fa-stop")) {
                            $(this).parent().parent().next().jPlayer("stop");
                        } else {
                            if(src) {
                                $(this).parent().parent().next().jPlayer("setMedia", {mp3: util.tomedia(src)}).jPlayer("play");
                            }
                        }
                    });
                });

                $(".audio-player").jPlayer({
                    playing: function() {
                        $(this).prev().find("i").removeClass("fa-play").addClass("fa-stop");
                    },
                    pause: function (event) {
                        $(this).prev().find("i").removeClass("fa-stop").addClass("fa-play");
                    },
                    swfPath: "resource/components/jplayer",
                    supplied: "mp3"
                });
                $(".audio-player-media").each(function(){
                    $(this).next().find(".audio-player-play").css("display", $(this).val() == "" ? "none" : "");
                });
            });
        });
    }
    setAudioPlayer();
</script>';
        echo $s;
        define('TPL_INIT_AUDIO', true);
    }
    $s .= '
    <div class="input-group">
        <input type="text" value="' . $value . '" name="' . $name . '" class="form-control audio-player-media" autocomplete="off" ' . ($options['extras']['text'] ? $options['extras']['text'] : '') . '>
        <span class="input-group-btn">
            <button class="btn btn-default audio-player-play" type="button" style="display:none;"><i class="fa fa-play"></i></button>
            <button class="btn btn-default" type="button" onclick="showAudioDialog(this, \'' . base64_encode(iserializer($options)) . '\',' . str_replace('"', '\'', json_encode($options)) . ');">选择媒体文件</button>
        </span>
    </div>
    <div class="input-group audio-player"></div>';

    return $s;
}

/**
 * 批量上传音频.
 *
 * @param string $name    表单input名称
 * @param array  $value   表单input值
 * @param array  $options 自定义上传路径
 *
 * @return string
 */
function tpl_form_field_multi_audio($name, $value = array(), $options = array()) {
    $s = '';
    $options['direct'] = false;
    $options['multiple'] = true;
    $options['fileSizeLimit'] = intval($GLOBALS['_W']['setting']['upload']['audio']['limit']) * 1024;

    if (!defined('TPL_INIT_MULTI_AUDIO')) {
        $s .= '
<script type="text/javascript">
    function showMultiAudioDialog(elm, name) {
        require(["util"], function(util){
            var btn = $(elm);
            var ipt = btn.parent().prev();
            var val = ipt.val();

            util.audio(val, function(urls){
                $.each(urls, function(idx, url){
                    var obj = $(\'<div class="multi-audio-item" style="height: 40px; position:relative; float: left; margin-right: 18px;"><div class="multi-audio-player"></div><div class="input-group"><input type="text" class="form-control" readonly value="\' + url.attachment + \'" /><div class="input-group-btn"><button class="btn btn-default" type="button"><i class="fa fa-play"></i></button><button class="btn btn-default" onclick="deleteMultiAudio(this)" type="button"><i class="fa fa-remove"></i></button></div></div><input type="hidden" name="\'+name+\'[]" value="\'+url.attachment+\'"></div>\');
                    $(elm).parent().parent().next().append(obj);
                    setMultiAudioPlayer(obj);
                });
            }, ' . json_encode($options) . ');
        });
    }
    function deleteMultiAudio(elm){
        $(elm).parent().parent().parent().remove();
    }
    function setMultiAudioPlayer(elm){
        require(["jquery.jplayer"], function(){
            $(".multi-audio-player",$(elm)).next().find("button").eq(0).click(function(){
                var src = $(this).parent().prev().val();
                if($(this).find("i").hasClass("fa-stop")) {
                    $(this).parent().parent().prev().jPlayer("stop");
                } else {
                    if(src) {
                        $(this).parent().parent().prev().jPlayer("setMedia", {mp3: util.tomedia(src)}).jPlayer("play");
                    }
                }
            });
            $(".multi-audio-player",$(elm)).jPlayer({
                playing: function() {
                    $(this).next().find("i").eq(0).removeClass("fa-play").addClass("fa-stop");
                },
                pause: function (event) {
                    $(this).next().find("i").eq(0).removeClass("fa-stop").addClass("fa-play");
                },
                swfPath: "resource/components/jplayer",
                supplied: "mp3"
            });
        });
    }
</script>';
        define('TPL_INIT_MULTI_AUDIO', true);
    }

    $s .= '
<div class="input-group">
    <input type="text" class="form-control" readonly="readonly" value="" placeholder="批量上传音乐" autocomplete="off">
    <span class="input-group-btn">
        <button class="btn btn-default" type="button" onclick="showMultiAudioDialog(this,\'' . $name . '\');">选择音乐</button>
    </span>
</div>
<div class="input-group multi-audio-details clear-fix" style="margin-top:.5em;">';
    if (!empty($value) && !is_array($value)) {
        $value = array($value);
    }
    if (is_array($value) && count($value) > 0) {
        $n = 0;
        foreach ($value as $row) {
            $m = random(8);
            $s .= '
    <div class="multi-audio-item multi-audio-item-' . $n . '-' . $m . '" style="height: 40px; position:relative; float: left; margin-right: 18px;">
        <div class="multi-audio-player"></div>
        <div class="input-group">
            <input type="text" class="form-control" value="' . $row . '" readonly/>
            <div class="input-group-btn">
                <button class="btn btn-default" type="button"><i class="fa fa-play"></i></button>
                <button class="btn btn-default" onclick="deleteMultiAudio(this)" type="button"><i class="fa fa-remove"></i></button>
            </div>
        </div>
        <input type="hidden" name="' . $name . '[]" value="' . $row . '">
    </div>
    <script language="javascript">setMultiAudioPlayer($(".multi-audio-item-' . $n . '-' . $m . '"));</script>';
            ++$n;
        }
    }
    $s .= '
</div>';

    return $s;
}

/**
 * 【表单控件】: 视频选择与上传.
 *
 * @param string $name    表单input名称
 * @param string $value   表单input值
 * @param array  $options 表单中input附加信息
 *
 * @return string
 */
function tpl_form_field_video($name, $value = '', $options = array()) {
    if (!is_array($options)) {
        $options = array();
    }
    if (!is_array($options)) {
        $options = array();
    }
    $options['direct'] = true;
    $options['multi'] = false;
    $options['type'] = 'video';
    $options['fileSizeLimit'] = intval($GLOBALS['_W']['setting']['upload']['audio']['limit']) * 1024;
    $s = '';
    if (!defined('TPL_INIT_VIDEO')) {
        $s = '
<script type="text/javascript">
    function showVideoDialog(elm, options) {
        require(["util"], function(util){
            var btn = $(elm);
            var ipt = btn.parent().prev();
            var val = ipt.val();
            util.audio(val, function(url){
                if(url && url.attachment && url.url){
                    btn.prev().show();
                    ipt.val(url.attachment);
                    ipt.attr("filename",url.filename);
                    ipt.attr("url",url.url);
                }
                if(url && url.media_id){
                    ipt.val(url.media_id);
                }
            }, ' . json_encode($options) . ');
        });
    }

</script>';
        echo $s;
        define('TPL_INIT_VIDEO', true);
    }

    $s .= '
    <div class="input-group">
        <input type="text" value="' . $value . '" name="' . $name . '" class="form-control" autocomplete="off" ' . ($options['extras']['text'] ? $options['extras']['text'] : '') . '>
        <span class="input-group-btn">
            <button class="btn btn-default" type="button" onclick="showVideoDialog(this,' . str_replace('"', '\'', json_encode($options)) . ');">选择媒体文件</button>
        </span>
    </div>';

    return $s;
}

function tpl_form_field_wechat_image($name, $value = '', $default = '', $options = array()) {
    global $_W;
    if (!$_W['acid'] || $_W['account']['level'] < 3) {
        $options['account_error'] = 1;
    } else {
        $options['acid'] = $_W['acid'];
    }
    if (empty($default)) {
        $default = './resource/images/nopic.jpg';
    }
    $val = $default;
    if (!empty($value)) {
        $media_data = (array) media2local($value, true);
        $val = $media_data['attachment'];
    }
    if (empty($options['class_extra'])) {
        $options['class_extra'] = '';
    }
    $options['direct'] = true;
    $options['multiple'] = false;
    $options['type'] = empty($options['type']) ? 'image' : $options['type'];
    $s = '';
    if (!defined('TPL_INIT_WECHAT_IMAGE')) {
        $s = '
        <script type="text/javascript">
            function showWechatImageDialog(elm, options) {
                require(["util"], function(util){
                    var btn = $(elm);
                    var ipt = btn.parent().prev();
                    var val = ipt.val();
                    var img = ipt.parent().next().children();
                    util.wechat_image(val, function(url){
                        if(url.media_id){
                            if(img.length > 0){
                                img.get(0).src = url.url;
                            }
                            ipt.val(url.media_id);
                        }
                    }, options);
                });
            }
            function deleteImage(elm){
                $(elm).prev().attr("src", "./resource/images/nopic.jpg");
                $(elm).parent().prev().find("input").val("");
            }
        </script>';
        define('TPL_INIT_WECHAT_IMAGE', true);
    }

    $s .= '
<div class="input-group ' . $options['class_extra'] . '">
    <input type="text" name="' . $name . '" value="' . $value . '"' . ($options['extras']['text'] ? $options['extras']['text'] : '') . ' class="form-control" autocomplete="off">
    <span class="input-group-btn">
        <button class="btn btn-default" type="button" onclick="showWechatImageDialog(this, ' . str_replace('"', '\'', json_encode($options)) . ');">选择图片</button>
    </span>
</div>';
    $s .=
        '<div class="input-group ' . $options['class_extra'] . '" style="margin-top:.5em;">
            <img src="' . $val . '" onerror="this.src=\'' . $default . '\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail" ' . ($options['extras']['image'] ? $options['extras']['image'] : '') . ' width="150" />
            <em class="close" style="position:absolute; top: 0px; right: -14px;" title="删除这张图片" onclick="deleteImage(this)">×</em>
        </div>';
    if (!empty($media_data) && 'temp' == $media_data['model'] && (time() - $media_data['createtime'] > 259200)) {
        $s .= '<span class="help-block"><b class="text-danger">该素材已过期 [有效期为3天]，请及时更新素材</b></span>';
    }

    return $s;
}

function tpl_form_field_wechat_multi_image($name, $value = '', $default = '', $options = array()) {
    global $_W;
    if (!$_W['acid'] || $_W['account']['level'] < 3) {
        $options['account_error'] = 1;
    } else {
        $options['acid'] = $_W['acid'];
    }
    if (empty($default)) {
        $default = './resource/images/nopic.jpg';
    }
    if (empty($options['class_extra'])) {
        $options['class_extra'] = '';
    }

    $options['direct'] = false;
    $options['multiple'] = true;
    $options['type'] = empty($options['type']) ? 'image' : $options['type'];
    $s = '';
    if (!defined('TPL_INIT_WECHAT_MULTI_IMAGE')) {
        $s = '
<script type="text/javascript">
    function uploadWechatMultiImage(elm) {
        var name = $(elm).next().val();
        util.wechat_image("", function(urls){
            $.each(urls, function(idx, url){
                $(elm).parent().parent().next().append(\'<div class="multi-item"><img onerror="this.src=\\\'./resource/images/nopic.jpg\\\'; this.title=\\\'图片未找到.\\\'" src="\'+url.url+\'" class="img-responsive img-thumbnail"><input type="hidden" name="\'+name+\'[]" value="\'+url.media_id+\'"><em class="close" title="删除这张图片" onclick="deleteWechatMultiImage(this)">×</em></div>\');
            });
        }, ' . json_encode($options) . ');
    }
    function deleteWechatMultiImage(elm){
        $(elm).parent().remove();
    }
</script>';
        define('TPL_INIT_WECHAT_MULTI_IMAGE', true);
    }

    $s .= <<<EOF
<div class="input-group">
    <input type="text" class="form-control" readonly="readonly" value="" placeholder="批量上传图片" autocomplete="off">
    <span class="input-group-btn">
        <button class="btn btn-default" type="button" onclick="uploadWechatMultiImage(this);">选择图片</button>
        <input type="hidden" value="{$name}" />
    </span>
</div>
<div class="input-group multi-img-details">
EOF;
    if (is_array($value) && count($value) > 0) {
        foreach ($value as $row) {
            $s .= '
<div class="multi-item">
    <img src="' . media2local($row) . '" onerror="this.src=\'./resource/images/nopic.jpg\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail">
    <input type="hidden" name="' . $name . '[]" value="' . $row . '" >
    <em class="close" title="删除这张图片" onclick="deleteWechatMultiImage(this)">×</em>
</div>';
        }
    }
    $s .= '</div>';

    return $s;
}

function tpl_form_field_wechat_voice($name, $value = '', $options = array()) {
    global $_W;
    if (!$_W['acid'] || $_W['account']['level'] < 3) {
        $options['account_error'] = 1;
    } else {
        $options['acid'] = $_W['acid'];
    }
    if (!empty($value)) {
        $media_data = (array) media2local($value, true);
        $val = $media_data['attachment'];
    }
    if (!is_array($options)) {
        $options = array();
    }
    $options['direct'] = true;
    $options['multiple'] = false;
    $options['type'] = 'voice';

    $s = '';
    if (!defined('TPL_INIT_WECHAT_VOICE')) {
        $s = '
<script type="text/javascript">
    function showWechatVoiceDialog(elm, options) {
        require(["util"], function(util){
            var btn = $(elm);
            var ipt = btn.parent().prev();
            var val = ipt.val();
            util.wechat_audio(val, function(url){
                if(url && url.media_id && url.url){
                    btn.prev().show();
                    ipt.val(url.media_id);
                    ipt.attr("media_id",url.media_id);
                    ipt.attr("url",url.url);
                    setWechatAudioPlayer();
                }
                if(url && url.media_id){
                    ipt.val(url.media_id);
                }
            } , ' . json_encode($options) . ');
        });
    }

    function setWechatAudioPlayer(){
        require(["jquery.jplayer"], function(){
            $(function(){
                $(".audio-player").each(function(){
                    $(this).prev().find("button").eq(0).click(function(){
                        var src = $(this).parent().prev().attr("url");
                        if($(this).find("i").hasClass("fa-stop")) {
                            $(this).parent().parent().next().jPlayer("stop");
                        } else {
                            if(src) {
                                $(this).parent().parent().next().jPlayer("setMedia", {mp3: util.tomedia(src)}).jPlayer("play");
                            }
                        }
                    });
                });

                $(".audio-player").jPlayer({
                    playing: function() {
                        $(this).prev().find("i").removeClass("fa-play").addClass("fa-stop");
                    },
                    pause: function (event) {
                        $(this).prev().find("i").removeClass("fa-stop").addClass("fa-play");
                    },
                    swfPath: "resource/components/jplayer",
                    supplied: "mp3"
                });
                $(".audio-player-media").each(function(){
                    $(this).next().find(".audio-player-play").css("display", $(this).val() == "" ? "none" : "");
                });
            });
        });
    }

    setWechatAudioPlayer();
</script>';
        echo $s;
        define('TPL_INIT_WECHAT_VOICE', true);
    }

    $s .= '
    <div class="input-group">
        <input type="text" value="' . $value . '" name="' . $name . '" class="form-control audio-player-media" autocomplete="off" ' . ($options['extras']['text'] ? $options['extras']['text'] : '') . '>
        <span class="input-group-btn">
            <button class="btn btn-default audio-player-play" type="button" style="display:none"><i class="fa fa-play"></i></button>
            <button class="btn btn-default" type="button" onclick="showWechatVoiceDialog(this,' . str_replace('"', '\'', json_encode($options)) . ');">选择媒体文件</button>
        </span>
    </div>
    <div class="input-group audio-player">
    </div>';
    if (!empty($media_data) && 'temp' == $media_data['model'] && (time() - $media_data['createtime'] > 259200)) {
        $s .= '<span class="help-block"><b class="text-danger">该素材已过期 [有效期为3天]，请及时更新素材</b></span>';
    }

    return $s;
}

function tpl_form_field_wechat_video($name, $value = '', $options = array()) {
    global $_W;
    if (!$_W['acid'] || $_W['account']['level'] < 3) {
        $options['account_error'] = 1;
    } else {
        $options['acid'] = $_W['acid'];
    }
    if (!empty($value)) {
        $media_data = (array) media2local($value, true);
        $val = $media_data['attachment'];
    }
    if (!is_array($options)) {
        $options = array();
    }
    if (empty($options['tabs'])) {
        $options['tabs'] = array('video' => 'active', 'browser' => '');
    }
    $options = array_elements(array('tabs', 'global', 'dest_dir', 'acid', 'error'), $options);
    $options['direct'] = true;
    $options['multi'] = false;
    $options['type'] = 'video';
    $s = '';
    if (!defined('TPL_INIT_WECHAT_VIDEO')) {
        $s = '
<script type="text/javascript">
    function showWechatVideoDialog(elm, options) {
        require(["util"], function(util){
            var btn = $(elm);
            var ipt = btn.parent().prev();
            var val = ipt.val();
            util.wechat_audio(val, function(url){
                if(url && url.media_id && url.url){
                    btn.prev().show();
                    ipt.val(url.media_id);
                    ipt.attr("media_id",url.media_id);
                    ipt.attr("url",url.url);
                }
                if(url && url.media_id){
                    ipt.val(url.media_id);
                }
            }, ' . json_encode($options) . ');
        });
    }

</script>';
        echo $s;
        define('TPL_INIT_WECHAT_VIDEO', true);
    }

    $s .= '
    <div class="input-group">
        <input type="text" value="' . $value . '" name="' . $name . '" class="form-control" autocomplete="off" ' . ($options['extras']['text'] ? $options['extras']['text'] : '') . '>
        <span class="input-group-btn">
            <button class="btn btn-default" type="button" onclick="showWechatVideoDialog(this,' . str_replace('"', '\'', json_encode($options)) . ');">选择媒体文件</button>
        </span>
    </div>
    <div class="input-group audio-player">
    </div>';
    if (!empty($media_data) && 'temp' == $media_data['model'] && (time() - $media_data['createtime'] > 259200)) {
        $s .= '<span class="help-block"><b class="text-danger">该素材已过期 [有效期为3天]，请及时更新素材</b></span>';
    }

    return $s;
}

/*
 * 门店类目选择三级联动
 * */
function tpl_form_field_location_category($name, $values = array(), $del = false) {
    $html = '';
    if (!defined('TPL_INIT_LOCATION_CATEGORY')) {
        $html .= '
        <script type="text/javascript">
            require(["location"], function(loc){
                $(".tpl-location-container").each(function(){

                    var elms = {};
                    elms.cate = $(this).find(".tpl-cate")[0];
                    elms.sub = $(this).find(".tpl-sub")[0];
                    elms.clas = $(this).find(".tpl-clas")[0];
                    var vals = {};
                    vals.cate = $(elms.cate).attr("data-value");
                    vals.sub = $(elms.sub).attr("data-value");
                    vals.clas = $(elms.clas).attr("data-value");
                    loc.render(elms, vals, {withTitle: true});
                });
            });
        </script>';
        define('TPL_INIT_LOCATION_CATEGORY', true);
    }
    if (empty($values) || !is_array($values)) {
        $values = array('cate' => '', 'sub' => '', 'clas' => '');
    }
    if (empty($values['cate'])) {
        $values['cate'] = '';
    }
    if (empty($values['sub'])) {
        $values['sub'] = '';
    }
    if (empty($values['clas'])) {
        $values['clas'] = '';
    }
    $html .= '
        <div class="row row-fix tpl-location-container">
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                <select name="' . $name . '[cate]" data-value="' . $values['cate'] . '" class="form-control tpl-cate">
                </select>
            </div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                <select name="' . $name . '[sub]" data-value="' . $values['sub'] . '" class="form-control tpl-sub">
                </select>
            </div>
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                <select name="' . $name . '[clas]" data-value="' . $values['clas'] . '" class="form-control tpl-clas">
                </select>
            </div>';
    if ($del) {
        $html .= '
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="padding-top:5px">
                <a title="删除" onclick="$(this).parents(\'.tpl-location-container\').remove();return false;"><i class="fa fa-times-circle"></i></a>
            </div>
        </div>';
    } else {
        $html .= '</div>';
    }

    return $html;
}

/*
 * 百度富文本编辑器
 * @param $id 表单input名称
 * @param $value 表单textarea值
 * @return string
*/
function tpl_ueditor($id, $value = '', $options = array()) {
    global $_W;
    $options['uniacid'] = isset($options['uniacid']) ? intval($options['uniacid']) : $_W['uniacid'];
    if (defined('SYSTEM_WELCOME_MODULE')) {
        $options['uniacid'] = 0;
    }
    $options['global'] = empty($options['global']) ? '' : $options['global'];
    $options['height'] = empty($options['height']) ? 200 : $options['height'];
    $options['allow_upload_video'] = isset($options['allow_upload_video']) ? $options['allow_upload_video'] : true;

    $s = '';
    $s .= !empty($id) ? "<textarea id=\"{$id}\" name=\"{$id}\" type=\"text/plain\" style=\"height:{$options['height']}px;\">{$value}</textarea>" : '';
    $s .= "
    <script type=\"text/javascript\">
        require(['util'], function(util){
            util.editor('" . ($id ? $id : '') . "', {
            uniacid : {$options['uniacid']}, 
            global : '" . $options['global'] . "', 
            height : {$options['height']}, 
            dest_dir : '" . (!empty($options['dest_dir']) ? $options['dest_dir'] : '') . "',
            image_limit : " . (intval($GLOBALS['_W']['setting']['upload']['image']['limit']) * 1024) . ',
            allow_upload_video : ' . ($options['allow_upload_video'] ? 'true' : 'false') . ',
            audio_limit : ' . (intval($GLOBALS['_W']['setting']['upload']['audio']['limit']) * 1024) . ",
            callback : ''
            });
        });
    </script>";

    return $s;
}
