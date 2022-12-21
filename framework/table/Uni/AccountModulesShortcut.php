<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Uni;

class AccountModulesShortcut extends \We7Table {
    protected $tableName = 'uni_account_modules_shortcut';
    protected $primaryKey = 'id';
    protected $field = array(
        'title',
        'url',
        'icon',
        'module_name',

    );
    protected $default = array(
        'title' => '',
        'url' => '',
        'icon' => '',
        'module_name' => '',

    );

    public function saveShortcut($fill, $id = 0) {
        if (!empty($id)) {
            $this->where('id', $id);
        }
        return $this->fill($fill)->save();
    }

    public function getShortcutListByModule($module, $pageindex = 1, $pagesize = 15) {
        $list = $this->query->where(array('module_name' => $module))->page($pageindex, $pagesize)->getall();
        $total = $this->getLastQueryTotal();
        return array('lists' => $list,  'total' => $total);
    }

    public function getShortcutById($id) {
        return $this->where('id', $id)->get();
    }

    public function deleteShortcutById($id) {
        return $this->where('id', $id)->delete();
    }
}
