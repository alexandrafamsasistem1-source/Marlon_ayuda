<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final class NotificationRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function ensureTable(): bool
    {
        try {
            if ($this->connection->query("SHOW TABLES LIKE 'notificaciones'")->fetch()) {
                return true;
            }

            $this->connection->exec(
                'CREATE TABLE IF NOT EXISTS notificaciones (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    usuario_id INT NOT NULL,
                    tipo VARCHAR(50) NOT NULL DEFAULT "ticket_nuevo",
                    mensaje TEXT NOT NULL,
                    referencia_id INT NULL,
                    leida TINYINT(1) NOT NULL DEFAULT 0,
                    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    fecha_lectura TIMESTAMP NULL,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    INDEX idx_usuario_leida (usuario_id, leida),
                    INDEX idx_fecha_creacion (fecha_creacion)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );

            return true;
        } catch (Throwable $exception) {
            error_log('Error creando tabla de notificaciones: ' . $exception->getMessage());
            return false;
        }
    }

    public function createForUsers(array $userIds, string $message, int $ticketId): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO notificaciones (usuario_id, tipo, mensaje, referencia_id)
             VALUES (?, ?, ?, ?)'
        );
        $created = 0;

        foreach ($userIds as $userId) {
            if ($statement->execute([(int)$userId, 'ticket_nuevo', $message, $ticketId])) {
                $created++;
            }
        }

        return $created;
    }

    public function createNotification(
        int $userId,
        string $type,
        string $message,
        ?int $referenceId = null
    ): bool {
        $statement = $this->connection->prepare(
            'INSERT INTO notificaciones (usuario_id, tipo, mensaje, referencia_id)
             VALUES (?, ?, ?, ?)'
        );

        return $statement->execute([$userId, $type, $message, $referenceId]);
    }

    public function countUnread(int $userId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0'
        );
        $statement->execute([$userId]);

        return (int)$statement->fetchColumn();
    }

    public function findForUser(int $userId, int $limit, bool $unreadOnly): array
    {
        $condition = $unreadOnly ? ' AND leida = 0' : '';
        $statement = $this->connection->prepare(
            'SELECT * FROM notificaciones
             WHERE usuario_id = ?' . $condition . '
             ORDER BY fecha_creacion DESC LIMIT ?'
        );
        $statement->execute([$userId, $limit]);

        return $statement->fetchAll();
    }

    public function markAsRead(int $userId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE notificaciones
             SET leida = 1, fecha_lectura = NOW()
             WHERE usuario_id = ? AND leida = 0'
        );

        return $statement->execute([$userId]);
    }
}
