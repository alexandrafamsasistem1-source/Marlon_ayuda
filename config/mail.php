<?php
/**
 * Configuración Global del Sistema de Correos
 */

if (!class_exists('TransportFactory')) {
    class TransportFactory {
        private static array $configs = [];

        public static function setConfig(string $name, array $config): void {
            self::$configs[$name] = $config;
        }

        public static function getConfig(string $name): ?array {
            return self::$configs[$name] ?? null;
        }
    }
}

// Tu código de configuración exacta de Mailtrap
TransportFactory::setConfig('mailtrap', [  
    'host' => 'sandbox.smtp.mailtrap.io',  
    'port' => 2525,  
    'username' => '94bb0d6955cbea',  
    'password' => '510ca7b1801fbf', // ⚠️ Sustituye los asteriscos por tu token real
    'className' => 'Smtp'
]);