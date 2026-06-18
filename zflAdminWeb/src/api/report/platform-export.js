import axios from 'axios'
import { useAppStoreHook } from '@/store/modules/app'
import { useSettingsStoreHook } from '@/store/modules/settings'
import { useUserStoreHook } from '@/store/modules/user'

const client = axios.create({
  baseURL: import.meta.env.VITE_APP_BASE_URL,
  timeout: 60000
})

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

function resolveFilename(contentDisposition = '', fallback = 'export.csv') {
  const utf8Match = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i)
  if (utf8Match?.[1]) {
    try {
      return decodeURIComponent(utf8Match[1])
    } catch (error) {
      return utf8Match[1]
    }
  }

  const plainMatch = contentDisposition.match(/filename="?([^"]+)"?/i)
  if (plainMatch?.[1]) {
    try {
      return decodeURIComponent(plainMatch[1])
    } catch (error) {
      return plainMatch[1]
    }
  }

  return fallback
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

async function exportBlob(action, params, fallbackFilename) {
  const response = await client.get(`/admin/report.PlatformExport/${action}`, buildRequestConfig(params))
  const filename = resolveFilename(response.headers['content-disposition'] || '', fallbackFilename)
  const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8' })
  saveBlob(blob, filename)
  return filename
}

export function downloadOrders(params) {
  return exportBlob('orders', params, 'platform_orders.csv')
}

export function downloadMerchants(params) {
  return exportBlob('merchants', params, 'platform_merchants.csv')
}

export function downloadRenewRecords(params) {
  return exportBlob('renewRecords', params, 'platform_renew_records.csv')
}

export function downloadAnalytics(params) {
  return exportBlob('analytics', params, 'platform_analytics.csv')
}
