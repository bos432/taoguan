ALTER TABLE `ya_system_setting`
ADD COLUMN `platform_voucher_image_id` int(11) DEFAULT NULL COMMENT '平台收款码' AFTER `service_wechat_image_id`;
