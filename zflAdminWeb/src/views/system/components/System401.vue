<template>
  <div class="app-container">
    <div class="result-shell">
      <div class="result-overview">
        <div class="overview-card">
          <span class="overview-label">访问状态</span>
          <strong>401 无权限</strong>
          <p>当前账号缺少进入此页面或执行此操作的授权。</p>
        </div>
        <div class="overview-card">
          <span class="overview-label">建议处理</span>
          <strong>返回首页或联系管理员</strong>
          <p>请核对角色菜单、按钮权限和数据范围配置。</p>
        </div>
      </div>

      <div class="result-card">
        <el-result icon="warning" title="401" sub-title="没有权限">
          <template #extra>
            <router-link to="/dashboard">
              <el-button type="primary">首页</el-button>
            </router-link>
          </template>
        </el-result>
      </div>
    </div>
  </div>
</template>

<script setup>
defineOptions({
  name: 'System401'
})
</script>

<style scoped>
.result-shell {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.result-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 14px;
}

.overview-card,
.result-card {
  border: 1px solid #ebeef5;
  border-radius: 16px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  box-shadow: 0 10px 24px rgba(31, 35, 41, 0.06);
}

.overview-card {
  padding: 18px;
}

.overview-label {
  display: inline-block;
  margin-bottom: 10px;
  color: #7a8599;
  font-size: 12px;
}

.overview-card strong {
  display: block;
  color: #1f2329;
  font-size: 20px;
}

.overview-card p {
  margin: 8px 0 0;
  color: #5f6b7a;
  font-size: 13px;
}

.result-card {
  padding: 12px;
}
</style>
