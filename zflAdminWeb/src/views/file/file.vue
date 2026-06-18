<template>
  <div class="app-container file-shell">
    <div class="file-page-hero">
      <div class="file-page-hero__main">
        <div class="file-page-hero__title-wrap">
          <h2>文件库</h2>
          <span class="file-page-hero__env">{{ runtimeEnvInfo.label }}</span>
        </div>
        <p>{{ runtimeHint }}</p>
      </div>
      <div class="file-page-hero__meta">
        <span>{{ runtimeEntryHint }}</span>
        <span>{{ runtimeEnvInfo.dataMode }}</span>
      </div>
    </div>
    <div class="file-summary-bar">
      <div class="file-summary-bar__item">
        <span class="file-summary-bar__label">文件总量</span>
        <strong>{{ fileSummary.total }}</strong>
      </div>
      <div class="file-summary-bar__item">
        <span class="file-summary-bar__label">当前目录</span>
        <strong>{{ fileSummary.currentDirectory }}</strong>
      </div>
      <div class="file-summary-bar__item">
        <span class="file-summary-bar__label">数据模式</span>
        <strong>{{ runtimeEnvInfo.dataMode }}</strong>
      </div>
      <div class="file-summary-bar__item file-summary-bar__item--tip">
        <span class="file-summary-bar__label">提示</span>
        <strong>{{ summaryTipText }}</strong>
      </div>
    </div>
    <div class="file-guide-panel">
      <div class="file-guide-panel__header">
        <div>
          <div class="file-guide-panel__title">第一次进文件库，建议先这样看</div>
          <div class="file-guide-panel__desc">这页更适合做素材抽查、上传和目录确认；如果你在找“为什么前台图片不对”，通常还要联动分组、标签和业务页一起看。</div>
        </div>
        <div class="file-guide-panel__badge">{{ fileGuideFocusLabel }}</div>
      </div>
      <div class="file-guide-panel__grid">
        <div v-for="item in fileGuideCards" :key="item.title" class="file-guide-card">
          <span class="file-guide-card__step">{{ item.step }}</span>
          <div class="file-guide-card__title">{{ item.title }}</div>
          <div class="file-guide-card__desc">{{ item.desc }}</div>
        </div>
      </div>
    </div>
    <div class="followup-panel">
      <div class="followup-panel__main">
        <div class="followup-panel__title">看完文件库后继续去哪</div>
        <div class="followup-panel__desc">{{ followupHint }}</div>
        <div class="followup-panel__tags">
          <span v-for="item in followupTags" :key="item">{{ item }}</span>
        </div>
      </div>
      <div class="followup-panel__actions">
        <el-button type="primary" @click="goToPage('/file/group')">去文件分组</el-button>
        <el-button @click="goToPage('/file/tag')">去文件标签</el-button>
        <el-button @click="goToPage('/file/setting')">去文件设置</el-button>
      </div>
    </div>
    <div class="manager-card">
      <div class="section-title-row">
        <div>
          <h3>资源库</h3>
          <p>保留现有文件列表、上传和筛选能力，只收拢首屏说明层级。</p>
        </div>
        <div class="section-title-row__meta">{{ runtimeEntryHint }}</div>
      </div>
      <FileManage ref="fileManage" />
    </div>
  </div>
</template>

<script>
import FileManage from '@/components/FileManage/index.vue'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'FileFile',
  components: { FileManage },
  data() {
    return {
      name: '文件管理',
      runtimeEnvInfo: resolveAdminRuntimeEnv(),
      fileSummary: {
        total: 0,
        currentDirectory: '全部文件'
      }
    }
  },
  computed: {
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    runtimeEntryHint() {
      return this.runtimeEnvInfo.isProd ? '正式资源库' : '联调资源库'
    },
    summaryTipText() {
      return this.runtimeEnvInfo.isProd
        ? '正式素材会直接影响前台展示，替换前先确认分组、命名和用途。'
        : '联调环境优先验证上传和分组结构，临时素材不要直接当正式资产。'
    },
    followupHint() {
      if (this.fileSummary.currentDirectory !== '全部文件') {
        return `当前正在查看「${this.fileSummary.currentDirectory}」目录，下一步适合去分组页核对承接范围，或去标签页检查文件聚合是否一致。`
      }
      return '文件库更适合做素材抽查和上传验证，处理完后建议继续去分组页整理结构，再去标签页补聚合入口。'
    },
    followupTags() {
      return [
        `文件总量：${this.fileSummary.total || 0}`,
        `当前目录：${this.fileSummary.currentDirectory}`,
        `环境：${this.runtimeEntryHint}`,
        `数据模式：${this.runtimeEnvInfo.dataMode}`
      ]
    },
    fileGuideFocusLabel() {
      if (this.fileSummary.currentDirectory === '回收站') {
        return '当前重点：先确认是不是误删文件，再决定是否恢复或彻底清理'
      }
      if (this.fileSummary.currentDirectory !== '全部文件') {
        return `当前重点：先核对「${this.fileSummary.currentDirectory}」目录里的素材是不是放对地方`
      }
      return '当前重点：先找目录，再核对文件用途'
    },
    fileGuideCards() {
      return [
        {
          step: '第一步',
          title: '先确认文件属于哪个目录',
          desc: '不要一上来全库翻找，先看它应该归在哪个分组或业务模块。'
        },
        {
          step: '第二步',
          title: '再判断是文件本身问题还是归档问题',
          desc: '文件错了就在这里替换；如果文件没错但前台取错了，通常要继续查分组、标签或内容绑定。'
        },
        {
          step: '第三步',
          title: '最后去分组、标签或业务页做承接',
          desc: '文件库只负责素材本身，真正影响前台展示的常常是外层引用关系。'
        }
      ]
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.bindFileManageSummary()
      this.syncFileSummary()
    })
  },
  methods: {
    bindFileManageSummary() {
      const fileManage = this.$refs.fileManage
      if (!fileManage || fileManage.__fileSummaryBound__) {
        return
      }
      const originalListData = fileManage.listData
      if (typeof originalListData === 'function') {
        fileManage.listData = (data) => {
          originalListData.call(fileManage, data)
          this.syncFileSummary()
        }
      }
      fileManage.__fileSummaryBound__ = true
    },
    syncFileSummary() {
      const fileManage = this.$refs.fileManage
      if (!fileManage) {
        return
      }
      const currentGroupId = fileManage.query && fileManage.query.group_id
      const currentGroup = Array.isArray(fileManage.groupData)
        ? fileManage.groupData.find((item) => String(item.group_id) === String(currentGroupId))
        : null
      this.fileSummary = {
        total: Number(fileManage.count) || 0,
        currentDirectory: fileManage.recycle
          ? '回收站'
          : currentGroup && currentGroup.group_name
            ? currentGroup.group_name
            : '全部文件'
      }
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'file-file'
        }
      })
    }
  }
}
</script>

<style scoped>
.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  border: 1px solid #e7ecf5;
  border-radius: 12px;
  background: #fbfcfe;
}

.file-guide-panel {
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.file-guide-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.file-guide-panel__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.file-guide-panel__desc {
  margin-top: 6px;
  color: #64748b;
  line-height: 1.7;
  font-size: 12px;
}

.file-guide-panel__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.file-guide-panel__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.file-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.file-guide-card__step {
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

.file-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.file-guide-card__desc {
  margin-top: 8px;
  color: #64748b;
  line-height: 1.7;
  font-size: 12px;
}

.followup-panel__main {
  flex: 1;
  min-width: 0;
}

.followup-panel__title {
  font-size: 14px;
  font-weight: 700;
  color: #1f2329;
}

.followup-panel__desc {
  margin-top: 6px;
  color: #5f6b7a;
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
  background: #fff;
  border: 1px solid #e7ecf5;
  color: #4a5670;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.file-shell {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.file-page-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 4px 2px;
}

.file-page-hero__main h2 {
  margin: 0;
  font-size: 24px;
  line-height: 1.2;
  color: #1f2329;
}

.file-page-hero__title-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.file-page-hero__main p {
  margin: 8px 0 0;
  font-size: 13px;
  line-height: 1.7;
  color: #5f6b7a;
}

.file-page-hero__env,
.file-page-hero__meta span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.file-page-hero__env {
  color: #315efb;
  background: #eef3ff;
}

.file-page-hero__meta {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.file-page-hero__meta span {
  color: #4a5670;
  background: #f5f7fb;
}

.file-summary-bar {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  padding: 14px 16px;
  border: 1px solid #e7ecf5;
  border-radius: 12px;
  background: #fbfcfe;
}

.file-summary-bar__item {
  min-width: 0;
}

.file-summary-bar__label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  color: #7a8599;
}

.file-summary-bar__item strong {
  display: block;
  font-size: 14px;
  line-height: 1.6;
  color: #1f2329;
}

.file-summary-bar__item--tip strong {
  color: #4f5d73;
  font-size: 13px;
}

.manager-card {
  padding: 16px;
  border: 1px solid #ebeef5;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  box-shadow: 0 10px 24px rgba(31, 35, 41, 0.06);
}

@media (max-width: 900px) {
  .followup-panel,
  .file-guide-panel__header,
  .file-page-hero {
    flex-direction: column;
    align-items: flex-start;
  }

  .file-page-hero__meta,
  .file-summary-bar,
  .file-guide-panel__badge {
    width: 100%;
    min-width: 0;
  }

  .section-title-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .file-summary-bar {
    grid-template-columns: 1fr 1fr;
  }

  .file-guide-panel__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .file-summary-bar {
    grid-template-columns: 1fr;
  }
}
</style>
