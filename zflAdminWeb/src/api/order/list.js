import request from '@/utils/request'
// 订单列表
const url = '/admin/order.Order/'
/**
 * 订单列表列表
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
 * 订单列表信息
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
 * 订单列表添加
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
 * 订单列表修改
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
 * 订单列表删除
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
 * 订单列表是否禁用
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
 * 订单发货
 * @param {array} data 请求数据
 */
export function sendDelivery(data) {
  return request({
    url: url + 'delivery',
    method: 'post',
    data
  })
}
/**
 * 查询物流
 * @param {array} data 请求数据
 */
export function logistics(data) {
  return request({
    url: url + 'logistics',
    method: 'post',
    data
  })
}
/**
 * 提货
 * @param {array} data 请求数据
 */
export function takeDelivery(data) {
  return request({
    url: url + 'takeDelivery',
    method: 'post',
    data
  })
}
/**
 * 确认收货
 * @param {array} data 请求数据
 */
export function confirmReceipt(data) {
  return request({
    url: url + 'confirmReceipt',
    method: 'post',
    data
  })
}

/**
 * 处理售后
 * @param {array} data 请求数据
 */
export function serviceOrder(data) {
  return request({
    url: url + 'serviceOrder',
    method: 'post',
    data
  })
}

/**
 * 支付审核
 * @param {array} data 请求数据
 */
export function orderPayAuth(data) {
  return request({
    url: url + 'orderPayAuth',
    method: 'post',
    data
  })
}