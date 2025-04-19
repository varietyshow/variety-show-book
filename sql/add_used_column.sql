ALTER TABLE `verification_tokens` 
ADD COLUMN `used` TINYINT(1) NOT NULL DEFAULT 0;
