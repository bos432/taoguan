import request from '@/utils/request'
// 商品
const url = '/merchant/goods.Goods/'
/**
 * 商品列表
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
 * 查询选择商品
 * @param {array} params 请求参数
 */
export function select(params) {
  return request({
    url: url + 'select',
    method: 'get',
    params: params
  })
}
/**
 * 商品列表参数
 * @param {array} params 请求参数
 */
export function params(params) {
  return request({
    url: url + 'getParams',
    method: 'get',
    params: params
  })
}
/**
 * 获取商品编码
 * @param {array} params 请求参数
 */
export function code(params) {
  return request({
    url: url + 'getCode',
    method: 'get',
    params: params
  })
}
/**
 * 商品信息
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
 * 商品添加
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
 * 商品修改
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
 * 商品删除
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
 * 商品是否禁用
 * @param {array} data 请求数据
 */
export function disable(data) {
  return request({
    url: url + 'disable',
    method: 'post',
    data
  })
}