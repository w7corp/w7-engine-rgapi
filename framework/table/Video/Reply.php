<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Video;

class Reply extends \We7Table {
    protected $tableName = 'video_reply';
    protected $primaryKey = 'id';
    protected $field = array(
        'rid',
        'title',
        'description',
        'mediaid',
        'createtime'
    );
    protected $default = array(
        'rid' => '',
        'title' => '',
        'description' => '',
        'mediaid' => '',
        'createtime' => '',
    );
}
