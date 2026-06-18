function normalizeText(value) {
  if (value === undefined || value === null) {
    return "";
  }
  return String(value).trim();
}

function getSystemPlatform() {
  try {
    const info = uni.getSystemInfoSync ? uni.getSystemInfoSync() || {} : {};
    return normalizeText(info.uniPlatform || info.platform).toLowerCase();
  } catch (error) {
    return "";
  }
}

function buildPaymentParams(orderInfo = {}, provider = "") {
  const params = {
    nonceStr: orderInfo.nonceStr,
    package: orderInfo.package,
    timeStamp: orderInfo.timeStamp,
    paySign: orderInfo.paySign,
    signType: orderInfo.signType,
  };
  if (provider) {
    params.provider = provider;
  }
  return params;
}

export function getPaymentErrorMessage(
  error,
  fallback = "支付暂时无法完成，请稍后重试",
) {
  const message = normalizeText(
    (error && (error.msg || error.message || error.errMsg || error.reason)) ||
      "",
  );
  if (!message) {
    return fallback;
  }
  if (/cancel/i.test(message)) {
    return "已取消支付";
  }
  return message;
}

export function requestOrderPayment(orderInfo = {}) {
  return new Promise((resolve, reject) => {
    const platform = getSystemPlatform();

    if (platform === "mp-weixin" || typeof wx !== "undefined") {
      uni.requestPayment({
        ...buildPaymentParams(orderInfo, "wxpay"),
        success: resolve,
        fail: reject,
      });
      return;
    }

    if (platform === "app" || platform === "app-plus") {
      uni.getProvider({
        service: "payment",
        success: (res) => {
          const providers = Array.isArray(res.provider) ? res.provider : [];
          if (!providers.includes("wxpay")) {
            reject({
              unsupported_payment: true,
              message:
                "当前 APP 未安装微信支付能力，请先配置支付通道后再发起支付",
            });
            return;
          }
          uni.requestPayment({
            ...buildPaymentParams(orderInfo, "wxpay"),
            success: resolve,
            fail: reject,
          });
        },
        fail: () => {
          reject({
            unsupported_payment: true,
            message: "当前设备暂时无法读取支付通道，请稍后重试",
          });
        },
      });
      return;
    }

    reject({
      unsupported_payment: true,
      message:
        "当前 H5 页面不支持直接调起微信支付，请改用微信小程序、APP，或选择线下凭证支付",
    });
  });
}
