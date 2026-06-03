-- Migration: Add 'area' column to tickets table if missing
-- Run this in MySQL (phpMyAdmin) or leave to the PHP migration script at tools/add_area_migration.php

ALTER TABLE tickets
  ADD COLUMN IF NOT EXISTS area ENUM('Administracion','Poscosecha') NOT NULL DEFAULT 'Administracion';
