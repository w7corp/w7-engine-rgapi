
// 微擎暂未封装 上传 使用 web目录上传
// 开发者可自己封装
let app = getApp();

export function chooseImage() {
  return new Promise((resolve, reject)=>{
    wx.chooseImage({
      count : 1,
      success: function (res) {
        resolve(res.tempFilePaths[0]);
      },
      fail: function (res) {
         reject(res);
      }
    })
  });
  
}

export function upload(path) {
  let siteroot = app.siteInfo.siteroot;
  siteroot = siteroot.replace('app/index.php', 'web/index.php');
  let upurl = siteroot + '?i='+app.siteInfo.uniacid+'&c=utility&a=file&do=upload&thumb=0';
  return new Promise((resolve,reject)=>{
    wx.uploadFile({
      url: upurl,
      filePath: path,
      name: 'file',
      header: {},
      formData: {},
      success: function (res) { 
        console.log('success');
         console.log(res);
         resolve(JSON.parse(res.data));
      },
      fail: function (res) { 
        console.log('errror');
        console.log(res);
         reject(res);
      },
      complete: function (res) {

      },
    })
  });
  
}


