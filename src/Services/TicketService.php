<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TicketRepository;
use InvalidArgumentException;

final class TicketService
{
    private const ALLOWED_STATUSES = ['Nuevo', 'En proceso', 'Resuelto', 'Cerrado'];
    private const ALLOWED_PROBLEM_TYPES = ['Software', 'Hardware'];

    private TicketRepository $tickets;
    private NotificationService $notifications;

    public function __construct(
        TicketRepository $tickets,
        NotificationService $notifications
    )
    {
        $this->tickets = $tickets;
        $this->notifications = $notifications;
    }

    public function createTicket(
        int $userId,
        string $subject,
        string $description,
        string $location,
        ?string $area = null
    ): int {
        if ($userId <= 0 || trim($subject) === '' || trim($description) === '' || trim($location) === '') {
            throw new InvalidArgumentException('Los datos del ticket son inválidos.');
        }

        $user = $this->tickets->findUser($userId);
        if ($user === null) {
            throw new InvalidArgumentException('El usuario del ticket no existe.');
        }

        $ticketArea = $area !== null && trim($area) !== ''
            ? trim($area)
            : ($user['area'] ?? null);
        $ticketId = $this->tickets->create($userId, $subject, $description, $location, $ticketArea);

        if ($ticketId > 0 && $this->notifications->ensureTable()) {
            $this->notifications->createTicketNotification(
                $ticketId,
                $this->tickets->findAdministratorIds(),
                (string)($user['nombre'] ?? 'Usuario'),
                $subject,
                $location,
                $ticketArea
            );
        }

        return $ticketId;
    }

    public function changeStatus(int $ticketId, string $status): bool
    {
        $this->assertTicketId($ticketId);
        $this->assertStatus($status);

        return $this->tickets->updateStatus($ticketId, $status);
    }

    public function findById(int $ticketId): ?array
    {
        $this->assertTicketId($ticketId);

        return $this->tickets->findById($ticketId);
    }

    public function canView(int $ticketId, int $actorId, bool $isAdmin = false): bool
    {
        $this->assertTicketId($ticketId);
        if ($actorId <= 0) {
            return false;
        }

        return $isAdmin || $this->tickets->isOwnedByUser($ticketId, $actorId);
    }

    public function canEdit(int $ticketId, int $actorId): bool
    {
        $this->assertTicketId($ticketId);
        return $actorId > 0
            && in_array($this->tickets->getActiveUserRole($actorId), ['admin', 'superadmin'], true);
    }

    public function getResponses(int $ticketId): array
    {
        $this->assertTicketId($ticketId);

        return $this->tickets->getResponses($ticketId);
    }

    public function addResponse(int $ticketId, int $userId, string $message): bool
    {
        $this->assertTicketId($ticketId);
        if ($userId <= 0 || trim($message) === '') {
            throw new InvalidArgumentException('Los datos de la respuesta son inválidos.');
        }

        if (!in_array($this->tickets->getActiveUserRole($userId), ['admin', 'superadmin'], true)) {
            return false;
        }

        $created = $this->tickets->addResponse($ticketId, $userId, $message);
        if ($created && $this->notifications->ensureTable()) {
            $ticket = $this->tickets->findById($ticketId);
            if ($ticket !== null) {
                $notification = 'Nueva respuesta en el ticket #' . $ticketId
                    . ': ' . (string)($ticket['asunto'] ?? 'Sin asunto');
                foreach ($this->tickets->findAdministratorIds() as $administratorId) {
                    $this->notifications->createNotification(
                        (int)$administratorId,
                        'respuesta_ticket',
                        $notification,
                        $ticketId
                    );
                }
            }
        }

        return $created;
    }

    public function deleteResponse(int $responseId, int $actorId): string
    {
        if ($responseId <= 0 || $actorId <= 0) {
            throw new InvalidArgumentException('Los datos de la respuesta son inválidos.');
        }

        if (!in_array($this->tickets->getActiveUserRole($actorId), ['admin', 'superadmin'], true)) {
            return 'unauthorized';
        }

        $ticketId = $this->tickets->findResponseTicketId($responseId);
        if ($ticketId === null) {
            return 'not_found';
        }

        return $this->tickets->deleteResponse($responseId, $ticketId)
            ? (string)$ticketId
            : 'error';
    }

    public function assignTicket(int $ticketId, ?int $assignedUserId): bool
    {
        $this->assertTicketId($ticketId);

        if ($assignedUserId !== null && $assignedUserId <= 0) {
            throw new InvalidArgumentException('El usuario asignado no es válido.');
        }

        return $this->tickets->assign($ticketId, $assignedUserId);
    }

    public function updateWorkflow(
        int $ticketId,
        string $status,
        ?int $assignedUserId = null,
        ?string $problemType = null
    ): bool {
        $this->assertTicketId($ticketId);
        $this->assertStatus($status);

        if ($assignedUserId !== null && $assignedUserId <= 0) {
            throw new InvalidArgumentException('El usuario asignado no es válido.');
        }

        if ($problemType !== null && !in_array($problemType, self::ALLOWED_PROBLEM_TYPES, true)) {
            throw new InvalidArgumentException('El tipo de problema no es válido.');
        }

        return $this->tickets->updateWorkflow($ticketId, $status, $assignedUserId, $problemType);
    }

    private function assertTicketId(int $ticketId): void
    {
        if ($ticketId <= 0) {
            throw new InvalidArgumentException('El ticket no es válido.');
        }
    }

    private function assertStatus(string $status): void
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('El estado del ticket no es válido.');
        }
    }
}
