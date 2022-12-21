<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Uni;

class Account extends \We7Table {
    protected $tableName = 'uni_account';
    protected $primaryKey = 'uniacid';
    protected $field = array(
        'type',
        'isconnect',
        'createtime'
    );
    protected $default = array(
        'type' => 0,
        'isconnect' => 0,
        'createtime' => 0,
    );
}
