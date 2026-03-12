<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$user = requireAdmin();
$flash = flashGet();
$error = '';
$loginBackgroundUrl = getLoginBackgroundUrl();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'update_login_background') {
    try {
        uploadLoginBackground($_FILES['login_background_image'] ?? []);
        flashSet('success', 'La imagen de fondo del login se actualizó correctamente.');
        header('Location: configuracion_visual.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración visual</title>
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
                <strong>admin <?php echo e($user['username']); ?></strong>
                <span>Configuración visual</span>
            </div>

            <div class="sidebar-section-title">Navegación</div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-link"><span class="nav-link-icon"></span>Dashboard</a>
                <a href="users.php" class="nav-link"><span class="nav-link-icon"></span>Accesos de tiendas</a>
                <a href="configuracion_visual.php" class="nav-link active"><span class="nav-link-icon"></span>Configuración visual</a>
                <a href="logout.php" class="nav-link"><span class="nav-link-icon"></span>Cerrar sesión</a>
            </nav>

            <div class="sidebar-bottom">
                <div class="brand-pill">Vista general</div>
            </div>
        </aside>

        <div class="app-main">
            <header class="headerbar">
                <div class="headerbar-copy">
                    <div class="brand-pill">Imagen login</div>
                    <h1>Configuración visual</h1>
                    <p>Sube o reemplaza la imagen de fondo del login.</p>
                </div>
                <div class="headerbar-actions">
                    <a href="admin_dashboard.php" class="btn btn-light">Volver al dashboard</a>
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

                <section class="card visual-settings-form-card">
                    <div class="card-header">
                        <div>
                            <h2 class="section-title">Imagen de fondo del login</h2>
                            <p class="section-copy">Formato permitido: JPG, JPEG, PNG y WEBP (máx. 4 MB).</p>
                        </div>
                    </div>

                    <form method="post" enctype="multipart/form-data" class="visual-settings-form">
                        <input type="hidden" name="action" value="update_login_background">

                        <div class="field">
                            <label for="login_background_image">Seleccionar imagen</label>
                            <input id="login_background_image" type="file" name="login_background_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        </div>

                        <div class="field">
                            <button type="submit" class="btn btn-primary">Guardar imagen</button>
                        </div>
                    </form>

                    <div class="visual-preview">
                        <p class="section-copy">Vista previa actual</p>
                        <?php if ($loginBackgroundUrl): ?>
                            <img id="loginBackgroundPreview" src="<?php echo e($loginBackgroundUrl); ?>" alt="Vista previa del fondo de login" class="visual-preview-image">
                        <?php else: ?>
                            <p class="muted">No hay imagen configurada todavía.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <script>
        const fileInput = document.getElementById('login_background_image');
        const previewImage = document.getElementById('loginBackgroundPreview');

        fileInput?.addEventListener('change', () => {
            const selected = fileInput.files && fileInput.files[0];
            if (!selected) return;

            const objectUrl = URL.createObjectURL(selected);
            if (previewImage) {
                previewImage.src = objectUrl;
            }
        });
    </script>
</body>
</html>
