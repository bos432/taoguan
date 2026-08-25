import { test, expect, request } from '@playwright/test'

const origin = process.env.ADMIN_AUDIT_ORIGIN || 'http://127.0.0.1:807'
const base = `${origin}${process.env.ADMIN_AUDIT_BASE_PATH || '/admin-next/'}`

async function createToken() {
  const api = await request.newContext()
  try {
    for (const password of [process.env.ADMIN_AUDIT_PASSWORD, 'star1229', '123456'].filter(
      Boolean
    )) {
      const response = await api.post(`${origin}/admin/system.Login/login?lang=zh-cn`, {
        data: { username: 'admin', password, captcha_id: '', captcha_code: '', ajcaptcha: {} }
      })
      const json = await response.json()
      if (json.code === 200 && json.data?.AdminToken) return json.data.AdminToken
    }
    throw new Error('No local admin audit password is valid')
  } finally {
    await api.dispose()
  }
}

test('gateway attempt page exposes unknown result evidence', async ({ page }) => {
  const token = await createToken()
  await page.addInitScript((value) => {
    localStorage.setItem('admin_AdminToken', value)
    localStorage.setItem('admin_tokenType', 'header')
    localStorage.setItem('admin_tokenName', 'AdminToken')
    localStorage.setItem('admin_layout', 'left')
    localStorage.setItem('admin_language', 'zh-cn')
  }, token)
  await page.route('**/admin/report.PaymentGatewayAttempt/summary*', (route) =>
    route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        code: 200,
        data: { total: 1, attention: 1, business_counts: { prepayment: 1 } }
      })
    })
  )
  await page.route('**/admin/report.PaymentGatewayAttempt/list*', (route) =>
    route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        code: 200,
        data: {
          count: 1,
          list: [
            {
              id: 7,
              order_no: 'SAGA-ORDER-001',
              business_type_title: '微信预下单',
              status: 4,
              status_title: '结果未知',
              merchant_request_no: 'SAGA-REQUEST-001',
              amount: '2998.00',
              attempt_count: 1,
              error_message: 'network timeout',
              update_time: '2026-08-25 12:00:00',
              member_order_id: 1
            }
          ]
        }
      })
    })
  )
  await page.route('**/admin/report.PaymentGatewayAttempt/info*', (route) =>
    route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        code: 200,
        data: {
          id: 7,
          order_no: 'SAGA-ORDER-001',
          business_type_title: '微信预下单',
          status_title: '结果未知',
          merchant_request_no: 'SAGA-REQUEST-001',
          provider_transaction_id: '',
          attempt_count: 1,
          error_message: 'network timeout',
          request: { out_trade_no: 'SAGA-REQUEST-001' },
          response: { state: 'unknown' }
        }
      })
    })
  )

  await page.goto(`${base}#/finance/gateway-attempts`, { waitUntil: 'networkidle' })
  await expect(page.getByRole('heading', { name: '支付网关异常' })).toBeVisible()
  await expect(page.getByText('SAGA-ORDER-001')).toBeVisible()
  await expect(page.getByText('结果未知', { exact: true }).first()).toBeVisible()
  await page.getByText('详情', { exact: true }).click()
  const detail = page.getByLabel('网关调用详情')
  await expect(detail.getByRole('heading', { name: '网关调用详情' })).toBeVisible()
  await expect(detail.getByText('network timeout')).toBeVisible()
  await expect(detail.getByText(/SAGA-REQUEST-001/).last()).toBeVisible()
  await expect(detail.getByText(/"state": "unknown"/)).toBeVisible()
})
