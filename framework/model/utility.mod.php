<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/framework/model/utility.mod.php : v a80418cf2718 : 2014/09/16 01:07:43 : Gorden $
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 检查验证码是否存在且正确
 * @param int $uniacid 统一公号
 * @param string $receiver 粉丝用户
 * @param string $code 验证码
 * @return boolean
 */
function code_verify($uniacid, $receiver, $code) {
    $receiver = safe_gpc_string($receiver);
    if (empty($receiver) || !is_numeric($code)) {
        return false;
    }
    $data = table('uni_verifycode')->where(array(
        'uniacid' => intval($uniacid),
        'receiver' => $receiver,
        'verifycode' => $code,
        'createtime >' => (TIMESTAMP - 1800)
    ))->get();

    return !empty($data);
}
