-- Migration: Add 'area' column to tickets table if missing
-- Run this migration in MySQL (phpMyAdmin).

ALTER TABLE tickets
  ADD COLUMN IF NOT EXISTS area ENUM('Administracion','Poscosecha') NOT NULL DEFAULT 'Administracion';
