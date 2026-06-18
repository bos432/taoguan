import cache from '@/utils/cache.js';
import { buildApiUrl } from '@/utils/resource.js';
/**
 * 上传图片
 */
function uploadImage(url= '/setting.Upload/file',params= {file_type: 'image'}){
	return new Promise((resolve, reject) => {
		let that = this;
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
				 		if(res.data){
				 			let data = JSON.parse(res.data);
				 			if(data.code && data.code == 200){
								resolve(data.data);
				 			}else{
				 				uni.hideToast();
				 				let msg = '请求失败';
				 				if (data && data.msg){
				 					msg = data.msg
				 				}
				 				uni.showToast({
				 					icon: 'none',
				 					title: msg
				 				});
								reject(data);
				 			}
				 		}
				 	},
					fail:(err)=>{
						reject(err);
					}
				 });
			}
		});
	});
}

export default {
	uploadImage
}
