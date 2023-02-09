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
 * 音乐消息
 */
class Music extends Message
{
    /** @var string  */
    protected $type = 'music';

    /** @var string  */
    protected $musicUrl;

    /** @var string  */
    protected $hqMusicUrl = '';

    /** @var string  */
    protected $thumbMediaId = '';

    /** @var string  */
    protected $title = '';

    /** @var string  */
    protected $description = '';

    /**
     * @param string $music_url      音乐链接
     * @param string $hq_music_url   高质量音乐链接，WIFI环境优先使用该链接播放音乐
     * @param string $thumb_media_id 缩略图的媒体id，通过素材管理中的接口上传多媒体文件，得到的id
     * @param string $title          音乐标题
     * @param string $description    音乐描述
     */
    public function __construct(
        string $music_url,
        string $hq_music_url = '',
        string $thumb_media_id = '',
        string $title = '',
        string $description = '',
    ) {
        $this->musicUrl     = $music_url;
        $this->hqMusicUrl   = $hq_music_url;
        $this->thumbMediaId = $thumb_media_id;
        $this->title        = $title;
        $this->description  = $description;
    }

    public function toArray(): array
    {
        return array_filter([
            'musicurl'       => $this->musicUrl,
            'thumb_media_id' => $this->thumbMediaId,
            'hqmusicurl'     => $this->hqMusicUrl,
            'title'          => $this->title,
            'description'    => $this->description,
        ]);
    }

    public function toXmlArray(): array
    {
        return [self::studly($this->getType()) => array_filter([
            'MusicUrl'     => $this->musicUrl,
            'ThumbMediaId' => $this->thumbMediaId,
            'HQMusicUrl'   => $this->hqMusicUrl,
            'Title'        => $this->title,
            'Description'  => $this->description,
        ])];
    }
}
