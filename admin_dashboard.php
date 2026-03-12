<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$user = requireAdmin();
$filters = getDashboardFilters();
$metrics = getDashboardMetrics($filters);
$reports = getDashboardReports($filters);
$stores = getStores();
$chartByStore = getChartDataByStore($filters);
$chartByDate = getChartDataByDate($filters);
$sourceTotals = getSourceChartTotals($filters);

$flash = flashGet();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Registro Diario de Tienda</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a href="admin_dashboard.php" class="logo">
                <div class="logo-mark">RD</div>
                <div class="logo-copy">
                    <strong>Registro Diario</strong>
                    <span>Panel administrador</span>
                </div>
            </a>

                <div class="sidebar-user">
                    <strong>admin <?php echo e($user['username']); ?></strong>
                    <span>Administrador</span>
                </div>

            <div class="sidebar-section-title">Navegación</div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-link active"><span class="nav-link-icon"></span>Dashboard</a>
                <a href="users.php" class="nav-link"><span class="nav-link-icon"></span>Accesos de tiendas</a>
                <a href="configuracion_visual.php" class="nav-link"><span class="nav-link-icon"></span>Configuración visual</a>
                <a href="logout.php" class="nav-link"><span class="nav-link-icon"></span>Cerrar sesión</a>
            </nav>

            <div class="sidebar-bottom">
                <div class="brand-pill">Vista general</div>
            </div>
        </aside>

        <div class="app-main">
            <header class="headerbar">
                <div class="headerbar-copy">
                    <div class="brand-pill">Dashboard</div>
                    <h1>Resumen general</h1>
                    <p>Consulta métricas, gráficas y registros capturados por sucursal.</p>
                </div>
                <div class="headerbar-actions">
                    <a href="users.php" class="btn btn-light">Accesos</a>
                    <a href="export_excel.php?date_from=<?php echo urlencode($filters['date_from']); ?>&date_to=<?php echo urlencode($filters['date_to']); ?>&store_id=<?php echo urlencode((string) ($filters['store_id'] ?? '')); ?>" class="btn btn-success">Exportar Excel</a>
                </div>
            </header>

            <main class="page">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>">
                        <?php echo e($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <section class="card card-soft">
                    <div class="card-header">
                        <div>
                            <h2 class="section-title">Filtros</h2>
                            <p class="section-copy">Ajusta las fechas o consulta una sucursal específica.</p>
                        </div>
                    </div>

                    <form method="get" class="filters-grid">
                        <div class="field">
                            <label>Fecha inicial</label>
                            <input type="date" name="date_from" value="<?php echo e($filters['date_from']); ?>">
                        </div>

                        <div class="field">
                            <label>Fecha final</label>
                            <input type="date" name="date_to" value="<?php echo e($filters['date_to']); ?>">
                        </div>

                        <div class="field">
                            <label>Tienda</label>
                            <select name="store_id">
                                <option value="">Todas</option>
                                <?php foreach ($stores as $store): ?>
                                    <option value="<?php echo (int) $store['id']; ?>" <?php echo ((int) ($filters['store_id'] ?? 0) === (int) $store['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($store['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field field-actions">
                            <button type="submit" class="btn btn-primary">Aplicar filtro</button>
                        </div>
                    </form>
                </section>

                <section class="metrics-grid">
                    <div class="metric-card metric-purple"><span>Registros</span><strong><?php echo (int) $metrics['total_records']; ?></strong></div>
                    <div class="metric-card metric-green"><span>Clientes en el día</span><strong><?php echo (int) $metrics['total_entered']; ?></strong></div>
                    <div class="metric-card metric-orange"><span>Compraron</span><strong><?php echo (int) $metrics['total_buyers']; ?></strong></div>
                    <div class="metric-card metric-blue"><span>Conversión</span><strong><?php echo e((string) $metrics['conversion_rate']); ?>%</strong></div>
                    <div class="metric-card metric-neutral"><span>Información</span><strong><?php echo (int) $metrics['total_information']; ?></strong></div>
                    <div class="metric-card metric-neutral"><span>Canales origen</span><strong><?php echo (int) $metrics['total_channel33'] + (int) $metrics['total_youtube'] + (int) $metrics['total_izzi'] + (int) $metrics['total_totalplay']; ?></strong></div>
                </section>

                <section class="charts-grid">
                    <div class="card chart-short">
                        <div class="card-header">
                            <div>
                                <h2>Clientes en el día vs Compraron</h2>
                                <p class="section-copy">Comparativa por sucursal.</p>
                            </div>
                        </div>
                        <canvas id="storeChart"></canvas>
                    </div>

                    <div class="card chart-short">
                        <div class="card-header">
                            <div>
                                <h2>Origen del interés</h2>
                                <p class="section-copy">Canal 3.3, YouTube, Izzi, Total Play, Recomendación y Radio.</p>
                            </div>
                        </div>
                        <canvas id="sourceChart"></canvas>
                    </div>


                    <div class="card chart-short">
                        <div class="card-header">
                            <div>
                                <h2>Resultado comercial</h2>
                                <p class="section-copy">Compraron vs solo información y conversión total.</p>
                            </div>
                        </div>
                        <div class="summary-grid">
                            <div class="summary-box"><span>Compraron</span><strong><?php echo (int) $metrics['total_buyers']; ?></strong></div>
                            <div class="summary-box"><span>Solo información</span><strong><?php echo (int) $metrics['total_information']; ?></strong></div>
                            <div class="summary-box"><span>% conversión</span><strong><?php echo e((string) $metrics['conversion_rate']); ?>%</strong></div>
                        </div>
                        <canvas id="conversionChart"></canvas>
                    </div>

                    <div class="card card-full">
                        <div class="card-header">
                            <div>
                                <h2>Comportamiento por fecha</h2>
                                <p class="section-copy">Seguimiento diario de entradas y compras.</p>
                            </div>
                        </div>
                        <canvas id="dateChart"></canvas>
                    </div>
                </section>

                <section class="card table-card">
                    <div class="card-header">
                        <div>
                            <h2>Registros capturados</h2>
                            <p class="section-copy">Edita o elimina cualquier registro desde este módulo.</p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tienda</th>
                                    <th>Clientes en el día</th>
                                    <th>Cliente nuevo</th>
                                    <th>Recurrentes</th>
                                    <th>Compraron</th>
                                    <th>Entraron</th>
                                    <th>Información</th>
                                    <th>Canal 3.3</th>
                                    <th>YouTube</th>
                                    <th>Izzi</th>
                                    <th>Total Play</th>
                                    <th>Recomendación</th>
                                    <th>Radio</th>
                                    <th>Capturó</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$reports): ?>
                                    <tr>
                                        <td colspan="16" class="text-center muted">No hay registros con ese filtro.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?php echo e(formatDateMx($report['report_date'])); ?></td>
                                            <td><?php echo e($report['store_name']); ?></td>
                                            <td><?php echo (int) $report['entered_total']; ?></td>
                                            <td><?php echo (int) $report['client_new']; ?></td>
                                            <td><?php echo (int) $report['recurrent']; ?></td>
                                            <td><?php echo (int) $report['buyers']; ?></td>
                                            <td><?php echo (int) $report['buyers'] + (int) $report['info_count']; ?></td>
                                            <td><?php echo (int) $report['info_count']; ?></td>
                                            <td><?php echo (int) $report['channel33_count']; ?></td>
                                            <td><?php echo (int) $report['youtube_count']; ?></td>
                                            <td><?php echo (int) $report['izzi_count']; ?></td>
                                            <td><?php echo (int) $report['totalplay_count']; ?></td>
                                            <td><?php echo (int) ($report['recommendation_count'] ?? 0); ?></td>
                                            <td><?php echo (int) ($report['radio_count'] ?? 0); ?></td>
                                            <td><?php echo e($report['created_by_username'] ?? '-'); ?></td>
                                            <td>
                                                <div class="actions-inline">
                                                    <a class="btn btn-small btn-light" href="edit_report.php?id=<?php echo (int) $report['id']; ?>">Editar</a>
                                                    <form method="post" action="delete_report.php" onsubmit="return confirm('¿Seguro que deseas eliminar este registro?');">
                                                        <input type="hidden" name="id" value="<?php echo (int) $report['id']; ?>">
                                                        <button type="submit" class="btn btn-small btn-danger">Eliminar</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        const commonBarOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { usePointStyle: true, boxWidth: 10 }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#7b8199' }
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: '#7b8199' },
                    grid: { color: 'rgba(123, 129, 153, 0.12)' }
                }
            }
        };

        new Chart(document.getElementById('storeChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($chartByStore, 'name')); ?>,
                datasets: [
                    {
                        label: 'Clientes en el día',
                        backgroundColor: '#6259ca',
                        borderRadius: 8,
                        data: <?php echo json_encode(array_map('intval', array_column($chartByStore, 'total_entered'))); ?>
                    },
                    {
                        label: 'Compraron',
                        backgroundColor: '#22c55e',
                        borderRadius: 8,
                        data: <?php echo json_encode(array_map('intval', array_column($chartByStore, 'total_buyers'))); ?>
                    }
                ]
            },
            options: commonBarOptions
        });

        new Chart(document.getElementById('dateChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(fn($row) => date('d/m', strtotime($row['report_date'])), $chartByDate)); ?>,
                datasets: [
                    {
                        label: 'Clientes en el día',
                        data: <?php echo json_encode(array_map('intval', array_column($chartByDate, 'total_entered'))); ?>,
                        borderColor: '#6259ca',
                        backgroundColor: 'rgba(98, 89, 202, 0.08)',
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Compraron',
                        data: <?php echo json_encode(array_map('intval', array_column($chartByDate, 'total_buyers'))); ?>,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.08)',
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: commonBarOptions
        });



        new Chart(document.getElementById('conversionChart'), {
            type: 'bar',
            data: {
                labels: ['Compraron', 'Solo información'],
                datasets: [{
                    data: [<?php echo (int) $metrics['total_buyers']; ?>, <?php echo (int) $metrics['total_information']; ?>],
                    backgroundColor: ['#22c55e', '#6259ca'],
                    borderRadius: 8
                }]
            },
            options: {
                ...commonBarOptions,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        new Chart(document.getElementById('sourceChart'), {
            type: 'doughnut',
            data: {
                labels: ['Canal 3.3', 'YouTube', 'Izzi', 'Total Play', 'Recomendación', 'Radio'],
                datasets: [{
                    data: [
                        <?php echo (int) $sourceTotals['channel33_total']; ?>,
                        <?php echo (int) $sourceTotals['youtube_total']; ?>,
                        <?php echo (int) $sourceTotals['izzi_total']; ?>,
                        <?php echo (int) $sourceTotals['totalplay_total']; ?>,
                        <?php echo (int) ($sourceTotals['recommendation_total'] ?? 0); ?>,
                        <?php echo (int) ($sourceTotals['radio_total'] ?? 0); ?>
                    ],
                    backgroundColor: ['#22c55e', '#ff9f43', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, boxWidth: 10 }
                    }
                }
            }
        });
    </script>
</body>
</html>
