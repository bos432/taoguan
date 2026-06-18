import Vue from 'vue'
import App from './App'
import store from './store/common.js'
import uView from '@/uni_modules/uview-ui'
import { syncMerchantExpireOnPageShow } from '@/utils/merchant-expire.js'
import { applyUiThemeFromCache } from '@/utils/ui-theme.js'
import { getCurrentEnvInfo, showEnvStartupHint } from '@/utils/env-runtime.js'
Vue.use(uView)
uni.$u.config.unit = 'px'
Vue.config.productionTip = false

try {
    const envInfo = getCurrentEnvInfo()
    console.info('[wxapp.env.init]', envInfo)
    showEnvStartupHint()
} catch (error) {}

Vue.mixin({
    onShow() {
        syncMerchantExpireOnPageShow()
        applyUiThemeFromCache()
    }
})

App.mpType = 'app'
Vue.prototype.$store = store
const app = new Vue({
    store,
    ...App
})
app.$mount()
