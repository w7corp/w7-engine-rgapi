<?php
/**
 * 收银台.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

class PaycenterModule extends WeModule {
	public $tablename = 'wxcard_reply';
	public $replies = array();

	public function fieldsFormDisplay($rid = 0) {
		if (!empty($rid)) {
			$replies = table($this->tablename)->where(array('rid' => $rid))->getall();
		}
		include $this->template('display');
	}

	public function fieldsFormValidate($rid = 0) {
		global $_GPC;
		$this->replies = @json_decode(htmlspecialchars_decode($_GPC['replies']), true);
		if (empty($this->replies)) {
			return '必须填写有效的回复内容.';
		}
		foreach ($this->replies as $k => &$row) {
			if (empty($row['cid']) || empty($row['card_id'])) {
				unset($k);
			}
		}
		if (empty($this->replies)) {
			return '必须填写有效的回复内容.';
		}

		return '';
	}

	public function fieldsFormSubmit($rid = 0) {
		global $_W, $_GPC;
		$rid = intval($rid);
		$rule_exists = table('rule')->getById($rid, $_W['uniacid']);
		if (empty($rule_exists)) {
			return false;
		}
		table($this->tablename)->where(array('rid' => $rid))->delete();
		foreach ($this->replies as $reply) {
			$data = array(
				'rid' => $rid,
				'title' => $reply['title'],
				'card_id' => $reply['card_id'],
				'cid' => $reply['cid'], //对应卡券表的id
				'brand_name' => $reply['brand_name'],
				'logo_url' => $reply['logo_url'],
				'success' => safe_gpc_string($_GPC['success']),
				'error' => safe_gpc_string($_GPC['error']),
			);
			table($this->tablename)->fill($data)->save();
		}

		return true;
	}

	public function ruleDeleted($rid = 0) {
		global $_W;
		$rid = intval($rid);
		$rule_exists = table('rule')->getById($rid, $_W['uniacid']);
		if (empty($rule_exists)) {
			return false;
		}
		table($this->tablename)->where(array('rid' => $rid))->delete();

		return true;
	}
}
