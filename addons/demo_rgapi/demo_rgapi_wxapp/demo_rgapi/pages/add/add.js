// we7_wxappdemo/pages/add/add.js
import http from '../../util/request.js';
import alert from '../../util/alert.js';
import {toast} from '../../util/alert.js';
import router from '../../util/router.js';

import { chooseImage,upload} from '../../util/image.js';

Page({

  /**
   * 页面的初始数据
   */
  data: {
     title:'',
     content: '',
     image : '',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
     
  },


  add: function(e) {
     let data = e.detail.value;
     let title = data.title;
     let content = data.content;
     let image = this.data.image;// 上传的图片路径
    
     let post = {title,content,image};
     http.post('add', post)
     .then((res)=>{
       return alert('添加日记成功');
     }, err=>{ 
       console.log(err); 
       alert('添加日记失败')
     }).then(()=>{
       console.log('router');
       router.index();  
     })
  }, 

  chooseImage() {
     chooseImage()
     .then(path=>{
        this.setData({image:path});
        return upload(path)
     }, err=>{
       toast('选择图片失败');
     }).then(updata=>{
        this.setData({image:updata.url});
     }, err=>{
        toast('图片上传失败')
     });
  },

  previewImage() {
    wx.previewImage({
      urls: [this.image],
    })
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
  
  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {
  
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
  
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
  
  }
})