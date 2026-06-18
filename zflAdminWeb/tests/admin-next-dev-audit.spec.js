import { test, expect, devices, chromium, request } from '@playwright/test'

const origin = process.env.ADMIN_AUDIT_ORIGIN || 'http://127.0.0.1:807'
const basePath = process.env.ADMIN_AUDIT_BASE_PATH || '/admin-next-dev/'
const base = `${origin}${basePath}`
const adminAuditPassword =
  process.env.ADMIN_AUDIT_PASSWORD || process.env.ADMIN_LOCAL_PASSWORD || '123456'

async function createToken() {
  const api = await request.newContext()
  const response = await api.post(`${origin}/admin/system.Login/login?lang=zh-cn`, {
    data: {
      username: 'admin',
      password: adminAuditPassword,
      captcha_id: '',
      captcha_code: '',
      ajcaptcha: {}
    }
  })
  const json = await response.json()
  await api.dispose()
  expect(json.code).toBe(200)
  expect(json.data?.AdminToken).toBeTruthy()
  return json.data.AdminToken
}

async function createAuthedApi(token) {
  return request.newContext({
    extraHTTPHeaders: {
      AdminToken: token,
      'think-lang': 'zh-cn'
    }
  })
}

async function fetchGoodsTransferFixture(token) {
  const api = await createAuthedApi(token)

  try {
    const [paramsResponse, listResponse] = await Promise.all([
      api.get(`${origin}/admin/goods.Goods/getParams`),
      api.get(`${origin}/admin/goods.Goods/list`, {
        params: {
          page: 1,
          limit: 100,
          merchant_id: -1
        }
      })
    ])

    const paramsJson = await paramsResponse.json()
    const listJson = await listResponse.json()

    expect(paramsJson.code).toBe(200)
    expect(listJson.code).toBe(200)

    const merchantList = Array.isArray(paramsJson.data?.merchant_list)
      ? paramsJson.data.merchant_list
      : []
    const goodsList = Array.isArray(listJson.data?.list) ? listJson.data.list : []
    const candidate = goodsList.find(
      (item) => Number(item.merchant_id) > 0 && Number(item.is_delete || 0) === 0
    )

    expect(candidate).toBeTruthy()

    const targetMerchant = merchantList.find(
      (item) => Number(item.id) > 0 && Number(item.id) !== Number(candidate.merchant_id)
    )

    expect(targetMerchant).toBeTruthy()

    const originalMerchant = merchantList.find(
      (item) => Number(item.id) === Number(candidate.merchant_id)
    ) || {
      id: candidate.merchant_id,
      member_id: candidate.member_id || 0
    }

    return {
      goodsId: Number(candidate.id),
      originalMerchantId: Number(candidate.merchant_id),
      targetMerchantId: Number(targetMerchant.id),
      originalMerchantTitle: originalMerchant.title || '',
      targetMerchantTitle: targetMerchant.title || ''
    }
  } finally {
    await api.dispose()
  }
}

async function fetchGoodsLabelFixture(token) {
  const api = await createAuthedApi(token)

  try {
    const response = await api.get(`${origin}/admin/goods.Goods/getParams`)
    const json = await response.json()
    expect(json.code).toBe(200)
    const labels = Array.isArray(json.data?.goods_labels) ? json.data.goods_labels : []
    const targetLabel = labels.find((item) => Number(item.id) > 0)
    if (!targetLabel) return null
    return {
      labelId: Number(targetLabel.id),
      labelTitle: targetLabel.title
    }
  } finally {
    await api.dispose()
  }
}

async function fetchGoodsInfo(token, goodsId) {
  const api = await createAuthedApi(token)

  try {
    const response = await api.get(`${origin}/admin/goods.Goods/info`, {
      params: { id: goodsId }
    })
    const json = await response.json()
    expect(json.code).toBe(200)
    return json.data
  } finally {
    await api.dispose()
  }
}

async function restoreGoodsMerchant(token, goodsId, merchantId) {
  const api = await createAuthedApi(token)

  try {
    if (Number(merchantId) === 0) {
      await api.post(`${origin}/admin/goods.Goods/transferToPlatform`, {
        data: { ids: [goodsId] }
      })
      return
    }

    await api.post(`${origin}/admin/goods.Goods/transferToMerchant`, {
      data: {
        ids: [goodsId],
        target_merchant_id: merchantId
      }
    })
  } finally {
    await api.dispose()
  }
}

async function restoreGoodsLabels(token, goodsId, labelIds) {
  const api = await createAuthedApi(token)

  try {
    await api.post(`${origin}/admin/goods.Goods/batchUpdateLabels`, {
      data: {
        ids: [goodsId],
        goods_label_id: labelIds
      }
    })
  } finally {
    await api.dispose()
  }
}

async function injectToken(page, token) {
  await page.addInitScript((tokenValue) => {
    window.localStorage.setItem('admin_AdminToken', tokenValue)
    window.localStorage.setItem('admin_tokenType', 'header')
    window.localStorage.setItem('admin_tokenName', 'AdminToken')
    window.localStorage.setItem('admin_layout', 'left')
    window.localStorage.setItem('admin_language', 'zh-cn')
  }, token)
}

async function seedTokenOnce(page, token) {
  await page.goto(base, { waitUntil: 'domcontentloaded' })
  await page.evaluate((tokenValue) => {
    window.localStorage.setItem('admin_AdminToken', tokenValue)
    window.localStorage.setItem('admin_tokenType', 'header')
    window.localStorage.setItem('admin_tokenName', 'AdminToken')
    window.localStorage.setItem('admin_layout', 'left')
    window.localStorage.setItem('admin_language', 'zh-cn')
  }, token)
  await page.reload({ waitUntil: 'domcontentloaded' })
}

async function createAuthedPage(context, token) {
  const page = await context.newPage()
  await seedTokenOnce(page, token)
  await page.goto(`${base}#/dashboard`, { waitUntil: 'networkidle' })
  await waitForSettled(page, 1200)
  return page
}

async function clickByEval(locator) {
  await locator.first().evaluate((node) => node.click())
}

async function ensureRowSelected(row) {
  const checkbox = row.locator('.el-checkbox').first()
  const isChecked = await checkbox.evaluate((node) => node.classList.contains('is-checked'))
  if (!isChecked) {
    await clickByEval(checkbox)
  }
}

async function selectElementOption(page, label, value) {
  const formItem = page.locator('.el-form-item', { hasText: label }).first()
  await clickByEval(formItem.locator('.el-select'))
  await clickByEval(page.locator(`.el-select-dropdown__item`).filter({ hasText: value }))
}

async function waitForSettled(page, timeout = 1200) {
  await page.waitForTimeout(timeout)
}

async function waitForUrlChange(page, currentUrl) {
  await page.waitForFunction((url) => window.location.href !== url, currentUrl, { timeout: 8000 })
}

async function waitForRouteRegistered(page, path) {
  await page.waitForFunction(
    (targetPath) => {
      const app = document.querySelector('#app')?.__vue_app__
      const router = app?.config?.globalProperties?.$router
      return router?.getRoutes?.().some((item) => item.path === targetPath)
    },
    path,
    { timeout: 15000 }
  )
}

test.describe('admin-next-dev audit', () => {
  let token

  test.beforeAll(async () => {
    token = await createToken()
  })

  test('real backend exposes platform menus and permissions', async () => {
    const api = await request.newContext({
      extraHTTPHeaders: {
        AdminToken: token,
        'think-lang': 'zh-cn'
      }
    })

    const response = await api.get(`${origin}/admin/system.UserCenter/info`)
    const json = await response.json()
    await api.dispose()

    expect(json.code).toBe(200)
    expect(Array.isArray(json.data?.menus)).toBeTruthy()
    expect(json.data.menus.some((item) => item.path === '/platform')).toBeTruthy()
    expect(json.data.roles).toContain('admin/report.PlatformAnalytics/summary')
    expect(json.data.roles).toContain('admin/report.PlatformExport/orders')
  })

  test('login page can sign in through real form', async () => {
    test.setTimeout(60000)
    const browser = await chromium.launch({ headless: true })
    const context = await browser.newContext()
    const page = await context.newPage()

    try {
      await page.goto(`${base}#/login`, { waitUntil: 'networkidle' })
      await waitForSettled(page, 1200)

      await page.locator('input[placeholder="账号"]').fill('admin')
      await page.locator('input[placeholder="密码"]').fill(adminAuditPassword)
      await expect(page.locator('.login-bottom')).toBeEnabled()

      await Promise.all([
        page.waitForResponse(
          (response) =>
            response.url().includes('/admin/system.Login/login') &&
            response.request().method() === 'POST' &&
            response.status() === 200,
          { timeout: 20000 }
        ),
        page.locator('.login-bottom').click()
      ])

      await expect
        .poll(() => page.url(), { timeout: 30000, intervals: [1000, 1500, 2000] })
        .not.toContain('#/login')

      expect(page.url()).toContain('#/dashboard')
      await expect(page.locator('.topbar__title')).toContainText('控制台总览', { timeout: 30000 })
    } finally {
      await context.close()
      await browser.close()
    }
  })

  test('merchant module loads and detail drawer opens', async () => {
    const browser = await chromium.launch({ headless: true })
    const context = await browser.newContext()
    const page = await createAuthedPage(context, token)

    try {
      await page.goto(`${base}#/merchant/merchant`, { waitUntil: 'networkidle' })
      await waitForSettled(page)

      await expect(page.locator('.panel__title').first()).toHaveText('商家管理')
      await expect(page.locator('.status-card')).toHaveCount(4)
      await expect(page.locator('.el-table').first()).toBeVisible()

      const rowCount = await page.locator('.el-table__body tbody tr').count()
      expect(rowCount).toBeGreaterThan(0)

      await clickByEval(page.getByRole('button', { name: '详情' }))
      await waitForSettled(page)
      await expect(page.getByRole('heading', { name: '商家详情' })).toBeVisible()
    } finally {
      await context.close()
      await browser.close()
    }
  })

  test('goods operations dashboard supports real transfer flow and restores data', async () => {
    const fixture = await fetchGoodsTransferFixture(token)
    const labelFixture = await fetchGoodsLabelFixture(token)
    const goodsDetail = await fetchGoodsInfo(token, fixture.goodsId)
    const browser = await chromium.launch({ headless: true })
    const context = await browser.newContext({ acceptDownloads: true })
    const page = await createAuthedPage(context, token)

    try {
      test.setTimeout(120000)
      expect(Number(goodsDetail.image_id || 0)).toBeGreaterThan(0)
      await page.evaluate(() => {
        window.localStorage.removeItem('admin_next_goods_query_v1')
        window.localStorage.removeItem('admin_next_goods_operation_history_v1')
        window.localStorage.removeItem('admin_next_goods_bridge_preferences_v1')
      })
      await waitForRouteRegistered(page, '/goods/Goods')
      await page.goto(`${base}#/goods/Goods`, { waitUntil: 'networkidle' })
      await waitForSettled(page, 1500)
      expect(page.url()).toContain('#/goods/Goods')

      await expect(page.locator('.panel__title').first()).toContainText('商品管理', {
        timeout: 45000
      })
      await clickByEval(page.getByRole('button', { name: '展开高级操作' }))
      await waitForSettled(page, 600)
      await expect(page.locator('.batch-bridge__title').first()).toContainText('批量迁移工具', {
        timeout: 45000
      })
      await expect(
        page.locator('.panel__sub-title').filter({ hasText: '运营筛选' }).first()
      ).toBeVisible({ timeout: 45000 })
      await expect(page.locator('.combined-filters')).toContainText('联动筛选', { timeout: 45000 })
      await expect(page.getByRole('button', { name: '当前页全选' }).first()).toBeVisible({
        timeout: 45000
      })
      await expect(page.locator('.bridge-summary')).toContainText('当前模式：迁移到平台', {
        timeout: 45000
      })
      await expect(
        page.getByRole('button', { name: '仅迁移已勾选到平台自营' }).first()
      ).toBeVisible({ timeout: 45000 })

      await expect(page.locator('.action-groups')).toContainText('状态处理')
      await expect(page.locator('.action-groups')).toContainText('图文维护')
      await expect(page.locator('.action-groups')).toContainText('归属迁移')
      await expect(page.locator('.bridge-insight-grid')).toContainText('迁移前归属分布')
      await expect(page.locator('.bridge-insight-grid')).toContainText('迁移后归属预判')
      await expect(page.locator('.bridge-risk-panel')).toContainText('操作风险提示')
      await expect(page.locator('.bridge-permissions')).toContainText('迁移权限状态')
      await expect(page.locator('.combined-filters')).toContainText('待审核且下架')
      await expect(page.locator('.online-actions--table')).toContainText('批量迁移到平台自营')
      await expect(page.locator('.online-actions--table')).toContainText('批量迁移到指定商家')
      await expect(page.locator('.online-actions--table')).toContainText('批量更换缩略图')
      await expect(page.locator('.merchant-picks')).toContainText('快捷筛选商家')
      await expect(page.locator('.filter-history')).toContainText('筛选轨迹')
      await expect(page.locator('.filter-history')).toContainText('回退一步')
      await expect(page.locator('.filter-history')).toContainText('前进一步')

      await selectElementOption(page, '关键词', 'ID')
      await page.locator('input[placeholder="请输入内容"]').fill(String(fixture.goodsId))
      await clickByEval(page.getByRole('button', { name: '查询' }))
      await waitForSettled(page, 1500)
      const goodsSearchUrl = page.url()
      expect(goodsSearchUrl).toContain(`search_field=id`)
      expect(goodsSearchUrl).toContain(`search_value=${fixture.goodsId}`)
      await expect(page.locator('.filter-history')).toContainText(/当前第 \d+ 步，共 \d+ 步/)
      await expect(page.getByRole('button', { name: '回退一步' })).toBeEnabled()
      await expect(page.getByRole('button', { name: '前进一步' })).toBeDisabled()

      await clickByEval(page.getByRole('button', { name: '回退一步' }))
      await waitForSettled(page, 1500)
      expect(page.url()).not.toContain(`search_value=${fixture.goodsId}`)
      await expect(page.locator('.current-params')).toContainText('当前为默认筛选条件')
      await expect(page.locator('.filter-history')).toContainText(/当前第 \d+ 步，共 \d+ 步/)
      await expect(page.getByRole('button', { name: '回退一步' })).toBeDisabled()
      await expect(page.getByRole('button', { name: '前进一步' })).toBeEnabled()

      await clickByEval(page.getByRole('button', { name: '前进一步' }))
      await waitForSettled(page, 1500)
      expect(page.url()).toContain(`search_value=${fixture.goodsId}`)
      await expect(page.locator('.filter-history')).toContainText(/当前第 \d+ 步，共 \d+ 步/)

      await page.reload({ waitUntil: 'networkidle' })
      await waitForSettled(page, 1500)
      expect(page.url()).toContain(`search_value=${fixture.goodsId}`)

      await page.goto(`${base}#/dashboard`, { waitUntil: 'networkidle' })
      await page.goto(`${base}#/goods/Goods`, { waitUntil: 'networkidle' })
      await waitForSettled(page, 1500)
      expect(page.url()).toContain(`search_value=${fixture.goodsId}`)

      const rows = page.locator('.el-table__body tbody tr')
      await expect(rows).toHaveCount(1)
      await expect(page.getByRole('columnheader', { name: '价格' })).toBeVisible()
      await expect(rows.first()).toContainText('勾选迁移')
      await expect(rows.first()).toContainText('无审核备注')

      await clickByEval(page.getByRole('button', { name: '批量打标' }))
      await waitForSettled(page, 500)
      await expect(page.getByRole('dialog')).toContainText('处理当前页')
      await expect(page.getByRole('dialog')).toContainText('将按当前筛选页的结果整体执行。')
      await clickByEval(page.getByRole('dialog').getByRole('button', { name: '取消' }))
      await waitForSettled(page, 400)

      await clickByEval(rows.first().getByText('详情', { exact: true }))
      await waitForSettled(page, 1200)
      await expect(page.getByText('商品详情')).toBeVisible()
      await expect(page.locator('.goods-detail')).toContainText(goodsDetail.title)
      await expect(page.locator('.goods-detail')).toContainText(goodsDetail.code)
      await page.keyboard.press('Escape')
      await waitForSettled(page, 500)
      await clickByEval(rows.first().locator('.el-checkbox'))
      await waitForSettled(page, 600)

      await clickByEval(page.getByRole('button', { name: '批量审核' }))
      await waitForSettled(page, 500)
      await expect(page.getByRole('dialog')).toContainText('商品审核')
      await clickByEval(page.getByRole('dialog').getByText('审核通过'))
      await clickByEval(page.getByRole('dialog').getByRole('button', { name: '提交' }))
      await waitForSettled(page, 1800)
      const authMessage = (await page.locator('.el-message').last().innerText()).trim()
      if (authMessage.includes('成功')) {
        await expect(page.locator('.bridge-feedback')).toContainText('批量审核')
        await expect(page.locator('.bridge-feedback')).toContainText('审核通过')
        await expect(page.locator('.bridge-feedback')).toContainText(
          `操作前筛选：关键词 ${fixture.goodsId}`
        )
        await expect(page.locator('.operation-diff')).toContainText('审核结果摘要')
        await expect(page.locator('.operation-diff')).toContainText('审核变化')
      } else {
        expect(authMessage).toContain('未有符合条件的商品需要审核')
      }
      await clickByEval(rows.first().locator('.el-checkbox'))
      await waitForSettled(page, 600)

      await clickByEval(page.getByRole('button', { name: '批量打标' }))
      await waitForSettled(page, 500)
      await expect(page.getByRole('dialog')).toContainText('商品批量打标')
      if (labelFixture?.labelTitle) {
        await clickByEval(page.getByRole('dialog').locator('.el-select'))
        await clickByEval(
          page.locator('.el-select-dropdown__item').filter({ hasText: labelFixture.labelTitle })
        )
      }
      await clickByEval(page.getByRole('dialog').getByRole('button', { name: '提交' }))
      await waitForSettled(page, 1800)
      await expect(page.locator('.el-message').last()).toContainText('成功')
      await expect(page.locator('.bridge-feedback')).toContainText('批量打标')
      await expect(page.locator('.bridge-feedback')).toContainText(
        labelFixture?.labelTitle ? '标签已更新' : '标签已清空'
      )
      await expect(page.locator('.bridge-feedback')).toContainText(
        `操作前筛选：关键词 ${fixture.goodsId}`
      )
      await expect(page.locator('.operation-diff')).toContainText('标签更新摘要')
      await clickByEval(rows.first().locator('.el-checkbox'))
      await waitForSettled(page, 600)

      await clickByEval(page.getByRole('button', { name: '批量换图' }))
      await waitForSettled(page, 500)
      await expect(page.getByRole('dialog')).toContainText('商品批量换图')
      await clickByEval(page.getByRole('button', { name: '使用首个商品当前缩略图' }))
      await waitForSettled(page, 500)
      await clickByEval(page.getByRole('dialog').getByRole('button', { name: '提交' }))
      await waitForSettled(page, 1800)
      await expect(page.locator('.el-message').last()).toContainText('成功')
      await expect(page.locator('.bridge-feedback')).toContainText('批量换图')
      await expect(page.locator('.bridge-feedback')).toContainText('缩略图已更新')
      await expect(page.locator('.operation-diff')).toContainText('缩略图更新摘要')
      await clickByEval(rows.first().locator('.el-checkbox'))
      await waitForSettled(page, 600)

      await clickByEval(page.getByRole('button', { name: '删除' }))
      await waitForSettled(page, 500)
      await expect(page.getByRole('dialog')).toContainText('商品删除')
      await clickByEval(page.getByRole('dialog').getByRole('button', { name: '取消' }))
      await waitForSettled(page, 400)
      await ensureRowSelected(rows.first())
      await waitForSettled(page, 600)

      await clickByEval(page.getByRole('button', { name: '仅迁移已勾选到平台自营' }))
      await waitForSettled(page, 600)
      await expect(page.getByRole('dialog')).toContainText('商品转平台')
      await expect(page.getByRole('dialog')).toContainText('处理范围')
      await expect(page.getByRole('dialog')).toContainText('仅处理已勾选')
      await expect(page.getByRole('dialog')).toContainText('迁移后预判')
      await clickByEval(page.getByRole('dialog').getByText('指定商品 ID', { exact: true }))
      await waitForSettled(page, 300)
      await clickByEval(page.getByRole('dialog').getByRole('button', { name: '填入当前勾选' }))
      await waitForSettled(page, 300)
      await expect(page.getByRole('dialog')).toContainText('手动指定 1 件')
      await expect(
        page.getByRole('dialog').locator('textarea[placeholder="例如：1001,1002,1003"]')
      ).toHaveValue(String(fixture.goodsId))
      await expect(page.getByRole('dialog')).toContainText('已识别商品预览')
      await expect(page.getByRole('dialog')).toContainText(`#${fixture.goodsId}`)
      await expect(page.getByRole('dialog')).toContainText('当前归属：')
      await expect(page.getByRole('dialog')).toContainText('本次将迁移至平台自营')
      await expect(page.getByRole('dialog')).toContainText('预计发生归属变化 1 件，保持原归属 0 件')
      await expect(page.getByRole('dialog')).toContainText('平台自营 0 件')
      await expect(page.getByRole('dialog')).toContainText('商家归属 1 件')
      await page
        .getByRole('dialog')
        .locator('textarea[placeholder="例如：1001,1002,1003"]')
        .fill(`${fixture.goodsId},abc,${fixture.goodsId}`)
      await waitForSettled(page, 300)
      await expect(page.getByRole('dialog')).toContainText('已去重 1 个')
      await expect(page.getByRole('dialog')).toContainText('已忽略 1 项无效内容')
      const keepResolvedButton = page
        .getByRole('dialog')
        .locator('.transfer-manual-actions .el-button')
        .filter({ hasText: '仅保留已识别' })
      await expect(keepResolvedButton).toBeVisible()
      await expect(keepResolvedButton).toBeEnabled()
      await clickByEval(keepResolvedButton)
      await waitForSettled(page, 300)
      await expect(
        page.getByRole('dialog').locator('textarea[placeholder="例如：1001,1002,1003"]')
      ).toHaveValue(String(fixture.goodsId))
      await clickByEval(page.getByRole('dialog').getByRole('button', { name: '提交' }))
      await waitForSettled(page, 1800)
      await expect(page.locator('.el-message')).toContainText('成功')
      await expect(page.locator('.bridge-feedback')).toContainText('最近一次操作回显')
      await expect(page.locator('.bridge-feedback')).toContainText('目标：平台自营')
      await expect(page.locator('.bridge-feedback')).toContainText('定位首个商品')
      await expect(page.locator('.bridge-feedback')).toContainText('恢复操作前筛选')
      await expect(page.locator('.bridge-feedback')).toContainText(
        `回退到${fixture.originalMerchantTitle}`
      )
      await expect(page.locator('.bridge-feedback')).toContainText(
        `操作前筛选：关键词 ${fixture.goodsId}`
      )
      await expect(page.locator('.operation-diff')).toContainText('迁移后差异高亮')
      await expect(page.locator('.operation-diff')).toContainText('本次 1 件商品都会发生归属变化。')
      await expect(page.locator('.bridge-history')).toContainText('最近操作记录')

      const exportDownloadPromise = page.waitForEvent('download', { timeout: 20000 })
      await clickByEval(page.getByRole('button', { name: '导出操作摘要' }))
      const exportDownload = await exportDownloadPromise
      expect(exportDownload.suggestedFilename()).toContain('goods_operation_summary_')

      const afterPlatform = await fetchGoodsInfo(token, fixture.goodsId)
      expect(Number(afterPlatform.merchant_id)).toBe(0)

      await page.locator('input[placeholder="请输入内容"]').fill(String(fixture.goodsId))
      await clickByEval(page.getByRole('button', { name: '查询' }))
      await waitForSettled(page, 1500)
      await clickByEval(rows.first().locator('.el-checkbox'))
      await waitForSettled(page, 600)

      await clickByEval(page.getByText('迁移到商家', { exact: true }))
      await waitForSettled(page, 500)
      await expect(page.getByRole('dialog')).toContainText('处理当前页')
      await expect(page.getByRole('dialog')).toContainText('快捷目标')
      await expect(page.getByRole('dialog')).toContainText(fixture.targetMerchantTitle)
      await clickByEval(page.locator('.bridge-merchant-select'))
      await clickByEval(
        page
          .locator('.bridge-merchant-dropdown .el-select-dropdown__item')
          .filter({ hasText: fixture.targetMerchantTitle })
      )
      await clickByEval(page.getByRole('button', { name: '仅迁移已勾选到指定商家' }))
      await waitForSettled(page, 600)
      await expect(page.getByRole('dialog')).toContainText('商品转商家')
      await expect(page.getByRole('dialog')).toContainText(fixture.targetMerchantTitle)
      await expect(page.getByRole('dialog')).toContainText('迁移预判')
      await clickByEval(page.locator('.transfer-merchant-dialog-select'))
      await clickByEval(
        page
          .locator('.transfer-merchant-dialog-dropdown .el-select-dropdown__item')
          .filter({ hasText: fixture.targetMerchantTitle })
      )
      await expect(page.getByRole('dialog')).toContainText(
        `预计 1 件商品统一迁移到 ${fixture.targetMerchantTitle}`
      )
      await clickByEval(page.getByRole('dialog').getByRole('button', { name: '提交' }))
      await waitForSettled(page, 1800)
      await expect(page.locator('.el-message')).toContainText('成功')
      await expect(page.locator('.bridge-feedback')).toContainText('最近一次操作回显')
      await expect(page.locator('.bridge-feedback')).toContainText(
        `目标：${fixture.targetMerchantTitle}`
      )
      await expect(page.locator('.operation-diff')).toContainText('迁移后差异高亮')
      await expect(page.locator('.operation-diff')).toContainText('本次 1 件商品都会发生归属变化。')
      await expect(page.locator('.bridge-history')).toContainText('恢复筛选')

      await page.reload({ waitUntil: 'networkidle' })
      await waitForSettled(page, 1500)
      await expect(page.locator('.bridge-feedback')).toContainText(
        `目标：${fixture.targetMerchantTitle}`
      )
      await expect(page.locator('.bridge-history')).toContainText('迁移到商家')
      await expect(page.locator('.bridge-history')).toContainText(fixture.targetMerchantTitle)
      await expect(page.locator('.bridge-summary')).toContainText('当前模式：迁移到商家')
      await expect(page.locator('.bridge-summary')).toContainText('已记忆偏好：迁移到商家')
      await expect(page.locator('.bridge-summary')).toContainText(
        `目标商家：${fixture.targetMerchantTitle}`
      )
      await expect(page.locator('.bridge-control--merchant')).toContainText('常用目标')
      await expect(page.locator('.bridge-control--merchant')).toContainText(
        fixture.targetMerchantTitle
      )
      await expect(rows.first()).toContainText('转到已记忆目标')
      await clickByEval(rows.first().getByText('转到已记忆目标', { exact: true }))
      await waitForSettled(page, 500)
      await expect(page.getByRole('dialog')).toContainText(
        `直接迁移到已记忆目标 ${fixture.targetMerchantTitle}`
      )
      await clickByEval(page.getByRole('dialog').getByRole('button', { name: '取消' }))
      await waitForSettled(page, 400)
      await expect(page.locator('.bridge-target-switch')).toContainText('快捷切换常用迁移目标')
      await expect(page.locator('.bridge-target-switch')).toContainText('平台自营')
      await expect(page.locator('.bridge-target-switch')).toContainText(fixture.targetMerchantTitle)
      await clickByEval(
        page.locator('.bridge-target-switch').getByRole('button', { name: '平台自营' })
      )
      await waitForSettled(page, 500)
      await expect(page.locator('.bridge-summary')).toContainText('当前模式：迁移到平台')
      await expect(page.locator('.bridge-summary')).toContainText('已记忆偏好：迁移到平台')

      await clickByEval(rows.first().locator('.el-checkbox'))
      await waitForSettled(page, 600)
      await clickByEval(page.getByRole('button', { name: '仅迁移已勾选到指定商家' }))
      await waitForSettled(page, 600)
      await expect(page.getByRole('dialog')).toContainText(fixture.targetMerchantTitle)
      await clickByEval(page.getByRole('dialog').getByRole('button', { name: '取消' }))
      await waitForSettled(page, 400)

      await clickByEval(page.getByRole('button', { name: '清除迁移偏好' }))
      await waitForSettled(page, 600)
      await expect(page.locator('.bridge-summary')).toContainText('当前模式：迁移到平台')
      await expect(page.locator('.bridge-summary')).toContainText('已记忆偏好：迁移到平台')

      const afterMerchant = await fetchGoodsInfo(token, fixture.goodsId)
      expect(Number(afterMerchant.merchant_id)).toBe(fixture.targetMerchantId)
    } finally {
      await restoreGoodsMerchant(token, fixture.goodsId, fixture.originalMerchantId)
      await restoreGoodsLabels(token, fixture.goodsId, goodsDetail.goods_label_id || [])
      await context.close()
      await browser.close()
    }

    const restored = await fetchGoodsInfo(token, fixture.goodsId)
    expect(Number(restored.merchant_id)).toBe(fixture.originalMerchantId)

    const mobileBrowser = await chromium.launch({ headless: true })
    const mobileContext = await mobileBrowser.newContext({ ...devices['iPhone 12'] })
    const mobilePage = await createAuthedPage(mobileContext, token)

    try {
      await waitForRouteRegistered(mobilePage, '/goods/Goods')
      await mobilePage.goto(`${base}#/goods/Goods`, { waitUntil: 'networkidle' })
      await waitForSettled(mobilePage, 1200)

      const shortcutXs = await mobilePage
        .locator('.hero-shortcut')
        .evaluateAll((nodes) => nodes.map((node) => node.getBoundingClientRect().x))
      expect(
        shortcutXs.every((value, index, arr) => index === 0 || Math.abs(value - arr[0]) < 2)
      ).toBeTruthy()

      const metricXs = await mobilePage
        .locator('.metric-card')
        .evaluateAll((nodes) => nodes.map((node) => node.getBoundingClientRect().x))
      expect(
        metricXs.every((value, index, arr) => index === 0 || Math.abs(value - arr[0]) < 2)
      ).toBeTruthy()
    } finally {
      await mobileContext.close()
      await mobileBrowser.close()
    }
  })

  test('analytics works with history and remembered filters', async () => {
    const browser = await chromium.launch({ headless: true })
    const context = await browser.newContext()
    const page = await createAuthedPage(context, token)

    try {
      await page.goto(`${base}#/analytics`, { waitUntil: 'networkidle' })
      await waitForSettled(page)

      await expect(page.locator('.panel__title').first()).toHaveText('超级管理员数据中心')
      await expect(page.locator('.chart')).toHaveCount(4)

      const merchantHighlight = page.locator('.highlight-card--merchant').first()
      if (await merchantHighlight.count()) {
        await clickByEval(merchantHighlight)
        await waitForSettled(page)
        await expect(page.getByRole('heading', { name: '商家分析详情' })).toBeVisible()
      }

      await clickByEval(page.locator('.quick-range__item').filter({ hasText: '昨天' }))
      await waitForSettled(page, 1500)
      const changedUrl = page.url()
      expect(changedUrl).toContain('quick_date=yesterday')

      await page.evaluate(() => history.back())
      await waitForUrlChange(page, changedUrl)
      expect(page.url()).not.toBe(changedUrl)

      await page.evaluate(() => history.forward())
      await waitForSettled(page, 1500)
      expect(page.url()).toBe(changedUrl)

      await page.goto(`${base}#/dashboard`, { waitUntil: 'networkidle' })
      await page.goto(`${base}#/analytics`, { waitUntil: 'networkidle' })
      await waitForSettled(page, 1500)
      expect(page.url()).toBe(changedUrl)

      await page.goto(`${base}#/login`, { waitUntil: 'networkidle' })
      await page.evaluate((tokenValue) => {
        window.localStorage.removeItem('admin_AdminToken')
        window.localStorage.setItem('admin_AdminToken', tokenValue)
        window.localStorage.setItem('admin_tokenType', 'header')
        window.localStorage.setItem('admin_tokenName', 'AdminToken')
      }, token)
      await page.goto(`${base}#/analytics`, { waitUntil: 'networkidle' })
      await waitForSettled(page, 1500)
      expect(page.url()).toBe(changedUrl)
    } finally {
      await context.close()
      await browser.close()
    }
  })

  test('exports downloads csv and remembers filters', async () => {
    const browser = await chromium.launch({ headless: true })
    const context = await browser.newContext({ acceptDownloads: true })
    const page = await createAuthedPage(context, token)

    try {
      await page.goto(`${base}#/exports`, { waitUntil: 'networkidle' })
      await waitForSettled(page)
      await page.waitForFunction(
        () => {
          const text = document.body?.innerText || ''
          return text.includes('导出中心') && document.querySelectorAll('.export-card').length >= 1
        },
        null,
        { timeout: 30000 }
      )

      await expect(page.locator('.panel__title').first()).toHaveText('导出中心')
      await expect(page.locator('.export-card')).toHaveCount(4)

      const enabledButtons = page.locator('.export-card .el-button:not([disabled])')
      expect(await enabledButtons.count()).toBeGreaterThan(0)

      const historyBefore = await page.locator('.history-item').count()
      const downloadPromise = page.waitForEvent('download', { timeout: 20000 })
      await clickByEval(enabledButtons)
      const download = await downloadPromise
      await waitForSettled(page, 1500)

      expect(download.suggestedFilename()).toContain('platform_')
      expect(await page.locator('.history-item').count()).toBeGreaterThanOrEqual(historyBefore)

      await clickByEval(page.locator('.quick-range__item').filter({ hasText: '昨天' }))
      await waitForSettled(page, 1500)
      const changedUrl = page.url()
      expect(changedUrl).toContain('quick_date=yesterday')

      await page.evaluate(() => history.back())
      await waitForSettled(page, 1500)
      expect(page.url()).not.toBe(changedUrl)

      await page.evaluate(() => history.forward())
      await waitForSettled(page, 1500)
      expect(page.url()).toBe(changedUrl)

      await page.goto(`${base}#/dashboard`, { waitUntil: 'networkidle' })
      await page.goto(`${base}#/exports`, { waitUntil: 'networkidle' })
      await waitForSettled(page, 1500)
      expect(page.url()).toBe(changedUrl)
    } finally {
      await context.close()
      await browser.close()
    }
  })

  test('analytics resilience and mobile layout stay usable', async () => {
    const browser = await chromium.launch({ headless: true })
    const context = await browser.newContext()
    const page = await createAuthedPage(context, token)

    try {
      await page.route(`${origin}/admin/report.PlatformAnalytics/summary*`, async (route) => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json; charset=utf-8',
          body: JSON.stringify({ code: 500, msg: '测试故障：统计汇总不可用', data: null })
        })
      })

      await page.goto(`${base}#/analytics`, { waitUntil: 'networkidle' })
      await waitForSettled(page, 1500)
      await expect(page.locator('.panel__title').first()).toHaveText('超级管理员数据中心')
      await expect(page.locator('.el-message').first()).toContainText('测试故障：统计汇总不可用')
      expect(page.url()).not.toContain('#/login')
    } finally {
      await context.close()
      await browser.close()
    }

    const mobileBrowser = await chromium.launch({ headless: true })
    const mobileContext = await mobileBrowser.newContext({ ...devices['iPhone 12'] })
    const mobilePage = await createAuthedPage(mobileContext, token)

    try {
      await mobilePage.goto(`${base}#/analytics`, { waitUntil: 'networkidle' })
      await waitForSettled(mobilePage, 1200)

      const summaryXs = await mobilePage
        .locator('.summary-banner__item')
        .evaluateAll((nodes) => nodes.map((node) => node.getBoundingClientRect().x))
      expect(
        summaryXs.every((value, index, arr) => index === 0 || Math.abs(value - arr[0]) < 2)
      ).toBeTruthy()

      await mobilePage.goto(`${base}#/exports`, { waitUntil: 'networkidle' })
      await waitForSettled(mobilePage, 1200)

      const exportXs = await mobilePage
        .locator('.export-card')
        .evaluateAll((nodes) => nodes.map((node) => node.getBoundingClientRect().x))
      expect(
        exportXs.every((value, index, arr) => index === 0 || Math.abs(value - arr[0]) < 2)
      ).toBeTruthy()
    } finally {
      await mobileContext.close()
      await mobileBrowser.close()
    }
  })

  test('sidebar toggle and logout stay usable', async () => {
    const browser = await chromium.launch({ headless: true })
    const context = await browser.newContext()
    const page = await context.newPage()

    try {
      await seedTokenOnce(page, token)
      await page.goto(`${base}#/dashboard`, { waitUntil: 'networkidle' })
      await waitForSettled(page, 1200)
      await page.waitForFunction(
        () => {
          return (
            !!document.querySelector('.admin-next-shell') &&
            !!document.querySelector('.aside__toggle')
          )
        },
        null,
        { timeout: 30000 }
      )

      const shell = page.locator('.admin-next-shell').first()
      await expect(shell).not.toHaveClass(/sidebar-collapsed/)

      await clickByEval(page.locator('.aside__toggle'))
      await waitForSettled(page, 600)
      await expect(shell).toHaveClass(/sidebar-collapsed/)

      await clickByEval(page.locator('.aside__toggle'))
      await waitForSettled(page, 600)
      await expect(shell).not.toHaveClass(/sidebar-collapsed/)

      await clickByEval(page.getByRole('button', { name: '退出登录' }))
      await waitForSettled(page, 400)
      await clickByEval(page.locator('.el-message-box__btns .el-button--primary'))
      await page.waitForFunction(() => !window.localStorage.getItem('admin_AdminToken'), null, {
        timeout: 15000
      })
      await page.waitForTimeout(1200)

      expect(await page.evaluate(() => window.localStorage.getItem('admin_AdminToken') || '')).toBe(
        ''
      )
    } finally {
      await context.close()
      await browser.close()
    }
  })
})
