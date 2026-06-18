ALTER TABLE `ya_merchant`
ADD COLUMN `expire_time` datetime NULL DEFAULT NULL COMMENT '商家到期时间',
ADD COLUMN `renew_remind_days` int(11) NOT NULL DEFAULT 7 COMMENT '提前续费提醒天数';

UPDATE `ya_merchant`
SET `renew_remind_days` = 7
WHERE `renew_remind_days` IS NULL OR `renew_remind_days` <= 0;
