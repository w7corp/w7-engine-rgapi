<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Core;

class Settings extends \We7Table {
    protected $tableName = 'core_settings';
    protected $primaryKey = 'key';
    protected $field = array(
        'key',
        'value',
    );
    protected $default = array(
        'key' => '',
        'value' => '',
    );
}
