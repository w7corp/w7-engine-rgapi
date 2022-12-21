<?php

$tablename = tablename('demo_rgapi_riji');
$sql = <<<EOT

CREATE TABLE IF NOT EXISTS $tablename (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '日记标题',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '日记内容',
  `createtime` int(11) NOT NULL COMMENT '创建时间',
  `updatetime` int(11) NOT NULL COMMENT '更新时间',
  `uid` varchar(255) NOT NULL DEFAULT '' COMMENT '用户标识',
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片路径',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

EOT;

pdo_query($sql);
