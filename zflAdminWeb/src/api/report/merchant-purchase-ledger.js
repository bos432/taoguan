import axios from 'axios'
import request from '@/utils/request'
import { useAppStoreHook } from '@/store/modules/app'
import { useSettingsStoreHook } from '@/store/modules/settings'
import { useUserStoreHook } from '@/store/modules/user'

const url = '/admin/report.MerchantPurchaseLedger/'

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

function buildRequestConfig(params = {}) {
  const appStore = useAppStoreHook()
  const userStore = useUserStoreHook()
  const settingsStore = useSettingsStoreHook()
  const tokenValue = userStore.token
  const tokenType = settingsStore.tokenType
  const tokenName = settingsStore.tokenName
  const config = {
    params: { ...params },
    headers: {},
    responseType: 'blob'
  }

  if (tokenValue) {
    if (tokenType === 'header') {
      config.headers[tokenName] = tokenValue
    } else {
      config.params[tokenName] = tokenValue
    }
  }

  if (tokenType === 'header') {
    config.headers['think-lang'] = appStore.language
  } else {
    config.params.lang = appStore.language
  }

  return config
}

function saveBlob(blob, filename) {
  const downloadUrl = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = downloadUrl
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(downloadUrl)
}

export async function downloadLedger(params) {
  const client = axios.create({
    baseURL: import.meta.env.VITE_APP_BASE_URL,
    timeout: 60000
  })
  const response = await client.get(url + 'export', buildRequestConfig(params))
  const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8' })
  saveBlob(blob, 'merchant_purchase_ledger.csv')
}
