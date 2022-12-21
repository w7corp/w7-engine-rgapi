

// 简单封装 跳转 方法
function index() {
  console.log('to index');
  wx.switchTab({
    url: '/demo_rgapi/pages/index/index',
  });
}


export default {index}

