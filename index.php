<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$user = currentUser();

if (!$user) {
    header('Location: login.php');
    exit;
}

if (($user['role'] ?? '') === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

header('Location: store_dashboard.php');
exit;
