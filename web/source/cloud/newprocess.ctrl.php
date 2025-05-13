<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
load()->model('extension');
load()->model('cloud');
load()->model('module');
load()->func('communication');
load()->func('db');
load()->model('system');

$dos = array('module_build');
$do = in_array($do, $dos) ? $do : '';

if ('module_build' == $do) {
	$module_name = safe_gpc_string($_GPC['module_name']);
	$is_upgrade = intval($_GPC['is_upgrade']);
	$packet = cloud_m_build($module_name, $is_upgrade ? 'upgrade' : '');
	if (is_error($packet)) {
		if ($packet['errno'] == -2) {
			if (!igetcookie((empty($code) ? $module_name : $code) . '_install')) {
				isetcookie((empty($code) ? $module_name : $code) . '_install', 1, CACHE_EXPIRE_MIDDLE);
				iajax(-1, $packet['message']);
			}
		} else {
			iajax(-1, $packet['message']);
		}
	}

	if (!empty($packet['zip_url'])) {
        set_time_limit(0);
        $to_zip_name = IA_ROOT . '/addons/' . $module_name . '.zip';
	    $hostfile = fopen($packet['zip_url'], 'rb');
	    $fh = fopen($to_zip_name, 'wb');
	    while (!feof($hostfile)) {
		    $output = fread($hostfile, 8192);
		    fwrite($fh, $output);
	    }
	    fclose($hostfile);

        if (file_exists($to_zip_name)) {
            $zip = new ZipArchive;
      		$res = $zip->open($to_zip_name);
    		if ($res === true) {
    			$result = $zip->extractTo(IA_ROOT . '/addons/');
    			$zip->close();
    			@chmod(IA_ROOT . '/addons/' . $module_name, 0755);
    			$file_tree = cloud_file_tree(IA_ROOT . '/addons/' . $module_name);
    			foreach ($file_tree as $file) {
    				@chmod($file, 0755);
    			}
    		} else {
				iajax(-1, '解压失败');
			}
			$manifestFile = IA_ROOT . '/addons/' . $module_name . '/manifest.xml';
			file_put_contents($manifestFile, $packet['manifest']);
			@chmod($manifestFile, 0755);
			@unlink($to_zip_name);
    	} else {
			iajax(-1, '下载失败');
		}
        
        iajax(0, 'success');
    }
}