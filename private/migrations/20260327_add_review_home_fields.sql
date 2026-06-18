ALTER TABLE `ya_system_setting`
ADD COLUMN `review_hero_title` varchar(255) NULL DEFAULT NULL,
ADD COLUMN `review_hero_desc` text NULL,
ADD COLUMN `review_intro_title` varchar(255) NULL DEFAULT NULL,
ADD COLUMN `review_intro_desc` text NULL,
ADD COLUMN `review_primary_btn_text` varchar(100) NULL DEFAULT NULL,
ADD COLUMN `review_secondary_btn_text` varchar(100) NULL DEFAULT NULL,
ADD COLUMN `review_intro_image_id` int(11) NULL DEFAULT NULL;

UPDATE `ya_system_setting`
SET
  `review_hero_title` = COALESCE(NULLIF(`review_hero_title`, ''), '源头直供，安心可溯，服务在线'),
  `review_hero_desc` = COALESCE(NULLIF(`review_hero_desc`, ''), '平台聚焦农产品展示、商家入驻、客户服务与品牌传播，提供清晰的商品信息、稳定的服务入口和持续更新的品牌内容。'),
  `review_intro_title` = COALESCE(NULLIF(`review_intro_title`, ''), '品牌介绍'),
  `review_intro_desc` = COALESCE(NULLIF(`review_intro_desc`, ''), CONCAT(`system_name`, ' 以品牌展示、服务联络、商品信息呈现为核心，围绕农产品供应、商家合作与客户服务建立统一的线上展示窗口。')),
  `review_primary_btn_text` = COALESCE(NULLIF(`review_primary_btn_text`, ''), '联系客服'),
  `review_secondary_btn_text` = COALESCE(NULLIF(`review_secondary_btn_text`, ''), '复制官网')
WHERE `setting_id` = 1;
