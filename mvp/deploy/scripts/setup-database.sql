-- BizDir Platform Database Setup
-- Generated on Fri Aug 22 11:30:48 PM IST 2025

CREATE DATABASE IF NOT EXISTS biz_directory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'biz_user'@'localhost' IDENTIFIED BY 'AxmLdFLRqO+fIcaYrHd2tYT7ihukIOhubtK//cernuc=';
GRANT ALL PRIVILEGES ON biz_directory.* TO 'biz_user'@'localhost';
FLUSH PRIVILEGES;

USE biz_directory;

-- Core WordPress tables will be created by WordPress installer
-- BizDir custom tables will be created by plugin activation

