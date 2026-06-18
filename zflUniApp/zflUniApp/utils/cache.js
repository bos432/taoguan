/**
 * 统一缓存工具
 *
 * 用法示例：
 * - 设置：cache.set('k', 'value', 60)
 * - 读取：cache.get('k', '')
 * - 删除：cache.remove('k')
 * - 清空：cache.clear()
 */

const postfix = "_scjyapp";

function set(k, v, t = 0) {
  uni.setStorageSync(k, v);
  const seconds = parseInt(t);
  if (seconds > 0) {
    let timestamp = Date.now();
    timestamp = timestamp / 1000 + seconds;
    uni.setStorageSync(k + postfix, timestamp);
  } else {
    uni.removeStorageSync(k + postfix);
  }
}

function get(k, def = "") {
  const deadtime = parseInt(uni.getStorageSync(k + postfix));
  if (deadtime && parseInt(deadtime) < Date.now() / 1000) {
    remove(k);
    return def;
  }

  const value = uni.getStorageSync(k);
  return value === undefined || value === "" ? def : value;
}

function remove(k) {
  uni.removeStorageSync(k);
  uni.removeStorageSync(k + postfix);
}

function clear() {
  uni.clearStorageSync();
}

export default {
  set,
  get,
  remove,
  clear,
};
