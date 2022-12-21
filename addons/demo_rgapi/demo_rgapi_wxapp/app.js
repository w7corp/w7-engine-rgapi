//app.js
import util from 'we7/resource/js/util.js';
App({
  onLaunch: function () {
    //调用API从本地缓存中获取数据
  },
  onShow: function () {
  },
  onHide: function () {
  },
  onError: function (msg) {
    console.log(msg)
  },
  util: util,
  globalData: {
    userInfo: null,
  },
  siteInfo: require('siteinfo.js')
});