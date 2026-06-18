import request from '@/utils/request'
// 批次
const url = '/admin/trace.TraceBatch/'
/**
 * 批次列表
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
 * 批次列表
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
 * 批次列表参数
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
 * 批次信息
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
 * 批次添加
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
 * 批次修改
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
 * 批次删除
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
 * 批次是否禁用
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
 * 审核
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
 * 查询溯源信息
 * @param params
 * @returns {*}
 */
export function getBatchTache(params) {
  return request({
    url: url + 'getBatchTache',
    method: 'get',
    params: params
  })
}
