-- Merchant backend legacy super-admin cleanup
-- Target scope:
--   1) clear old merchant backend super flags for mer_user_id 22 / 23
--   2) detach their role bindings to remove stale backend super-role residue
-- Notes:
--   - keep the accounts themselves for now; do not delete rows
--   - this script is idempotent and safe to run multiple times

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

-- 1) Clear the old super-admin flags on the duplicate merchant backend users
UPDATE `ya_merchant_user`
SET
  `is_super` = 0,
  `update_time` = NOW()
WHERE `mer_user_id` IN (22, 23);

-- 2) Remove stale user-role bindings for these duplicate legacy accounts
DELETE FROM `ya_merchant_user_attributes`
WHERE `mer_user_id` IN (22, 23);

-- 3) Soft-disable now-unbound legacy super roles
UPDATE `ya_merchant_role` AS `r`
LEFT JOIN (
  SELECT DISTINCT `role_id`
  FROM `ya_merchant_user_attributes`
  WHERE `role_id` IN (22, 23)
) AS `bound` ON `bound`.`role_id` = `r`.`role_id`
SET
  `r`.`is_disable` = 1,
  `r`.`is_delete` = 1,
  `r`.`update_time` = NOW()
WHERE `r`.`role_id` IN (22, 23)
  AND `bound`.`role_id` IS NULL;

COMMIT;

-- 4) Verification snapshot
SELECT
  `mer_user_id`,
  `mer_id`,
  `username`,
  `nickname`,
  `phone`,
  `is_super`,
  `is_disable`,
  `is_delete`,
  `update_time`
FROM `ya_merchant_user`
WHERE `mer_user_id` IN (22, 23)
ORDER BY `mer_user_id`;

SELECT
  `mer_user_id`,
  `role_id`
FROM `ya_merchant_user_attributes`
WHERE `mer_user_id` IN (22, 23)
ORDER BY `mer_user_id`, `role_id`;

SELECT
  `role_id`,
  `mer_id`,
  `role_name`,
  `is_admin`,
  `is_disable`,
  `is_delete`,
  `update_time`
FROM `ya_merchant_role`
WHERE `role_id` IN (22, 23)
ORDER BY `role_id`;

SET FOREIGN_KEY_CHECKS = 1;
