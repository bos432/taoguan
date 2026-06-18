import request from "./request.js";

export default {
  getContents: (params) => request("/content.Content/getListByHome", params, "get"),
  getContentInfo: (params) => request("/content.Content/info", params, "get"),
  getContentList: (params) => request("/content.Content/list", params, "get"),
};
