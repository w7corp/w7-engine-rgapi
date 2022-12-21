<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Mc;

class CardNotices extends \We7Table {
    protected $tableName = 'mc_card_notices';
    protected $primaryKey = 'id';
    protected $field = array(
        'uniacid',
        'uid',
        'type',
        'title',
        'thumb',
        'groupid',
        'content',
        'addtime',
    );
    protected $default = array(
        'uniacid' => 0,
        'uid' => 0,
        'type' => 1,
        'title' => '',
        'thumb' => '',
        'groupid' => 0,
        'content' => '',
        'addtime' => 0,
    );
}
