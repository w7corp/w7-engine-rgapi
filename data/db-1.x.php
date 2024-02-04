<?php
$schemas = json_decode(file_get_contents(dirname(__FILE__) . '/db.json'), true);
$datas = [];
return ['schemas' => $schemas, 'datas' => $datas];
