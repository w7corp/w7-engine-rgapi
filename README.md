# 软擎授权示例应用-原生应用

#### 介绍
1. 框架上基于微擎2.0系统开发，功能上基于软擎授权系统，建立的一个使独立系统和2.0应用串联起来的框架。
2. 这是一个已经开发好的适用于2.0应用的独立系统，可在开发者中心独立发布，开发者可以参考该应用进行自己应用的开发；
3. 开发者想发布自己的应用，只需将该示例中的demo_rgapi替换成自己的应用即可；
4. 更多说明请点击 [这里](https://wiki.w7.com/document/35/7302) 。

#### 本地开发
1. 将项目根目录下的```.env.example```重命名为```.env```；
   > .env文件只是本地开发时使用，在 [开发者中心](https://https://dev.w7.cc) 上传应用时一定要删除，一定要删除，一定要删除！
2. 在```.env```中将数据库信息修改为自己的；
3. 初始化```data/init-accounts.php```的平台数据；
4. 浏览器访问站点域名即可；
5. 本地开发时，需要手动切换```manifest.yaml```中对应的左侧菜单的路由，见5；
6. 三个路由：
<br>（1） 应用管理```域名/web/index.php?c=module&a=display&do=switch_module```，对应控制台已上线的软擎授权示例应用下的左侧菜单之“应用管理”，如下图：<br>
   ![image.png](https://rangine-1251470023.cos.ap-shanghai.myqcloud.com/document/ixwFtvU3wpwmLPaApKAUpVPT3A131mFF.png)
<br>（2） 站点设置```域名/web/index.php?c=system&a=setting&do=basic```，对应控制台已上线的软擎授权示例应用下的左侧菜单之“站点设置”，如下图：<br>
   ![image.png](https://rangine-1251470023.cos.ap-shanghai.myqcloud.com/document/pe2wgE2Yh2QWk2Twaww8122zhtTXeX3r.png)
