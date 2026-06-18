<template>
  <view class="footmark-page">
    <view class="hero-card">
      <view class="hero-card__badge">最近浏览</view>
      <view class="hero-card__title">浏览足迹整理中</view>
      <view class="hero-card__desc">
        这个入口先不再空白，后续如果要继续做“我的足迹”，可以直接在这页往下接数据和筛选。
      </view>
    </view>

    <view class="status-card">
      <view class="status-card__title">当前状态</view>
      <view class="status-card__tags">
        <text class="status-card__tag">页面已承接</text>
        <text class="status-card__tag">支持返回上一页</text>
        <text class="status-card__tag">后续可接真实足迹接口</text>
      </view>
      <view class="status-card__desc">
        现在先保底不出现空白页，避免运营或测试点到“足迹”时直接中断链路。
      </view>
    </view>

    <view class="action-list">
      <view class="action-item" @tap="goBack">返回上一页</view>
      <view class="action-item action-item--primary" @tap="goMy">回到我的</view>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {}
  },
  methods: {
    goBack() {
      const pages = getCurrentPages()
      if (Array.isArray(pages) && pages.length > 1) {
        uni.navigateBack()
        return
      }
      this.goMy()
    },
    goMy() {
      uni.switchTab({
        url: '/pages/app/my'
      })
    }
  }
}
</script>

<style scoped>
.footmark-page {
  min-height: 100vh;
  padding: 28rpx;
  background: linear-gradient(180deg, #f7f8fb 0%, #ffffff 46%);
  box-sizing: border-box;
}

.hero-card,
.status-card {
  padding: 32rpx 28rpx;
  border-radius: 28rpx;
  background: #ffffff;
  box-shadow: 0 18rpx 40rpx rgba(34, 49, 84, 0.08);
}

.status-card {
  margin-top: 24rpx;
}

.hero-card__badge {
  display: inline-flex;
  align-items: center;
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  background: #eef3ff;
  color: #4263eb;
  font-size: 22rpx;
}

.hero-card__title,
.status-card__title {
  margin-top: 20rpx;
  color: #1f2937;
  font-size: 38rpx;
  font-weight: 700;
}

.hero-card__desc,
.status-card__desc {
  margin-top: 16rpx;
  color: #5b6475;
  font-size: 26rpx;
  line-height: 1.7;
}

.status-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
  margin-top: 20rpx;
}

.status-card__tag {
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  background: #f4f6fb;
  color: #4b5563;
  font-size: 24rpx;
}

.action-list {
  display: grid;
  gap: 20rpx;
  margin-top: 32rpx;
}

.action-item {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 92rpx;
  border-radius: 24rpx;
  background: #ffffff;
  color: #334155;
  font-size: 28rpx;
  font-weight: 600;
  box-shadow: 0 12rpx 28rpx rgba(34, 49, 84, 0.06);
}

.action-item--primary {
  background: linear-gradient(135deg, #ff7a45, #ff9f45);
  color: #ffffff;
}
</style>
