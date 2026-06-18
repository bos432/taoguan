<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">内容设置</div>
          <div class="section-title-row__desc">
            统一维护内容站点基础资料、联系信息和默认展示资源，首屏直接进入可提交配置。
          </div>
        </div>
        <div class="section-title-row__meta">{{ runtimeEnvInfo.label }}</div>
      </div>
      <div v-if="entryContextVisible" class="entry-context-banner">
        <div class="entry-context-banner__main">
          <div class="entry-context-banner__eyebrow">外部入口承接</div>
          <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
          <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
        </div>
        <div class="entry-context-banner__actions">
          <el-button type="primary" @click="handleEntryContextPrimary">{{
            entryContextPrimaryLabel
          }}</el-button>
          <el-button @click="goToEntryContextBack">回来源页</el-button>
        </div>
      </div>
      <div class="setting-plain-guide">
        <div class="setting-plain-guide__header">
          <div>
            <div class="setting-plain-guide__title">内容设置第一次进来，先分清你在改哪一层</div>
            <div class="setting-plain-guide__desc">
              这里更偏站点基础资料和前台默认资源，不是具体内容维护；先判断你是在补站点信息，还是在调内容前台开关和默认图。
            </div>
          </div>
          <div class="setting-plain-guide__badge">{{ contentSettingGuideFocusLabel }}</div>
        </div>
        <div class="setting-plain-guide__grid">
          <div
            v-for="item in contentSettingGuideCards"
            :key="item.title"
            class="setting-plain-guide-card"
          >
            <span class="setting-plain-guide-card__step">{{ item.step }}</span>
            <div class="setting-plain-guide-card__title">{{ item.title }}</div>
            <div class="setting-plain-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <div class="setting-summary-bar">
        <div class="setting-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">站点 {{ model.name || '未设置' }}</span>
          <span class="summary-chip">联系 {{ contactSummary }}</span>
          <span class="summary-chip">前台内容 {{ model.is_api_content ? '已开启' : '已关闭' }}</span>
          <span class="summary-chip">默认图 {{ defaultImageSummary }}</span>
          <span class="summary-chip">完整度 {{ filledFieldCount }}/20</span>
          <span class="summary-chip">{{ runtimeEnvInfo.dataMode }}</span>
          <span class="summary-chip">{{ enabledDefaultImageText }}</span>
          <span class="summary-chip summary-chip--muted">{{ submitHint }}</span>
        </div>
        <div class="setting-summary-bar__hint" :class="summaryHintClass">
          <span class="setting-summary-bar__hint-title">{{ summaryHintTitle }}</span>
          <span class="setting-summary-bar__hint-text">{{ summaryHintText }}</span>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">配置完后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/content/content')">去内容管理</el-button>
          <el-button @click="goToPage('/content/category')">去内容分类</el-button>
          <el-button @click="goToPage('/content/tag')">去内容标签</el-button>
        </div>
      </div>
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <el-tabs>
          <el-tab-pane label="基本信息">
            <div class="section-title-row section-title-row--content">
              <div>
                <div class="section-title-row__title">基础资料</div>
                <div class="section-title-row__desc">
                  维护名称、标题、关键词和站点基础展示素材。
                </div>
              </div>
              <div class="section-title-row__meta">已填写 {{ basicFilledCount }}/8 项</div>
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
                      :height="50"
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
            <div class="section-title-row section-title-row--content">
              <div>
                <div class="section-title-row__title">联系通道</div>
                <div class="section-title-row__desc">
                  维护电话、邮箱、微信、地址以及公众号和小程序码展示。
                </div>
              </div>
              <div class="section-title-row__meta">已填写 {{ contactFilledCount }}/9 项</div>
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
          <el-tab-pane label="其它信息">
            <div class="section-title-row section-title-row--content">
              <div>
                <div class="section-title-row__title">默认资源</div>
                <div class="section-title-row__desc">
                  控制前台内容模块开关，以及内容、分类、标签默认图片。
                </div>
              </div>
              <div class="section-title-row__meta">{{ enabledDefaultImageText }}</div>
            </div>
            <el-scrollbar native :max-height="height - 30">
              <el-row>
                <el-col :span="16">
                  <el-form-item label="前台内容" prop="is_api_content">
                    <el-switch v-model="model.is_api_content" :active-value="1" :inactive-value="0" />
                    <span> 是否开启前台内容功能</span>
                  </el-form-item>
                  <el-form-item label="内容默认图片" prop="content_default_img_id">
                    <FileImage
                      v-model="model.content_default_img_id"
                      :file-url="model.content_default_img_url"
                      file-title="上传内容默认图片"
                      file-tip="图片小于 100 KB，jpg、png格式。"
                      :height="100"
                      upload
                    />
                  </el-form-item>
                  <el-form-item prop="content_default_img_open">
                    <el-col :span="12">
                      <el-switch
                        v-model="model.content_default_img_open"
                        :active-value="1"
                        :inactive-value="0"
                      />
                    </el-col>
                    <el-col :span="12">是否开启内容默认图片。</el-col>
                  </el-form-item>
                  <el-form-item label="分类默认图片" prop="category_default_img_id">
                    <FileImage
                      v-model="model.category_default_img_id"
                      :file-url="model.category_default_img_url"
                      file-title="上传分类默认图片"
                      file-tip="图片小于 100 KB，jpg、png格式。"
                      :height="100"
                      upload
                    />
                  </el-form-item>
                  <el-form-item prop="category_default_img_open">
                    <el-col :span="12">
                      <el-switch
                        v-model="model.category_default_img_open"
                        :active-value="1"
                        :inactive-value="0"
                      />
                    </el-col>
                    <el-col :span="12">是否开启分类默认图片。</el-col>
                  </el-form-item>
                  <el-form-item label="标签默认图片" prop="tag_default_img_id">
                    <FileImage
                      v-model="model.tag_default_img_id"
                      :file-url="model.tag_default_img_url"
                      file-title="上传标签默认图片"
                      file-tip="图片小于 100 KB，jpg、png格式。"
                      :height="100"
                      upload
                    />
                  </el-form-item>
                  <el-form-item prop="tag_default_img_open">
                    <el-col :span="12">
                      <el-switch
                        v-model="model.tag_default_img_open"
                        :active-value="1"
                        :inactive-value="0"
                      />
                    </el-col>
                    <el-col :span="12">是否开启标签默认图片。</el-col>
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
import { info, edit } from '@/api/content/setting'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'ContentSetting',
  computed: {
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'dashboard') return '来自控制台总览'
      if (source === 'system-setting') return '来自系统设置中心'
      if (source === 'file-setting') return '来自文件设置'
      if (source === 'content-content') return '来自内容管理'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自控制台总览') return '当前从控制台进入内容设置'
      if (this.entrySourceLabel === '来自系统设置中心') return '当前从系统设置中心进入内容设置'
      if (this.entrySourceLabel === '来自文件设置') return '当前从文件设置进入内容设置'
      if (this.entrySourceLabel === '来自内容管理') return '当前从内容管理进入内容设置'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '这类进入通常是为了排首页信息、默认图或内容前台开关问题。建议先补站点资料，再回内容页看真实展示。'
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        return '这类进入通常是为了继续补全站点基础信息。建议先确认站点资料和默认图，再回系统设置检查其它全局配置。'
      }
      if (this.entrySourceLabel === '来自文件设置') {
        return '这类进入通常是因为默认图或资源链路需要联调。建议先核内容默认图和前台开关，再回文件设置确认上传边界。'
      }
      if (this.entrySourceLabel === '来自内容管理') {
        return '这类进入通常是因为前台展示样式或默认资源不对。建议先修全局资料和默认图，再回内容管理看实际内容承接。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自文件设置') return '回文件设置'
      return '去内容管理复核'
    },
    defaultImageSummary() {
      const enabled = [
        this.model.content_default_img_open,
        this.model.category_default_img_open,
        this.model.tag_default_img_open
      ].filter(Boolean).length
      return `已启用 ${enabled} / 3`
    },
    basicFilledCount() {
      const fields = ['favicon_id', 'logo_id', 'name', 'title', 'keywords', 'description', 'icp', 'copyright']
      return fields.filter((key) => this.hasValue(this.model[key])).length
    },
    contactFilledCount() {
      const fields = ['offi_id', 'mini_id', 'address', 'tel', 'fax', 'mobile', 'email', 'wechat', 'qq']
      return fields.filter((key) => this.hasValue(this.model[key])).length
    },
    filledFieldCount() {
      return this.basicFilledCount + this.contactFilledCount + this.enabledDefaultImageCount
    },
    enabledDefaultImageCount() {
      return [
        this.model.content_default_img_open,
        this.model.category_default_img_open,
        this.model.tag_default_img_open
      ].filter(Boolean).length
    },
    enabledDefaultImageText() {
      return this.enabledDefaultImageCount ? `默认图已开启 ${this.enabledDefaultImageCount} 类` : '默认图暂未启用'
    },
    contactSummary() {
      return this.model.mobile || this.model.tel || this.model.email || '未设置'
    },
    summaryHintTitle() {
      if (!this.model.name || !this.contactSummary || this.contactSummary === '未设置') {
        return '建议先补齐基础信息'
      }
      return this.model.is_api_content ? '当前可直接提交' : '提交前确认前台开关'
    },
    summaryHintText() {
      if (!this.model.name || this.contactSummary === '未设置') {
        return '建议先补名称和主要联系方式，再处理默认图和前台开关。'
      }
      return this.model.is_api_content
        ? '基础信息已具备，提交后会同步影响前台内容展示和联系入口。'
        : '前台内容当前关闭，若准备上线内容模块，请确认默认图和联系方式一并齐全。'
    },
    summaryHintClass() {
      return this.model.is_api_content ? 'setting-summary-bar__hint--ready' : 'setting-summary-bar__hint--review'
    },
    submitHint() {
      return this.model.is_api_content ? '提交后会同步前台内容展示' : '提交前确认是否需要开放前台内容'
    },
    followupHint() {
      if (!this.model.is_api_content) {
        return '前台内容当前关闭，改完配置后建议先去内容分类和内容管理确认是否只做后台整理，不直接开放前台。'
      }
      if (!this.model.name || this.contactSummary === '未设置') {
        return '基础信息还不够完整，建议先补站点名称和联系方式，再去内容管理页做实际内容投放。'
      }
      return '当前配置已经适合继续做内容投放，建议先去内容管理核对展示，再回分类和标签补结构。'
    },
    followupTags() {
      return [
        `前台内容：${this.model.is_api_content ? '已开启' : '已关闭'}`,
        `基础资料：${this.basicFilledCount}/8`,
        `联系信息：${this.contactFilledCount}/9`,
        `默认图：${this.enabledDefaultImageCount}/3`
      ]
    },
    contentSettingGuideFocusLabel() {
      if (!this.model.is_api_content) {
        return '当前重点：前台内容关闭，先确认这次只是补后台资料，还是准备开放前台内容模块'
      }
      if (!this.model.name || this.contactSummary === '未设置') {
        return '当前重点：站点名称或联系方式还不完整，先把基础资料补齐，再谈前台展示'
      }
      if (!this.enabledDefaultImageCount) {
        return '当前重点：默认图都没开，先判断前台缺图时是否要兜底展示'
      }
      return '当前重点：先分清“站点资料”与“内容默认资源”，改完后再去内容页核前台展示'
    },
    contentSettingGuideCards() {
      return [
        {
          step: '第一步',
          title: '先判断是在补站点资料，还是在改内容前台能力',
          desc: '名称、标题、联系方式属于站点资料；前台内容开关和默认图属于展示能力，两类问题最好分开处理。'
        },
        {
          step: '第二步',
          title: '默认图只负责兜底，不替代真实内容图',
          desc: '内容、分类、标签默认图用于前台缺图时兜底，真正的内容封面和分类图仍要回具体内容页、分类页单独维护。'
        },
        {
          step: '第三步',
          title: '提交后回内容、分类、标签页做真实验证',
          desc: '设置页本身只改全局规则，最终还是要回内容管理、分类和标签页，看前台展示和结构承接是不是都对上了。'
        }
      ]
    }
  },
  data() {
    return {
      name: '内容设置',
      height: 680,
      loading: false,
      model: {
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
        qq: '',
        is_api_content: 0,
        content_default_img_id: 0,
        content_default_img_url: '',
        content_default_img_open: 0,
        category_default_img_id: 0,
        category_default_img_url: '',
        category_default_img_open: 0,
        tag_default_img_id: 0,
        tag_default_img_url: '',
        tag_default_img_open: 0
      },
      rules: {},
      runtimeEnvInfo: resolveAdminRuntimeEnv()
    }
  },
  created() {
    this.height = screenHeight(270)
    this.info()
  },
  methods: {
    hasValue(value) {
      return value !== '' && value !== null && value !== undefined && value !== 0
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自文件设置') {
        this.goToPage('/file/setting')
        return
      }
      this.goToPage('/content/content')
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.$router.push({ path: '/dashboard', query: { from: 'content-setting' } })
        return
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        this.$router.push({ path: '/system/setting', query: { from: 'content-setting' } })
        return
      }
      if (this.entrySourceLabel === '来自文件设置') {
        this.$router.push({ path: '/file/setting', query: { from: 'content-setting' } })
        return
      }
      if (this.entrySourceLabel === '来自内容管理') {
        this.$router.push({ path: '/content/content', query: { from: 'content-setting' } })
      }
    },
    // 信息
    info() {
      info().then((res) => {
        this.model = res.data
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      info()
        .then((res) => {
          this.model = res.data
          this.loading = false
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
          from: 'content-setting'
        }
      })
    }
  }
}
</script>

<style scoped>
.entry-context-banner {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin: 0 0 16px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #dbe7f5;
  background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
}

.entry-context-banner__main {
  flex: 1;
  min-width: 0;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
}

.entry-context-banner__title {
  margin-top: 4px;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.entry-context-banner__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.entry-context-banner__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin: 0 0 14px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #e6eef8;
  background: #fbfdff;
}

.followup-panel__main {
  flex: 1;
  min-width: 0;
}

.followup-panel__title {
  font-size: 14px;
  font-weight: 700;
  color: #1f2937;
}

.followup-panel__desc {
  margin-top: 6px;
  color: #64748b;
  line-height: 1.7;
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
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e7edf5;
  color: #475569;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.setting-plain-guide {
  margin: 0 0 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.setting-plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.setting-plain-guide__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.setting-plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.setting-plain-guide__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.setting-plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.setting-plain-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.setting-plain-guide-card__step {
  display: inline-flex;
  align-items: center;
  min-height: 26px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
}

.setting-plain-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.setting-plain-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.setting-summary-bar {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin: 16px 0 8px;
  padding: 14px 16px;
  border-radius: 14px;
  background: linear-gradient(135deg, #f8fbff 0%, #f4f7fb 100%);
  border: 1px solid #e6eef8;
}

.setting-summary-bar__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #fff;
  border: 1px solid #e7edf5;
  color: #4b5563;
  font-size: 12px;
  line-height: 1;
}

.summary-chip--primary {
  color: #166534;
  background: #ecfdf3;
  border-color: #ccebd8;
}

.summary-chip--muted {
  color: #6b7280;
  background: #f8fafc;
}

.setting-summary-bar__hint {
  min-width: 250px;
  max-width: 320px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #dbeafe;
  background: rgba(255, 255, 255, 0.85);
}

.setting-summary-bar__hint--ready {
  border-color: #ccebd8;
  background: #f6fdf8;
}

.setting-summary-bar__hint--review {
  border-color: #fde68a;
  background: #fffdf3;
}

.setting-summary-bar__hint-title {
  font-size: 13px;
  font-weight: 600;
  color: #111827;
}

.setting-summary-bar__hint-text {
  font-size: 12px;
  line-height: 1.6;
  color: #6b7280;
}

@media (max-width: 1200px) {
  .entry-context-banner,
  .followup-panel,
  .setting-plain-guide__header,
  .setting-summary-bar {
    flex-direction: column;
  }

  .followup-panel__actions,
  .setting-summary-bar__hint {
    max-width: none;
  }

  .setting-plain-guide__badge {
    min-width: 0;
  }

  .setting-plain-guide__grid {
    grid-template-columns: 1fr;
  }
}
</style>
