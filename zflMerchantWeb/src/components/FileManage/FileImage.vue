<template>
  <el-row style="width: 100%">
    <el-col :span="fileSpan" :style="fileStyle" style="height: auto;">
      <div v-if="fileUrl" @mouseover="deleteIconVisible = true" @mouseout="deleteIconVisible = false">
        <!-- 为您的内容添加一个容器，以便在右上角放置删除图标 -->
        <div class="content-container" @click="handlePictureCardPreview()">
          <el-image
              v-if="fileType === 'image'"
              :style="imageStyle"
              :src="fileUrl"
              :fit="fit"
              :preview-teleported="previewTeleported"
              :lazy="lazy"
              :preview-src-list="[fileUrl]"
          >
            <template #error>
              <svg-icon icon-class="picture" />
            </template>
          </el-image>
          <video v-else-if="fileType === 'video'" :style="videoStyle">
            <source :src="fileUrl" type="video/mp4" />
            <object :data="fileUrl" :style="videoStyle">
              <embed :src="fileUrl" :style="videoStyle" />
            </object>
          </video>
          <audio  v-else-if="fileType === 'audio'" :style="audioStyle" controls>
            <source :src="fileUrl" type="audio/mp3" />
            <embed :src="fileUrl" :style="audioStyle" />
          </audio>
          <div v-else-if="fileType === 'word'">
            <svg-icon icon-class="document" :size="iconSize" />
          </div>
          <div v-else-if="fileType === 'other'">
            <svg-icon icon-class="folder" :size="iconSize" />
          </div>
          <el-icon v-if="fileSource !='list'" v-show="deleteIconVisible" class="delete-icon" @click="fileDelete">
            <CloseBold />
          </el-icon>
        </div>
      </div>
      <div v-else>
        <el-avatar v-if="avatar" :size="height">
          <svg-icon icon-class="user-filled" :size="iconSize" />
        </el-avatar>
        <div v-if="fileSource =='list'" class="upLoadPicBox">
          <div class="el-upload" :style="{ height: height + 'px',width: height + 'px' }">
            <el-icon :size="height" ><Picture /></el-icon>
          </div>
        </div>
        <div v-else-if="fileType=='video'" class="upLoadPicBox" @click="fileUpload()" >
          <div class="el-upload el-upload--picture-card" :style="{ height: height + 'px',width: height + 'px' }">
            <el-icon :size="height/2" ><Plus /></el-icon>
          </div>
        </div>
        <div v-else-if="!avatar" class="upLoadPicBox" @click="fileUpload()" >
          <div class="el-upload el-upload--picture-card" :style="{ height: height + 'px',width: height + 'px' }">
            <el-icon :size="height/2"><Plus /></el-icon>
          </div>
        </div>
      </div>
    </el-col>
    <el-col :span="operSpan" v-if="is_btn">
      <el-row>
        <el-col>
          <el-button @click="fileUpload()">{{ uploadBtn }}</el-button>
          <el-button @click="fileDelete()">{{ deleteBtn }}</el-button>
          <el-button v-if="fileUrl" @click="fileDownload()">下载</el-button>
        </el-col>
        <el-col>
          <el-text size="default" truncated :title="fileTip">{{ fileTip }}</el-text>
        </el-col>
      </el-row>
    </el-col>
    <el-dialog
        v-model="fileDialog"
        :title="fileTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        top="1vh"
        width="80%"
        append-to-body
    >
      <FileManage :file-type="fileType" @file-cancel="fileCancel" @file-submit="fileSubmit" />
    </el-dialog>
    <!----视频预览----->
    <el-dialog v-model="dialogVisible" append-to-body>
      <video if="fileType === 'video'" style="width: 100%" controls>
        <source :src="fileUrl" type="video/mp4" />
        <object :data="fileUrl" style="width: 100%">
          <embed :src="fileUrl" style="width: 100%" />
        </object>
      </video>
    </el-dialog>
  </el-row>
</template>

<script setup>
import FileManage from '@/components/FileManage/index.vue'
import { Camera, CloseBold, Picture,VideoCamera,Plus } from '@element-plus/icons-vue';
const deleteIconVisible =  ref(false)
// 图片上传
const model = defineModel({
  type: Number,
  default: 0
})
const fileUrl = defineModel('fileUrl', {
  type: String,
  default: ''
})
const props = defineProps({
  fileType: {
    type: String,
    default: 'image'
  },
  fileSource: {
    type: String,
    default: 'image'
  },
  fileTitle: {
    type: String,
    default: '上传图片'
  },
  fileTip: {
    type: String,
    default: '图片小于200KB，jpg、png格式。'
  },
  height: {
    type: Number,
    default: 30
  },
  fit: {
    type: String,
    default: ''
  },
  lazy: {
    type: Boolean,
    default: false
  },
  is_btn: {
    type: Boolean,
    default: false
  },
  previewTeleported: {
    type: Boolean,
    default: true
  },
  uploadBtn: {
    type: String,
    default: '上传'
  },
  deleteBtn: {
    type: String,
    default: '删除'
  },
  avatar: {
    type: Boolean,
    default: false
  },
  upload: {
    type: Boolean,
    default: false
  },
  disabled:{
    type:Boolean,
    default:false
  }
})

const fileSpan = ref(24)
const operSpan = ref(0)
const fileDialog = ref(false)
const fileStyle = computed(() => {
  return { height: props.height + 'px' }
})
const imageStyle = computed(() => {
  return { height: props.height + 'px' }
})
const videoStyle = computed(() => {
  return { height: props.height + 'px' }
})
const audioStyle = computed(() => {
  return { width: '90%' }
})
const iconSize = computed(() => {
  return props.height * 0.7 + 'px'
})

watch(
    [() => props.avatar, () => props.upload],
    ([avatar, upload]) => {
      if (avatar) {
        imageStyle.value.width = props.height + 'px'
        imageStyle.value.borderRadius = '50%'
      }
      if (upload) {
        fileSpan.value = 12
        operSpan.value = 12
      }
    },
    { immediate: true }
)

function fileUpload() {
  if(props.disabled){
    return false;
  }
  fileDialog.value = true
}
function fileCancel() {
  fileDialog.value = false
}
function fileSubmit(fileList) {
  fileDialog.value = false
  const fileLength = fileList.length
  if (fileLength) {
    const index = fileLength - 1
    model.value = fileList[index]['file_id']
    fileUrl.value = fileList[index]['file_url']
  }
}
function fileDelete() {
  if(props.disabled){
    return false;
  }
  model.value = 0
  fileUrl.value = ''
}
function fileDownload() {
  window.open(fileUrl.value, '_blank')
}

//视频预览
const dialogVisible = ref(false)
function handlePictureCardPreview(){
  if(props.fileType  == 'video'){
    dialogVisible.value = true;
  }

}
</script>
<style scoped>
.content-container {
  position: relative;
  display: inline-block; /* 确保容器大小与内容匹配 */
}

.delete-icon {
  position: absolute;
  top: 0; /* 调整位置至右上角 */
  right: 0; /* 调整位置至右上角 */
  cursor: pointer;
  z-index: 10;
  background: rgba(255, 255, 255, 0.7); /* 半透明背景，确保图标在浅色图片上也可见 */
  border-radius: 50%; /* 圆形背景 */
  padding: 1px; /* 内边距确保图标不贴边 */
}
.upLoadPicBox {
  display: inline-block;
  cursor: pointer;
}
.upLoadPicBox .upLoad {
  width: 58px;
  height: 58px;
  line-height: 58px;
  border: 1px dotted rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  background: rgba(0, 0, 0, 0.02);
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>