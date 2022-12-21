<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Core;

class Performance extends \We7Table {
    protected $tableName = 'core_performance';
    protected $primaryKey = 'id';
    protected $field = array(
        'type',
        'runtime',
        'runurl',
        'runsql',
        'createtime',
    );
    protected $default = array(
        'type' => '',
        'runtime' => '',
        'runurl' => '',
        'runsql' => '',
        'createtime' => '',
    );
}
