import { defineStore } from 'pinia'
import { resetRouter } from '@/router'
import { store } from '@/store'
import { useStorage } from '@vueuse/core'
import { useSettingsStore } from '@/store/modules/settings'
import { login as loginApi, logout as logoutApi } from '@/api/system/login'
import {
  info as userInfoApi,
  permissionContext as permissionContextApi
} from '@/api/system/user-center'
import defaultSettings from '@/settings'

export const useUserStore = defineStore('user', () => {
  const settingsStore = useSettingsStore()
  const storePrefix = defaultSettings.storePrefix
  const tokenName = settingsStore.tokenName
  const token = useStorage(storePrefix + tokenName, '')
  const user = reactive({
    username: '',
    nickname: '',
    avatar_url: '',
    roles: [],
    menus: [],
    role_codes: [],
    permission_codes: [],
    permission_map: {},
    permission_version: '',
    data_scope: {},
    merchant: {},
    identity: {}
  })

  function applyPermissionContext(context = {}) {
    user.role_codes = context.role_codes || []
    user.permission_codes = context.permission_codes || []
    user.permission_map = context.permission_map || {}
    user.permission_version = context.permission_version || ''
    user.data_scope = context.data_scope || {}
    user.merchant = context.merchant || {}
    user.identity = context.identity || {}
    return context
  }

  function refreshPermissionContext() {
    return permissionContextApi().then(({ data }) => applyPermissionContext(data))
  }

  // 登录
  function login(data) {
    return new Promise((resolve, reject) => {
      loginApi(data)
        .then((res) => {
          const data = res.data
          const tokenName = settingsStore.tokenName
          token.value = data[tokenName]
          settingsStore.changeSetting({ key: 'merTitle', value: data.mer_title })
          resolve()
        })
        .catch((err) => {
          reject(err)
        })
    })
  }

  // 用户信息
  function userInfo() {
    return new Promise((resolve, reject) => {
      Promise.all([userInfoApi(), permissionContextApi()])
        .then(([{ data }, { data: permissionContext }]) => {
          if (!data) {
            reject('Verification failed, please Login again.')
            return
          }
          if (!data.roles || data.roles.length <= 0) {
            reject('userInfo: roles must be a non-null array!')
            return
          }
          user.nickname = data.nickname
          user.username = data.username
          user.avatar_url = data.avatar_url
          user.roles = data.roles
          user.menus = data.menus
          applyPermissionContext(permissionContext)
          resolve(data)
        })
        .catch((err) => {
          reject(err)
        })
    })
  }

  // 退出
  function logout() {
    return new Promise((resolve, reject) => {
      logoutApi()
        .then(() => {
          token.value = ''
          location.reload() // 清空路由
          resolve()
        })
        .catch((err) => {
          reject(err)
        })
    })
  }

  // 移除token
  function resetToken() {
    return new Promise((resolve) => {
      token.value = ''
      resetRouter()
      resolve()
    })
  }

  return {
    token,
    user,
    login,
    userInfo,
    refreshPermissionContext,
    logout,
    resetToken
  }
})

// 非setup
export function useUserStoreHook() {
  return useUserStore(store)
}
