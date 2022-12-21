<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Wxapp;

class Versions extends \We7Table {
    protected $tableName = 'wxapp_versions';
    protected $primaryKey = 'id';
    protected $field = array(
        'uniacid',
        'version',
        'description',
        'modules',
        'quickmenu',
        'createtime',
        'appjson',
        'default_appjson',
        'use_default',
        'type',
        'entry_id',
        'tominiprogram',
    );
    protected $default = array(
        'uniacid' => '',
        'version' => '',
        'description' => '',
        'modules' => '',
        'quickmenu' => '',
        'createtime' => '',
        'appjson' => '',
        'default_appjson' => '',
        'use_default' => 1,
        'type' => 0,
        'entry_id' => 0,
        'tominiprogram' => '',
    );

    public function getByAccountType($account_type) {
        $result = $this->query
            ->select('w.*')
            ->from($this->tableName, 'w')
            ->leftjoin('account', 'a')
            ->on('a.uniacid', 'w.uniacid')
            ->where('a.type', $account_type)
            ->get();
        $result = $this->dataunserializer($result);
        return $result;
    }
    public function getByUniacidAndVersion($uniacid, $version) {
        $result = $this->query->where('uniacid', $uniacid)->where('version', $version)->get();
        if (empty($result)) {
            return array();
        }
        $result = $this->dataunserializer($result);
        return $result;
    }

    public function getAllByUniacid($uniacid) {
        $result = $this->where('uniacid', $uniacid)->orderby(array('id' => 'DESC'))->getall();
        if (empty($result)) {
            return array();
        }
        foreach ($result as $key => $row) {
            $result[$key] = $this->dataunserializer($row);
        }
        return $result;
    }
    public function dataunserializer($data) {
        $data['modules'] = iunserializer($data['modules']);
        $data['quickmenu'] = iunserializer($data['quickmenu']);
        return $data;
    }
}
