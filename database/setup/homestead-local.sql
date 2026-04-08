-- Run as MySQL admin (example):
--   /usr/local/mysql/bin/mysql -u root -p < database/setup/homestead-local.sql
--
-- Then import data (example):
--   /usr/local/mysql/bin/mysql -u homestead -psecret homestead < db_rentasuit_php.sql
--
-- Or: php artisan db:bootstrap-local --import
--
-- Drops and recreates `homestead` so password and grants are always correct (fixes stale users).

CREATE DATABASE IF NOT EXISTS homestead CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

DROP USER IF EXISTS 'homestead'@'localhost';
DROP USER IF EXISTS 'homestead'@'127.0.0.1';
DROP USER IF EXISTS 'homestead'@'%';

CREATE USER 'homestead'@'localhost' IDENTIFIED BY 'secret';
CREATE USER 'homestead'@'127.0.0.1' IDENTIFIED BY 'secret';
CREATE USER 'homestead'@'%' IDENTIFIED BY 'secret';

GRANT ALL PRIVILEGES ON homestead.* TO 'homestead'@'localhost';
GRANT ALL PRIVILEGES ON homestead.* TO 'homestead'@'127.0.0.1';
GRANT ALL PRIVILEGES ON homestead.* TO 'homestead'@'%';

FLUSH PRIVILEGES;
