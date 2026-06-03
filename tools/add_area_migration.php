<?php
// Script para agregar la columna 'area' a la tabla tickets si no existe
// Ejecutar desde la raíz del proyecto: php tools/add_area_migration.php

try {
    $pdo = require __DIR__ . '/../config/database.php';

    $dbName = defined('DB_NAME') ? DB_NAME : null;
    if (!$dbName) throw new Exception('DB_NAME no definida en config/database.php');

    $check = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'area'");
    $check->execute([$dbName]);
    $exists = (bool)$check->fetchColumn();

    if ($exists) {
        echo "La columna 'area' ya existe en la tabla tickets.\n";
        exit(0);
    }

    // Agregar columna 'area'
    $sql = "ALTER TABLE tickets ADD COLUMN area ENUM('Administracion','Poscosecha') NOT NULL DEFAULT 'Administracion'";
    $pdo->exec($sql);
    echo "Columna 'area' creada correctamente.\n";
    exit(0);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
