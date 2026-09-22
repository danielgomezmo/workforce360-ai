<?php
$title = 'Acceso denegado · Workforce360 AI';
require BASE_PATH . '/app/Views/layouts/header.php';
?>
<div class="card border-0 shadow-sm mx-auto" style="max-width: 680px;">
    <div class="card-body p-5 text-center">
        <div class="display-5 fw-bold text-danger mb-2">403</div>
        <h1 class="h3 fw-bold">No tienes permiso para ingresar aquí</h1>
        <p class="text-secondary">Este módulo está disponible para Administración y Recursos Humanos.</p>
        <a class="btn btn-primary" href="<?= e(route_url('dashboard')) ?>">Volver al dashboard</a>
    </div>
</div>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
