import request from "./request.js";

export default {
  getSetting: (params) => request("/setting.Setting/setting", params, "get"),
  getServiceInfo: (params) => request("/setting.Setting/getServiceInfo", params, "get"),
  getCarouselList: (params) => request("/setting.Carousel/list", params, "get"),
  getPopupNotice: (params) => request("/setting.Notice/popupInfo", params, "get"),
  readPopupNotice: (params) => request("/setting.Notice/popupRead", params, "post"),
  getNoticeInfo: (params) => request("/setting.Notice/info", params, "get"),
  getAccordInfo: (params) => request("/setting.Accord/info", params, "get"),
  getAccordList: (params) => request("/setting.Accord/list", params, "get"),
  getAccordStatus: (params) => request("/setting.Accord/status", params, "post"),
  acceptAccords: (params) => request("/setting.Accord/accept", params, "post"),
};
