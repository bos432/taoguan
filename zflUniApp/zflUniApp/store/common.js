import Vue from "vue";
import Vuex from "vuex";
import cache from "@/utils/cache.js";
import api from "@/api";

Vue.use(Vuex);

const PENDING_ACCORD_KEY = "pending_accord_accept_map";
const MERCHANT_IDENTITY_KEY = "current_merchant_identity_mer_user_id";
const MERCHANT_IDENTITY_LIST_KEY = "merchant_identity_list";

function clearLoginCache(state) {
  cache.remove("userInfo");
  cache.remove("token");
  cache.remove(PENDING_ACCORD_KEY);
  cache.remove(MERCHANT_IDENTITY_KEY);
  cache.remove(MERCHANT_IDENTITY_LIST_KEY);
  state.hasLogin = false;
  state.userInfo = {};
  state.openid = null;
}

const store = new Vuex.Store({
  state: {
    bg_color: "#e54d42",
    userInfo: {},
    hasLogin: false,
    openid: "",
    setting: {},
    device: {
      mserviceuuid: "0000FFE0-0000-1000-8000-00805F9B34FB",
      mtxduuid: "0000FFE1-0000-1000-8000-00805F9B34FB",
      mrxduuid: "0000FFE1-0000-1000-8000-00805F9B34FB",
      usrserviceuuid: "0000FFE0-0000-1000-8000-00805F9B34FB",
      usrtxduuid: "0000FFE1-0000-1000-8000-00805F9B34FB",
      usrrxduuid: "0000FFE1-0000-1000-8000-00805F9B34FB",
      muuidSel: 0,
      mautoSendInv: 10,
      msendText: "",
      ble_device: null,
    },
  },
  mutations: {
    readUUID(state) {
      state.device.usrserviceuuid = cache.get("usrserviceuuid") || "0000FFE0-0000-1000-8000-00805F9B34FB";
      state.device.usrrxduuid = cache.get("usrrxduuid") || "0000FFE1-0000-1000-8000-00805F9B34FB";
      state.device.usrtxduuid = cache.get("usrtxduuid") || "0000FFE1-0000-1000-8000-00805F9B34FB";
      state.device.muuidSel = cache.get("lastsel") || 0;
    },
    readSetting(state) {
      this.commit("readUUID");
      state.device.mautoSendInv = cache.get("autoSendInv") || 100;
      state.device.msendText = cache.get("sendText") || "1024";
      state.device.muuidSel = cache.get("lastsel") || 0;

      switch (state.device.muuidSel) {
        case 0:
          state.device.mserviceuuid = "0000FFE0-0000-1000-8000-00805F9B34FB";
          state.device.mtxduuid = "0000FFE1-0000-1000-8000-00805F9B34FB";
          state.device.mrxduuid = "0000FFE1-0000-1000-8000-00805F9B34FB";
          break;
        case 1:
          state.device.mserviceuuid = "0000FFE0-0000-1000-8000-00805F9B34FB";
          state.device.mtxduuid = "0000FFE2-0000-1000-8000-00805F9B34FB";
          state.device.mrxduuid = "0000FFE1-0000-1000-8000-00805F9B34FB";
          break;
        case 2:
          state.device.mserviceuuid = state.device.usrserviceuuid;
          state.device.mtxduuid = state.device.usrtxduuid;
          state.device.mrxduuid = state.device.usrrxduuid;
          break;
      }
    },
    saveSetting(state, time, text) {
      cache.set("autoSendInv", time);
      cache.set("sendText", text);
    },
    savelastsel(state, sel) {
      cache.set("lastsel", sel);
    },
    saveusrUUID(state, id_s, id_t, id_r) {
      state.device.usrserviceuuid = id_s;
      state.device.usrrxduuid = id_r;
      state.device.usrtxduuid = id_t;
      cache.set("usrserviceuuid", id_s);
      cache.set("usrrxduuid", id_r);
      cache.set("usrtxduuid", id_t);
    },
    setSetting(state, data) {
      if (cache.get("setting")) {
        state.setting = cache.get("setting");
      } else {
        state.setting = data;
      }
    },
    login(state) {
      if (cache.get("userInfo") && cache.get("token")) {
        state.hasLogin = true;
        state.userInfo = cache.get("userInfo");
      }
    },
    logout(state) {
      api
        .logout({})
        .catch(() => {})
        .finally(() => {
          clearLoginCache(state);
          /* #ifdef MP-WEIXIN */
          uni.reLaunch({
            url: "/pages/app/my",
          });
          /* #endif */
        });
    },
    clearUserInfo(state) {
      clearLoginCache(state);
    },
    setOpenid(state, openid) {
      state.openid = openid;
    },
  },
  actions: {},
});

export default store;
