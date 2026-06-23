import cache from '@/utils/cache.js';
import { buildApiUrl } from '@/utils/resource.js';

function normalizeUploadErrorMessage(error) {
	const rawMsg = String(
		(error && (error.msg || error.message || error.errMsg)) || error || ''
	).trim();
	if (!rawMsg) return '上传失败，请稍后重试';
	if (/mkdir|Operation not permitted|Permission denied|is not writable|权限/i.test(rawMsg)) {
		return '上传目录权限异常，请联系管理员检查服务器存储权限';
	}
	if (/JSON|Unexpected token|SyntaxError/i.test(rawMsg)) {
		return '上传接口返回异常，请稍后重试';
	}
	return rawMsg.length > 28 ? rawMsg.slice(0, 28) : rawMsg;
}

function showUploadError(error) {
	const msg = normalizeUploadErrorMessage(error);
	uni.hideToast();
	uni.showToast({
		icon: 'none',
		title: msg
	});
	return msg;
}

function parseUploadResponse(res) {
	if (!res || !res.data) {
		throw { msg: '上传接口未返回数据' };
	}
	if (typeof res.data === 'object') {
		return res.data;
	}
	try {
		return JSON.parse(res.data);
	} catch (error) {
		throw { msg: '上传接口返回异常，请稍后重试', raw: res.data, error };
	}
}

/**
 * 上传图片
 */
function uploadImage(url= '/setting.Upload/file',params= {file_type: 'image'}){
	return new Promise((resolve, reject) => {
		uni.chooseImage({
			count: 1, //默认9
			sizeType: ['original', 'compressed'], //可以指定是原图还是压缩图，默认二者都有
			sourceType: ['album'], //从相册选择
			success: function (chooseImageRes) {
				 const tempFilePaths = chooseImageRes.tempFilePaths;
				 params.file_type = 'image';
				 uni.uploadFile({
				 	url: buildApiUrl(url),
				 	filePath: tempFilePaths[0],
				 	name: 'file',
				 	formData: params,
					header: {
					    'ApiToken':cache.get('token', '')
					},
				 	success: (res) => {
				 		try {
				 			const data = parseUploadResponse(res);
				 			if(data.code && data.code == 200){
								resolve(data.data);
				 			}else{
				 				showUploadError(data);
								reject(data);
				 			}
				 		} catch (error) {
				 			const msg = showUploadError(error);
				 			reject({ msg, error });
				 		}
				 	},
					fail:(err)=>{
						const msg = showUploadError(err);
						reject({ msg, error: err });
					}
				 });
			}
		});
	});
}

export default {
	uploadImage
}
