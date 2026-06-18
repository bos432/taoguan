import cache from "@/utils/cache.js";

export const MERCHANT_IDENTITY_KEY = "current_merchant_identity_mer_user_id";
export const MERCHANT_IDENTITY_LIST_KEY = "merchant_identity_list";

export function getCurrentMerchantIdentityId() {
  return Number(cache.get(MERCHANT_IDENTITY_KEY, 0) || 0);
}

export function setCurrentMerchantIdentityId(merUserId = 0) {
  const next = Number(merUserId || 0);
  if (next > 0) {
    cache.set(MERCHANT_IDENTITY_KEY, next);
  } else {
    cache.remove(MERCHANT_IDENTITY_KEY);
  }
}

export function clearCurrentMerchantIdentity() {
  cache.remove(MERCHANT_IDENTITY_KEY);
  cache.remove(MERCHANT_IDENTITY_LIST_KEY);
}

export function getMerchantIdentityList() {
  return cache.get(MERCHANT_IDENTITY_LIST_KEY, []) || [];
}

export function setMerchantIdentityList(list = []) {
  cache.set(MERCHANT_IDENTITY_LIST_KEY, Array.isArray(list) ? list : []);
}
