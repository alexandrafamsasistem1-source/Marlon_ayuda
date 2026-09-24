<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class HistoryRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function record(int $ticketId, int $userId, string $action, string $description): bool
    {
        $statement = $this->connection->prepare(
            'INSERT INTO historial_tickets (ticket_id, usuario_id, tipo_accion, descripcion)
             VALUES (?, ?, ?, ?)'
        );

        return $statement->execute([$ticketId, $userId, $action, $description]);
    }

    public function findByTicket(int $ticketId): array
    {
        $statement = $this->connection->prepare(
            'SELECT h.id, h.tipo_accion, h.descripcion, h.fecha_creacion,
                    u.nombre AS autor_nombre, u.rol AS autor_rol
             FROM historial_tickets h
             INNER JOIN usuarios u ON h.usuario_id = u.id
             WHERE h.ticket_id = ?
             ORDER BY h.fecha_creacion DESC, h.id DESC'
        );
        $statement->execute([$ticketId]);

        return $statement->fetchAll();
    }
}
