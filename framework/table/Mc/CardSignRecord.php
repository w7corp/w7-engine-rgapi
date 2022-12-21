<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Mc;

class CardSignRecord extends \We7Table {
    protected $tableName = 'mc_card_sign_record';
    protected $primaryKey = 'id';
    protected $field = array(
        'uniacid',
        'uid',
        'credit',
        'is_grant',
        'addtime',
    );
    protected $default = array(
        'uniacid' => 0,
        'uid' => 0,
        'credit' => 0,
        'is_grant' => 0,
        'addtime' => 0,
    );
}
