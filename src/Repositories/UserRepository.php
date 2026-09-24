<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, nombre, email, rol, area, fecha_registro
             FROM usuarios WHERE id = ?'
        );
        $statement->execute([$id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM usuarios WHERE email = ?');
        $statement->execute([$email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function create(
        string $name,
        string $email,
        string $password,
        string $role,
        string $area,
        int $mustChangePassword
    ): int {
        $statement = $this->connection->prepare(
            'INSERT INTO usuarios
             (nombre, email, password, rol, area, debe_cambiar_password)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$name, $email, $password, $role, $area, $mustChangePassword]);

        return (int)$this->connection->lastInsertId();
    }

    public function findAdministrators(): array
    {
        return $this->connection->query(
            'SELECT id, nombre, email, rol FROM usuarios
             WHERE rol IN ("admin", "superadmin") AND activo = 1'
        )->fetchAll();
    }

    public function findAdministratorEmails(?string $role = null): array
    {
        if ($role !== null) {
            $statement = $this->connection->prepare(
                'SELECT email FROM usuarios
                 WHERE rol = ? AND activo = 1 AND email IS NOT NULL'
            );
            $statement->execute([$role]);
            return $statement->fetchAll();
        }

        return $this->connection->query(
            'SELECT email FROM usuarios
             WHERE rol IN ("admin", "superadmin") AND activo = 1 AND email IS NOT NULL'
        )->fetchAll();
    }

    public function findAll(int $limit, int $offset): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, nombre, email, rol, area, fecha_registro, activo
             FROM usuarios ORDER BY fecha_registro DESC LIMIT ? OFFSET ?'
        );
        $statement->execute([$limit, $offset]);

        return $statement->fetchAll();
    }

    public function countAll(): int
    {
        return (int)$this->connection->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }

    public function emailBelongsToOther(string $email, int $id): bool
    {
        $statement = $this->connection->prepare(
            'SELECT id FROM usuarios WHERE email = ? AND id != ?'
        );
        $statement->execute([$email, $id]);

        return (bool)$statement->fetchColumn();
    }

    public function update(
        int $id,
        string $name,
        string $email,
        string $role,
        string $area,
        ?string $password = null
    ): bool {
        if ($password !== null && $password !== '') {
            $statement = $this->connection->prepare(
                'UPDATE usuarios SET nombre = ?, email = ?, rol = ?, area = ?, password = ?
                 WHERE id = ?'
            );
            return $statement->execute([$name, $email, $role, $area, $password, $id]);
        }

        $statement = $this->connection->prepare(
            'UPDATE usuarios SET nombre = ?, email = ?, rol = ?, area = ? WHERE id = ?'
        );

        return $statement->execute([$name, $email, $role, $area, $id]);
    }

    public function countSuperAdmins(): int
    {
        return (int)$this->connection
            ->query('SELECT COUNT(*) FROM usuarios WHERE rol = "superadmin"')
            ->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM usuarios WHERE id = ?');
        return $statement->execute([$id]);
    }

    public function deleteNotifications(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM notificaciones WHERE usuario_id = ?');
        return $statement->execute([$id]);
    }

    public function updatePassword(int $id, string $password): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE usuarios SET password = :password, debe_cambiar_password = 0 WHERE id = :id'
        );

        return $statement->execute([':password' => $password, ':id' => $id]);
    }
}
