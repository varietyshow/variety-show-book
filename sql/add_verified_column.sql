-- Add verified column if it doesn't exist
ALTER TABLE `customer_account`
ADD COLUMN IF NOT EXISTS `verified` tinyint(1) NOT NULL DEFAULT 0 AFTER `reset_expiry`;
