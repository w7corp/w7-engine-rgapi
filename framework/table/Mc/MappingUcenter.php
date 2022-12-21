<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Mc;

class MappingUcenter extends \We7Table {
    protected $tableName = 'mc_mapping_ucenter';
    protected $primaryKey = 'fanid';
    protected $field = array(
        'uniacid',
        'uid',
        'centeruid'
    );
    protected $default = array(
        'uniacid' => '',
        'uid' => '',
        'centeruid' => ''
    );
}
