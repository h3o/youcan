-- Migration 002: Add language and visibility to stories
-- 2026-06-15

ALTER TABLE `stories`
  ADD COLUMN `language`     VARCHAR(10)                                       NULL DEFAULT NULL  AFTER `genres`,
  ADD COLUMN `visibility`   ENUM('public','members','secret') NOT NULL DEFAULT 'public'          AFTER `language`,
  ADD COLUMN `secret_token` CHAR(64)                                          NULL DEFAULT NULL  AFTER `visibility`;
