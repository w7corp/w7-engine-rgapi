

/**
 *  开发者可以自己简单封装支付
 *  我承认偏爱 Promise
 */

import http from './request.js';

export default function pay(orderid) {

  return http.post('pay', { orderid, orderid}).then(param=>{
    
    // 此处判断 显示 商户号不对 openid不对等配置信息
    if (param.hasOwnProperty('errno') === true) //兼容 php 直接$this->pay 未判断iserror
    { 
      return Promise.reject(param.message);
    }
    return wxPay(param); 
  }, err=>{
     return Promise.reject(err);
  })
}


function wxPay(param) {
  console.log('wxPay');
  return new Promise((resolve, reject)=>{
    wx.requestPayment({
      'timeStamp': param.timeStamp,
      'nonceStr': param.nonceStr,
      'package': param.package,
      'signType': 'MD5',
      'paySign': param.paySign,
      'success': function (res) {
         console.log(res);
         resolve(res);
      },
      'fail': function (fail) {
          console.log(fail);
          reject(fail.errMsg);
      }

    });
  });
}