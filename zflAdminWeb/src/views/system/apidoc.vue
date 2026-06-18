<template>
  <div class="app-container">
    <el-card class="app-main">
      <div class="apidoc-overview">
        <div class="apidoc-overview__head">
          <div>
            <div class="apidoc-overview__title">接口文档</div>
            <div class="apidoc-overview__desc">查看当前环境的接口文档地址、访问凭据和在线预览。</div>
          </div>
          <div class="apidoc-overview__head-actions">
            <el-button text type="primary" title="刷新" @click="refresh()">
              <svg-icon icon-class="refresh" size="18px" />
            </el-button>
            <el-link
              type="primary"
              :href="model.apidoc_url"
              :underline="false"
              target="_blank"
              title="新标签页打开"
            >
              <svg-icon icon-class="position" size="18px" />
            </el-link>
          </div>
        </div>
        <div v-if="entryContextVisible" class="entry-context-banner">
          <div class="entry-context-banner__main">
            <div class="entry-context-banner__eyebrow">外部入口承接</div>
            <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
            <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
          </div>
          <div class="entry-context-banner__actions">
            <el-button type="primary" @click="handleEntryContextPrimary">
              {{ entryContextPrimaryLabel }}
            </el-button>
            <el-button @click="goToEntryContextBack">回来源页</el-button>
          </div>
        </div>
        <div class="page-overview-grid">
          <div v-for="item in summaryItems" :key="item.label" class="page-overview-grid__item">
            <span class="page-overview-grid__label">{{ item.label }}</span>
            <strong class="page-overview-grid__value">{{ item.value }}</strong>
          </div>
        </div>
        <div class="plain-guide">
          <div class="plain-guide__header">
            <div>
              <div class="plain-guide__title">这页建议先这样看</div>
              <div class="plain-guide__desc">
                接口文档页不是先点预览，而是先确认文档地址、访问密码和当前 token 对不对。信息都对了，再决定去站内预览、去新标签页打开，还是回系统设置继续核配置。
              </div>
            </div>
            <span class="plain-guide__badge">{{ apidocFocusLabel }}</span>
          </div>
          <div class="plain-guide__grid">
            <div v-for="item in apidocGuideCards" :key="item.title" class="plain-guide-card">
              <div class="plain-guide-card__title">{{ item.title }}</div>
              <div class="plain-guide-card__desc">{{ item.desc }}</div>
              <div class="plain-guide-card__action">{{ item.action }}</div>
            </div>
          </div>
        </div>
        <div class="doc-status-strip">
          <div class="doc-status-strip__main">
            <div class="doc-status-strip__label">当前建议</div>
            <div class="doc-status-strip__title">{{ apidocCurrentSuggestion.title }}</div>
            <div class="doc-status-strip__desc">{{ apidocCurrentSuggestion.desc }}</div>
          </div>
          <div class="doc-status-strip__tags">
            <span v-for="item in apidocCurrentSuggestion.tags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="doc-info-panel">
          <div class="doc-info-row">
            <span class="doc-info-row__label">环境</span>
            <div class="doc-info-row__content">
              <span class="doc-info-row__text">{{ envLabel }}</span>
            </div>
          </div>
          <div class="doc-info-row">
            <span class="doc-info-row__label">访问密码</span>
            <div class="doc-info-row__content">
              <span class="doc-info-row__text">{{ model.apidoc_pwd || '未设置' }}</span>
              <el-button text type="primary" title="复制密码" @click="copy(model.apidoc_pwd)">
                <svg-icon icon-class="copy-document" />
              </el-button>
            </div>
          </div>
          <div class="doc-info-row">
            <span class="doc-info-row__label">访问 Token</span>
            <div class="doc-info-row__content">
              <span class="doc-info-row__text">{{ model.token_sub || '未获取' }}</span>
              <el-button text type="primary" title="复制Token" @click="copy(model.token)">
                <svg-icon icon-class="copy-document" />
              </el-button>
            </div>
          </div>
          <div class="doc-info-row">
            <span class="doc-info-row__label">文档地址</span>
            <div class="doc-info-row__content doc-info-row__content--wrap">
              <span class="doc-info-row__text doc-info-row__text--link">{{ model.apidoc_url || '未配置文档地址' }}</span>
              <el-button text type="primary" title="复制文档地址" @click="copy(model.apidoc_url)">
                <svg-icon icon-class="copy-document" />
              </el-button>
              <el-link
                type="primary"
                :href="model.apidoc_url"
                :underline="false"
                target="_blank"
                title="新标签页打开"
              >
                打开
              </el-link>
            </div>
          </div>
        </div>
        <div class="doc-followup-grid">
          <button type="button" class="doc-followup-card" @click="goToSystemSetting('apiInfo')">
            <span class="doc-followup-card__title">去接口设置核配置</span>
            <span class="doc-followup-card__desc">看完文档地址和凭据后，直接回接口设置页继续核对开关、规则和保存回显。</span>
          </button>
          <button type="button" class="doc-followup-card" @click="goToSystemSetting('tokenInfo')">
            <span class="doc-followup-card__title">去 Token 设置核凭据</span>
            <span class="doc-followup-card__desc">如果当前 Token 不对或需要换调试口令，继续去 Token 设置页最顺。</span>
          </button>
          <button type="button" class="doc-followup-card" @click="goToUserLog">
            <span class="doc-followup-card__title">去操作日志看调用结果</span>
            <span class="doc-followup-card__desc">接口联调或后台动作异常时，直接去操作日志页继续定位结果。</span>
          </button>
        </div>
      </div>
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">文档预览</div>
          <div class="section-title-row__desc">默认不再自动嵌入预览，避免文档端脚本异常拖慢后台页面；需要查看时可手动打开。</div>
        </div>
        <div class="section-title-row__meta">{{ model.apidoc_url || '未配置文档地址' }}</div>
      </div>
      <div class="doc-preview-panel">
        <div class="doc-preview-panel__title">预览入口</div>
        <div class="doc-preview-panel__desc">
          当前文档站点在 iframe 场景下会触发模块脚本 MIME 异常。为避免影响后台操作，本页改为手动打开文档。
        </div>
        <div class="doc-preview-panel__actions">
          <el-button v-if="model.apidoc_url" type="primary" plain @click="openPreviewDrawer">站内预览</el-button>
          <el-link
            v-if="model.apidoc_url"
            type="primary"
            :href="model.apidoc_url"
            :underline="false"
            target="_blank"
          >
            在新标签页打开接口文档
          </el-link>
          <span v-else class="doc-preview-panel__empty">当前未配置文档地址</span>
        </div>
      </div>
    </el-card>

    <el-drawer v-model="previewVisible" title="接口文档预览" size="80%">
      <div class="preview-drawer">
        <div class="preview-drawer__toolbar">
          <div class="preview-drawer__hint">{{ previewDrawerHint }}</div>
          <el-link
            v-if="model.apidoc_url"
            type="primary"
            :href="model.apidoc_url"
            :underline="false"
            target="_blank"
          >
            新标签页打开
          </el-link>
        </div>
        <div class="preview-drawer__guide">
          <div v-for="item in previewGuideCards" :key="item.title" class="preview-guide-card">
            <div class="preview-guide-card__title">{{ item.title }}</div>
            <div class="preview-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
        <div v-if="previewError" class="preview-drawer__error">
          站内预览没有正常加载，通常是文档站点限制 iframe 或脚本资源异常。建议直接在新标签页打开。
        </div>
        <iframe
          v-if="previewVisible && model.apidoc_url"
          class="preview-drawer__frame"
          :src="model.apidoc_url"
          @load="handlePreviewLoad"
          @error="handlePreviewError"
        ></iframe>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import clip from '@/utils/clipboard'
import { apidoc } from '@/api/system/apidoc'
import { useUserStoreHook } from '@/store/modules/user'

export default {
  name: 'SystemApidoc',
  data() {
    return {
      name: '接口文档',
      height: 680,
      isload: false,
      envName: '',
      model: {
        apidoc_url: '',
        apidoc_pwd: '',
        token: '',
        token_sub: ''
      },
      previewVisible: false,
      previewError: false
    }
  },
  created() {
    this.height = screenHeight(200)
    if (!this.isload) {
      this.apidoc()
    }
    const userStore = useUserStoreHook()
    this.model.token = userStore.token || ''
    this.model.token_sub = this.model.token ? this.model.token.substring(0, 16) + '...' : ''
    this.envName = window.location.hostname
  },
  computed: {
    currentSettingTab() {
      return this.$route?.query?.setting_tab || this.$route?.query?.tab || 'apiInfo'
    },
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'system-setting' || source.startsWith('system-setting')) return '来自系统设置'
      if (source === 'system-user-log') return '来自用户日志'
      if (source === 'dashboard') return '来自后台首页'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自系统设置') return '当前从系统设置进入接口文档'
      if (this.entrySourceLabel === '来自用户日志') return '当前从用户日志进入接口文档'
      if (this.entrySourceLabel === '来自后台首页') return '当前从后台首页进入接口文档'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自系统设置') {
        return '这类进入通常是为了确认接口配置和文档地址是不是一致。建议先核文档地址、访问密码和 token，再回系统设置继续复核保存项。'
      }
      if (this.entrySourceLabel === '来自用户日志') {
        return '这类进入通常是为了追某次接口调用或后台动作到底对应哪份文档。建议先确认文档环境和凭据，再回日志页继续定位异常。'
      }
      return '这类进入通常是首页巡检后的继续下钻。建议先确认文档接入状态和联调凭据，再决定回设置页还是直接打开文档站。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自系统设置') return '回系统设置'
      if (this.entrySourceLabel === '来自用户日志') return '回用户日志'
      return '回后台首页'
    },
    envLabel() {
      if (!this.envName) {
        return '本地环境'
      }
      if (this.envName.includes('localhost') || this.envName === '127.0.0.1') {
        return '本地环境'
      }
      if (this.envName.includes('test') || this.envName.includes('uat')) {
        return '测试环境'
      }
      return '线上环境'
    },
    dataModeLabel() {
      return this.model.apidoc_url ? '在线文档' : '待接入'
    },
    apidocFocusLabel() {
      if (!this.model.apidoc_url) {
        return '先补文档地址'
      }
      if (!this.model.apidoc_pwd) {
        return '先核访问口令'
      }
      return '先核联调凭据'
    },
    summaryItems() {
      return [
        {
          label: '接入状态',
          value: this.model.apidoc_url ? '已接入' : '待配置'
        },
        {
          label: '访问口令',
          value: this.model.apidoc_pwd || '未设置'
        },
        {
          label: '当前 Token',
          value: this.model.token_sub || '未获取'
        },
        {
          label: '数据模式',
          value: this.dataModeLabel
        }
      ]
    },
    apidocGuideCards() {
      return [
        {
          title: '第一步：先看文档地址能不能用',
          desc: '先确认当前环境的文档地址是不是对的，很多“打不开文档”的问题其实是地址没接对。',
          action: this.model.apidoc_url || '当前还没有文档地址，先去系统设置补配置。'
        },
        {
          title: '第二步：再看访问口令和 Token',
          desc: '就算地址对了，没口令或 token 不对，联调也还是会卡住，所以这两项要一起确认。',
          action: `访问密码：${this.model.apidoc_pwd || '未设置'}；当前 Token：${this.model.token_sub || '未获取'}`
        },
        {
          title: '第三步：最后再决定怎么打开',
          desc: '站内预览适合顺手看，新标签页更适合完整联调；如果还不顺，就回系统设置或日志页继续查。',
          action: this.model.apidoc_url ? '当前可以直接预览或新标签页打开。' : '先把文档接入，再继续预览。'
        }
      ]
    },
    apidocCurrentSuggestion() {
      if (!this.model.apidoc_url) {
        return {
          title: '先回系统设置补文档地址',
          desc: '当前还没有可用文档地址，继续点预览不会有结果，最顺的路径是先补接口配置，再回来核口令和 Token。',
          tags: ['待接入', this.envLabel, '先去接口设置']
        }
      }
      if (!this.model.apidoc_pwd) {
        return {
          title: '地址已接通，但还要先补访问口令',
          desc: '现在最容易卡在“地址能打开但进不去”。先补访问密码，再决定是站内预览还是外部联调。',
          tags: ['地址已就绪', '口令缺失', '先核凭据']
        }
      }
      return {
        title: '可以开始联调，但建议先看凭据再开文档',
        desc: '当前地址、口令和 Token 基本齐了。先复制口令和 Token，再打开文档，能减少来回切页。',
        tags: [this.envLabel, this.dataModeLabel, '可预览 / 可联调']
      }
    },
    previewDrawerHint() {
      return this.previewError
        ? '当前站内预览没有正常加载，建议直接在新标签页打开，避免继续被 iframe 限制卡住。'
        : '如果文档站点本身限制 iframe 或脚本加载异常，可直接点右侧按钮在新标签页打开。'
    },
    previewGuideCards() {
      return [
        {
          title: '先看这个预览是否正常加载',
          desc: this.previewError ? '当前已经出现加载异常，优先改走新标签页。' : '如果能正常加载，就适合顺手浏览目录和接口结构。'
        },
        {
          title: '联调时优先复制凭据',
          desc: `当前口令：${this.model.apidoc_pwd || '未设置'}；当前 Token：${this.model.token_sub || '未获取'}。`
        },
        {
          title: '不顺时直接回设置或日志',
          desc: '文档页负责给入口；真正要判断配置是否生效，还是回接口设置和操作日志最直接。'
        }
      ]
    }
  },
  methods: {
    // 文档
    apidoc() {
      apidoc().then((res) => {
        this.isload = true
        this.model.apidoc_url = res.data.apidoc_url
        this.model.apidoc_pwd = res.data.apidoc_pwd
      })
    },
    // 刷新
    refresh() {
      this.apidoc()
    },
    // 复制
    copy(text) {
      clip(text)
    },
    openPreviewDrawer() {
      this.previewVisible = true
      this.previewError = false
    },
    handlePreviewLoad() {
      this.previewError = false
    },
    handlePreviewError() {
      this.previewError = true
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自系统设置') {
        this.goToSystemSetting(this.currentSettingTab)
        return
      }
      if (this.entrySourceLabel === '来自用户日志') {
        this.goToUserLog()
        return
      }
      this.$router.push({
        path: '/dashboard',
        query: this.buildEntryRouteQuery({}, 'system-apidoc')
      })
    },
    goToEntryContextBack() {
      this.handleEntryContextPrimary()
    },
    buildEntryRouteQuery(extraQuery = {}, nextFrom = '') {
      const query = {
        ...this.$route.query,
        ...extraQuery
      }
      if (nextFrom) {
        query.from = nextFrom
      }
      return query
    },
    goToSystemSetting(tab) {
      this.$router.push({
        path: '/system/setting',
        query: this.buildEntryRouteQuery({
          tab,
          setting_tab: tab
        }, 'system-apidoc')
      })
    },
    goToUserLog() {
      this.$router.push({
        path: '/system/user-log',
        query: this.buildEntryRouteQuery({
          tab: this.currentSettingTab,
          setting_tab: this.currentSettingTab
        }, 'system-apidoc')
      })
    }
  }
}
</script>

<style scoped>
.apidoc-overview {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f5f7ff 0%, #fffaf0 100%);
  border: 1px solid #e5e7eb;
}

.entry-context-banner__main {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 600;
  color: #909399;
}

.entry-context-banner__title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.entry-context-banner__desc {
  font-size: 13px;
  line-height: 1.6;
  color: #606266;
}

.entry-context-banner__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.apidoc-overview__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.apidoc-overview__title {
  font-size: 18px;
  font-weight: 600;
  color: #243b53;
}

.apidoc-overview__desc {
  margin-top: 4px;
  font-size: 12px;
  color: #7c8aa5;
}

.apidoc-overview__head-actions {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.page-overview-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.plain-guide {
  margin-top: 10px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.plain-guide__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.plain-guide__badge {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #eef2ff;
  color: #4338ca;
  font-size: 12px;
  white-space: nowrap;
}

.plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
  margin-top: 12px;
}

.plain-guide-card {
  padding: 12px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #fff;
}

.plain-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.plain-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.plain-guide-card__action {
  margin-top: 8px;
  font-size: 12px;
  color: #4f46e5;
}

.page-overview-grid__item {
  padding: 12px 14px;
  min-height: 72px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 6px;
  border: 1px solid #e6ecf5;
  border-radius: 8px;
  background: #ffffff;
}

.page-overview-grid__label {
  display: block;
  font-size: 12px;
  color: #7c8aa5;
}

.page-overview-grid__value {
  font-size: 14px;
  color: #243b53;
}

.section-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}

.section-title-row--content {
  margin-top: 4px;
}

.section-title-row__title {
  font-size: 15px;
  font-weight: 600;
  color: #243b53;
}

.section-title-row__desc,
.section-title-row__meta {
  font-size: 12px;
  color: #7c8aa5;
}

.section-title-row__meta {
  font-weight: 600;
}

.doc-info-panel {
  margin-bottom: 16px;
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  background: #ffffff;
}

.doc-status-strip {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe5f1;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.doc-status-strip__main {
  min-width: 0;
  flex: 1;
}

.doc-status-strip__label {
  font-size: 12px;
  color: #7c8aa5;
}

.doc-status-strip__title {
  margin-top: 6px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.doc-status-strip__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.doc-status-strip__tags {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.doc-status-strip__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eef2ff;
  color: #4338ca;
  font-size: 12px;
  white-space: nowrap;
}

.doc-info-row {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 12px 16px;
}

.doc-info-row + .doc-info-row {
  border-top: 1px solid #eef3f8;
}

.doc-info-row__label {
  width: 76px;
  flex-shrink: 0;
  line-height: 32px;
  font-size: 12px;
  color: #7c8aa5;
}

.doc-info-row__content {
  flex: 1;
  min-width: 0;
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.doc-info-row__content--wrap {
  row-gap: 6px;
}

.doc-info-row__text {
  line-height: 32px;
  color: #243b53;
}

.doc-info-row__text--link {
  word-break: break-all;
}

.doc-preview-panel {
  border: 1px dashed #d9e3f0;
  border-radius: 12px;
  padding: 18px 20px;
  background: #f8fbff;
}

.doc-followup-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
}

.doc-followup-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  min-height: 104px;
  padding: 12px 14px;
  text-align: left;
  border: 1px solid #dbe5f1;
  border-radius: 12px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.doc-followup-card:hover {
  transform: translateY(-1px);
  border-color: rgba(37, 99, 235, 0.28);
  box-shadow: 0 12px 28px rgba(37, 99, 235, 0.08);
}

.doc-followup-card__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.doc-followup-card__desc {
  font-size: 12px;
  line-height: 1.6;
  color: #64748b;
}

.doc-preview-panel__title {
  font-size: 14px;
  font-weight: 600;
  color: #243b53;
}

.doc-preview-panel__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7b8c;
}

.doc-preview-panel__actions {
  margin-top: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.doc-preview-panel__empty {
  font-size: 12px;
  color: #9aa5b1;
}

.preview-drawer {
  display: flex;
  flex-direction: column;
  gap: 12px;
  height: 100%;
}

.preview-drawer__toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.preview-drawer__hint {
  font-size: 12px;
  color: #7c8aa5;
}

.preview-drawer__guide {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 10px;
}

.preview-guide-card {
  padding: 12px 14px;
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  background: #ffffff;
}

.preview-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.preview-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.preview-drawer__error {
  padding: 12px 14px;
  font-size: 12px;
  color: #9f1239;
  background: #fff1f2;
  border: 1px solid #fecdd3;
  border-radius: 12px;
}

.preview-drawer__frame {
  flex: 1;
  width: 100%;
  min-height: 70vh;
  border: 1px solid #d9e2ec;
  border-radius: 14px;
  background: #ffffff;
}

@media (max-width: 768px) {
  .entry-context-banner,
  .page-overview-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .entry-context-banner,
  .plain-guide__header {
    flex-direction: column;
  }

  .doc-status-strip {
    flex-direction: column;
  }

  .doc-status-strip__tags {
    justify-content: flex-start;
  }

  .doc-info-row {
    flex-direction: column;
    gap: 4px;
  }

  .doc-info-row__label,
  .doc-info-row__text {
    line-height: 1.6;
  }

  .preview-drawer__toolbar {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 560px) {
  .page-overview-grid {
    grid-template-columns: 1fr;
  }
}
</style>
