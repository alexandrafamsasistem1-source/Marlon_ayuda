<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MailQueueRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function enqueue(string $recipient, string $subject, string $body): bool
    {
        $statement = $this->connection->prepare(
            "INSERT INTO cola_correos (destinatario, asunto, cuerpo, estado)
             VALUES (:destinatario, :asunto, :cuerpo, 'pendiente')"
        );

        return $statement->execute([
            ':destinatario' => filter_var($recipient, FILTER_SANITIZE_EMAIL),
            ':asunto' => trim($subject),
            ':cuerpo' => $body,
        ]);
    }
}
