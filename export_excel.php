<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$user = requireAdmin();
$filters = getDashboardFilters();
$reports = getDashboardReports($filters);

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="registro_diario_tienda_' . date('Ymd_His') . '.xls"');

echo '<table border="1">';
echo '<tr>
        <th>Fecha</th>
        <th>Tienda</th>
        <th>Entraron</th>
        <th>Cliente nuevo</th>
        <th>Recurrentes</th>
        <th>Compraron</th>
        <th>Información</th>
        <th>Canal 3.3</th>
        <th>YouTube</th>
        <th>Izzi</th>
        <th>Total Play</th>
        <th>Capturó</th>
      </tr>';

foreach ($reports as $report) {
    echo '<tr>';
    echo '<td>' . e(formatDateMx($report['report_date'])) . '</td>';
    echo '<td>' . e($report['store_name']) . '</td>';
    echo '<td>' . (int) $report['entered_total'] . '</td>';
    echo '<td>' . (int) $report['client_new'] . '</td>';
    echo '<td>' . (int) $report['recurrent'] . '</td>';
    echo '<td>' . (int) $report['buyers'] . '</td>';
    echo '<td>' . (int) $report['info_count'] . '</td>';
    echo '<td>' . (int) $report['channel33_count'] . '</td>';
    echo '<td>' . (int) $report['youtube_count'] . '</td>';
    echo '<td>' . (int) $report['izzi_count'] . '</td>';
    echo '<td>' . (int) $report['totalplay_count'] . '</td>';
    echo '<td>' . e($report['created_by_username'] ?? '-') . '</td>';
    echo '</tr>';
}

echo '</table>';
exit;
