<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
namespace We7\Table\Attachment;

class Group extends \We7Table {
    protected $tableName = 'attachment_group';
    protected $primaryKey = 'id';
    protected $field = array(
        'name',
        'uniacid',
        'uid',
        'type',
    );
    protected $default = array(
        'name' => '',
        'uniacid' => 0,
        'uid' => 0,
        'type' => 0,
    );

    public function searchWithUniacidOrUid($uniacid, $uid) {
        if (empty($uniacid)) {
            $this->query->where('uid', $uid);
        } else {
            $this->query->where('uniacid', $uniacid);
        }
        return $this;
    }

    /**
     *  删除素材组数据
     * @param $uniacid
     * @return mixed
     */
    public function deleteByUniacid($uniacid) {
        return $this->where('uniacid', $uniacid)->delete();
    }
}
