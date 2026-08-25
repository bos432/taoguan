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

test('merchant member binding requires evidence and exposes authorization history', async ({
  page
}) => {
  const token = await createToken()
  await page.addInitScript((value) => {
    localStorage.setItem('admin_AdminToken', value)
    localStorage.setItem('admin_tokenType', 'header')
    localStorage.setItem('admin_tokenName', 'AdminToken')
    localStorage.setItem('admin_layout', 'left')
    localStorage.setItem('admin_language', 'zh-cn')
  }, token)

  const merchant = {
    id: 4,
    title: '权限测试商家',
    username: 'merchant-4',
    name: '测试联系人',
    phone: '13800000000',
    address: '重庆市',
    auth_state: 1,
    auth_state_title: '审核通过',
    is_disable: 0,
    is_delete: 0,
    member_id: 13,
    member_is_super: 1,
    expire_time: '2027-08-25 00:00:00',
    create_time: '2026-08-25 10:00:00'
  }
  await page.route('**/admin/merchant.Merchant/getParams*', (route) =>
    route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        code: 200,
        data: {
          auth_states: [
            { value: 0, label: '待审核' },
            { value: 1, label: '审核通过' },
            { value: 2, label: '审核拒绝' }
          ],
          expire_statuses: []
        }
      })
    })
  )
  await page.route('**/admin/merchant.Merchant/list*', (route) =>
    route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ code: 200, data: { count: 1, list: [merchant], status_nums: {} } })
    })
  )
  await page.route('**/admin/merchant.Merchant/authorizationLogs*', (route) =>
    route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({
        code: 200,
        data: {
          count: 1,
          list: [
            {
              id: 9,
              action_title: '绑定会员',
              before_title: '会员 #13',
              after_title: '会员 #24',
              operator_title: '超级管理员',
              source_title: '平台后台',
              reason: '门店负责人变更',
              request_id: 'merchant-member-bind:test-request',
              create_time: '2026-08-25 13:00:00'
            }
          ]
        }
      })
    })
  )

  let bindingPayload = null
  await page.route('**/admin/merchant.Merchant/memberBind*', async (route) => {
    bindingPayload = route.request().postDataJSON()
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ code: 200, msg: '操作成功', data: { changed: true } })
    })
  })

  await page.goto(`${base}#/merchant/merchant`, { waitUntil: 'networkidle' })
  await expect(page.getByText('权限测试商家').first()).toBeVisible()
  await page.getByRole('button', { name: '绑定会员' }).first().click()
  const dialog = page.getByRole('dialog', { name: '绑定小程序会员' })
  await expect(dialog.locator('input').nth(0)).toHaveValue('权限测试商家')
  await expect(dialog.locator('input').nth(1)).toHaveValue('会员 #13')
  await expect(dialog.getByText('换绑或解绑将同时取消该权限')).toBeVisible()
  await dialog.getByRole('spinbutton').fill('24')
  await dialog.getByRole('button', { name: '确认变更' }).click()
  await expect(page.getByText('请填写会员绑定变更原因')).toBeVisible()
  expect(bindingPayload).toBeNull()

  await dialog.getByPlaceholder('请填写绑定、换绑或解绑原因').fill('门店负责人变更')
  await dialog.getByRole('button', { name: '确认变更' }).click()
  await expect.poll(() => bindingPayload).not.toBeNull()
  expect(bindingPayload.id).toBe(4)
  expect(bindingPayload.member_id).toBe(24)
  expect(bindingPayload.reason).toBe('门店负责人变更')
  expect(bindingPayload.request_id).toMatch(/^merchant-member-bind:/)

  await page.getByRole('button', { name: '授权记录' }).first().click()
  const drawer = page.getByLabel('权限测试商家 · 授权记录')
  await expect(drawer.getByText('会员 #13 → 会员 #24')).toBeVisible()
  await expect(drawer.getByText('超级管理员')).toBeVisible()
  await expect(drawer.getByText('门店负责人变更')).toBeVisible()
  await expect(drawer.getByText('merchant-member-bind:test-request')).toBeVisible()
})
