// we7_wxappdemo/pages/edit/edit.js
import http from '../../util/request.js';
import alert from '../../util/alert.js';
import router from '../../util/router.js';
Page({

  /**
   * 页面的初始数据
   */
  data: {
     riji: {
        id : 0,
        title : '',
        content : ''
     }
  },

  /**
   * 生命周期函数--监听页面加载
   */ 
  onLoad: function (options) { 
    var id = options.id;     
    
    http.get('show', {id}).then(data=>{
       this.setData({  
          riji:data 
       })
    }, err=>{ 
      console.log(err);
    })

  },

  edit(e) {
    let data = e.detail.value;
    console.log(this.data.riji);
    data.id = this.data.riji.id;
    http.post('edit', data)
    .then(result=>{
      return alert(result==1 ? '编辑成功' : '编辑失败');
    }, err=>{
      return alert('编辑失败');
    }).then(()=>{
      router.index();
    })
  },

  doDelete() {
     let id = this.data.riji.id;
     http.post('del', {id:id}).then(result=>{
        return alert(result == 1 ? '删除成功' : '删除失败');
     }).then(err=>{
        alert('删除失败');
     }).then(()=>{
       router.index();
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