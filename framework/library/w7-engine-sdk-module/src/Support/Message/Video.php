<?php

/**
 * WeEngine System
 *
 * (c) We7Team 2022 <https://www.w7.cc>
 *
 * This is not a free software
 * Using it under the license terms
 * visited https://www.w7.cc for more details
 */

namespace W7\Sdk\Module\Support\Message;

/**
 * 视频消息
 */
class Video extends Message
{
    protected $type = 'video';

    /** @var string */
    protected $mediaId;

    /** @var string */
    protected $thumbMediaId = '';

    /** @var string */
    protected $title = '';

    /** @var string */
    protected $description = '';

    /**
     * @param string $media_id       通过素材管理中的接口上传多媒体文件，得到的id
     * @param string $thumb_media_id 视频封面媒体ID
     * @param string $title          视频消息的标题
     * @param string $description    视频消息的描述
     */
    public function __construct(
        string $media_id,
        string $thumb_media_id = '',
        string $title = '',
        string $description = '',
    ) {
        $this->mediaId      = $media_id;
        $this->thumbMediaId = $thumb_media_id;
        $this->title        = $title;
        $this->description  = $description;
    }

    public function toArray(): array
    {
        return array_filter([
            'media_id'       => $this->mediaId,
            'thumb_media_id' => $this->thumbMediaId,
            'title'          => $this->title,
            'description'    => $this->description,
        ]);
    }

    public function toXmlArray(): array
    {
        return [
            'MsgType'                      => $this->getType(),
            self::studly($this->getType()) => array_filter([
            'MediaId'      => $this->mediaId,
            'ThumbMediaId' => $this->thumbMediaId,
            'Title'        => $this->title,
            'Description'  => $this->description,
        ])];
    }
}
