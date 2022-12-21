<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Mc;

class MemberAddress extends \We7Table {
    protected $tableName = 'mc_member_address';
    protected $primaryKey = 'id';
    protected $field = array(
        'uniacid',
        'uid',
        'username',
        'mobile',
        'zipcode',
        'province',
        'city',
        'district',
        'address',
        'isdefault'
    );
    protected $default = array(
        'uniacid' => '',
        'uid' => '',
        'username' => '',
        'mobile' => '',
        'zipcode' => '',
        'province' => '',
        'city' => '',
        'district' => '',
        'address' => '',
        'isdefault' => 0
    );
}
