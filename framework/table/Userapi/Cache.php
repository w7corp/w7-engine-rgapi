<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Userapi;

class Cache extends \We7Table {
    protected $tableName = 'userapi_cache';
    protected $primaryKey = 'id';
    protected $field = array(
        'key',
        'content',
        'lastupdate',

    );
    protected $default = array(
        'key' => '',
        'content' => '',
        'lastupdate' => '',

    );
}
