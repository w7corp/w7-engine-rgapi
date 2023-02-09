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

class News extends Message
{
    protected $type = 'news';

    /** @var NewsItem[]  */
    protected $item = [];

    /**
     * @param NewsItem[] $item
     */
    public function __construct(array $item = [])
    {
        $this->item = $item;
    }

    public function toArray(): array
    {
        return [
            'articles' => array_map(function ($item) {
                return $item->toArray();
            }, $this->item)
        ];
    }

    public function toXmlArray(): array
    {
        return [
            'MsgType'      => $this->getType(),
            'ArticleCount' => count($this->item),
            'Articles'     => array_map(function ($item) {
                return $item->toXmlArray();
            }, $this->item)
        ];
    }
}
