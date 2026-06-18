const CryptoJS = require('@/common/aes.js').CryptoJS;
var env = require('@/config/env.js');
const aes_key = CryptoJS.enc.Utf8.parse(env.aes_key);  //十六位十六进制数作为密钥
const aes_iv  = CryptoJS.enc.Utf8.parse(env.aes_iv);  //十六位十六进制数作为密钥偏移量

//解密方法
function aesDecrypt(str) {
    var decrypted = CryptoJS.AES.decrypt(
        str,
        aes_key,
        {
            iv:aes_iv,
            mode:CryptoJS.mode.CBC,
            padding:CryptoJS.pad.Pkcs7
        },
    );
    decrypted = decrypted.toString(CryptoJS.enc.Utf8);
    return decrypted;//返回utf-8字符串
}
//加密方法
function aesEncrypt(str) {
    //utf8编码
    var data = CryptoJS.enc.Utf8.parse(str);
    //加密
    var encrypted = CryptoJS.AES.encrypt(
        data,aes_key,
        {
            iv:aes_iv,
            mode:CryptoJS.mode.CBC,
            padding:CryptoJS.pad.Pkcs7,
        },
    );
    encrypted = encrypted.toString();
    return encrypted;//返回base64编码
}
module.exports={
    aesDecrypt   : aesDecrypt,
    aesEncrypt   : aesEncrypt,
}