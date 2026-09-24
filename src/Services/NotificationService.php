<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NotificationRepository;
use InvalidArgumentException;

final class NotificationService
{
    private NotificationRepository $notifications;

    public function __construct(NotificationRepository $notifications)
    {
        $this->notifications = $notifications;
    }

    public function ensureTable(): bool
    {
        return $this->notifications->ensureTable();
    }

    public function createTicketNotification(
        int $ticketId,
        array $adminIds,
        string $userName,
        string $subject,
        string $location,
        ?string $area
    ): int {
        if ($ticketId <= 0) {
            throw new InvalidArgumentException('El ticket no es válido.');
        }

        $areaText = $area !== null && trim($area) !== '' ? $area : 'Sin área';
        $message = "Nuevo ticket #{$ticketId}: {$subject} ({$location} - {$areaText}) enviado por {$userName}";

        return $this->notifications->createForUsers($adminIds, $message, $ticketId);
    }

    public function createNotification(
        int $userId,
        string $type,
        string $message,
        ?int $referenceId = null
    ): bool {
        if ($userId <= 0 || trim($type) === '' || trim($message) === '') {
            throw new InvalidArgumentException('Los datos de la notificación son inválidos.');
        }

        return $this->notifications->createNotification(
            $userId,
            $type,
            $message,
            $referenceId
        );
    }

    public function unreadCount(int $userId): int
    {
        return $userId > 0 ? $this->notifications->countUnread($userId) : 0;
    }

    public function getUnread(int $userId): int
    {
        return $this->unreadCount($userId);
    }

    public function forUser(int $userId, int $limit = 10, bool $unreadOnly = true): array
    {
        if ($userId <= 0) {
            return [];
        }

        return $this->notifications->findForUser($userId, $limit, $unreadOnly);
    }

    public function markAsRead(int $userId): bool
    {
        return $userId > 0 && $this->notifications->markAsRead($userId);
    }
}
