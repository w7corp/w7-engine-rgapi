<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Core;

class Paylog extends \We7Table {
    protected $tableName = 'core_paylog';
    protected $primaryKey = 'id';
    protected $field = array(
        'type',
        'uniacid',
        'acid',
        'openid',
        'uniontid',
        'tid',
        'fee',
        'status',
        'module',
        'tag',
        'is_usecard',
        'card_type',
        'card_id',
        'card_fee',
        'encrypt_code',
        'is_wish',
        'coupon'
    );
    protected $default = array(
        'type' => '',
        'uniacid' => 0,
        'acid' => 0,
        'openid' => '',
        'uniontid' => '',
        'tid' => '',
        'fee' => 0,
        'status' => '',
        'module' => '',
        'tag' => '',
        'is_usecard' => 0,
        'card_type' => '',
        'card_id' => '',
        'card_fee' => '',
        'encrypt_code' => '',
        'is_wish' => 0,
        'coupon' => ''
    );

    public function searchWithUniacid($uniacid) {
        return $this->query->where('uniacid', $uniacid);
    }

    public function searchWithModule($module) {
        return $this->query->where('module', $module);
    }

    public function searchWithTid($tid) {
        return $this->query->where('tid', $tid);
    }
}
