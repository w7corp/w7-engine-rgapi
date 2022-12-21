<?php
/**
 * 图片处理类.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

/**
 *  用法示例:
 *  Image::create('/a.jpg')->resize(50, 50)->crop(10, 5, 4)->saveTo('/b.jpg')
 * Class Image.
 */
class Image {
    private $src;
    private $actions = array(); //操作数组 支持resize crop
    // resize 数据
    private $resize_width = 0;
    private $resize_height = 0;

    private $image = null;
    private $imageinfo = array();
    //裁剪数据
    //裁剪的宽度
    private $crop_width = 0;
    //裁剪的高度
    private $crop_height = 0;
    // 裁剪的位置 //9宫格
    private $crop_position = 1;

    private $ext = '';

    public function __construct($src) {
        $this->src = $src;
        $this->ext = pathinfo($src, PATHINFO_EXTENSION);
    }

    public static function create($src) {
        return new self($src);
    }

    public function resize($width = 0, $height = 0) {
        if ($width > 0 || $height > 0) {
            $this->actions[] = 'resize';
        }
        if ($width > 0 && 0 == $height) {
            $height = $width;
        }
        if ($height > 0 && 0 == $width) {
            $width = $height;
        }
        $this->resize_width = $width;
        $this->resize_height = $height;

        return $this;
    }

    public function crop($width = 400, $height = 300, $position = 1) {
        if ($width > 0 || $height > 0) {
            $this->actions[] = 'crop';
        }
        if ($width > 0 && 0 == $height) {
            $height = $width;
        }
        if ($height > 0 && 0 == $width) {
            $width = $height;
        }
        $this->crop_width = $width;
        $this->crop_height = $height;
        //9宫格裁剪
        $this->crop_position = min(intval($position), 9);

        return $this;
    }

    public function getExt() {
        return in_array($this->ext, array('jpg', 'jpeg', 'png', 'gif')) ? $this->ext : 'jpeg';
    }

    public function isPng() {
        return file_is_image($this->src) && 'png' == $this->getExt();
    }

    public function isJPEG() {
        return file_is_image($this->src) && in_array($this->getExt(), array('jpg', 'jpeg'));
    }

    public function isGif() {
        return file_is_image($this->src) && 'gif' == $this->getExt();
    }

    /**
     *  保存.
     *
     * @param $path
     * @param $quality 0 不压缩 压缩比 0--100 gif 不压缩
     *
     * @since version
     */
    public function saveTo($path, $quality = 0) {
        $path = safe_gpc_path($path);
        if (empty($path)) {
            return false;
        }
        $result = $this->handle();
        if (!$result) {
            return false;
        }
        $ext = $this->getExt();
        if ('jpg' == $ext) {
            $ext = 'jpeg';
        }
        $func = 'image' . $ext;
        $real_quality = $this->realQuality($quality);
        $saved = false;
        $image = $this->image();
        imagealphablending($image, false);
        imagesavealpha($image, true);
        if (empty($real_quality)) {
            $saved = $func($image, $path);
        } else {
            if (!$this->isGif()) {
                $saved = $func($image, $path, $real_quality);
            }
        }
        $this->destroy();

        return $saved ? $path : $saved;
    }

    private function realQuality($quality = null) {
        if (is_null($quality)) {
            return null;
        }
        // 不要让越界
        $quality = min($quality, 100);
        if ($this->isJPEG()) {
            return $quality;
        }
        if ($this->isPng()) {
            return round(abs((100 - $quality) / 11.111111));
        }

        return null;
    }

    protected function handle() {
        //创建资源
        if (!function_exists('gd_info')) {
            return false;
        }
        $this->image = $this->createResource();
        if (!$this->image) {
            return false;
        }
        $this->imageinfo = getimagesize($this->src);
        $actions = array_unique($this->actions);
        $src_image = $this->image;
        foreach ($actions as $action) {
            $method = 'do' . ucfirst($action);
            $src_image = $this->{$method}($src_image);
        }
        $this->image = $src_image;

        return true;
    }

    /**
     * 裁剪图片.
     */
    protected function doCrop($src_image) {
        list($dst_x, $dst_y) = $this->getCropDestPoint();
        if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
            $new_image = imagecrop($src_image, array('x' => $dst_x, 'y' => $dst_y, 'width' => $this->crop_width, 'height' => $this->crop_height));
            imagedestroy($src_image);
        } else {
            $new_image = $this->modify(
                $src_image,
                $this->crop_width,
                $this->crop_height,
                $this->crop_width,
                $this->crop_height,
                0,
                0,
                $dst_x,
                $dst_y
            );
        }
        $this->imageinfo[0] = $this->crop_width;
        $this->imageinfo[1] = $this->crop_height;

        return $new_image;
    }

    /**
     *  缩略图.
     *
     * @param $src_image
     *
     * @return resource
     */
    protected function doResize($src_image) {
        $newimage = $this->modify(
            $src_image,
            $this->resize_width,
            $this->resize_height,
            $this->imageinfo[0],
            $this->imageinfo[1]
        );
        $this->imageinfo[0] = $this->resize_width;
        $this->imageinfo[1] = $this->resize_height;

        return $newimage;
    }

    /**
     *  修改图片.
     *
     * @param $src_image
     * @param $width
     * @param $height
     * @param $src_width
     * @param $src_height
     * @param int $dst_x
     * @param int $dst_y
     * @param int $src_x
     * @param int $src_y
     *
     * @return resource
     */
    protected function modify(
        $src_image,
        $width,
        $height,
        $src_width,
        $src_height,
        $dst_x = 0,
        $dst_y = 0,
        $src_x = 0,
        $src_y = 0
    ) {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagecopyresampled(
            $image,
            $src_image,
            $dst_x,
            $dst_y,
            $src_x,
            $src_y,
            $width,
            $height,
            $src_width,
            $src_height
        );
        imagedestroy($src_image);

        return $image;
    }

    private function image() {
        return $this->image;
    }

    private function destroy() {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }

    private function createResource() {
        if (file_exists($this->src) && !is_readable($this->src)) {
            return null;
        }
        if ($this->isPng()) {
            return imagecreatefrompng($this->src);
        }
        if ($this->isJPEG()) {
            return imagecreatefromjpeg($this->src);
        }
        if ($this->isGif()) {
            return imagecreatefromgif($this->src);
        }

        return null;
    }

    /**
     * 转为base64.
     *
     * @param string $prefix
     *
     * @return string
     *
     * @since version
     */
    public function toBase64($prefix = 'data:image/%s;base64,') {
        $filename = tempnam('tmp', 'base64');
        $prefix = sprintf($prefix, $this->getExt());
        $result = $this->saveTo($filename);
        if (!$result) {
            return false;
        }
        $content = file_get_contents($filename);
        $base64 = base64_encode($content);
        unlink($filename);

        return $prefix . $base64;
    }

    /**
     * 获取元素裁剪 坐标.
     *
     * @return array
     */
    private function getCropDestPoint() {
        //图片的原始宽高
        $s_width = $this->imageinfo[0];
        $s_height = $this->imageinfo[1];
        $dst_x = $dst_y = 0;
        // 处理裁剪的宽高
        if ('0' == $this->crop_width || $this->crop_width > $s_width) {
            $this->crop_width = $s_width;
        }
        if ('0' == $this->crop_height || $this->crop_height > $s_height) {
            $this->crop_height = $s_height;
        }
        switch ($this->crop_position) {
            case 0:
            case 1:
                $dst_x = 0;
                $dst_y = 0;
                break;
            case 2:
                $dst_x = ($s_width - $this->crop_width) / 2;
                $dst_y = 0;
                break;
            case 3:
                $dst_x = $s_width - $this->crop_width;
                $dst_y = 0;
                break;
            case 4:
                $dst_x = 0;
                $dst_y = ($s_height - $this->crop_height) / 2;
                break;
            case 5:
                $dst_x = ($s_width - $this->crop_width) / 2;
                $dst_y = ($s_height - $this->crop_height) / 2;
                break;
            case 6:
                $dst_x = $s_width - $this->crop_width;
                $dst_y = ($s_height - $this->crop_height) / 2;
                break;
            case 7:
                $dst_x = 0;
                $dst_y = $s_height - $this->crop_height;
                break;
            case 8:
                $dst_x = ($s_width - $this->crop_width) / 2;
                $dst_y = $s_height - $this->crop_height;
                break;
            case 9:
                $dst_x = $s_width - $this->crop_width;
                $dst_y = $s_height - $this->crop_height;
                break;
            default:
                $dst_x = 0;
                $dst_y = 0;
        }
        if ($this->crop_width == $s_width) {
            $dst_x = 0;
        }
        if ($this->crop_height == $s_height) {
            $dst_y = 0;
        }

        return array(intval($dst_x), intval($dst_y));
    }
}
