import request from '@/utils/request'
// 商品
const url = '/admin/goods.Goods/'
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
 * 查询商品编码
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
/**
 * 商品审核
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
 * 商品批量迁移到平台
 * @param {array} data 请求数据
 */
export function transferToPlatform(data) {
  return request({
    url: url + 'transferToPlatform',
    method: 'post',
    data
  })
}
/**
 * 商品批量迁移到商家
 * @param {array} data 请求数据
 */
export function transferToMerchant(data) {
  return request({
    url: url + 'transferToMerchant',
    method: 'post',
    data
  })
}
/**
 * 商品批量更换缩略图
 * @param {array} data 请求数据
 */
export function batchUpdateThumbnail(data) {
  return request({
    url: url + 'batchUpdateThumbnail',
    method: 'post',
    data
  })
}
/**
 * 商品批量更新标签
 * @param {array} data 请求数据
 */
export function batchUpdateLabels(data) {
  return request({
    url: url + 'batchUpdateLabels',
    method: 'post',
    data
  })
}
