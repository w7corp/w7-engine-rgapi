
// 简单封装 alert toast  方法
function alert(content, title= '提示') {

  return new Promise((resolve, reject)=>{
    wx.showModal({
      title: title,
      content: content,
      cancel: false,
      success() {
         resolve(); 
      },
      fail() {
        reject();
      }
    });
  })
}

export function toast(title) {
  return new Promise((resolve, reject)=>{
    wx.showToast({
      title: title,
      success() {
         resolve();
      },
      fail(err) {
        reject(err);
      }
    })
  });
  
}
export default alert;