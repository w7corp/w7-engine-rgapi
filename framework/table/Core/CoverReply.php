<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Core;

class CoverReply extends \We7Table {
    protected $tableName = 'cover_reply';
    protected $primaryKey = 'id';
    protected $field = array(
        'uniacid',
        'multiid',
        'rid',
        'module',
        'do',
        'title',
        'description',
        'thumb',
        'url',
    );
    protected $default = array(
        'uniacid' => '',
        'multiid' => '0',
        'rid' => '',
        'module' => '',
        'do' => '',
        'title' => '',
        'description' => '',
        'thumb' => '',
        'url' => '',
    );

    public function getByModuleAndUniacid($module, $uniacid) {
        $result = $this->query->where('module', $module)->where('uniacid', $uniacid)->get();
        return $result;
    }

    public function searchWithMultiid($multiid) {
        return $this->query->where('multiid', $multiid);
    }
}
