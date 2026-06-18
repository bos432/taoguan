import request from '@/utils/request'

const url = '/admin/system.MerchantPurchaseLimit/'

export function info(params) {
  return request({
    url: url + 'info',
    method: 'get',
    params
  })
}

export function edit(data) {
  return request({
    url: url + 'edit',
    method: 'post',
    data
  })
}
