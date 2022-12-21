<?php
/**
 * 默认回复处理类
 * 优先回复“优先级”大于默认级别的模块。
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/framework/builtin/default/processor.php : v b352eceaaed4 : 2015/01/09 03:19:15 : RenChao $
 */
defined('IN_IA') or exit('Access Denied');

class DefaultModuleProcessor extends WeModuleProcessor {
	public function respond() {
		global $_W, $engine;
		if ('trace' == $this->message['type']
			|| 'view_miniprogram' == $this->message['event']
			|| 'VIEW' == $this->message['event']
		) {
			return $this->respText('');
		}
		$setting = uni_setting($_W['uniacid'], array('default'));
		if (!empty($setting['default'])) {
			$flag = array('image' => 'url', 'link' => 'url', 'text' => 'content');
			$message = $this->message;
			$message['type'] = 'text';
			$message['content'] = $setting['default'];
			$message['redirection'] = true;
			$message['source'] = 'default';
			$message['original'] = $this->message[$flag[$this->message['type']]];
			$pars = $engine->analyzeText($message);
			if (is_array($pars)) {
				return array('params' => $pars);
			}
		}
	}
}
