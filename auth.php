<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return getUserById((int) $_SESSION['user_id']);
}

function requireLogin(): array
{
    $user = currentUser();

    if (!$user) {
        header('Location: login.php');
        exit;
    }

    return $user;
}

function requireAdmin(): array
{
    $user = requireLogin();

    if (($user['role'] ?? '') !== 'admin') {
        header('Location: store_dashboard.php');
        exit;
    }

    return $user;
}

function requireStoreUser(): array
{
    $user = requireLogin();

    if (($user['role'] ?? '') !== 'store') {
        header('Location: admin_dashboard.php');
        exit;
    }

    return $user;
}
