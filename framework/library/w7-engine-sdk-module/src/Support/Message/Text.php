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
 * 文本消息
 */
class Text extends Message
{
    protected $type = 'text';

    /** @var string */
    protected $content;

    /**
     * @param string $content 回复的消息内容（换行：在content中能够换行，微信客户端就支持换行显示）
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * @return array{
     *     content: string
     * }
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content
        ];
    }

    /**
     * @return array{
     *     Content: string
     * }
     */
    public function toXmlArray(): array
    {
        return [
            'MsgType' => $this->getType(),
            'Content' => $this->content
        ];
    }
}
