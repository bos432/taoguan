import request from '@/utils/request'

const url = '/admin/report.InternalTakeover/'

export function filters() {
  return request({
    url: url + 'filters',
    method: 'get'
  })
}

export function summary(params) {
  return request({
    url: url + 'summary',
    method: 'get',
    params
  })
}

export function list(params) {
  return request({
    url: url + 'list',
    method: 'get',
    params
  })
}

export function detail(params) {
  return request({
    url: url + 'detail',
    method: 'get',
    params
  })
}
