<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$user = requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_dashboard.php');
    exit;
}

$reportId = (int) ($_POST['id'] ?? 0);

if ($reportId <= 0) {
    flashSet('danger', 'Registro inválido.');
    header('Location: admin_dashboard.php');
    exit;
}

deleteDailyReport($reportId);
flashSet('success', 'Registro eliminado correctamente.');
header('Location: admin_dashboard.php');
exit;
