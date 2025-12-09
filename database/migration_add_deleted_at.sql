-- Migration to add deleted_at column to users table for soft delete functionality
-- Run this if you have an existing database

ALTER TABLE `users` ADD COLUMN `deleted_at` timestamp NULL DEFAULT NULL AFTER `block_reason`;
