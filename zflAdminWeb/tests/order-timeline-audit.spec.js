import { test, expect, request } from '@playwright/test'

const origin = process.env.ADMIN_AUDIT_ORIGIN || 'http://127.0.0.1:807'
const basePath = process.env.ADMIN_AUDIT_BASE_PATH || '/admin-next/'
const base = `${origin}${basePath}`
const passwords = [
  process.env.ADMIN_AUDIT_PASSWORD,
  process.env.ADMIN_LOCAL_PASSWORD,
  'star1229',
  '123456'
].filter(Boolean)

async function createToken() {
  const api = await request.newContext()
  try {
    for (const password of passwords) {
      const response = await api.post(`${origin}/admin/system.Login/login?lang=zh-cn`, {
        data: {
          username: 'admin',
          password,
          captcha_id: '',
          captcha_code: '',
          ajcaptcha: {}
        }
      })
      const json = await response.json()
      if (json.code === 200 && json.data?.AdminToken) return json.data.AdminToken
    }
    throw new Error('No local admin audit password is valid')
  } finally {
    await api.dispose()
  }
}

test('order timeline exposes event and legacy audit records', async ({ page }) => {
  const token = await createToken()
  await page.addInitScript((tokenValue) => {
    window.localStorage.setItem('admin_AdminToken', tokenValue)
    window.localStorage.setItem('admin_tokenType', 'header')
    window.localStorage.setItem('admin_tokenName', 'AdminToken')
    window.localStorage.setItem('admin_layout', 'left')
    window.localStorage.setItem('admin_language', 'zh-cn')
  }, token)

  await page.route('**/admin/order.Order/timeline*', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        code: 200,
        msg: 'success',
        data: {
          order: {
            id: 1,
            order_no: 'TIMELINE-AUDIT-001',
            status_title: '待评价',
            pay_price: '2998.00',
            total_num: 1
          },
          coverage: 'hybrid',
          events: [
            {
              id: 1,
              event_type: 'order.received',
              before_status_title: '待收货',
              after_status_title: '待评价',
              amount: '2998.00',
              quantity: 1,
              source: 'uniapp-weixin',
              operator_type: 'member',
              operator_id: 13,
              request_id: 'audit:timeline:001',
              reason: '买家确认收货',
              occurred_at: '2026-08-25 12:00:00'
            }
          ],
          legacy_logs: [
            {
              id: 2,
              title: '订单已发货',
              content: '顺丰速运',
              role_type: 1,
              create_uid: 1,
              create_time: '2026-08-24 10:00:00'
            }
          ]
        }
      })
    })
  })

  await page.goto(`${base}#/order/order`, { waitUntil: 'networkidle' })
  const timelineAction = page.getByText('流转', { exact: true }).first()
  await expect(timelineAction).toBeVisible()
  await timelineAction.click()

  await expect(page.getByRole('heading', { name: '订单流转' })).toBeVisible()
  await expect(page.getByText('TIMELINE-AUDIT-001')).toBeVisible()
  await expect(page.getByText('新旧记录并行')).toBeVisible()
  await expect(page.getByText('买家确认收货', { exact: true })).toBeVisible()
  await expect(page.getByText('请求编号：audit:timeline:001')).toBeVisible()

  await page.getByRole('tab', { name: '旧操作日志 (1)' }).click()
  await expect(page.getByText('订单已发货')).toBeVisible()
  await expect(page.getByText('顺丰速运')).toBeVisible()
})
