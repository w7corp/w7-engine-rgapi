<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Core;

class MenuShortcut extends \We7Table {
    protected $tableName = 'core_menu_shortcut';
    protected $primaryKey = 'id';
    protected $field = array(
        'uid',
        'uniacid',
        'modulename',
        'displayorder',
        'position',
        'updatetime',

    );
    protected $default = array(
        'uid' => '',
        'uniacid' => '',
        'modulename' => '',
        'displayorder' => '0',
        'position' => '',
        'updatetime' => '',

    );

    public function getCurrentModuleMenuPluginList($main_module) {
        global $_W;
        $position = 'module_' . $main_module . '_menu_plugin_shortcut';
        return $this->where(array('uid' => $_W['uid'], 'uniacid' => $_W['uniacid'], 'position' => $position))->getall('modulename');
    }
}
