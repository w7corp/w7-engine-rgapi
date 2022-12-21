<?php
/**
 * 粉丝管理模块订阅器.
 *
 * @author WeEngine Team
 */
defined('IN_IA') or exit('Access Denied');
class CoreModuleReceiver extends WeModuleReceiver {
	public function receive() {
		global $_W;
		if ('subscribe' == $this->message['event'] && !empty($this->message['ticket'])) {
			$sceneid = $this->message['scene'];
			$acid = $this->acid;
			$uniacid = $this->uniacid;
			$ticket = trim($this->message['ticket']);
			if (!empty($ticket)) {
				$qr = table('qrcode')
					->select(array('id', 'keyword', 'name', 'acid'))
					->where(array(
						'uniacid' => $uniacid,
						'ticket' => $ticket
					))
					->getall();
				if (!empty($qr)) {
					if (1 != count($qr)) {
						$qr = array();
					} else {
						$qr = $qr[0];
					}
				}
			}
			if (empty($qr)) {
				$sceneid = trim($this->message['scene']);
				$where = array(
					'uniacid' => $_W['uniacid']
				);
				if (is_numeric($sceneid)) {
					$where['qrcid'] = $sceneid;
				} else {
					$where['scene_str'] = $sceneid;
				}
				$qr = table('qrcode')
					->select(array('id', 'keyword', 'name', 'acid'))
					->where($where)
					->get();
			}
			$insert = array(
				'uniacid' => $_W['uniacid'],
				'acid' => $qr['acid'],
				'qid' => $qr['id'],
				'openid' => $this->message['from'],
				'type' => 1,
				'qrcid' => intval($sceneid),
				'scene_str' => $sceneid,
				'name' => $qr['name'],
				'createtime' => TIMESTAMP,
			);
			table('qrcode_stat')->fill($insert)->save();
		} elseif ('SCAN' == $this->message['event']) {
			$sceneid = trim($this->message['scene']);
			$where = array('uniacid' => $_W['uniacid']);
			if (is_numeric($sceneid)) {
				$where['qrcid'] = $sceneid;
			} else {
				$where['scene_str'] = $sceneid;
			}
			$row = table('qrcode')
				->select(array('id', 'keyword', 'name', 'acid'))
				->where($where)
				->get();
			$insert = array(
				'uniacid' => $_W['uniacid'],
				'acid' => $row['acid'],
				'qid' => $row['id'],
				'openid' => $this->message['from'],
				'type' => 2,
				'qrcid' => intval($sceneid),
				'scene_str' => $sceneid,
				'name' => $row['name'],
				'createtime' => TIMESTAMP,
			);
			//开启后只记录首次扫描
			if ($_W['setting']['qr_status']['status'] == 1) {
				$qrLog = table('qrcode_stat')->where(array('uniacid' => $_W['uniacid'], 'qid' => $row['id'], 'openid' => $this->message['from']))->get();
				if (empty($qrLog)) table('qrcode_stat')->fill($insert)->save();
			} else {
				table('qrcode_stat')->fill($insert)->save();
			}

		} elseif ('user_get_card' == $this->message['event']) {
			$sceneid = $this->message['outerid'];
			$row = table('qrcode')->where(array('qrcid' => $sceneid))->get();
			if (!empty($row)) {
				$insert = array(
					'uniacid' => $_W['uniacid'],
					'acid' => $row['acid'],
					'qid' => $row['id'],
					'openid' => $this->message['from'],
					'type' => 2,
					'qrcid' => $sceneid,
					'scene_str' => $sceneid,
					'name' => $row['name'],
					'createtime' => TIMESTAMP,
				);
				table('qrcode_stat')->fill($insert)->save();
			}
		}
		if ('subscribe' == $this->message['event'] && !empty($_W['account']) && ($_W['account']['level'] == ACCOUNT_SERVICE_VERIFY || $_W['account']['level'] == ACCOUNT_SUBSCRIPTION_VERIFY)) {
			$account_obj = WeAccount::createByUniacid();
			$userinfo = $account_obj->fansQueryInfo($this->message['from']);
			if (!is_error($userinfo) && !empty($userinfo) && !empty($userinfo['subscribe'])) {
				load()->model('mc');
				$fan = mc_fansinfo($this->message['from'], 0 , $_W['uniacid']);
				$userinfo['nickname'] = $fan['nickname'];
				$fans = array(
					'unionid' => $userinfo['unionid'],
				);
				if (empty($fan['tag'])) {
					$fans['tag'] = base64_encode(iserializer($userinfo));
				}
				table('mc_mapping_fans')
					->where(array('openid' => $this->message['from']))
					->fill($fans)
					->save();
				$mc_fans_tag_table = table('mc_fans_tag');
				$mc_fans_tag_fields = mc_fans_tag_fields();
				$fans_tag_update_info = array();
				foreach ($userinfo as $fans_field_key => $fans_field_info) {
					if (in_array($fans_field_key, array_keys($mc_fans_tag_fields))) {
						$fans_tag_update_info[$fans_field_key] = $fans_field_info;
					}
				}
				$fans_tag_update_info['tagid_list'] = iserializer($fans_tag_update_info['tagid_list']);
				$fans_tag_update_info['uniacid'] = $_W['uniacid'];
				$fans_tag_update_info['fanid'] = $fan['fanid'];
				$fans_tag_exists = $mc_fans_tag_table->getByOpenid($fans_tag_update_info['openid']);
				if (!empty($fans_tag_exists)) {
					unset($fans_tag_update_info['headimgurl']);
					table('mc_fans_tag')
						->where(array('openid' => $fans_tag_update_info['openid']))
						->fill($fans_tag_update_info)
						->save();
				} else {
					table('mc_fans_tag')->fill($fans_tag_update_info)->save();
				}
				$uid = !empty($_W['member']['uid']) ? $_W['member']['uid'] : $this->message['from'];
				if (!empty($uid)) {
					$member = array();
					if (!empty($userinfo['nickname'])) {
						$member['nickname'] = $userinfo['nickname'];
					}
					mc_update($uid, $member);
				}
			}
		}
	}
}
