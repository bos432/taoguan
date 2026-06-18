import request from "./request.js";

export default {
  merchantApply: (params) => request("/merchant.Merchant/add", params, "post"),
  merchantInfo: (params) => request("/merchant.Merchant/info", params, "get"),
  merchantIdentityList: (params) => request("/merchant.Identity/list", params, "get"),
  merchantIdentityCurrent: (params) => request("/merchant.Identity/current", params, "get"),
  merchantIdentitySwitch: (params) => request("/merchant.Identity/switch", params, "post"),
  merchantIdentityPermissions: (params) => request("/merchant.Identity/permissions", params, "get"),
  merchantList: (params) => request("/merchant.Merchant/list", params, "get"),
  getMerchantRenewRecords: (params) => request("/merchant.Merchant/getRenewRecords", params, "get"),
  getMerParams: (params) => request("/merchant.Merchant/getMerParams", params, "get"),
  getMerOrderList: (params) => request("/merchant.Merchant/getOrderlist", params, "post"),
  getMerAnalytics: (params) => request("/merchant.Merchant/getAnalytics", params, "get"),
  merOrderPayAuth: (params) => request("/merchant.Merchant/orderPayAuth", params, "post"),
};
