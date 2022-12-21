<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Rule;

class Keyword extends \We7Table {
    protected $tableName = 'rule_keyword';
    protected $primaryKey = 'id';
    protected $field = array(
        'rid',
        'uniacid',
        'module',
        'content',
        'type',
        'displayorder',
        'status',
    );
    protected $default = array(
        'rid' => '0',
        'uniacid' => '0',
        'module' => '',
        'content' => '',
        'type' => '1',
        'displayorder' => '1',
        'status' => '1',
    );

    public function getByUniacidAndContent($uniacid, $content, $status = 1) {
        return $this->where(array(
            'uniacid' => $uniacid,
            'content' => $content,
            'status' => $status,
        ))->get();
    }

    public function searchWithCoverReply() {
        return $this->query->from($this->tableName, 'a')
            ->leftjoin('cover_reply', 'b')
            ->on(array(
                'a.rid' => 'b.rid'
            ));
    }
}
