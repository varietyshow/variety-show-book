-- First, create a backup of the package_price data
ALTER TABLE roles ADD COLUMN package_price_temp DECIMAL(10,2) NULL;

-- Update the temporary column, converting existing values
UPDATE roles SET package_price_temp = CASE 
    WHEN package_price = '' OR package_price IS NULL THEN 0.00
    ELSE CAST(package_price AS DECIMAL(10,2))
END;

-- Drop the old package_price column
ALTER TABLE roles DROP COLUMN package_price;

-- Rename the temporary column to package_price
ALTER TABLE roles CHANGE COLUMN package_price_temp package_price DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- Update existing records to set default values where needed
UPDATE roles SET package = 'Standard Package' WHERE package = '';
UPDATE roles SET package_price = 0.00 WHERE package_price IS NULL;
