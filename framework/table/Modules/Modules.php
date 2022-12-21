<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Modules;

class Modules extends \We7Table {
    protected $tableName = 'modules';
    protected $primaryKey = 'mid';
    protected $field = array(
        'name',
        'type',
        'title',
        'title_initial',
        'version',
        'ability',
        'description',
        'author',
        'url',
        'settings',
        'subscribes',
        'handles',
        'isrulefields',
        'permissions',
        'wxapp_support',
        'account_support',
        'welcome_support',
        'webapp_support',
        'oauth_type',
        'phoneapp_support',
        'aliapp_support',
        'logo',
        'baiduapp_support',
        'toutiaoapp_support',
        'display_order',
        'createtime',
    );
    protected $default = array(
        'name' => '',
        'type' => '',
        'title' => '',
        'title_initial' => '',
        'version' => '',
        'ability' => '',
        'description' => '',
        'author' => '',
        'url' => '',
        'settings' => '0',
        'subscribes' => '',
        'handles' => '',
        'isrulefields' => '0',
        'permissions' => '',
        'wxapp_support' => '1',
        'account_support' => '1',
        'welcome_support' => '1',
        'webapp_support' => '1',
        'oauth_type' => '1',
        'phoneapp_support' => '1',
        'aliapp_support' => '1',
        'logo' => '',
        'baiduapp_support' => '1',
        'toutiaoapp_support' => '1',
        'display_order' => 0,
        'createtime' => 0,
    );

    public function getByName($module_name) {
        $result = $this->query->where('name', $module_name)->get();
        if (!empty($result['subscribes'])) {
            $result['subscribes'] = iunserializer($result['subscribes']);
        }
        if (!empty($result['handles'])) {
            $result['handles'] = iunserializer($result['handles']);
        }
        return $result;
    }

    public function deleteByName($module_name) {
        return $this->query->where('name', $module_name)->delete();
    }

    public function getByHasSubscribes() {
        return $this->query->select('name', 'subscribes')->where('subscribes !=', '')->getall();
    }

    public function searchWithType($type, $method = '=') {
        if ($method == '=') {
            $this->query->where('type', $type);
        } else {
            $this->query->where('type <>', $type);
        }
        return $this;
    }
}
