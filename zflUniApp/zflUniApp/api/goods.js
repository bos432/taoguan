import request from "./request.js";

export default {
  getGoodsParams: (params) => request("/goods.Goods/getParams", params, "get"),
  getGoodsList: (params) => request("/goods.Goods/list", params, "post"),
  getGoodsInfo: (params) => request("/goods.Goods/info", params, "post"),
  getReleaseParams: (params) => request("/goods.Goods/getReleaseParams", params, "get"),
  saveRelease: (params) => request("/goods.Goods/saveRelease", params, "post"),
  delRelease: (params) => request("/goods.Goods/memberDele", params, "post"),
  transactionRelease: (params) => request("/goods.Goods/transaction", params, "post"),
  getBatchTacheByCode: (params) => request("/goods.Goods/getBatchTacheByCode", params, "get"),
};
