/**
 * app request API
 */
import settingApi from "./setting.js";
import contentApi from "./content.js";
import memberApi from "./member.js";
import goodsApi from "./goods.js";
import merchantApi from "./merchant.js";
import adminApi from "./admin.js";

export default {
  ...settingApi,
  ...contentApi,
  ...memberApi,
  ...goodsApi,
  ...merchantApi,
  ...adminApi,
};
