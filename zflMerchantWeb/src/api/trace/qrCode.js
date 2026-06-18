import request from '@/utils/request'
// 二维码管理
const url = '/merchant/trace.TraceQrCode/'
/**
 * 二维码管理列表
 * @param {array} params 请求参数
 */
export function list(params) {
  return request({
    url: url + 'list',
    method: 'get',
    params: params
  })
}
/**
 * 查询参数
 * @param {array} params 请求参数
 */
export function getParams(params) {
  return request({
    url: url + 'getParams',
    method: 'get',
    params: params
  })
}

/**
 * 二维码管理信息
 * @param {array} params 请求参数
 */
export function info(params) {
  return request({
    url: url + 'info',
    method: 'get',
    params: params
  })
}
/**
 * 二维码管理添加
 * @param {array} data 请求数据
 */
export function add(data) {
  let josn = {
    url: url + 'add',
    method: 'post',
    data
  };
  if(data.is_download){
    josn = {
      url: url + 'add',
      method: 'post',
      responseType: 'blob',
      data
    };
  }
  return request(josn)
}
/**
 * 二维码管理修改
 * @param {array} data 请求数据
 */
export function edit(data) {
  return request({
    url: url + 'edit',
    method: 'post',
    data
  })
}
/**
 * 二维码管理删除
 * @param {array} data 请求数据
 */
export function dele(data) {
  return request({
    url: url + 'dele',
    method: 'post',
    data
  })
}
/**
 * 二维码管理是否禁用
 * @param {array} data 请求数据
 */
export function disable(data) {
  return request({
    url: url + 'disable',
    method: 'post',
    data
  })
}
/**
 * 二维码批量下载
 * @param {array} data 请求数据
 */
export function download(data) {
  return request({
    url: url + 'download',
    method: 'post',
    responseType: 'blob',
    data
  })
}