<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Voice;

class Reply extends \We7Table {
    protected $tableName = 'voice_reply';
    protected $primaryKey = 'id';
    protected $field = array(
        'rid',
        'title',
        'mediaid',
        'createtime',
    );
    protected $default = array(
        'rid' => '',
        'title' => '',
        'mediaid' => '',
        'createtime' => '',
    );
}
