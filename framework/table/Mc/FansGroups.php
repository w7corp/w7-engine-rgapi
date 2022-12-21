<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Mc;

class FansGroups extends \We7Table {
    protected $tableName = 'mc_fans_groups';
    protected $primaryKey = 'id';
    protected $field = array(
        'uniacid',
        'acid',
        'groups',
    );
    protected $default = array(
        'uniacid' => '0',
        'acid' => '0',
        'groups' => '',
    );

    public function getByUniacid($uniacid) {
        $result = $this->query->where('uniacid', $uniacid)->get();
        if (!empty($result)) {
            $result['groups'] = iunserializer($result['groups']);
        }
        return $result;
    }
}
