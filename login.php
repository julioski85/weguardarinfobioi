<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (currentUser()) {
    header('Location: index.php');
    exit;
}

$error = '';
$loginBackgroundUrl = getLoginBackgroundUrl();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $user = getUserByUsername($username);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            header('Location: index.php');
            exit;
        }

        $error = 'Usuario o contraseña incorrectos.';
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
    <title>Registro Diario de Tienda | Acceso</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-body"<?php if ($loginBackgroundUrl): ?> style="--login-bg-image: url('<?php echo e($loginBackgroundUrl); ?>');"<?php endif; ?>>
    <div class="auth-layout">
        <div class="auth-card-wrap">
            <section class="auth-card">
                <div class="auth-card-head">
                    <div class="auth-logo">
                        <div class="auth-logo-mark">RD</div>
                        <div class="auth-logo-copy">
                            <strong>Registro Diario Panel administrador</strong>
                        </div>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="post" class="auth-form">
                    <div class="field full">
                        <label>Usuario</label>
                        <input type="text" name="username" required autocomplete="username" placeholder="Usuario">
                    </div>

                    <div class="field full">
                        <label>Contraseña</label>
                        <input type="password" name="password" required autocomplete="current-password" placeholder="Contraseña">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Iniciar sesión</button>
                </form>
            </section>
        </div>
    </div>
</body>
</html>
