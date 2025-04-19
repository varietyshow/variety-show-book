-- Add email field
ALTER TABLE customer_account ADD COLUMN IF NOT EXISTS email VARCHAR(255) NOT NULL AFTER last_name;

-- Add verified status
ALTER TABLE `customer_account` 
ADD COLUMN IF NOT EXISTS `verified` tinyint(1) NOT NULL DEFAULT 0 AFTER `reset_expiry`;

-- Drop address and contact fields that are no longer needed
ALTER TABLE customer_account 
    DROP COLUMN IF EXISTS street,
    DROP COLUMN IF EXISTS barangay,
    DROP COLUMN IF EXISTS municipality,
    DROP COLUMN IF EXISTS province,
    DROP COLUMN IF EXISTS contact_number;

-- Add unique constraint on email
ALTER TABLE customer_account ADD UNIQUE INDEX idx_email (email);
