import request from '@/utils/request'
// 仓库管理
const url = '/admin/setting.SettingWarehouse/'
/**
 * 仓库列表
 * @param {array} params 请求参数
 */
export function list(params) {
  return request({
    url: url + 'list',
    method: 'get',
    params: params
  })
}
export function select(params) {
  return request({
    url: url + 'select',
    method: 'get',
    params: params
  })
}
/**
 * 仓库信息
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
 * 仓库添加
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
 * 仓库修改
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
 * 仓库删除
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
 * 仓库是否禁用
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
 * 修改上级
 * @param {array} data 请求数据
 */
export function editpid(data) {
  return request({
    url: url + 'editpid',
    method: 'post',
    data
  })
}