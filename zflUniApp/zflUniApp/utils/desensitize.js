const SAFE_DISPLAY_TITLES = new Set([
  "平台直营",
  "平台订单",
  "平台自营",
  "商家信息",
  "商家服务中心",
]);

function normalizeText(value = "") {
  return String(value == null ? "" : value).trim();
}

function hasMaskMark(value = "") {
  return normalizeText(value).includes("*");
}

function maskCoreText(value = "", fallback = "") {
  const text = normalizeText(value);
  const safeFallback = normalizeText(fallback);

  if (!text) {
    return safeFallback;
  }
  if (SAFE_DISPLAY_TITLES.has(text) || hasMaskMark(text)) {
    return text;
  }

  const chars = Array.from(text);
  if (chars.length <= 1) {
    return text;
  }

  return `${chars[0]}***${chars[chars.length - 1]}`;
}

export function maskMerchantTitle(value = "", fallback = "平台直营") {
  return maskCoreText(value, fallback);
}

export function maskMemberNickname(value = "", fallback = "匿名用户") {
  return maskCoreText(value, fallback);
}

export default {
  maskMerchantTitle,
  maskMemberNickname,
};
