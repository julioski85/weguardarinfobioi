<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$user = requireAdmin();
$flash = flashGet();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $newPassword = trim((string) ($_POST['new_password'] ?? ''));

    if ($userId <= 0 || $newPassword === '') {
        $error = 'Completa todos los datos.';
    } else {
        updateUserPassword($userId, $newPassword);
        flashSet('success', 'Contraseña actualizada correctamente.');
        header('Location: users.php');
        exit;
    }
}

$storeUsers = getStoreUsers();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesos de tiendas</title>
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
                <strong><?php echo e($user['username']); ?></strong>
                <span>Gestión de accesos</span>
            </div>

            <div class="sidebar-section-title">Navegación</div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-link"><span class="nav-link-icon"></span>Dashboard</a>
                <a href="users.php" class="nav-link active"><span class="nav-link-icon"></span>Accesos de tiendas</a>
                <a href="logout.php" class="nav-link"><span class="nav-link-icon"></span>Cerrar sesión</a>
            </nav>
        </aside>

        <div class="app-main">
            <header class="headerbar">
                <div>
                    <div class="brand-pill">Usuarios</div>
                    <h1>Accesos de tiendas</h1>
                    <p>Actualiza contraseñas de cada sucursal desde este módulo.</p>
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

                <section class="card table-card">
                    <div class="card-header">
                        <div>
                            <h2>Usuarios de sucursal</h2>
                            <p class="section-copy">Las contraseñas se capturan ocultas por seguridad.</p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tienda</th>
                                    <th>Usuario</th>
                                    <th>Última actualización</th>
                                    <th>Actualizar contraseña</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($storeUsers as $storeUser): ?>
                                    <tr>
                                        <td><?php echo e($storeUser['store_name']); ?></td>
                                        <td><?php echo e($storeUser['username']); ?></td>
                                        <td><?php echo e($storeUser['updated_at'] ? date('d/m/Y H:i', strtotime($storeUser['updated_at'])) : '-'); ?></td>
                                        <td>
                                            <form method="post" class="inline-password">
                                                <input type="hidden" name="user_id" value="<?php echo (int) $storeUser['id']; ?>">
                                                <input type="password" name="new_password" placeholder="Nueva contraseña" required autocomplete="new-password">
                                                <button type="submit" class="btn btn-small btn-primary">Guardar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
