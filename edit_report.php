<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$user = requireAdmin();
$reportId = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$report = getReportById($reportId);

if (!$report) {
    flashSet('danger', 'Registro no encontrado.');
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $clientNew = validateNonNegativeInt($_POST['client_new'] ?? '');
        $recurrent = validateNonNegativeInt($_POST['recurrent'] ?? '');
        $buyers = validateNonNegativeInt($_POST['buyers'] ?? '');
        $infoCount = validateNonNegativeInt($_POST['info_count'] ?? '');
        $channel33Count = validateNonNegativeInt($_POST['channel33_count'] ?? '');
        $youtubeCount = validateNonNegativeInt($_POST['youtube_count'] ?? '');

        $enteredTotal = $clientNew + $recurrent;

        if ($buyers > $enteredTotal) {
            throw new InvalidArgumentException('Compraron no puede ser mayor que Entraron.');
        }

        updateDailyReport($reportId, [
            'client_new' => $clientNew,
            'recurrent' => $recurrent,
            'entered_total' => $enteredTotal,
            'buyers' => $buyers,
            'info_count' => $infoCount,
            'channel33_count' => $channel33Count,
            'youtube_count' => $youtubeCount,
            'updated_at' => appDateTimeNow(),
        ]);

        flashSet('success', 'Registro actualizado correctamente.');
        header('Location: admin_dashboard.php');
        exit;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar registro</title>
    <link rel="stylesheet" href="assets/style.css">
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
                <strong><?php echo e($report['store_name']); ?></strong>
                <span><?php echo e(formatDateMx($report['report_date'])); ?></span>
            </div>

            <div class="sidebar-section-title">Navegación</div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-link"><span class="nav-link-icon"></span>Dashboard</a>
                <a href="users.php" class="nav-link"><span class="nav-link-icon"></span>Accesos de tiendas</a>
                <a href="logout.php" class="nav-link"><span class="nav-link-icon"></span>Cerrar sesión</a>
            </nav>
        </aside>

        <div class="app-main">
            <header class="headerbar">
                <div>
                    <div class="brand-pill">Edición</div>
                    <h1>Editar registro</h1>
                    <p>Ajusta los valores y guarda los cambios.</p>
                </div>
                <div class="headerbar-actions">
                    <a href="admin_dashboard.php" class="btn btn-light">Volver</a>
                </div>
            </header>

            <main class="page page-sm">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2><?php echo e($report['store_name']); ?></h2>
                            <p class="section-copy">Fecha: <?php echo e(formatDateMx($report['report_date'])); ?></p>
                        </div>
                    </div>

                    <form method="post" id="editForm" class="form-grid">
                        <input type="hidden" name="id" value="<?php echo (int) $report['id']; ?>">

                        <div class="field">
                            <label>Cliente nuevo</label>
                            <input type="number" name="client_new" min="0" step="1" value="<?php echo (int) $report['client_new']; ?>" required>
                        </div>

                        <div class="field">
                            <label>Recurrentes</label>
                            <input type="number" name="recurrent" min="0" step="1" value="<?php echo (int) $report['recurrent']; ?>" required>
                        </div>

                        <div class="field">
                            <label>Entraron</label>
                            <input type="number" id="entered_total_preview" value="<?php echo (int) $report['entered_total']; ?>" readonly class="readonly-input">
                        </div>

                        <div class="field">
                            <label>Compraron</label>
                            <input type="number" name="buyers" id="buyers" min="0" step="1" value="<?php echo (int) $report['buyers']; ?>" required>
                            <small class="helper-text">No puede ser mayor que Entraron.</small>
                        </div>

                        <div class="field">
                            <label>Información</label>
                            <input type="number" name="info_count" min="0" step="1" value="<?php echo (int) $report['info_count']; ?>" required>
                        </div>

                        <div class="source-box">
                            <div class="source-box-title">Origen del interés</div>
                            <div class="source-grid">
                                <div class="field">
                                    <label>Canal 3.3</label>
                                    <input type="number" name="channel33_count" min="0" step="1" value="<?php echo (int) $report['channel33_count']; ?>" required>
                                </div>

                                <div class="field">
                                    <label>YouTube</label>
                                    <input type="number" name="youtube_count" min="0" step="1" value="<?php echo (int) $report['youtube_count']; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="field full">
                            <button type="submit" class="btn btn-primary btn-block">Guardar cambios</button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>

    <script>
        const clientNewInput = document.querySelector('input[name="client_new"]');
        const recurrentInput = document.querySelector('input[name="recurrent"]');
        const buyersInput = document.getElementById('buyers');
        const enteredTotalPreview = document.getElementById('entered_total_preview');
        const editForm = document.getElementById('editForm');

        function recalculateEntered() {
            const clientNew = parseInt(clientNewInput?.value || 0, 10);
            const recurrent = parseInt(recurrentInput?.value || 0, 10);
            const total = clientNew + recurrent;
            enteredTotalPreview.value = total;
            buyersInput.max = total;
        }

        clientNewInput?.addEventListener('input', recalculateEntered);
        recurrentInput?.addEventListener('input', recalculateEntered);

        editForm?.addEventListener('submit', function (event) {
            const total = parseInt(enteredTotalPreview.value || 0, 10);
            const buyers = parseInt(buyersInput.value || 0, 10);

            if (buyers > total) {
                event.preventDefault();
                alert('Compraron no puede ser mayor que Entraron.');
            }
        });

        recalculateEntered();
    </script>
</body>
</html>
