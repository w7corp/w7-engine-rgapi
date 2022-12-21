<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Account;

class Account extends \We7Table {
    protected $tableName = 'account';
    protected $primaryKey = 'acid';
    protected $field = array(
        'uniacid',
        'name',
        'logo',
        'type',
        'app_id',
        'aes_key',
        'token',
    );
    protected $default = array(
        'uniacid' => 0,
        'name' => '',
        'logo' => '',
        'type' => '',
        'app_id' => '',
        'aes_key' => '',
        'token' => '',
    );

    public function getOrderByTypeAsc() {
        return $this->orderby('type asc')->get();
    }
    public function getByUniacid($uniacid) {
        return $this->where('uniacid', $uniacid)->get();
    }

    public function getUniAccountByAcid($acid) {
        return $this->query
            ->from($this->tableName, 'a')
            ->leftjoin('uni_account', 'u')
            ->on('a.uniacid', 'u.uniacid')
            ->where('a.acid', intval($acid))
            ->get();
    }

    public function getUniAccountByUniacid($uniacid) {
        return $this->query
            ->from($this->tableName, 'a')
            ->leftjoin('uni_account', 'u')
            ->on('a.uniacid', 'u.uniacid')
            ->where('a.uniacid', intval($uniacid))
            ->get();
    }
}
