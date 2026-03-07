<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

const REMEMBER_ME_COOKIE = 'remember_me';
const REMEMBER_ME_DAYS = 30;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function buildRememberMeSignature(array $user, int $expiresAt): string
{
    $payload = implode('|', [
        (string) $user['id'],
        (string) $user['username'],
        (string) $user['password_hash'],
        (string) $expiresAt,
    ]);

    return hash_hmac('sha256', $payload, DB_PASS);
}

function clearRememberMeCookie(): void
{
    setcookie(REMEMBER_ME_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
}

function setRememberMeCookie(array $user): void
{
    $expiresAt = time() + (REMEMBER_ME_DAYS * 86400);
    $signature = buildRememberMeSignature($user, $expiresAt);
    $value = $user['id'] . ':' . $expiresAt . ':' . $signature;

    setcookie(REMEMBER_ME_COOKIE, $value, [
        'expires' => $expiresAt,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
}

function restoreUserFromRememberMeCookie(): ?array
{
    $cookie = (string) ($_COOKIE[REMEMBER_ME_COOKIE] ?? '');

    if ($cookie === '') {
        return null;
    }

    $parts = explode(':', $cookie);

    if (count($parts) !== 3) {
        clearRememberMeCookie();
        return null;
    }

    [$userId, $expiresAt, $signature] = $parts;

    if (!ctype_digit($userId) || !ctype_digit($expiresAt)) {
        clearRememberMeCookie();
        return null;
    }

    if ((int) $expiresAt < time()) {
        clearRememberMeCookie();
        return null;
    }

    $user = getUserById((int) $userId);

    if (!$user || !(bool) $user['is_active']) {
        clearRememberMeCookie();
        return null;
    }

    $expected = buildRememberMeSignature($user, (int) $expiresAt);

    if (!hash_equals($expected, $signature)) {
        clearRememberMeCookie();
        return null;
    }

    setRememberMeCookie($user);

    return $user;
}

if (empty($_SESSION['user_id'])) {
    $rememberedUser = restoreUserFromRememberMeCookie();

    if ($rememberedUser) {
        $_SESSION['user_id'] = (int) $rememberedUser['id'];
    }
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
