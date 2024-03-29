<?php

$tablename_riji = tablename('demo_rgapi_riji');
$tablename_paylog = tablename('demo_rgapi_paylog');
$sql = <<<EOT

CREATE TABLE IF NOT EXISTS $tablename_riji (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '日记标题',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '日记内容',
  `createtime` int(11) NOT NULL COMMENT '创建时间',
  `updatetime` int(11) NOT NULL COMMENT '更新时间',
  `uid` varchar(255) NOT NULL DEFAULT '' COMMENT '用户标识',
  `uniacid` int(11) NOT NULL DEFAULT '0',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片路径',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS $tablename_paylog (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `no` varchar(255) NOT NULL DEFAULT '' COMMENT '商户订单号',
  `code` varchar(255) NOT NULL DEFAULT '' COMMENT '二维码链接',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0:未支付;1:已支付;2:已退款;',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '支付类型 1:Native(微信扫码);2:ali(支付宝);3:Wechat(公众号jsapi);4:Wxapp(小程序jsapi);5:w7pay',
  `createtime` int(11) NOT NULL COMMENT '创建时间',
  `updatetime` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  `uid` varchar(255) NOT NULL DEFAULT '' COMMENT '用户标识',
  `uniacid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

EOT;

pdo_run($sql);
