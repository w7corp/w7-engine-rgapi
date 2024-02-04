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
    public function searchWithType($types = array()) {
        $this->query->where(array('b.type' => $types));
        return $this;
    }
    /**
     * 平台列表搜索
     * @param $expire_type 到期类型:1.到期expire;2.未到期unexpire;3.false
     * @param $fields
     * @param int $isdeleted
     * @return $this
     */
    public function searchAccount($expire_type, $fields, $isdeleted = 1, $uid = 0) {
        global $_W;
        $uid = empty($uid) ? $_W['uid'] : $uid;
        $valid_account_type = array(
            ACCOUNT_TYPE_OFFCIAL_NORMAL,
            ACCOUNT_TYPE_OFFCIAL_AUTH,
            ACCOUNT_TYPE_APP_NORMAL,
            ACCOUNT_TYPE_WEBAPP_NORMAL,
            ACCOUNT_TYPE_PHONEAPP_NORMAL,
            ACCOUNT_TYPE_APP_AUTH,
            ACCOUNT_TYPE_ALIAPP_NORMAL,
            ACCOUNT_TYPE_BAIDUAPP_NORMAL,
            ACCOUNT_TYPE_TOUTIAOAPP_NORMAL
        );
        $this->query->from('uni_account', 'a')
            ->select($fields)
            ->leftjoin('account', 'b')
            ->on(array('a.uniacid' => 'b.uniacid'))
            ->where('b.type IN ', $valid_account_type);
        
        if ($expire_type == 'expire') {
            $this->searchWithExprie();
        } elseif ($expire_type == 'unexpire') {
            $this->searchWithUnExprie();
        }
        return $this;
    }
    public function searchAccountList($expire = false, $isdeleted = 1, $fields = 'a.uniacid', $uid = 0) {
        $this->searchAccount($expire, $fields, $isdeleted, $uid);
        $this->query->groupby('a.uniacid');
        $list = $this->query->getall('uniacid');
        return $list;
    }
}
