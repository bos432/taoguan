import request from "./request.js";

export default {
  adminMerchantParams: (params) => request("/admin.MobileAdmin/merchantParams", params, "get"),
  adminMerchantList: (params) => request("/admin.MobileAdmin/merchantList", params, "post"),
  adminMerchantInfo: (params) => request("/admin.MobileAdmin/merchantInfo", params, "get"),
  adminMerchantAuth: (params) => request("/admin.MobileAdmin/merchantAuth", params, "post"),
  adminOrderParams: (params) => request("/admin.MobileAdmin/orderParams", params, "get"),
  adminOrderList: (params) => request("/admin.MobileAdmin/orderList", params, "post"),
  adminOrderPayAuth: (params) => request("/admin.MobileAdmin/orderPayAuth", params, "post"),
  adminOrderWriteoff: (params) => request("/admin.MobileAdmin/orderWriteoff", params, "post"),
};
