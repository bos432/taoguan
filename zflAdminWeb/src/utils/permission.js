import { useUserStore } from '@/store/modules/user'
/**
 * 权限验证
 * @param {Array} value
 * @returns {Boolean}
 */
export default function checkPermission(value) {
  const userStore = useUserStore()
  if (Number(userStore.user.is_super) === 1) {
    return true
  }
  if (value && value instanceof Array && value.length > 0) {
    const roles = userStore.user.roles
    const permissionRoles = value
    const permissionHas = roles.some((role) => {
      return permissionRoles.includes(role)
    })
    return permissionHas
  } else {
    return false
  }
}
