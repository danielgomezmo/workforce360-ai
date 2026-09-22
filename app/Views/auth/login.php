<?php
use App\Core\Csrf;
$error = flash('error');
$success = flash('success');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Workforce360 AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="login-page devioz-login">
<div id="particles-js" aria-hidden="true"></div>
<div class="login-wrapper">
    <div class="login-card card border-0 w-100">
        <div class="text-center mb-4">
            <img src="<?= e(asset('devioz-logo1.png')) ?>" alt="DEVIOZ" class="login-logo mb-3">
            <h1 class="h4 fw-bold mb-1">Workforce360 AI</h1>
            <p class="text-secondary small mb-0">Acceso al Sistema de Control Operativo</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 small text-center py-2 mb-4">
                <i class="fa-solid fa-triangle-exclamation me-1"></i><?= e($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success border-0 small text-center py-2 mb-4">
                <i class="fa-solid fa-circle-check me-1"></i><?= e($success) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(route_url('login')) ?>" novalidate>
            <?= Csrf::field() ?>
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold small">Usuario</label>
                <div class="input-group login-input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input id="username" name="username" type="text" class="form-control" value="<?= old('username') ?>" placeholder="Ingrese su usuario" autocomplete="username" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label fw-semibold small">Contraseña</label>
                <div class="input-group login-input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input id="password" name="password" type="password" class="form-control" placeholder="••••••••" autocomplete="current-password" required>
                </div>
            </div>
            <button class="btn btn-devioz w-100 fw-bold py-2" type="submit">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Ingresar al Sistema
            </button>
        </form>

        <div class="demo-credentials mt-4 p-3">
            <div class="small fw-bold mb-1"><i class="fa-solid fa-flask me-1"></i> Accesos de prueba</div>
            <div class="small text-secondary">Administrador: <code>admin</code> / <code>Admin123*</code></div>
            <div class="small text-secondary mt-1">Colaborador: <code>colaborador</code> / <code>Colab123*</code></div>
        </div>

        <div class="text-center mt-4 pt-3 border-top">
            <span class="text-secondary login-footer-text">© DEVIOZ · Workforce360 AI</span>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.particlesJS) {
        particlesJS('particles-js', {
            particles: {
                number: { value: 72, density: { enable: true, value_area: 800 } },
                color: { value: '#ffffff' },
                shape: { type: 'circle' },
                opacity: { value: 0.36, random: false },
                size: { value: 2.5, random: true },
                line_linked: { enable: true, distance: 150, color: '#ffffff', opacity: 0.22, width: 1 },
                move: { enable: true, speed: 2.6, direction: 'none', random: false, straight: false, out_mode: 'out', bounce: false }
            },
            interactivity: {
                detect_on: 'canvas',
                events: { onhover: { enable: true, mode: 'repulse' }, onclick: { enable: true, mode: 'push' }, resize: true },
                modes: { repulse: { distance: 130, duration: 0.4 }, push: { particles_nb: 3 } }
            },
            retina_detect: true
        });
    }
});
</script>
</body>
</html>
