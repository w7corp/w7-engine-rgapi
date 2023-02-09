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
 * 图文消息
 */
class NewsItem extends Message
{
    protected $type = 'news';

    /** @var string */
    protected $title = '';

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $url = '';

    /** @var string */
    protected $picUrl = '';
        
    /**
     * @param string $title       图文消息的标题
     * @param string $description 图文消息的描述
     * @param string $url         图文消息被点击后跳转的链接
     * @param string $pic_url     图文消息的图片链接，支持JPG、PNG格式，较好的效果为大图640*320，小图80*80
     */
    public function __construct(
        string $title = '',
        string $description = '',
        string $url = '',
        string $pic_url = ''
    ) {
        $this->title       = $this;
        $this->description = $description;
        $this->url         = $url;
        $this->picUrl      = $pic_url;
    }

    public function toArray(): array
    {
        return array_filter([
            'title'       => $this->title,
            'description' => $this->description,
            'url'         => $this->url,
            'picurl'      => $this->picUrl
        ]);
    }

    public function toXmlArray(): array
    {
        return array_filter([
            'Title'       => $this->title,
            'Description' => $this->description,
            'Url'         => $this->url,
            'PicUrl'      => $this->picUrl
        ]);
    }
}
