<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Music;

class Reply extends \We7Table {
    protected $tableName = 'music_reply';
    protected $primaryKey = 'id';
    protected $field = array(
        'rid',
        'title',
        'description',
        'url',
        'hqurl'
    );
    protected $default = array(
        'rid' => '',
        'title' => '',
        'description' => '',
        'url' => '',
        'hqurl' => ''
    );
}
