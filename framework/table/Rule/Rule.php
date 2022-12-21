<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Rule;

class Rule extends \We7Table {
    protected $tableName = 'rule';
    protected $primaryKey = 'id';
    protected $field = array(
        'uniacid',
        'name',
        'module',
        'containtype',
        'displayorder',
        'status',
    );
    protected $default = array(
        'uniacid' => '0',
        'name' => '',
        'module' => '',
        'containtype' => '',
        'displayorder' => '0',
        'status' => '1',
    );
}
