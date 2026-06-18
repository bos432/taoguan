import request from '@/utils/request'

const url = '/admin/report.PlatformAnalytics/'

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

export function trend(params) {
  return request({
    url: url + 'trend',
    method: 'get',
    params
  })
}

export function ranking(params) {
  return request({
    url: url + 'ranking',
    method: 'get',
    params
  })
}

export function alerts(params) {
  return request({
    url: url + 'alerts',
    method: 'get',
    params
  })
}

export function merchantDetail(params) {
  return request({
    url: url + 'merchantDetail',
    method: 'get',
    params
  })
}
