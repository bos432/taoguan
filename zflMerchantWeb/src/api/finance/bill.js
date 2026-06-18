import request from '@/utils/request'
// 商家
const url = '/merchant/finance.MerchantBill/'
/**
 * 资金明细
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
 * 账单类型
 * @param {array} params 请求参数
 */
export function getParams(params) {
  return request({
    url: url + 'getParams',
    method: 'get',
    params: params
  })
}