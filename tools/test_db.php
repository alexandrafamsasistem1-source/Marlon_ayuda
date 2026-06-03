<?php
require __DIR__ . '/../includes/functions.php';
try {
    $db = getDB();
    echo get_class($db) . "\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
