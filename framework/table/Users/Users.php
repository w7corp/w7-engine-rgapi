<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Users;

class Users extends \We7Table {
    protected $tableName = 'users';
    protected $primaryKey = 'uid';
    protected $field = array(
        'username',
        'avatar',
        'salt',
        'openid',
        'role_identity',
        'lastvisit',
        'lastip',
        'component_appid',
        'starttime',
    );
    protected $default = array(
        'username' => '',
        'avatar' => '',
        'salt' => '',
        'openid' => '',
        'role_identity' => '',
        'lastvisit' => '',
        'lastip' => '',
        'component_appid' => '',
        'starttime' => 0
    );
}
