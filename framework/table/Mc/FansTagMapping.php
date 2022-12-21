<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Mc;

class FansTagMapping extends \We7Table {
    protected $tableName = 'mc_fans_tag_mapping';
    protected $primaryKey = 'id';
    protected $field = array(
        'fanid',
        'tagid',
    );
    protected $default = array(
        'fanid' => '',
        'tagid' => '',
    );
}
