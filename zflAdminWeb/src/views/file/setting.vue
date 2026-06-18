<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">文件设置</div>
          <div class="section-title-row__desc">
            统一维护文件存储、上传权限、前台资源能力和文件限制，首屏直接进入可提交配置。
          </div>
        </div>
        <div class="section-title-row__meta">当前存储：{{ currentStorageLabel }}</div>
      </div>
      <div v-if="entryContextVisible" class="entry-context-banner">
        <div class="entry-context-banner__main">
          <div class="entry-context-banner__eyebrow">外部入口承接</div>
          <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
          <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
        </div>
        <div class="entry-context-banner__actions">
          <el-button type="primary" @click="handleEntryContextPrimary">{{
            entryContextPrimaryLabel
          }}</el-button>
          <el-button @click="goToEntryContextBack">回来源页</el-button>
        </div>
      </div>
      <div class="setting-plain-guide">
        <div class="setting-plain-guide__header">
          <div>
            <div class="setting-plain-guide__title">文件设置第一次进来，先判断你在改哪条链路</div>
            <div class="setting-plain-guide__desc">
              先分清是“上传入口有问题”、“存储云配置有问题”，还是“前台资源范围不对”，再往下改会稳很多。
            </div>
          </div>
          <div class="setting-plain-guide__badge">{{ fileSettingGuideFocusLabel }}</div>
        </div>
        <div class="setting-plain-guide__grid">
          <div
            v-for="item in fileSettingGuideCards"
            :key="item.title"
            class="setting-plain-guide-card"
          >
            <span class="setting-plain-guide-card__step">{{ item.step }}</span>
            <div class="setting-plain-guide-card__title">{{ item.title }}</div>
            <div class="setting-plain-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <div class="setting-summary-bar">
        <div class="setting-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">存储 {{ currentStorageLabel }}</span>
          <span class="summary-chip">上传 {{ uploadSummary }}</span>
          <span class="summary-chip">前台资源 {{ apiFileSwitchText }}</span>
          <span class="summary-chip">资源范围 {{ apiFileSummary }}</span>
          <span class="summary-chip">文件限制 {{ limitSummary }}</span>
          <span class="summary-chip summary-chip--muted">{{ storageHint }}</span>
        </div>
        <div class="setting-summary-bar__hint" :class="summaryHintClass">
          <span class="setting-summary-bar__hint-title">{{ summaryHintTitle }}</span>
          <span class="setting-summary-bar__hint-text">{{ summaryHintText }}</span>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">配置完后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/file/file')">去文件管理</el-button>
          <el-button @click="goToPage('/file/group')">去文件分组</el-button>
          <el-button @click="goToPage('/file/tag')">去文件标签</el-button>
        </div>
      </div>
      <el-form ref="ref" :model="model" :rules="rules" label-width="150px">
        <el-tabs>
          <el-tab-pane label="文件设置" lazy>
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="前台上传" prop="is_upload_api">
                <el-switch v-model="model.is_upload_api" :active-value="1" :inactive-value="0" />
                <span> 关闭后，前台无法上传文件</span>
              </el-form-item>
              <el-form-item label="后台上传" prop="is_upload_admin">
                <el-switch v-model="model.is_upload_admin" :active-value="1" :inactive-value="0" />
                <span> 关闭后，后台无法上传文件</span>
              </el-form-item>
              <el-form-item label="存储方式" prop="storage">
                <el-select v-model="model.storage" placeholder="请选择" class="!w-xs">
                  <el-option
                    v-for="(item, index) in storages"
                    :key="index"
                    :label="item"
                    :value="index"
                    @change="storageChange"
                  />
                </el-select>
              </el-form-item>
              <div v-if="model.storage == 'local'">
                <el-form-item>
                  <el-card shadow="never">
                    <div>
                      文件将存储在本地服务器，默认保存在 public/storage 目录，文件以散列 hash 命名。
                      <br />
                      文件存储的目录需要有读写权限（777），有足够的存储空间。
                    </div>
                  </el-card>
                </el-form-item>
              </div>
              <div v-else-if="model.storage == 'qiniu'">
                <el-form-item>
                  <el-card shadow="never">
                    <div>
                      文件将上传到七牛云 Kodo 存储，对象存储 > 空间管理 > 空间设置 > 访问控制, 设置为
                      公开空间。
                      <br />
                      需要配置跨域访问 CORS 规则，设置：来源 Origin 为 *，允许 Methods 为
                      GET,POST，允许 Headers 为 *。
                    </div>
                  </el-card>
                </el-form-item>
                <el-form-item label="AccessKey" prop="qiniu_access_key">
                  <el-col :span="11">
                    <el-input v-model="model.qiniu_access_key" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.qiniu_access_key)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    AccessKey（AK）在 [ 七牛云 > 个人中心 > 密钥管理 ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="SecretKey" prop="qiniu_secret_key">
                  <el-col :span="11">
                    <el-input v-model="model.qiniu_secret_key" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.qiniu_secret_key)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    SecretKey（SK）在 [ 七牛云 > 个人中心 > 密钥管理 ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="空间名称" prop="qiniu_bucket">
                  <el-col :span="11">
                    <el-input v-model="model.qiniu_bucket" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.qiniu_bucket)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    空间名称 在 [ 七牛云 > 对象存储 > 空间管理 ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="外链域名" prop="qiniu_domain">
                  <el-col :span="11">
                    <el-input v-model="model.qiniu_domain" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.qiniu_domain)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    外链域名 在 [ 七牛云 > 对象存储 > 空间管理 > 域名设置 ] 设置和获取
                  </el-col>
                </el-form-item>
              </div>
              <div v-else-if="model.storage == 'aliyun'">
                <el-form-item>
                  <el-card shadow="never">
                    <div>
                      文件将上传到阿里云 OSS 存储，需要配置 OSS 公开访问及跨域策略。
                      <br />
                      需要配置跨域访问 CORS 规则，设置：来源 Origin 为 *，允许 Methods 为
                      GET,POST，允许 Headers 为 *。
                    </div>
                  </el-card>
                </el-form-item>
                <el-form-item label="AccessKey ID" prop="aliyun_access_key_id">
                  <el-col :span="11">
                    <el-input v-model="model.aliyun_access_key_id" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.aliyun_access_key_id)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    AccessKey ID 在 [ 阿里云 > 个人中心 > AccessKey 管理 ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="AccessKey Secret" prop="aliyun_access_key_secret">
                  <el-col :span="11">
                    <el-input v-model="model.aliyun_access_key_secret" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.aliyun_access_key_secret)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    AccessKey Secret 在 [ 阿里云 > 个人中心 > AccessKey 管理 ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="Bucket名称" prop="aliyun_bucket">
                  <el-col :span="11">
                    <el-input v-model="model.aliyun_bucket" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.aliyun_bucket)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    Bucket 名称 在 [ 阿里云 > 对象存储 > Bucket 列表 ] 获取
                  </el-col>
                </el-form-item>
                <el-form-item label="Bucket域名" prop="aliyun_bucket_domain">
                  <el-col :span="11">
                    <el-input v-model="model.aliyun_bucket_domain" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.aliyun_bucket_domain)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    Bucket 域名 在 [ 阿里云 > 对象存储 > Bucket 列表 > Bucket 概览 ] 获取
                  </el-col>
                </el-form-item>
                <el-form-item label="Endpoint地域节点" prop="aliyun_endpoint">
                  <el-col :span="11">
                    <el-input v-model="model.aliyun_endpoint" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.aliyun_endpoint)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    Endpoint（地域节点） 在 [ 阿里云 > 对象存储 > Bucket 列表 > Bucket 概览 ] 获取
                  </el-col>
                </el-form-item>
              </div>
              <div v-else-if="model.storage == 'tencent'">
                <el-form-item>
                  <el-card shadow="never">
                    <div>
                      文件将上传到腾讯云 COS 存储，需要配置 COS 公有读私有写访问权限及跨域策略。
                      <br />
                      需要配置跨域访问 CORS 规则，设置：来源 Origin 为 *，允许 Methods 为
                      GET,POST，允许 Headers 为 *。
                    </div>
                  </el-card>
                </el-form-item>
                <el-form-item label="SecretId" prop="tencent_secret_id">
                  <el-col :span="11">
                    <el-input v-model="model.tencent_secret_id" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.tencent_secret_id)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    SecretId 在 [ 腾讯云 > 个人中心 > 访问管理 > 访问密钥 ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="SecretKey" prop="tencent_secret_key">
                  <el-col :span="11">
                    <el-input v-model="model.tencent_secret_key" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.tencent_secret_key)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    SecretKey 在 [ 腾讯云 > 个人中心 > 访问管理 > 访问密钥 ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="存储桶名称" prop="tencent_bucket">
                  <el-col :span="11">
                    <el-input v-model="model.tencent_bucket" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.tencent_bucket)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    存储桶名称 在 [ 腾讯云 > 对象存储 > 存储桶列表 ] 获取
                  </el-col>
                </el-form-item>
                <el-form-item label="所属地域" prop="tencent_region">
                  <el-col :span="11">
                    <el-input v-model="model.tencent_region" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.tencent_region)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    所属地域 在 [ 腾讯云 > 对象存储 > 存储桶列表 ] 获取。如：ap-guangzhou
                  </el-col>
                </el-form-item>
                <el-form-item label="访问域名" prop="tencent_domain">
                  <el-col :span="11">
                    <el-input v-model="model.tencent_domain" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.tencent_domain)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    访问域名 在 [ 腾讯云 > 对象存储 > 存储桶列表 > 概览 ] 获取
                  </el-col>
                </el-form-item>
              </div>
              <div v-else-if="model.storage == 'baidu'">
                <el-form-item>
                  <el-card shadow="never">
                    <div>
                      文件将上传到百度云 BOS 存储，对象存储 > Bucket列表 > 配置设置 > Bucket权限配置,
                      设置为 公共 *。
                      <br />
                      需要配置跨域访问 CORS 规则，设置：来源 Origin 为 *，允许 Methods 为
                      GET,POST，允许 Headers 为 *。
                    </div>
                  </el-card>
                </el-form-item>
                <el-form-item label="Access Key" prop="baidu_access_key">
                  <el-col :span="11">
                    <el-input v-model="model.baidu_access_key" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.baidu_access_key)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    Access Key 在 [ 百度云 > 个人中心 > 安全认证 > Access Key ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="Secret Key" prop="baidu_secret_key">
                  <el-col :span="11">
                    <el-input v-model="model.baidu_secret_key" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.baidu_secret_key)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    Secret Key 在 [ 百度云 > 个人中心 > 安全认证 > Access Key ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="Bucket名称" prop="baidu_bucket">
                  <el-col :span="11">
                    <el-input v-model="model.baidu_bucket" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.baidu_bucket)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    Bucket 名称 在 [ 百度云 > 对象存储 > Bucket 列表 ] 获取。如：yyladmin
                  </el-col>
                </el-form-item>
                <el-form-item label="官方域名" prop="baidu_domain">
                  <el-col :span="11">
                    <el-input v-model="model.baidu_domain" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.baidu_domain)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    官方域名 在 [ 百度云 > 对象存储 > Bucket列表 > 发布管理 ]
                    获取。如：yyladmin.gz.bcebos.com
                  </el-col>
                </el-form-item>
                <el-form-item label="所属地域" prop="baidu_endpoint">
                  <el-col :span="11">
                    <el-input v-model="model.baidu_endpoint" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.baidu_endpoint)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    所属地域：官方域名去掉 Bucket 名称，如：gz.bcebos.com
                  </el-col>
                </el-form-item>
              </div>
              <div v-else-if="model.storage == 'upyun'">
                <el-form-item>
                  <el-card shadow="never">
                    <div>
                      文件将上传到又拍云 USS 存储，云存储 > 服务管理 > 配置 > CORS 跨域共享, 设置为
                      已开启。
                      <br />
                      请根据业务域名和需求，配置 CORS 跨域共享 规则，CORS 配置。
                    </div>
                  </el-card>
                </el-form-item>
                <el-form-item label="服务名称" prop="upyun_service_name">
                  <el-col :span="11">
                    <el-input v-model="model.upyun_service_name" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.upyun_service_name)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    服务名称 在 [ 又拍云 > 云存储 > 服务管理 ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="操作员" prop="upyun_operator_name">
                  <el-col :span="11">
                    <el-input v-model="model.upyun_operator_name" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.upyun_operator_name)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    操作员 在 [ 又拍云 > 云存储 > 服务管理 > 配置 > 存储管理-操作员授权 ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="操作员密码" prop="upyun_operator_pwd">
                  <el-col :span="11">
                    <el-input v-model="model.upyun_operator_pwd" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.upyun_operator_pwd)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    操作员密码 在 [ 又拍云 > 云存储 > 服务管理 > 配置 > 存储管理-操作员授权 ]
                    设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="加速域名" prop="upyun_domain">
                  <el-col :span="11">
                    <el-input v-model="model.upyun_domain" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.upyun_domain)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    加速域名 在 [ 又拍云 > 云存储 > 服务管理 > 配置 > 域名管理-加速域名 ] 设置和获取
                  </el-col>
                </el-form-item>
              </div>
              <div v-else-if="model.storage == 'aws'">
                <el-form-item>
                  <el-card shadow="never">
                    <div>
                      文件将上传到 AWS S3。
                      <br />
                      请根据业务域名和需求，配置 AWS S3 访问控制。
                    </div>
                  </el-card>
                </el-form-item>
                <el-form-item label="Access Key ID" prop="aws_access_key_id">
                  <el-col :span="11">
                    <el-input v-model="model.aws_access_key_id" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.aws_access_key_id)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    Access Key ID 在 [ AWS > 我的账号 > 安全凭证 ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="Secret Access KEY" prop="aws_secret_access_key">
                  <el-col :span="11">
                    <el-input v-model="model.aws_secret_access_key" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.aws_secret_access_key)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13">
                    Secret Access KEY 在 [ AWS > 我的账号 > 安全凭证 ] 设置和获取
                  </el-col>
                </el-form-item>
                <el-form-item label="区域终端节点" prop="aws_region">
                  <el-col :span="11">
                    <el-input v-model="model.aws_region" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.aws_region)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13"> 区域终端节点 在 [ AWS > S3 ] 设置和获取 </el-col>
                </el-form-item>
                <el-form-item label="存储桶名称" prop="aws_bucket">
                  <el-col :span="11">
                    <el-input v-model="model.aws_bucket" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.aws_bucket)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13"> 存储桶名称 在 [ AWS > S3 ] 设置和获取 </el-col>
                </el-form-item>
                <el-form-item label="访问域名" prop="aws_domain">
                  <el-col :span="11">
                    <el-input v-model="model.aws_domain" clearable>
                      <template #append>
                        <el-button title="复制" @click="copy(model.aws_domain)">
                          <svg-icon icon-class="copy-document" />
                        </el-button>
                      </template>
                    </el-input>
                  </el-col>
                  <el-col class="line" :span="13"> 访问域名 在 [ AWS > S3 ] 设置和获取 </el-col>
                </el-form-item>
              </div>
            </el-scrollbar>
          </el-tab-pane>
          <el-tab-pane label="文件限制" lazy>
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="图片格式" prop="image_ext" class="!mb-[5px]">
                <el-col :span="11">
                  <el-input v-model="model.image_ext" clearable />
                </el-col>
                <el-col class="line" :span="13">
                  <el-tooltip :content="model.image_exts">
                    <svg-icon icon-class="question-filled" />
                  </el-tooltip>
                  允许上传的图片后缀，逗号,隔开
                </el-col>
              </el-form-item>
              <el-form-item label="图片大小" prop="image_size">
                <el-col :span="11">
                  <el-input v-model="model.image_size" type="number" clearable>
                    <template #append>MB</template>
                  </el-input>
                </el-col>
                <el-col class="line" :span="13"> 允许上传的图片大小，单位 MB </el-col>
              </el-form-item>

              <el-form-item label="视频格式" prop="video_ext" class="!mb-[5px]">
                <el-col :span="11">
                  <el-input v-model="model.video_ext" clearable />
                </el-col>
                <el-col class="line" :span="13">
                  <el-tooltip :content="model.video_exts">
                    <svg-icon icon-class="question-filled" />
                  </el-tooltip>
                  允许上传的视频后缀，逗号,隔开
                </el-col>
              </el-form-item>
              <el-form-item label="视频大小" prop="video_size">
                <el-col :span="11">
                  <el-input v-model="model.video_size" type="number" clearable>
                    <template #append>MB</template>
                  </el-input>
                </el-col>
                <el-col class="line" :span="13"> 允许上传的视频大小，单位 MB </el-col>
              </el-form-item>

              <el-form-item label="音频格式" prop="audio_ext" class="!mb-[5px]">
                <el-col :span="11">
                  <el-input v-model="model.audio_ext" clearable />
                </el-col>
                <el-col class="line" :span="13">
                  <el-tooltip :content="model.audio_exts">
                    <svg-icon icon-class="question-filled" />
                  </el-tooltip>
                  允许上传的音频后缀，逗号,隔开
                </el-col>
              </el-form-item>
              <el-form-item label="音频大小" prop="audio_size">
                <el-col :span="11">
                  <el-input v-model="model.audio_size" type="number" clearable>
                    <template #append>MB</template>
                  </el-input>
                </el-col>
                <el-col class="line" :span="13"> 允许上传的音频大小，单位 MB </el-col>
              </el-form-item>

              <el-form-item label="文档格式" prop="word_ext" class="!mb-[5px]">
                <el-col :span="11">
                  <el-input v-model="model.word_ext" clearable />
                </el-col>
                <el-col class="line" :span="13">
                  <el-tooltip :content="model.word_exts">
                    <svg-icon icon-class="question-filled" />
                  </el-tooltip>
                  允许上传的文档后缀，逗号,隔开
                </el-col>
              </el-form-item>
              <el-form-item label="文档大小" prop="word_size">
                <el-col :span="11">
                  <el-input v-model="model.word_size" type="number" clearable>
                    <template #append>MB</template>
                  </el-input>
                </el-col>
                <el-col class="line" :span="13"> 允许上传的文档大小，单位 MB </el-col>
              </el-form-item>

              <el-form-item label="其它格式" prop="other_ext" class="!mb-[5px]">
                <el-col :span="11">
                  <el-input v-model="model.other_ext" clearable />
                </el-col>
                <el-col class="line" :span="13">
                  <el-tooltip :content="model.other_exts">
                    <svg-icon icon-class="question-filled" />
                  </el-tooltip>
                  允许上传的其它文件后缀，逗号,隔开
                </el-col>
              </el-form-item>
              <el-form-item label="其它大小" prop="other_size">
                <el-col :span="11">
                  <el-input v-model="model.other_size" type="number" clearable>
                    <template #append>MB</template>
                  </el-input>
                </el-col>
                <el-col class="line" :span="13"> 允许上传的其它文件大小，单位 MB </el-col>
              </el-form-item>

              <el-form-item label="最大上传个数" prop="limit_max" class="!mb-[5px]">
                <el-col :span="11">
                  <el-input v-model="model.limit_max" type="number" clearable />
                </el-col>
                <el-col class="line" :span="13"> 允许上传最大文件个数（每次最多选择） </el-col>
              </el-form-item>
            </el-scrollbar>
          </el-tab-pane>
          <el-tab-pane label="前台设置" lazy>
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="前台文件" prop="is_api_file">
                <el-switch v-model="model.is_api_file" :active-value="1" :inactive-value="0" />
                <span> 是否开启前台文件功能</span>
              </el-form-item>
              <el-form-item label="文件类型" prop="api_file_types">
                <el-checkbox-group v-model="model.api_file_types">
                  <el-checkbox
                    v-for="(item, index) in file_types"
                    :key="index"
                    :value="index"
                    :label="item"
                  />
                </el-checkbox-group>
              </el-form-item>
              <el-form-item label="文件分组" prop="api_file_group_ids">
                <el-select
                  v-model="model.api_file_group_ids"
                  clearable
                  filterable
                  multiple
                  class="!w-[50%]"
                >
                  <el-option
                    v-for="(item, index) in group"
                    :key="index"
                    :value="item.group_id"
                    :label="item.group_name"
                  />
                </el-select>
              </el-form-item>
              <el-form-item label="文件标签" prop="api_file_tag_ids">
                <el-select
                  v-model="model.api_file_tag_ids"
                  clearable
                  filterable
                  multiple
                  class="!w-[50%]"
                >
                  <el-option
                    v-for="item in tag"
                    :key="item.tag_id"
                    :label="item.tag_name"
                    :value="item.tag_id"
                  />
                </el-select>
              </el-form-item>
            </el-scrollbar>
          </el-tab-pane>
        </el-tabs>
      </el-form>
      <el-form label-width="150px">
        <el-form-item>
          <el-button :loading="loading" @click="refresh()">刷新</el-button>
          <el-button :loading="loading" type="primary" @click="submit()">提交</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import checkPermission from '@/utils/permission'
import clip from '@/utils/clipboard'
import { info, edit } from '@/api/file/setting'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'FileSetting',
  computed: {
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'dashboard') return '来自控制台总览'
      if (source === 'system-setting') return '来自系统设置中心'
      if (source === 'content-setting') return '来自内容设置'
      if (source === 'file-file') return '来自文件管理'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自控制台总览') return '当前从控制台进入文件设置'
      if (this.entrySourceLabel === '来自系统设置中心') return '当前从系统设置中心进入文件设置'
      if (this.entrySourceLabel === '来自内容设置') return '当前从内容设置进入文件设置'
      if (this.entrySourceLabel === '来自文件管理') return '当前从文件管理进入文件设置'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '这类进入通常是为了排查上传失败、资源失效或前台文件库问题。建议先看上传开关和存储方式，再回文件库抽样验证。'
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        return '这类进入通常是为了继续补基础能力。建议先确认存储、上传和前台资源边界，再回系统设置看其它全局项是否还需要收口。'
      }
      if (this.entrySourceLabel === '来自内容设置') {
        return '这类进入通常是因为默认图或内容资源承接有问题。建议先核上传开关与资源范围，再回内容设置看默认图链路。'
      }
      if (this.entrySourceLabel === '来自文件管理') {
        return '这类进入通常是因为文件库里已经看到了症状。建议先修能力边界，再回文件管理核上传、分组和标签回显。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自内容设置') return '回内容设置'
      return '去文件管理复核'
    },
    currentStorageLabel() {
      if (Array.isArray(this.storages)) {
        const match = this.storages.find((item, index) => index === this.model.storage)
        return match || this.model.storage || '未配置'
      }
      return this.storages?.[this.model.storage] || this.model.storage || '未配置'
    },
    storageHint() {
      if (this.model.storage === 'local') {
        return '当前资源保存在本地服务器。'
      }
      return '当前已切换为云端对象存储模式。'
    },
    uploadSummary() {
      const parts = []
      parts.push(this.model.is_upload_api ? '前台可上传' : '前台已关闭')
      parts.push(this.model.is_upload_admin ? '后台可上传' : '后台已关闭')
      return parts.join(' / ')
    },
    apiFileSummary() {
      return `类型 ${this.model.api_file_types?.length || 0} 个，分组 ${this.model.api_file_group_ids?.length || 0} 个，标签 ${this.model.api_file_tag_ids?.length || 0} 个`
    },
    limitSummary() {
      return `单次最多 ${this.model.limit_max || 0} 个`
    },
    apiFileSwitchText() {
      return this.model.is_api_file ? '已开启' : '已关闭'
    },
    summaryHintTitle() {
      if (!this.model.is_upload_api && !this.model.is_upload_admin) {
        return '当前上传入口全部关闭'
      }
      return this.model.is_api_file ? '配置可直接提交' : '提交前确认前台文件能力'
    },
    summaryHintText() {
      if (!this.model.is_upload_api && !this.model.is_upload_admin) {
        return '前后台上传都已关闭，若准备恢复文件投放或运营上传，建议先检查上传开关和存储方式。'
      }
      if (this.model.is_api_file) {
        return `当前 ${this.currentStorageLabel} 已承载前台文件能力，${this.limitSummary}，提交后会同步影响上传入口与资源展示。`
      }
      return '前台文件能力当前关闭，若后续要开放资源库，建议一并确认文件类型、分组标签和大小限制。'
    },
    summaryHintClass() {
      return this.model.is_api_file ? 'setting-summary-bar__hint--ready' : 'setting-summary-bar__hint--review'
    },
    followupHint() {
      if (!this.model.is_upload_api && !this.model.is_upload_admin) {
        return '上传入口当前全部关闭，改完后建议先回文件管理核对历史资源是否仍可访问，再决定是否恢复前台能力。'
      }
      if (!this.model.is_api_file) {
        return '前台文件能力当前关闭，建议先去文件分组和标签整理资源结构，确认无误后再开放前台文件库。'
      }
      return `当前 ${this.currentStorageLabel} 已启用文件能力，建议先去文件管理抽查上传结果，再回分组和标签页补资源结构。`
    },
    followupTags() {
      return [
        `存储：${this.currentStorageLabel}`,
        `前台上传：${this.model.is_upload_api ? '开启' : '关闭'}`,
        `后台上传：${this.model.is_upload_admin ? '开启' : '关闭'}`,
        `前台资源：${this.model.is_api_file ? '开启' : '关闭'}`,
        `环境：${this.runtimeEnvInfo.label}`
      ]
    },
    fileSettingGuideFocusLabel() {
      if (!this.model.is_upload_api && !this.model.is_upload_admin) {
        return '当前重点：上传入口全关，先判断这是故意封停，还是配置误关'
      }
      if (!this.model.is_api_file) {
        return '当前重点：前台文件库关闭，先确认只是后台存资源，还是准备重新开放前台入口'
      }
      if (this.model.storage !== 'local') {
        return `当前重点：已切到 ${this.currentStorageLabel}，先核对密钥、桶和域名，再去文件库抽样验证`
      }
      return '当前重点：先区分“上传链路”与“前台资源范围”，不要把存储配置和分组标签问题混在一起改'
    },
    fileSettingGuideCards() {
      return [
        {
          step: '第一步',
          title: '先判断是上传问题，还是资源展示问题',
          desc: '上传失败优先看前后台上传开关和存储方式；前台看不到资源，优先看前台文件开关、文件类型、分组和标签范围。'
        },
        {
          step: '第二步',
          title: '云存储只改一套，不要跨平台混改',
          desc: '切换七牛、阿里云、腾讯云等配置时，一次只核一套密钥、桶和域名，避免多套参数混在一起导致排查变难。'
        },
        {
          step: '第三步',
          title: '提交后回文件库、分组和标签页复核',
          desc: '设置页只负责能力开关和边界，真正要看上传是否成功、资源是否能被找到，还得回文件管理、分组和标签页抽查。'
        }
      ]
    }
  },

  data() {
    return {
      name: '文件设置',
      height: 680,
      loading: false,
      storages: [],
      file_types: [],
      group: [],
      tag: [],
      model: {
        is_upload_admin: 1,
        is_upload_api: 1,
        storage: 'local',
        qiniu_access_key: '',
        qiniu_secret_key: '',
        qiniu_bucket: '',
        qiniu_domain: '',
        aliyun_access_key_id: '',
        aliyun_access_key_secret: '',
        aliyun_bucket: '',
        aliyun_bucket_domain: '',
        aliyun_endpoint: '',
        tencent_secret_id: '',
        tencent_secret_key: '',
        tencent_bucket: '',
        tencent_region: '',
        tencent_domain: '',
        baidu_access_key: '',
        baidu_secret_key: '',
        baidu_bucket: '',
        baidu_endpoint: '',
        baidu_domain: '',
        upyun_service_name: '',
        upyun_operator_name: '',
        upyun_operator_pwd: '',
        upyun_domain: '',
        aws_access_key_id: '',
        aws_secret_access_key: '',
        aws_bucket: '',
        aws_region: '',
        aws_domain: '',
        image_ext: '',
        image_exts: '',
        image_size: 0,
        video_ext: '',
        video_exts: '',
        video_size: 0,
        audio_ext: '',
        audio_exts: '',
        audio_size: 0,
        word_ext: '',
        word_exts: '',
        word_size: 0,
        other_ext: '',
        other_exts: '',
        other_size: 0,
        is_api_file: 0,
        api_file_types: [],
        api_file_group_ids: [],
        api_file_tag_ids: []
      },
      runtimeEnvInfo: resolveAdminRuntimeEnv(),
      rules: {
        storage: [{ required: true, message: '请选择存储方式', trigger: 'blur' }],
        qiniu_access_key: [{ required: true, message: '请输入 AccessKey', trigger: 'blur' }],
        qiniu_secret_key: [{ required: true, message: '请输入 SecretKey', trigger: 'blur' }],
        qiniu_bucket: [{ required: true, message: '请输入空间名称', trigger: 'blur' }],
        qiniu_domain: [{ required: true, message: '请输入外链域名', trigger: 'blur' }],
        aliyun_access_key_id: [{ required: true, message: '请输入 AccessKey ID', trigger: 'blur' }],
        aliyun_access_key_secret: [
          { required: true, message: '请输入 AccessKey Secret', trigger: 'blur' }
        ],
        aliyun_bucket: [{ required: true, message: '请输入 Bucket 名称', trigger: 'blur' }],
        aliyun_bucket_domain: [{ required: true, message: '请输入 Bucket 域名', trigger: 'blur' }],
        aliyun_endpoint: [{ required: true, message: '请输入 Endpoint 地域节点', trigger: 'blur' }],
        tencent_secret_id: [{ required: true, message: '请输入 SecretId', trigger: 'blur' }],
        tencent_secret_key: [{ required: true, message: '请输入 SecretKey', trigger: 'blur' }],
        tencent_bucket: [{ required: true, message: '请输入存储桶名称', trigger: 'blur' }],
        tencent_region: [{ required: true, message: '请输入所属地域', trigger: 'blur' }],
        tencent_domain: [{ required: true, message: '请输入访问域名', trigger: 'blur' }],
        baidu_access_key: [{ required: true, message: '请输入 Access Key', trigger: 'blur' }],
        baidu_secret_key: [{ required: true, message: '请输入 Secret Key', trigger: 'blur' }],
        baidu_bucket: [{ required: true, message: '请输入 Bucket 名称', trigger: 'blur' }],
        baidu_endpoint: [{ required: true, message: '请输入官方域名', trigger: 'blur' }],
        baidu_domain: [{ required: true, message: '请输入所属地域', trigger: 'blur' }],
        upyun_service_name: [{ required: true, message: '请输入服务名称', trigger: 'blur' }],
        upyun_operator_name: [{ required: true, message: '请输入操作员', trigger: 'blur' }],
        upyun_operator_pwd: [{ required: true, message: '请输入操作员密码', trigger: 'blur' }],
        upyun_domain: [{ required: true, message: '请输入加速域名', trigger: 'blur' }],
        aws_access_key_id: [{ required: true, message: '请输入 Access Key ID', trigger: 'blur' }],
        aws_secret_access_key: [
          { required: true, message: '请输入 Secret Access Key', trigger: 'blur' }
        ],
        aws_bucket: [{ required: true, message: '请输入存储桶名称', trigger: 'blur' }],
        aws_region: [{ required: true, message: '请输入区域终端节点', trigger: 'blur' }],
        aws_domain: [{ required: true, message: '请输入访问域名', trigger: 'blur' }]
      }
    }
  },
  created() {
    this.height = screenHeight(270)
    this.info()
  },
  methods: {
    checkPermission,
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自内容设置') {
        this.goToPage('/content/setting')
        return
      }
      this.goToPage('/file/file')
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.$router.push({ path: '/dashboard', query: { from: 'file-setting' } })
        return
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        this.$router.push({ path: '/system/setting', query: { from: 'file-setting' } })
        return
      }
      if (this.entrySourceLabel === '来自内容设置') {
        this.$router.push({ path: '/content/setting', query: { from: 'file-setting' } })
        return
      }
      if (this.entrySourceLabel === '来自文件管理') {
        this.$router.push({ path: '/file/file', query: { from: 'file-setting' } })
      }
    },
    // 信息
    info() {
      info().then((res) => {
        this.setData(res)
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      info()
        .then((res) => {
          this.setData(res)
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    setData(res) {
      this.model = { ...res.data }
      this.storages = res.data.storages
      this.file_types = res.data.file_types
      this.group = res.data.group
      this.tag = res.data.tag
      delete this.model.storages
      delete this.model.file_types
      delete this.model.group
      delete this.model.tag
      this.loading = false
    },
    // 提交
    submit() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          this.loading = true
          edit(this.model)
            .then((res) => {
              this.loading = false
              ElMessage.success(res.msg)
            })
            .catch(() => {
              this.loading = false
            })
        }
      })
    },
    // 存储方式
    storageChange() {
      if (this.$refs['ref'] !== undefined) {
        this.$refs['ref'].clearValidate()
      }
    },
    // 复制
    copy(text, event) {
      clip(text, event)
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'file-setting'
        }
      })
    }
  }
}
</script>

<style scoped>
.entry-context-banner {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin: 0 0 16px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #dbe7f5;
  background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
}

.entry-context-banner__main {
  flex: 1;
  min-width: 0;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
}

.entry-context-banner__title {
  margin-top: 4px;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.entry-context-banner__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.entry-context-banner__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin: 0 0 14px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #e6eef8;
  background: #fbfdff;
}

.followup-panel__main {
  flex: 1;
  min-width: 0;
}

.followup-panel__title {
  font-size: 14px;
  font-weight: 700;
  color: #1f2937;
}

.followup-panel__desc {
  margin-top: 6px;
  color: #64748b;
  line-height: 1.7;
}

.followup-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.followup-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e7edf5;
  color: #475569;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.setting-plain-guide {
  margin: 0 0 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.setting-plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.setting-plain-guide__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.setting-plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.setting-plain-guide__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.setting-plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.setting-plain-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.setting-plain-guide-card__step {
  display: inline-flex;
  align-items: center;
  min-height: 26px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
}

.setting-plain-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.setting-plain-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.setting-summary-bar {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin: 16px 0 8px;
  padding: 14px 16px;
  border-radius: 14px;
  background: linear-gradient(135deg, #f8fbff 0%, #f4f7fb 100%);
  border: 1px solid #e6eef8;
}

.setting-summary-bar__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #fff;
  border: 1px solid #e7edf5;
  color: #4b5563;
  font-size: 12px;
  line-height: 1;
}

.summary-chip--primary {
  color: #166534;
  background: #ecfdf3;
  border-color: #ccebd8;
}

.summary-chip--muted {
  color: #6b7280;
  background: #f8fafc;
}

.setting-summary-bar__hint {
  min-width: 250px;
  max-width: 320px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #dbeafe;
  background: rgba(255, 255, 255, 0.85);
}

.setting-summary-bar__hint--ready {
  border-color: #ccebd8;
  background: #f6fdf8;
}

.setting-summary-bar__hint--review {
  border-color: #fde68a;
  background: #fffdf3;
}

.setting-summary-bar__hint-title {
  font-size: 13px;
  font-weight: 600;
  color: #111827;
}

.setting-summary-bar__hint-text {
  font-size: 12px;
  line-height: 1.6;
  color: #6b7280;
}

@media (max-width: 1200px) {
  .entry-context-banner,
  .followup-panel,
  .setting-plain-guide__header,
  .setting-summary-bar {
    flex-direction: column;
  }

  .followup-panel__actions,
  .setting-summary-bar__hint {
    max-width: none;
  }

  .setting-plain-guide__badge {
    min-width: 0;
  }

  .setting-plain-guide__grid {
    grid-template-columns: 1fr;
  }
}
</style>
