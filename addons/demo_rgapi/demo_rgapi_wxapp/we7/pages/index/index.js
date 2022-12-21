//index.js
//获取应用实例
var app = getApp()
Page({
	data: {
		navs: [],
		slide: [],
		commend: [],
		userInfo: {}
	},
	onLoad: function () {
		var that = this
		app.util.footer(that);
		//初始化导航数据
		app.util.request({
			'url': 'wxapp/home/nav',
			'cachetime': '30',
			success: function (res) {
				if (!res.data.message.errno) {
					console.log(res.data.message.message)
					that.setData({
						navs: res.data.message.message,
					})
				}
			}
		});
		app.util.request({
			'url': 'wxapp/home/slide',
			'cachetime': '30',
			success: function (res) {
				if (!res.data.message.errno) {
					that.setData({
						slide: res.data.message.message,
					})
				}
			}
		});
		app.util.request({
			url: 'wxapp/home/commend',
			cachetime: '30',
			success: function (res) {
				if (!res.data.message.errno) {
					that.setData({
						commend: res.data.message.message,
					})
				}
			}
		});
	}
});
