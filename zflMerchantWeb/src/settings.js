const defaultSettings = {
  loginBgUrl: '',
  loginBgColor: '',
  systemName: '商家端后端管理',
  pageTitle: '',
  faviconUrl: '',
  logoUrl: '',
  sidebarLogo: true,
  sidebarName: true,
  fixedHeader: true,
  tagsView: true,
  layout: 'left', // 导航模式：left、top、mix
  theme: 'light', // 主题：light明亮，dark暗黑
  themeColor: '#409EFF', // 主题颜色
  tokenType: 'header', // token方式，header、param
  tokenName: 'MerchantToken', // token名称，前后端必须一致
  pageLimit: 20, // 分页每页默认数量
  size: 'default', // 组件大小：large、default、small
  language: 'zh-cn', //语言：zh-cn中文，en英文
  storePrefix: 'mer_', // 本地存储前缀
  merTitle:'商家端后端管理',
}

export default defaultSettings
