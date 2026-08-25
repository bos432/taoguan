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

test('difference order opens preloaded business timeline evidence', async ({ page }) => {
  const token = await createToken()
  await page.addInitScript((value) => {
    localStorage.setItem('admin_AdminToken', value)
    localStorage.setItem('admin_tokenType', 'header')
    localStorage.setItem('admin_tokenName', 'AdminToken')
    localStorage.setItem('admin_layout', 'left')
    localStorage.setItem('admin_language', 'zh-cn')
  }, token)
  await page.route('**/admin/report.MerchantPurchaseLedger/filters*', (route) =>
    route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ code: 200, data: {} })
    })
  )
  await page.route('**/admin/report.MerchantPurchaseLedger/list*', (route) =>
    route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ code: 200, data: { list: [], count: 0 } })
    })
  )
  await page.route('**/admin/report.MerchantPurchaseLedger/summary*', (route) =>
    route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        code: 200,
        data: {
          merchant_trade_compare: [
            {
              merchant_id: 4,
              merchant_title: '安思建材',
              buy_amount: 2998,
              sell_amount: 0,
              net_amount: 2998,
              trade_ratio: 0,
              buy_order_count: 1,
              sell_order_count: 0,
              trade_judgement: '买入明显更多'
            }
          ]
        }
      })
    })
  )
  await page.route('**/admin/report.MerchantPurchaseLedger/tradeDiffOrders*', (route) =>
    route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        code: 200,
        data: {
          match_type: 'balance',
          target_amount: 2998,
          message: '找到未配平买入订单',
          candidate_orders: [],
          goods_gaps: [],
          evidence_summary: { order_count: 1, event_count: 1, legacy_log_count: 1 },
          orders: [
            {
              member_order_id: 13,
              order_no: '2606131437304501',
              pay_time: '2026-06-13 14:37:30',
              amount: 2998,
              quantity: 1,
              side_title: '未配平买入',
              diagnosis_title: '未卖出/仍在库存',
              diagnosis_message: '当前商品仍有库存，尚未找到卖出流水。',
              timeline: {
                coverage: 'hybrid',
                order: {
                  id: 13,
                  order_no: '2606131437304501',
                  status_title: '完成',
                  pay_price: 2998,
                  total_num: 1
                },
                events: [
                  {
                    id: 1,
                    event_type: 'payment.voucher_approved',
                    before_status_title: '待付款',
                    after_status_title: '完成',
                    amount: 2998,
                    quantity: 1,
                    source: 'admin-next',
                    operator_type: 'platform_admin',
                    operator_id: 1,
                    request_id: 'DIFF-2998',
                    reason: '凭证审核通过',
                    occurred_at: '2026-06-13 14:40:00'
                  }
                ],
                legacy_logs: [
                  {
                    id: 1,
                    title: '凭证支付订单成功',
                    role_type: 3,
                    create_uid: 13,
                    create_time: '2026-06-13 14:40:00'
                  }
                ]
              }
            }
          ]
        }
      })
    })
  )

  await page.goto(`${base}#/report/merchant-purchase-ledger`, { waitUntil: 'networkidle' })
  await expect(page.getByText('安思建材')).toBeVisible()
  await page.getByText('查看差额订单', { exact: true }).click()
  await expect(page.getByText('已关联 1 笔订单流转')).toBeVisible()
  await page.getByText('流转证据', { exact: true }).last().click()
  await expect(page.getByRole('heading', { name: '订单流转' })).toBeVisible()
  await expect(page.getByText('2606131437304501').last()).toBeVisible()
  await expect(page.getByText('凭证支付审核通过')).toBeVisible()
  await expect(page.getByText('请求编号：DIFF-2998')).toBeVisible()
})
