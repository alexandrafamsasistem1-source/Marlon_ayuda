<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use PDO;
use RuntimeException;

final class AuthService
{
    private string $baseUrl;

    private PDO $connection;

    public function __construct(string $baseUrl, ?Database $database = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->connection = ($database ?? Database::getInstance())->getConnection();
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }

    public function isAdmin(): bool
    {
        return $this->isLoggedIn()
            && isset($_SESSION['rol'])
            && in_array($_SESSION['rol'], ['admin', 'superadmin'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->isLoggedIn()
            && isset($_SESSION['rol'])
            && $_SESSION['rol'] === 'superadmin';
    }

    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/auth/login.php');
        }

        if ((int)($_SESSION['debe_cambiar_password'] ?? 0) !== 1) {
            return;
        }

        $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
        if (!in_array($currentPage, ['cambiar_password.php', 'logout.php'], true)) {
            $this->redirect('/auth/cambiar_password.php');
        }
    }

    public function requireAdmin(): void
    {
        $this->requireLogin();

        if (!$this->isAdmin()) {
            $this->redirect('/usuario/dashboard.php');
        }
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        if ($hash === '') {
            return false;
        }

        return password_verify($password, $hash);
    }

    public function hashPassword(string $password): string
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

        if ($hash === false) {
            throw new RuntimeException('No se pudo generar el hash de la contraseña.');
        }

        return $hash;
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $this->baseUrl . '/' . ltrim($path, '/'));
        exit;
    }
}
