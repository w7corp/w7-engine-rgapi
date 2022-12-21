<?php
/**
 * 参数设置
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('upload_file');
$do = in_array($do, $dos) ? $do : 'upload_file';

if ('upload_file' == $do) {
	if (checksubmit()) {
		if (empty($_FILES['file']['tmp_name'])) {
			itoast('请选择文件', url('system/common/upload_file'), 'error');
		}
		if ($_FILES['file']['type'] != 'text/plain') {
			itoast('文件类型错误', url('system/common/upload_file'), 'error');
		}
		$file = file_get_contents($_FILES['file']['tmp_name']);
		$file_name = 'MP_verify_' . $file . '.txt';
		if ($_FILES['file']['name'] != $file_name || !preg_match('/^[A-Za-z0-9]+$/', $file)) {
			itoast('上传文件不合法,请重新上传', url('system/common/upload_file'), 'error');
		}
		file_put_contents(IA_ROOT . '/' . $_FILES['file']['name'], $file);
		itoast('上传成功', url('system/common/upload_file'), 'success');
	}
}
template('system/upload_file');
