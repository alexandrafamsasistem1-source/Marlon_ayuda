-- Migración 004: Agregar clasificación de tipo de problema (Software/Hardware)
ALTER TABLE tickets 
ADD COLUMN tipo_problema ENUM('Software', 'Hardware', 'Ninguno') DEFAULT 'Ninguno' AFTER area;