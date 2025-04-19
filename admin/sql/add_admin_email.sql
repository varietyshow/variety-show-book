ALTER TABLE admin_account ADD COLUMN email VARCHAR(255) NOT NULL DEFAULT 'admin@example.com';
UPDATE admin_account SET email = 'admin@example.com' WHERE admin_id = 1;
