<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Wechat;

class Attachment extends \We7Table {
    protected $tableName = 'wechat_attachment';
    protected $primaryKey = 'id';
    protected $field = array(
        'uniacid',
        'acid',
        'uid',
        'filename',
        'attachment',
        'media_id',
        'width',
        'height',
        'type',
        'model',
        'tag',
        'createtime',
        'module_upload_dir',
        'maxpcaccount',
        'group_id',
        'displayorder',
        'publish_id',
        'publish_status',
        'article_id',
    );
    protected $default = array(
        'uniacid' => '',
        'acid' => '',
        'uid' => '',
        'filename' => '',
        'attachment' => '',
        'media_id' => '',
        'width' => '',
        'height' => '',
        'type' => '',
        'model' => '',
        'tag' => '',
        'createtime' => '',
        'module_upload_dir' => '',
        'maxpcaccount' => '0',
        'group_id' => '0',
        'displayorder' => '0',
        'publish_id',
        'publish_status',
        'article_id',
    );

    public function getByMediaId($media_id) {
        return $this->query->where('media_id', $media_id)->get();
    }

    public function deleteById($id) {
        return $this->where('id', $id)->delete();
    }

    public function searchWithUniacid($uniacid) {
        return $this->query->where('uniacid', $uniacid);
    }

    public function searchWithUid($uid) {
        return $this->query->where('uid', $uid);
    }

    public function searchWithUploadDir($module_upload_dir) {
        return $this->query->where(array('module_upload_dir' => $module_upload_dir));
    }

    public function searchWithType($type) {
        return $this->query->where(array('type' => $type));
    }

    public function searchWithArticleId($where) {
        return $this->query->where($where);
    }

    public function searchWithModel($model) {
        return $this->query->where(array('model' => $model));
    }

    public function searchWithGroupId($groupid) {
        return $this->query->where(array('group_id =' => $groupid));
    }

    public function searchWithTime($start_time, $end_time) {
        return $this->query->where(array('createtime >=' => $start_time))->where(array('createtime <=' => $end_time));
    }

    public function SearchWithUserAndUniAccount() {
        return $this->query->from($this->tableName, 'a')
            ->leftjoin('users', 'b')
            ->on('b.uid', 'a.uid')
            ->leftjoin('uni_account', 'c')
            ->on('a.uniacid', 'c.uniacid');
    }
}
