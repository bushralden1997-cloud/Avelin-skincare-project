-- =====================================================
-- AVELIN — Orders table update
-- Run this ONCE after importing create_database.sql
-- Safe: uses IF NOT EXISTS / ALTER IF NOT EXISTS
-- =====================================================
USE ciltbakim_db;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS name         VARCHAR(100)    DEFAULT '' AFTER user_id,
  ADD COLUMN IF NOT EXISTS email        VARCHAR(100)    DEFAULT '' AFTER name,
  ADD COLUMN IF NOT EXISTS phone        VARCHAR(20)     DEFAULT '' AFTER email,
  ADD COLUMN IF NOT EXISTS city         VARCHAR(100)    DEFAULT '' AFTER shipping_address,
  ADD COLUMN IF NOT EXISTS postal_code  VARCHAR(20)     DEFAULT '' AFTER city,
  ADD COLUMN IF NOT EXISTS shipping_cost DECIMAL(10,2)  DEFAULT 0  AFTER postal_code,
  ADD COLUMN IF NOT EXISTS discount     DECIMAL(10,2)   DEFAULT 0  AFTER shipping_cost,
  ADD COLUMN IF NOT EXISTS final_amount DECIMAL(10,2)   DEFAULT 0  AFTER discount;

-- Make order_status a plain VARCHAR (supports Turkish strings)
ALTER TABLE orders MODIFY COLUMN order_status VARCHAR(60) DEFAULT 'Beklemede';

COMMIT;
