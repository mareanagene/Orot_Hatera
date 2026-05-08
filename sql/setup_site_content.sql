-- Ensures Hebrew text is stored and returned correctly
CREATE DATABASE IF NOT EXISTS defaultdb
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE defaultdb;

CREATE TABLE IF NOT EXISTS site_content (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  page_name VARCHAR(100) NOT NULL,
  section_id VARCHAR(100) NOT NULL,
  headline VARCHAR(255) NOT NULL,
  body_text TEXT NULL,
  image_url VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_page_section (page_name, section_id),
  KEY idx_page_name (page_name),
  KEY idx_section_id (section_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
