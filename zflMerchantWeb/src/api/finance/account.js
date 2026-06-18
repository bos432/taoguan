import request from '@/utils/request'
// 银行卡管理
const url = '/merchant/finance.MerchantAccount/'
/**
 * 银行卡列表
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
 * 选择银行卡
 * @param {array} params 请求参数
 */
export function selectBank(params) {
  return request({
    url: url + 'select',
    method: 'get',
    params: params
  })
}
/**
 * 银行卡信息
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
 * 银行卡添加
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
 * 银行卡修改
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
 * 银行卡删除
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
 * 银行卡是否禁用
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
 * 新增支付宝授权账号
 * @param {array} data 请求数据
 */
export function addAlipay(data) {
  return request({
    url: url + 'addAlipay',
    method: 'post',
    data
  })
}