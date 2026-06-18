<template>
  <div class="app-container">
    <el-card  class="app-main">
      <el-tabs>
        <el-tab-pane v-if="checkPermission(['merchant/system.UserCenter/info'])" label="我的信息" lazy>
          <UserCenterInfo />
        </el-tab-pane>
        <el-tab-pane v-if="checkPermission(['merchant/system.UserCenter/edit'])" label="修改信息" lazy>
          <UserCenterEdit />
        </el-tab-pane>
        <el-tab-pane v-if="checkPermission(['merchant/system.UserCenter/pwd'])" label="修改密码" lazy>
          <UserCenterPwd />
        </el-tab-pane>
<!--        <el-tab-pane v-if="checkPermission(['merchant/system.UserCenter/log'])" label="我的日志" lazy>-->
<!--          <UserCenterLog />-->
<!--        </el-tab-pane>-->
      </el-tabs>
    </el-card>
  </div>
</template>

<script>
import checkPermission from '@/utils/permission'
import UserCenterInfo from './components/UserCenterInfo.vue'
import UserCenterEdit from './components/UserCenterEdit.vue'
import UserCenterPwd from './components/UserCenterPwd.vue'
import UserCenterLog from './components/UserCenterLog.vue'

export default {
  name: 'SystemUserCenter',
  components: { UserCenterInfo, UserCenterEdit, UserCenterPwd, UserCenterLog },
  data() {
    return {
      name: '个人中心'
    }
  },
  methods: {
    checkPermission
  }
}
</script>
