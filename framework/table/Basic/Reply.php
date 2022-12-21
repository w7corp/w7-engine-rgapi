<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Basic;

class Reply extends \We7Table {
    protected $tableName = 'basic_reply';
    protected $primaryKey = 'id';
    protected $field = array(
        'rid',
        'content'
    );
    protected $default = array(
        'rid' => '',
        'content' => ''
    );
}
