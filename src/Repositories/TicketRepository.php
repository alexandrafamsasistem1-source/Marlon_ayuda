<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TicketRepository
{
    private PDO $connection;

    private string $databaseName;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        try {
            $this->databaseName = (string)$connection->query('SELECT DATABASE()')->fetchColumn();
        } catch (\Throwable $exception) {
            $this->databaseName = defined('DB_NAME') ? (string)DB_NAME : '';
        }
    }

    public function findById(int $ticketId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT t.*, u.nombre AS usuario_nombre, u.email AS usuario_email,
                    u.area AS usuario_area,
                    a.nombre AS asignado_nombre
             FROM tickets t
             LEFT JOIN usuarios u ON t.usuario_id = u.id
             LEFT JOIN usuarios a ON t.asignado_a = a.id
             WHERE t.id = ?'
        );
        $statement->execute([$ticketId]);
        $ticket = $statement->fetch();

        return $ticket ?: null;
    }

    public function findAll(int $limit = 50, int $offset = 0, ?string $status = null): array
    {
        $urgency = $this->hasColumn('urgencia') ? ', t.urgencia' : '';
        $where = '';
        $parameters = [];

        if ($status !== null && $status !== '') {
            $where = 'WHERE t.estado = ?';
            $parameters[] = $status;
        }

        $statement = $this->connection->prepare(
            'SELECT t.id, t.asunto, t.asignado_a, t.estado, t.ubicacion,
                    t.fecha_creacion' . $urgency . ',
                    u.nombre AS usuario_nombre, u.email AS usuario_email,
                    a.nombre AS asignado_nombre
             FROM tickets t
             LEFT JOIN usuarios u ON t.usuario_id = u.id
             LEFT JOIN usuarios a ON t.asignado_a = a.id
             ' . $where . '
             ORDER BY t.fecha_creacion DESC
             LIMIT ? OFFSET ?'
        );
        $parameters[] = $limit;
        $parameters[] = $offset;
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findByUser(
        int $userId,
        int $limit = 50,
        int $offset = 0,
        ?string $status = null,
        ?string $from = null,
        ?string $until = null
    ): array {
        $area = $this->hasColumn('area')
            ? ', COALESCE(NULLIF(TRIM(u.area), ""), NULLIF(TRIM(t.area), "")) AS area'
            : ', u.area AS area';
        $where = ['t.usuario_id = ?'];
        $parameters = [$userId];

        if ($status !== null && $status !== '') {
            $where[] = 't.estado = ?';
            $parameters[] = $status;
        }
        if ($from !== null && $from !== '') {
            $where[] = 't.fecha_creacion >= ?';
            $parameters[] = $from . ' 00:00:00';
        }
        if ($until !== null && $until !== '') {
            $where[] = 't.fecha_creacion < DATE_ADD(?, INTERVAL 1 DAY)';
            $parameters[] = $until . ' 00:00:00';
        }

        $statement = $this->connection->prepare(
            'SELECT t.id, t.asunto, t.estado, t.ubicacion, t.fecha_creacion' . $area . '
             FROM tickets t
             INNER JOIN usuarios u ON u.id = t.usuario_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY t.fecha_creacion DESC
             LIMIT ? OFFSET ?'
        );
        $parameters[] = $limit;
        $parameters[] = $offset;
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function create(
        int $userId,
        string $subject,
        string $description,
        string $location,
        ?string $area = null
    ): int {
        $columns = ['usuario_id', 'asunto', 'descripcion', 'ubicacion'];
        $values = [$userId, $subject, $description, $location];

        if ($this->hasColumn('area')) {
            $columns[] = 'area';
            $values[] = $area;
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $statement = $this->connection->prepare(
            'INSERT INTO tickets (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')'
        );
        $statement->execute($values);

        return (int)$this->connection->lastInsertId();
    }

    public function updateStatus(int $ticketId, string $status): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE tickets
             SET estado = ?, fecha_ultima_actualizacion = NOW()
             WHERE id = ?'
        );

        return $statement->execute([$status, $ticketId]);
    }

    public function assign(int $ticketId, ?int $assignedUserId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE tickets
             SET asignado_a = ?, fecha_ultima_actualizacion = NOW()
             WHERE id = ?'
        );

        return $statement->execute([$assignedUserId, $ticketId]);
    }

    public function updateWorkflow(
        int $ticketId,
        string $status,
        ?int $assignedUserId,
        ?string $problemType
    ): bool {
        $columns = ['estado = ?', 'asignado_a = ?'];
        $values = [$status, $assignedUserId];

        if ($this->hasColumn('tipo_problema')) {
            $columns[] = 'tipo_problema = ?';
            $values[] = $problemType;
        }

        $columns[] = 'fecha_ultima_actualizacion = NOW()';
        $values[] = $ticketId;

        $statement = $this->connection->prepare(
            'UPDATE tickets SET ' . implode(', ', $columns) . ' WHERE id = ?'
        );

        return $statement->execute($values);
    }

    public function getActiveUserRole(int $userId): ?string
    {
        $statement = $this->connection->prepare(
            'SELECT rol FROM usuarios WHERE id = ? AND activo = 1 LIMIT 1'
        );
        $statement->execute([$userId]);
        $role = $statement->fetchColumn();

        return $role === false ? null : (string)$role;
    }

    public function findUser(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, nombre, email, rol, area
             FROM usuarios WHERE id = ? LIMIT 1'
        );
        $statement->execute([$userId]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findAdministratorIds(): array
    {
        $statement = $this->connection->query(
            'SELECT id FROM usuarios
             WHERE rol IN ("admin", "superadmin") AND activo = 1'
        );

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function addResponse(int $ticketId, int $userId, string $message): bool
    {
        $statement = $this->connection->prepare(
            'INSERT INTO respuestas_ticket (ticket_id, usuario_id, mensaje)
             VALUES (?, ?, ?)'
        );
        if (!$statement->execute([$ticketId, $userId, $message])) {
            return false;
        }

        return $this->touch($ticketId);
    }

    public function findResponses(int $ticketId): array
    {
        $statement = $this->connection->prepare(
            'SELECT r.id, r.mensaje, r.fecha_creacion,
                    u.nombre AS usuario_nombre, u.rol
             FROM respuestas_ticket r
             LEFT JOIN usuarios u ON r.usuario_id = u.id
             WHERE r.ticket_id = ?
             ORDER BY r.fecha_creacion ASC'
        );
        $statement->execute([$ticketId]);

        return $statement->fetchAll();
    }

    public function getResponses(int $ticketId): array
    {
        return $this->findResponses($ticketId);
    }

    public function findResponseTicketId(int $responseId): ?int
    {
        $statement = $this->connection->prepare(
            'SELECT ticket_id FROM respuestas_ticket WHERE id = ? LIMIT 1'
        );
        $statement->execute([$responseId]);
        $ticketId = $statement->fetchColumn();

        return $ticketId === false ? null : (int)$ticketId;
    }

    public function deleteResponse(int $responseId, int $ticketId): bool
    {
        $statement = $this->connection->prepare(
            'DELETE FROM respuestas_ticket WHERE id = ?'
        );
        if (!$statement->execute([$responseId])) {
            return false;
        }

        return $this->touch($ticketId);
    }

    public function touch(int $ticketId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE tickets SET fecha_ultima_actualizacion = NOW() WHERE id = ?'
        );

        return $statement->execute([$ticketId]);
    }

    public function countAll(): int
    {
        return (int)$this->connection->query('SELECT COUNT(*) FROM tickets')->fetchColumn();
    }

    public function countTotal(): int
    {
        return $this->countAll();
    }

    public function countByStatus(): array
    {
        $statement = $this->connection->query(
            'SELECT estado, COUNT(*) AS cantidad FROM tickets GROUP BY estado'
        );

        return $statement->fetchAll();
    }

    public function countByLocation(): array
    {
        $statement = $this->connection->query(
            'SELECT ubicacion, COUNT(*) AS cantidad FROM tickets GROUP BY ubicacion'
        );

        return $statement->fetchAll();
    }

    public function getResolvedByMonth(
        int $year,
        int $month,
        ?int $assignedUserId = null
    ): array {
        $extraColumns = '';
        if ($this->hasColumn('area')) {
            $extraColumns .= ', t.area';
        }
        if ($this->hasColumn('tipo_problema')) {
            $extraColumns .= ', t.tipo_problema';
        }

        $assignedFilter = '';
        $parameters = [$year, $month];
        if ($assignedUserId !== null) {
            $assignedFilter = ' AND t.asignado_a = ?';
            $parameters[] = $assignedUserId;
        }

        $statement = $this->connection->prepare(
            'SELECT t.id, t.asunto, t.estado, t.ubicacion, t.fecha_creacion,
                    t.fecha_ultima_actualizacion AS fecha_resolucion' . $extraColumns . ',
                    u.nombre AS usuario_nombre, u.area AS usuario_area,
                    a.nombre AS asignado_nombre
             FROM tickets t
             LEFT JOIN usuarios u ON t.usuario_id = u.id
             LEFT JOIN usuarios a ON t.asignado_a = a.id
             WHERE t.estado IN ("Resuelto", "Cerrado")
               AND YEAR(t.fecha_ultima_actualizacion) = ?
               AND MONTH(t.fecha_ultima_actualizacion) = ?' . $assignedFilter . '
             ORDER BY t.fecha_ultima_actualizacion DESC'
        );
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function isOwnedByUser(int $ticketId, int $userId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM tickets WHERE id = ? AND usuario_id = ? LIMIT 1'
        );
        $statement->execute([$ticketId, $userId]);

        return (bool)$statement->fetchColumn();
    }

    public function delete(int $ticketId): bool
    {
        $this->connection->beginTransaction();

        try {
            $responses = $this->connection->prepare(
                'DELETE FROM respuestas_ticket WHERE ticket_id = ?'
            );
            $responses->execute([$ticketId]);

            $ticket = $this->connection->prepare('DELETE FROM tickets WHERE id = ?');
            $deleted = $ticket->execute([$ticketId]);
            $this->connection->commit();

            return $deleted;
        } catch (\Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    public function unassignUserTickets(int $userId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE tickets SET usuario_id = NULL WHERE usuario_id = ?'
        );

        return $statement->execute([$userId]);
    }

    public function deleteUserResponses(int $userId): bool
    {
        $statement = $this->connection->prepare(
            'DELETE FROM respuestas_ticket WHERE usuario_id = ?'
        );

        return $statement->execute([$userId]);
    }

    private function hasColumn(string $column): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
             LIMIT 1'
        );
        $statement->execute([$this->databaseName, 'tickets', $column]);

        return (bool)$statement->fetchColumn();
    }
}
