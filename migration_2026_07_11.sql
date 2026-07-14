-- ============================================================
--  STEADYVOLT — Migration: manual rating override + staff reviews
--            + admin profile pictures
--  Run this once against an EXISTING database.
--  (Fresh installs: database.sql already includes these columns
--   and creates the database as "steadyvolt" directly.)
--
--  NOTE ON THE DATABASE NAME:
--  MySQL/MariaDB have no in-place "RENAME DATABASE" command, so if
--  your existing installation's database is still literally called
--  "voltpeak", either:
--    a) keep it named "voltpeak" and just change the line below to
--       `USE voltpeak;` before running this file, and update
--       includes/config.php DB_NAME back to 'voltpeak' — the app
--       works fine either way, the DB name is just an internal
--       label and isn't shown to customers, OR
--    b) actually rename it — create a new "steadyvolt" database,
--       then: `mysqldump -u root -p voltpeak | mysql -u root -p steadyvolt`
--       then drop the old one, and keep DB_NAME as 'steadyvolt' in
--       includes/config.php (already set that way in this codebase).
-- ============================================================
USE steadyvolt;

ALTER TABLE products
  ADD COLUMN rating_override       DECIMAL(2,1) DEFAULT NULL COMMENT 'Admin-set star rating shown instead of the review average. NULL = use real reviews.' AFTER meta_desc,
  ADD COLUMN rating_override_count INT UNSIGNED DEFAULT NULL COMMENT 'Optional display count shown next to the override rating.' AFTER rating_override;

ALTER TABLE reviews
  ADD COLUMN is_staff TINYINT(1) DEFAULT 0 COMMENT '1 = written manually by an admin (shown with a "SteadyVolt Team" badge), not a real customer submission' AFTER is_approved;

-- Note: admin profile pictures reuse the existing users.avatar column —
-- no schema change needed there, just the new admin/profile.php page.
