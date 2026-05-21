<?php
// Ejecutar esto desde el navegador para resetear OPcache (solo en desarrollo)
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reset ejecutado.";
} else {
    echo "OPcache no está disponible en este entorno.";
}
