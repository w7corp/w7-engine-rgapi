# 软擎授权示例应用

#### 介绍
1. 框架上基于微擎2.0系统开发，功能上基于软擎授权系统，建立的一个使独立系统和2.0应用串联起来的框架。
2. 这是一个已经开发好的2.0应用独立系统，可在开发者中心独立发布，开发者可以参考该应用进行自己应用的开发；
3. 开发者想发布自己的应用，只需将该示例中的demo_rgapi替换成自己的应用即可；
4. 更多说明请点击 [这里](https://wiki.w7.com/document/35/7302) 。

#### 本地开发
1. 将项目根目录下的```.env.example```重命名为```.env```（此文件只是本地开发时使用，在开发者中心上传应用时一定要删除此文件）；
2. 在```.env```中将数据库信息修改为自己的；
3. 浏览器访问站点域名即可；
4. 本地开发时，需要手动切换```manifest.yaml```中对应的左侧菜单的路由，见5；
5. 三个路由：
<br>（1） 应用管理```域名/web/index.php?c=module&a=display&do=switch```，对应控制台已上线的软擎授权示例应用下的左侧菜单之“应用管理”，如下图：<br>
   ![image.png](https://rangine-1251470023.cos.ap-shanghai.myqcloud.com/document/S1JjDJb6Kry4y04jj4jhyjj664CpPjPn.png)；
<br>（2） 系统功能```域名/web/index.php?c=system&a=base-info&do=display```，对应控制台已上线的软擎授权示例应用下的左侧菜单之“系统功能”，如下图：<br>
   ![image.png](https://rangine-1251470023.cos.ap-shanghai.myqcloud.com/document/PGdQGglgngdl4bA5eC54NqCegQwc1YEG.png)；
<br>（3） 站点设置```域名/web/index.php?c=system&a=setting&do=basic```，对应控制台已上线的软擎授权示例应用下的左侧菜单之“站点设置”，如下图：<br>
   ![image.png](https://rangine-1251470023.cos.ap-shanghai.myqcloud.com/document/RVVffMq56hsq365CHc553Z45mH5ZcTcf.png)；
