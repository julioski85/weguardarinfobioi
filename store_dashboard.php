<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$user = requireStoreUser();
$store = getStoreById((int) $user['store_id']);
$todayReport = getTodayReportForStore((int) $user['store_id']);
$flash = flashGet();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$todayReport) {
    try {
        $clientNew = validateNonNegativeInt($_POST['client_new'] ?? '');
        $recurrent = validateNonNegativeInt($_POST['recurrent'] ?? '');
        $buyers = validateNonNegativeInt($_POST['buyers'] ?? '');
        $infoCount = validateNonNegativeInt($_POST['info_count'] ?? '');
        $channel33Count = validateNonNegativeInt($_POST['channel33_count'] ?? '');
        $youtubeCount = validateNonNegativeInt($_POST['youtube_count'] ?? '');
        $izziCount = validateNonNegativeInt($_POST['izzi_count'] ?? '');
        $totalplayCount = validateNonNegativeInt($_POST['totalplay_count'] ?? '');
        $recommendationCount = validateNonNegativeInt($_POST['recommendation_count'] ?? '');
        $radioCount = validateNonNegativeInt($_POST['radio_count'] ?? '');

        $enteredTotal = $clientNew + $recurrent;

        if ($buyers > $enteredTotal) {
            throw new InvalidArgumentException('Compraron no puede ser mayor que Clientes en el día.');
        }

        createDailyReport([
            'store_id' => (int) $user['store_id'],
            'report_date' => appDateToday(),
            'client_new' => $clientNew,
            'recurrent' => $recurrent,
            'entered_total' => $enteredTotal,
            'buyers' => $buyers,
            'info_count' => $infoCount,
            'channel33_count' => $channel33Count,
            'youtube_count' => $youtubeCount,
            'izzi_count' => $izziCount,
            'totalplay_count' => $totalplayCount,
            'recommendation_count' => $recommendationCount,
            'radio_count' => $radioCount,
            'created_by_user_id' => (int) $user['id'],
            'created_at' => appDateTimeNow(),
            'updated_at' => appDateTimeNow(),
        ]);

        flashSet('success', 'Registro enviado correctamente.');
        header('Location: store_dashboard.php');
        exit;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$todayReport = getTodayReportForStore((int) $user['store_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Diario de Tienda</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a href="store_dashboard.php" class="logo">
                <div class="logo-mark">RD</div>
                <div class="logo-copy">
                    <strong>Registro Diario</strong>
                    <span><?php echo e($store['name'] ?? 'Sucursal'); ?></span>
                </div>
            </a>

            <div class="sidebar-user">
                <strong><?php echo e($store['name'] ?? 'Sucursal'); ?></strong>
                <span>Usuario: <?php echo e($user['username']); ?></span>
            </div>

            <div class="sidebar-section-title">Menú</div>
            <nav class="sidebar-nav">
                <a href="store_dashboard.php" class="nav-link active"><span class="nav-link-icon"></span>Registro del día</a>
                <a href="logout.php" class="nav-link"><span class="nav-link-icon"></span>Cerrar sesión</a>
            </nav>

            <div class="sidebar-bottom">
                <div class="brand-pill"><?php echo e(formatDateMx(appDateToday())); ?></div>
            </div>
        </aside>

        <div class="app-main">
            <header class="headerbar">
                <div>
                    <div class="brand-pill">Sucursal</div>
                    <h1><?php echo e($store['name'] ?? 'Registro'); ?></h1>
                    <p>Captura diaria con fecha automática y validaciones activas.</p>
                </div>
                <div class="headerbar-actions">
                    <div class="status-badge <?php echo $todayReport ? 'done' : 'pending'; ?>">
                        <?php echo $todayReport ? 'Ya registrado' : 'Pendiente'; ?>
                    </div>
                </div>
            </header>

            <main class="page page-sm">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>">
                        <?php echo e($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2>Registro del día</h2>
                            <p class="section-copy">Fecha automática: <strong><?php echo e(formatDateMx(appDateToday())); ?></strong></p>
                        </div>
                    </div>

                    <?php if ($todayReport): ?>
                        <div class="empty-state">
                            <h2>Esta tienda ya registró su información hoy.</h2>
                            <p class="section-copy">Si necesitas cambios, el administrador puede editar el registro.</p>
                        </div>

                        <div class="summary-grid">
                            <div class="summary-box"><span>Clientes en el día</span><strong><?php echo (int) $todayReport['entered_total']; ?></strong></div>
                            <div class="summary-box"><span>Cliente nuevo</span><strong><?php echo (int) $todayReport['client_new']; ?></strong></div>
                            <div class="summary-box"><span>Recurrentes</span><strong><?php echo (int) $todayReport['recurrent']; ?></strong></div>
                            <div class="summary-box"><span>Compraron</span><strong><?php echo (int) $todayReport['buyers']; ?></strong></div>
                            <div class="summary-box"><span>Entraron</span><strong><?php echo (int) $todayReport['buyers'] + (int) $todayReport['info_count']; ?></strong></div>
                            <div class="summary-box"><span>Información</span><strong><?php echo (int) $todayReport['info_count']; ?></strong></div>
                            <div class="summary-box"><span>Canal 3.3</span><strong><?php echo (int) $todayReport['channel33_count']; ?></strong></div>
                            <div class="summary-box"><span>YouTube</span><strong><?php echo (int) $todayReport['youtube_count']; ?></strong></div>
                            <div class="summary-box"><span>Izzi</span><strong><?php echo (int) $todayReport['izzi_count']; ?></strong></div>
                            <div class="summary-box"><span>Total Play</span><strong><?php echo (int) $todayReport['totalplay_count']; ?></strong></div>
                            <div class="summary-box"><span>Recomendación</span><strong><?php echo (int) ($todayReport['recommendation_count'] ?? 0); ?></strong></div>
                            <div class="summary-box"><span>Radio</span><strong><?php echo (int) ($todayReport['radio_count'] ?? 0); ?></strong></div>
                        </div>
                    <?php else: ?>
                        <form method="post" id="reportForm" class="form-grid">
                            <div class="field">
                                <label>Cliente nuevo</label>
                                <input type="number" name="client_new" min="0" step="1" required>
                            </div>

                            <div class="field">
                                <label>Recurrentes</label>
                                <input type="number" name="recurrent" min="0" step="1" required>
                            </div>

                            <div class="field">
                                <label>Clientes en el día</label>
                                <input type="number" id="entered_total_preview" value="0" readonly class="readonly-input">
                            </div>

                            <div class="field">
                                <label>Compraron</label>
                                <input type="number" name="buyers" id="buyers" min="0" step="1" required>
                                <small class="helper-text">No puede ser mayor que Clientes en el día.</small>
                            </div>

                            <div class="field">
                                <label>Entraron</label>
                                <input type="number" id="entered_from_interest_preview" value="0" readonly class="readonly-input">
                            </div>

                            <div class="field">
                                <label>Información</label>
                                <input type="number" name="info_count" id="info_count" min="0" step="1" required>
                            </div>

                            <div class="source-box full">
                                <div class="source-box-title">Origen del interés</div>
                                <div class="source-grid">
                                    <div class="field">
                                        <label>Canal 3.3</label>
                                        <input type="number" name="channel33_count" min="0" step="1" required>
                                    </div>

                                    <div class="field">
                                        <label>YouTube</label>
                                        <input type="number" name="youtube_count" min="0" step="1" required>
                                    </div>

                                    <div class="field">
                                        <label>Izzi</label>
                                        <input type="number" name="izzi_count" min="0" step="1" required>
                                    </div>

                                    <div class="field">
                                        <label>Total Play</label>
                                        <input type="number" name="totalplay_count" min="0" step="1" required>
                                    </div>

                                    <div class="field">
                                        <label>Recomendación</label>
                                        <input type="number" name="recommendation_count" min="0" step="1" required>
                                    </div>

                                    <div class="field">
                                        <label>Radio</label>
                                        <input type="number" name="radio_count" min="0" step="1" required>
                                    </div>
                                </div>
                            </div>

                            <div class="field full">
                                <button type="submit" class="btn btn-primary btn-block">Guardar registro</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

    <script>
        const clientNewInput = document.querySelector('input[name="client_new"]');
        const recurrentInput = document.querySelector('input[name="recurrent"]');
        const buyersInput = document.getElementById('buyers');
        const enteredTotalPreview = document.getElementById('entered_total_preview');
        const infoCountInput = document.getElementById('info_count');
        const enteredFromInterestPreview = document.getElementById('entered_from_interest_preview');
        const reportForm = document.getElementById('reportForm');

        function recalculateEntered() {
            const clientNew = parseInt(clientNewInput?.value || 0, 10);
            const recurrent = parseInt(recurrentInput?.value || 0, 10);
            const total = clientNew + recurrent;
            if (enteredTotalPreview) enteredTotalPreview.value = total;
            if (buyersInput) buyersInput.max = total;

            const buyers = parseInt(buyersInput?.value || 0, 10);
            const infoCount = parseInt(infoCountInput?.value || 0, 10);
            if (enteredFromInterestPreview) enteredFromInterestPreview.value = buyers + infoCount;
        }

        clientNewInput?.addEventListener('input', recalculateEntered);
        recurrentInput?.addEventListener('input', recalculateEntered);
        buyersInput?.addEventListener('input', recalculateEntered);
        infoCountInput?.addEventListener('input', recalculateEntered);

        reportForm?.addEventListener('submit', function (event) {
            const total = parseInt(enteredTotalPreview.value || 0, 10);
            const buyers = parseInt(buyersInput.value || 0, 10);

            if (buyers > total) {
                event.preventDefault();
                alert('Compraron no puede ser mayor que Clientes en el día.');
            }
        });

        recalculateEntered();
    </script>
</body>
</html>
