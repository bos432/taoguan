import request from '@/utils/request'
// 检测机构
const url = '/merchant/inspection.Inspection/'
/**
 * 查询检测机构
 * @param {array} params 请求参数
 */
export function select(params) {
  return request({
    url: url + 'select',
    method: 'get',
    params: params
  })
}