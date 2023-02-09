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
 * 语音消息
 */
class Voice extends Message
{
    protected $type = 'voice';

    /** @var string */
    protected $mediaId;

    /**
     * @param string $media_id 通过素材管理中的接口上传多媒体文件，得到的id
     */
    public function __construct(string $media_id)
    {
        $this->mediaId = $media_id;
    }

    public function toArray(): array
    {
        return [
            'media_id' => $this->mediaId
        ];
    }

    public function toXmlArray(): array
    {
        return [
            'MsgType'                       => $this->getType(),
            $this->studly($this->getType()) => [
                'MediaId' => $this->mediaId
            ]
        ];
    }
}
