import request from '@/utils/request'
// 商家管理
const url = '/admin/merchant.Merchant/'
/**
 * 商家列表
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
 * 商家信息
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
 * 商家添加
 * @param {array} data 请求数据
 */
export function add(data) {
  return request({
    url: url + 'add',
    method: 'post',
    data
  })
}
/**
 * 商家修改
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
 * 商家删除
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
 * 商家状态
 * @param {array} data 请求数据
 */
export function status(data) {
  return request({
    url: url + 'status',
    method: 'post',
    data
  })
}
/**
 * 商家是否禁用
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
 * 查询参数
 * @param {array} data 请求数据
 */
export function getParams(data) {
  return request({
    url: url + 'getParams',
    method: 'post',
    data
  })
}

/**
 * 商家审核
 * @param {array} data 请求数据
 */
export function auth(data) {
  return request({
    url: url + 'auth',
    method: 'post',
    data
  })
}

/**
 * 商家续费
 * @param {array} data 请求数据
 */
export function renew(data) {
  return request({
    url: url + 'renew',
    method: 'post',
    data
  })
}

/**
 * 续费记录
 * @param {array} params 请求参数
 */
export function renewRecordList(params) {
  return request({
    url: url + 'renewRecordList',
    method: 'get',
    params
  })
}
