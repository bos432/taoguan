<template>
  <div class="app-container">
    <div class="result-shell">
      <div class="result-overview">
        <div class="overview-card">
          <span class="overview-label">页面状态</span>
          <strong>404 未找到</strong>
          <p>可能是旧入口仍在访问，或菜单路径尚未对齐。</p>
        </div>
        <div class="overview-card">
          <span class="overview-label">排查方向</span>
          <strong>检查路由与菜单映射</strong>
          <p>建议回到首页后从最新菜单重新进入对应栏目。</p>
        </div>
      </div>

      <div class="result-card">
        <el-result icon="error" title="404" sub-title="页面不存在">
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
  name: 'System404'
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
  background: linear-gradient(180deg, #ffffff 0%, #fefefe 100%);
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
