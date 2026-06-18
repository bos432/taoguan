<template>
  <div class="app-container">
    <el-card class="app-main">
      <div class="setting-page-header">
        <div>
          <div class="setting-page-header__title">站点配置</div>
          <div class="setting-page-header__desc">{{ runtimeHint }}</div>
        </div>
        <div class="setting-page-header__env">
          <span class="setting-page-header__tag">{{ runtimeEnvInfo.label }}</span>
        </div>
      </div>
      <div class="setting-summary-bar">
        <div v-for="item in summaryBarItems" :key="item.label" class="setting-summary-bar__item">
          <span class="setting-summary-bar__label">{{ item.label }}</span>
          <strong class="setting-summary-bar__value">{{ item.value }}</strong>
        </div>
      </div>
      <div v-if="entryContextVisible" class="entry-context-banner">
        <div class="entry-context-banner__main">
          <div class="entry-context-banner__eyebrow">外部入口承接</div>
          <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
          <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
        </div>
        <div class="entry-context-banner__actions">
          <el-button type="primary" @click="goToPage('/system/setting')">去系统设置中心</el-button>
          <el-button @click="goToPage('/dashboard')">回控制台总览</el-button>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样配</div>
            <div class="plain-guide__desc">
              {{ entryContextVisible ? entryContextDesc : '先补高优先级的站点名称、Logo、联系方式和二维码，再去处理 SEO、版权和说明类字段，不要从低优先级文案开始填。' }}
            </div>
          </div>
          <span class="plain-guide__badge" :class="configFollowupBadgeClass">{{ siteConfigFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in siteConfigGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-inline-status">
          <div class="setting-inline-status__chips">
            <span
              v-for="item in compactReviewItems"
              :key="item"
              class="setting-inline-status__chip"
            >
              {{ item }}
            </span>
          </div>
        <div class="setting-inline-status__hint">
          <span class="setting-inline-status__badge" :class="configFollowupBadgeClass">
            {{ configFollowupBadgeText }}
          </span>
          <span>{{ compactReviewHint }}</span>
        </div>
      </div>
        <div class="followup-panel">
          <div class="followup-panel__main">
            <div class="followup-panel__title">站点配完后继续去哪</div>
            <div class="followup-panel__desc">{{ siteConfigFollowupHint }}</div>
            <div class="followup-panel__tags">
              <span v-for="item in siteConfigFollowupTags" :key="item">{{ item }}</span>
            </div>
          </div>
          <div class="followup-panel__actions">
            <el-button type="primary" @click="goToPage('/setting/notice')">去公告配置</el-button>
            <el-button @click="goToPage('/setting/accord')">去协议管理</el-button>
            <el-button @click="goToPage('/system/apidoc')">去接口文档</el-button>
          </div>
        </div>
        <el-tabs>
          <el-tab-pane label="基本信息">
            <div class="section-title-row">
              <div>
                <div class="section-title-row__title">基础站点信息</div>
                <div class="section-title-row__desc">
                  维护品牌名称、站点标题、关键词和基础展示素材。
                </div>
              </div>
              <div class="section-title-row__meta">适用于后台、H5 与小程序基础展示</div>
            </div>
            <el-scrollbar native :max-height="height - 30">
              <el-row>
                <el-col :span="16">
                  <el-form-item label="favicon" prop="favicon_id">
                    <FileImage
                      v-model="model.favicon_id"
                      :file-url="model.favicon_url"
                      file-title="上传favicon"
                      file-tip="图片小于 50 KB，jpg、png、ico格式，128 x 128。"
                      :height="100"
                      upload
                    />
                  </el-form-item>
                  <el-form-item label="logo" prop="logo_id">
                    <FileImage
                      v-model="model.logo_id"
                      :file-url="model.logo_url"
                      file-title="上传logo"
                      :height="100"
                      upload
                    />
                  </el-form-item>
                  <el-form-item label="名称" prop="name">
                    <el-input v-model="model.name" placeholder="name" clearable />
                  </el-form-item>
                  <el-form-item label="标题" prop="title">
                    <el-input v-model="model.title" placeholder="title" clearable />
                  </el-form-item>
                  <el-form-item label="关键词" prop="keywords">
                    <el-input v-model="model.keywords" placeholder="keywords" clearable />
                  </el-form-item>
                  <el-form-item label="描述" prop="description">
                    <el-input
                      v-model="model.description"
                      type="textarea"
                      autosize
                      placeholder="description"
                    />
                  </el-form-item>
                  <el-form-item label="备案号" prop="icp">
                    <el-input v-model="model.icp" placeholder="icp" clearable />
                  </el-form-item>
                  <el-form-item label="版权" prop="copyright">
                    <el-input v-model="model.copyright" placeholder="copyright" clearable />
                  </el-form-item>
                </el-col>
              </el-row>
            </el-scrollbar>
          </el-tab-pane>
          <el-tab-pane label="联系信息">
            <div class="section-title-row">
              <div>
                <div class="section-title-row__title">联系与渠道信息</div>
                <div class="section-title-row__desc">
                  维护电话、微信、邮箱、地址以及公众号和小程序码。
                </div>
              </div>
              <div class="section-title-row__meta">提交后会影响多个前端联系入口显示</div>
            </div>
            <el-scrollbar native :max-height="height - 30">
              <el-row>
                <el-col :span="16">
                  <el-form-item label="公众号码" prop="offi_id">
                    <FileImage
                      v-model="model.offi_id"
                      :file-url="model.offi_url"
                      file-title="上传公众号码"
                      :height="100"
                      upload
                    />
                  </el-form-item>
                  <el-form-item label="小程序码" prop="mini_id">
                    <FileImage
                      v-model="model.mini_id"
                      :file-url="model.mini_url"
                      file-title="上传小程序码"
                      :height="100"
                      upload
                    />
                  </el-form-item>
                  <el-form-item label="地址" prop="address">
                    <el-input v-model="model.address" placeholder="address" clearable />
                  </el-form-item>
                  <el-form-item label="电话" prop="tel">
                    <el-input v-model="model.tel" placeholder="tel" clearable />
                  </el-form-item>
                  <el-form-item label="传真" prop="fax">
                    <el-input v-model="model.fax" placeholder="fax" clearable />
                  </el-form-item>
                  <el-form-item label="手机" prop="mobile">
                    <el-input v-model="model.mobile" placeholder="mobile" clearable />
                  </el-form-item>
                  <el-form-item label="邮箱" prop="email">
                    <el-input v-model="model.email" placeholder="email" clearable />
                  </el-form-item>
                  <el-form-item label="微信" prop="wechat">
                    <el-input v-model="model.wechat" placeholder="wechat" clearable />
                  </el-form-item>
                  <el-form-item label="QQ" prop="qq">
                    <el-input v-model="model.qq" placeholder="qq" clearable />
                  </el-form-item>
                </el-col>
              </el-row>
            </el-scrollbar>
          </el-tab-pane>
        </el-tabs>
        <el-form-item>
          <el-button :loading="loading" @click="refresh()">刷新</el-button>
          <el-button :loading="loading" type="primary" @click="submit()">提交</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { info, edit } from '@/api/setting/setting'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SettingSetting',
  data() {
    return {
      name: '设置管理',
      height: 680,
      loading: false,
      model: {
        setting_id: '',
        favicon_id: 0,
        favicon_url: '',
        logo_id: 0,
        logo_url: '',
        name: '',
        title: '',
        keywords: '',
        description: '',
        icp: '',
        copyright: '',
        offi_id: 0,
        offi_url: '',
        mini_id: 0,
        mini_url: '',
        address: '',
        tel: '',
        fax: '',
        mobile: '',
        email: '',
        wechat: '',
        qq: ''
      },
      rules: {},
      recentActionSummary: '',
      runtimeEnvInfo: resolveAdminRuntimeEnv()
    }
  },
  computed: {
    basicFilledCount() {
      const fields = [
        'favicon_id',
        'logo_id',
        'name',
        'title',
        'keywords',
        'description',
        'icp',
        'copyright'
      ]
      return fields.filter((key) => {
        const value = this.model[key]
        return value !== '' && value !== null && value !== undefined && value !== 0
      }).length
    },
    contactFilledCount() {
      const fields = [
        'offi_id',
        'mini_id',
        'address',
        'tel',
        'fax',
        'mobile',
        'email',
        'wechat',
        'qq'
      ]
      return fields.filter((key) => {
        const value = this.model[key]
        return value !== '' && value !== null && value !== undefined && value !== 0
      }).length
    },
    filledFieldCount() {
      const fields = [
        'favicon_id',
        'logo_id',
        'name',
        'title',
        'keywords',
        'description',
        'icp',
        'copyright',
        'offi_id',
        'mini_id',
        'address',
        'tel',
        'fax',
        'mobile',
        'email',
        'wechat',
        'qq'
      ]
      return fields.filter((key) => {
        const value = this.model[key]
        return value !== '' && value !== null && value !== undefined && value !== 0
      }).length
    },
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'dashboard') {
        return '来自控制台总览'
      }
      if (source === 'system-setting') {
        return '来自系统设置中心'
      }
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '当前从控制台进入站点配置复核'
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        return '当前从系统设置中心进入站点配置'
      }
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '当前更适合优先核对站点名称、Logo、联系方式和二维码素材是否与当前环境一致，再去补 SEO、版权等说明字段。'
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        return '当前更适合把站点配置当作前台展示底座来复核，先看名称、Logo、联系方式，再看协议、公告和联调入口。'
      }
      return '当前页主要负责站点基础信息与前台素材配置。'
    },
    submitRiskHint() {
      return this.runtimeEnvInfo.isProd
        ? '正式环境提交会影响 H5、小程序和后台基础展示，请先复核名称、Logo、联系方式和二维码素材。'
        : '当前环境适合验证站点基础配置回显与素材上传，不要把测试配置当作正式运营结果。'
    },
    configFollowupBadgeText() {
      return this.filledFieldCount >= 14 ? '接近完成' : '待补配置'
    },
    configFollowupBadgeClass() {
      return this.filledFieldCount >= 14 ? 'is-safe' : 'is-warning'
    },
    summaryBarItems() {
      return [
        { label: '配置分区', value: '2' },
        { label: '站点名称', value: this.model.name || '未设置' },
        { label: '联系手机', value: this.model.mobile || '未设置' },
        { label: '完整度', value: `${this.filledFieldCount}/17` },
        { label: '入口来源', value: this.entrySourceLabel || '页内直接进入' },
        {
          label: '环境 / 数据模式',
          value: `${this.runtimeEnvInfo.label} / ${this.runtimeEnvInfo.dataMode}`
        },
        { label: '最近操作', value: this.recentActionSummary || '已进入配置页' }
      ]
    },
    compactReviewItems() {
      return [
        `基础信息 ${this.basicFilledCount}/8`,
        `联系信息 ${this.contactFilledCount}/9`,
        `站点名称 ${this.model.name ? '已配置' : '待补齐'}`,
        `联系手机 ${this.model.mobile ? '已配置' : '待补齐'}`
      ]
    },
    compactReviewHint() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '当前从控制台进入，建议先把站点名称、Logo、联系方式和二维码复核一遍，再继续公告、协议和接口联调。'
      }
      return this.model.name && this.model.mobile ? this.configFollowupRiskText : this.submitRiskHint
    },
    siteConfigFocusLabel() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '先做环境复核'
      }
      if (!this.model.name || !this.model.mobile) {
        return '先补核心信息'
      }
      if (!this.model.logo_id || !this.model.mini_id || !this.model.offi_id) {
        return '先补前台素材'
      }
      return '可以做上线复核'
    },
    configFollowupRiskText() {
      if (!this.model.name || !this.model.mobile) {
        return '站点名称和联系手机属于高优先级字段，建议先补齐这两项，再继续检查 Logo、二维码和渠道信息。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境提交会直接影响后台、H5 和小程序基础展示，请以站点名称、Logo 和联系方式为重点复核对象。'
        : '测试环境可继续验证素材上传、回显和字段持久化，不建议把测试内容直接当成正式站点配置。'
    },
    siteConfigGuideCards() {
      return [
        {
          title: '第一步先补会直接露出的信息',
          desc: this.model.name && this.model.mobile
            ? '站点名称和联系手机已经有了，下一步优先看 Logo、公众号码和小程序码是不是齐全。'
            : '站点名称、Logo、联系方式会直接影响后台、H5 和小程序的第一眼展示，优先级最高。',
          action: this.model.name && this.model.mobile ? '继续补素材和渠道码。' : '先补名称与联系方式。'
        },
        {
          title: '第二步再补渠道与联系入口',
          desc: '联系信息不是孤立字段，它会影响前端联系页、商家咨询和运营展示，所以要一起看电话、微信、邮箱和地址。',
          action: '渠道字段尽量成套补齐。'
        },
        {
          title: '第三步最后再补说明类字段',
          desc: '关键词、描述、版权、备案号这些更适合在主信息稳定后再整理，不建议抢在核心配置前面处理。',
          action: 'SEO 和版权放在最后一轮复核。'
        }
      ]
    },
    siteConfigFollowupHint() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '这页更像控制台后的配置复核站。站点信息确认后，建议继续去系统设置中心、公告配置和协议管理，把前后台展示链路一起收口。'
      }
      if (!this.model.name || !this.model.mobile) {
        return '当前还没补齐高优先级站点信息，先别急着做外围页面联调，先把名称、Logo 和联系方式补完整。'
      }
      return '站点配置更像全局底座。提交后通常还要继续去公告、协议和接口文档页，检查前端文案、协议链路和联调入口是不是都跟着对齐。'
    },
    siteConfigFollowupTags() {
      return [
        `完整度 ${this.filledFieldCount}/17`,
        `站点名称 ${this.model.name ? '已配置' : '待补齐'}`,
        `联系手机 ${this.model.mobile ? '已配置' : '待补齐'}`,
        `环境 ${this.runtimeEnvInfo.label}`
      ]
    }
  },
  created() {
    this.height = screenHeight(270)
    this.info()
  },
  watch: {
    '$route.fullPath'(nextPath, prevPath) {
      if (nextPath === prevPath) {
        return
      }
      this.info()
    }
  },
  methods: {
    // 信息
    info() {
      info().then((res) => {
        this.model = res.data
        this.recentActionSummary = this.entrySourceLabel
          ? `已加载站点配置：${this.model.name || '未命名站点'}，入口来源 ${this.entrySourceLabel}。`
          : `已加载站点配置：${this.model.name || '未命名站点'}。`
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      info()
        .then((res) => {
          this.model = res.data
          this.loading = false
          this.recentActionSummary = `已刷新站点配置，当前名称：${
            this.model.name || '未命名站点'
          }${this.entrySourceLabel ? `，入口来源 ${this.entrySourceLabel}` : ''}。`
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 提交
    submit() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          this.loading = true
          edit(this.model)
            .then((res) => {
              this.loading = false
              this.recentActionSummary = `已提交站点配置，站点名称：${
                this.model.name || '未命名站点'
              }，联系手机：${this.model.mobile || '未设置'}。`
              ElMessage.success(res.msg)
            })
            .catch(() => {
              this.loading = false
            })
        } else {
          ElMessage.error('请完善必填项（带红色星号*）')
        }
      })
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'setting-setting'
        }
      })
    }
  }
}
</script>

<style scoped>
.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  padding: 14px 16px;
  margin-bottom: 14px;
  border: 1px solid #dbeafe;
  border-radius: 14px;
  background: linear-gradient(135deg, #f5f9ff 0%, #ffffff 100%);
}

.entry-context-banner__main {
  display: grid;
  gap: 6px;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 600;
  color: #2563eb;
}

.entry-context-banner__title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.entry-context-banner__desc {
  font-size: 13px;
  line-height: 1.7;
  color: #475569;
}

.entry-context-banner__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.setting-page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
}

.setting-page-header__title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
}

.setting-page-header__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.setting-page-header__env {
  display: flex;
  align-items: center;
}

.setting-page-header__tag {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 999px;
  background: #eef4ff;
  color: #3152bf;
  font-size: 12px;
  font-weight: 600;
}

.setting-summary-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 10px;
  margin-bottom: 16px;
  padding: 12px 14px;
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  background: #f8fafc;
}

.plain-guide {
  margin-bottom: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f6;
  border-radius: 12px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.plain-guide__title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.8;
  color: #64748b;
}

.plain-guide__badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  background: #fff5e8;
  color: #b45309;
  font-size: 12px;
  font-weight: 700;
}

.plain-guide__badge.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
  margin-top: 12px;
}

.plain-guide-card {
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #e6ecf5;
  background: #fff;
}

.plain-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.plain-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.plain-guide-card__action {
  margin-top: 8px;
  font-size: 12px;
  color: #1d4ed8;
}

.setting-summary-bar__item {
  min-width: 0;
}

.setting-summary-bar__label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  color: #7c8aa5;
}

.setting-summary-bar__value {
  display: block;
  overflow: hidden;
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.setting-inline-status {
  margin-bottom: 16px;
  padding: 12px 14px;
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  background: #ffffff;
}

.setting-inline-status__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.setting-inline-status__chip {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  background: #eef4ff;
  color: #3b5ccc;
  font-size: 12px;
}

.setting-inline-status__hint {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.setting-inline-status__badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  background: #fff5e8;
  color: #b45309;
  font-size: 12px;
  font-weight: 700;
}

.setting-inline-status__badge.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
  padding: 12px 14px;
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  background: #fff;
}

.followup-panel__main {
  flex: 1;
  min-width: 0;
}

.followup-panel__title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.followup-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.followup-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.followup-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #eef4ff;
  color: #3b5ccc;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.section-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.section-title-row__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.section-title-row__desc {
  margin-top: 4px;
  font-size: 12px;
  color: #64748b;
}

.section-title-row__meta {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  white-space: nowrap;
}

@media (max-width: 900px) {
  .setting-page-header,
  .plain-guide__header,
  .followup-panel {
    flex-direction: column;
  }

  .section-title-row {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }

  .setting-summary-bar {
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  }
}
</style>
