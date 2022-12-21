<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Modules;

class Plugin extends \We7Table {
    protected $tableName = 'modules_plugin';
    protected $primaryKey = 'id';
    protected $field = array(
        'name',
        'main_module',
    );
    protected $default = array(
        'name' => '',
        'main_module' => '',
    );

    public function getAllByNameOrMainModule($module_name) {
        return $this->where('name', $module_name)->whereor('main_module', $module_name)->getall();
    }

    public function deleteByMainModule($module_name) {
        return $this->query->where('main_module', $module_name)->delete();
    }
    
    public function getPluginExists($main_module, $plugin_name) {
        return $this->query->where('main_module', $main_module)->where('name', $plugin_name)->exists();
    }
}
