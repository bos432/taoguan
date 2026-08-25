import request from '@/utils/request'

const url = '/admin/report.PaymentGatewayAttempt/'

export function list(params) {
  return request({ url: url + 'list', method: 'get', params })
}

export function summary() {
  return request({ url: url + 'summary', method: 'get' })
}

export function info(params) {
  return request({ url: url + 'info', method: 'get', params })
}
