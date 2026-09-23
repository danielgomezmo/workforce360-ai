<?php
use App\Core\Auth;
use App\Core\Csrf;
$user = Auth::user();
$currentRoute = trim((string) ($_GET['route'] ?? ''), '/');
$success = flash('success');
$error = flash('error');
$canManage = $user && Auth::hasAnyRole(['ADMINISTRADOR', 'RRHH']);
$canViewMarks = $user && Auth::hasAnyRole(['ADMINISTRADOR', 'RRHH', 'SUPERVISOR']);
$canViewReports = $user && Auth::hasAnyRole(['ADMINISTRADOR', 'RRHH', 'SUPERVISOR', 'GERENCIA']);
$hasLinkedCollaborator = $user && Auth::collaboratorId() !== null;
$canUsePersonalAttendance = $user && (Auth::hasRole('COLABORADOR') || $hasLinkedCollaborator);
$isCollaboratorPanel = $user
    && Auth::hasRole('COLABORADOR')
    && !Auth::hasAnyRole(['ADMINISTRADOR', 'RRHH', 'SUPERVISOR', 'GERENCIA']);
$homeRoute = $isCollaboratorPanel ? 'mi-asistencia' : 'dashboard';
$panelLabel = $isCollaboratorPanel ? 'Panel de Colaborador' : 'Sistema de Control Operativo';
$roleLabel = $isCollaboratorPanel ? 'Mi Perfil' : (Auth::hasRole('ADMINISTRADOR') ? 'Panel de Control' : ($user['roles'] ?: 'Workforce360 AI'));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? config('app.name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="app-body <?= $isCollaboratorPanel ? 'role-collaborator' : 'role-management' ?>">
<div class="layout-root">
    <header class="app-header shadow-sm">
        <div class="header-left">
            <button class="btn-toggle-sidebar" id="toggleSidebar" type="button" title="Contraer/Desplegar menú" aria-label="Contraer o desplegar menú">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a href="<?= e(route_url($homeRoute)) ?>" class="header-brand" aria-label="Inicio">
                <img src="<?= e(asset('devioz-logo1.png')) ?>" alt="DEVIOZ">
            </a>
            <span class="system-chip d-none d-md-inline-flex">
                <i class="fa-solid fa-circle"></i><?= e($panelLabel) ?>
            </span>
        </div>

        <?php if ($user): ?>
        <div class="header-right">
            <div class="header-user d-none d-sm-flex">
                <div class="header-avatar"><i class="fa-solid <?= $isCollaboratorPanel ? 'fa-user' : 'fa-user-shield' ?>"></i></div>
                <div class="header-user-copy">
                    <strong><?= e($user['nombre_mostrar']) ?></strong>
                    <span><?= e($roleLabel) ?></span>
                </div>
            </div>
            <form method="post" action="<?= e(route_url('logout')) ?>" class="m-0">
                <?= Csrf::field() ?>
                <button class="btn btn-outline-light btn-sm btn-logout" type="submit">
                    <i class="fa-solid fa-right-from-bracket"></i><span class="d-none d-sm-inline">Cerrar Sesión</span>
                </button>
            </form>
        </div>
        <?php endif; ?>
    </header>

    <div class="app-shell">
        <aside class="app-sidebar" id="sidebar">
            <?php if ($isCollaboratorPanel): ?>
                <div class="sidebar-heading">MENÚ PRINCIPAL</div>
                <nav class="sidebar-nav">
                    <a class="nav-link-custom <?= $currentRoute === 'mi-asistencia' ? 'active' : '' ?>" href="<?= e(route_url('mi-asistencia')) ?>" title="Panel de Marcación">
                        <i class="fa-solid fa-fingerprint"></i><span class="link-text">Panel de Marcación</span>
                    </a>
                    <a class="nav-link-custom <?= $currentRoute === 'mi-historial' ? 'active' : '' ?>" href="<?= e(route_url('mi-historial')) ?>" title="Mi Historial">
                        <i class="fa-solid fa-clock-rotate-left"></i><span class="link-text">Mi Historial</span>
                    </a>
                    <a class="nav-link-custom <?= str_starts_with($currentRoute, 'mis-solicitudes') ? 'active' : '' ?>" href="<?= e(route_url('mis-solicitudes')) ?>" title="Mis Solicitudes">
                        <i class="fa-solid fa-file-circle-check"></i><span class="link-text">Mis Solicitudes</span>
                    </a>
                    <a class="nav-link-custom <?= $currentRoute === 'dashboard' ? 'active' : '' ?>" href="<?= e(route_url('dashboard')) ?>" title="Resumen">
                        <i class="fa-solid fa-chart-pie"></i><span class="link-text">Resumen</span>
                    </a>
                </nav>
            <?php else: ?>
                <div class="sidebar-heading">MENÚ PRINCIPAL</div>
                <nav class="sidebar-nav">
                    <a class="nav-link-custom <?= $currentRoute === 'dashboard' ? 'active' : '' ?>" href="<?= e(route_url('dashboard')) ?>" title="Dashboard">
                        <i class="fa-solid fa-chart-line"></i><span class="link-text">Dashboard</span>
                    </a>
                </nav>

                <div class="sidebar-heading">GESTIÓN OPERATIVA</div>
                <nav class="sidebar-nav">
                    <?php if ($canManage): ?>
                        <a class="nav-link-custom <?= str_starts_with($currentRoute, 'colaboradores') ? 'active' : '' ?>" href="<?= e(route_url('colaboradores')) ?>" title="Colaboradores">
                            <i class="fa-solid fa-users"></i><span class="link-text">Colaboradores</span>
                        </a>
                        <a class="nav-link-custom <?= str_starts_with($currentRoute, 'areas') ? 'active' : '' ?>" href="<?= e(route_url('areas')) ?>" title="Áreas">
                            <i class="fa-solid fa-sitemap"></i><span class="link-text">Áreas / Cargos</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($canViewMarks): ?>
                        <a class="nav-link-custom <?= $currentRoute === 'marcaciones' ? 'active' : '' ?>" href="<?= e(route_url('marcaciones')) ?>" title="Asistencias">
                            <i class="fa-solid fa-clock"></i><span class="link-text">Asistencias</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($canViewMarks): ?>
                        <a class="nav-link-custom <?= str_starts_with($currentRoute, 'incidencias') ? 'active' : '' ?>" href="<?= e(route_url('incidencias')) ?>" title="Incidencias">
                            <i class="fa-solid fa-clipboard-check"></i><span class="link-text">Incidencias</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($canManage): ?>
                        <a class="nav-link-custom <?= str_starts_with($currentRoute, 'horarios') ? 'active' : '' ?>" href="<?= e(route_url('horarios')) ?>" title="Horarios">
                            <i class="fa-solid fa-calendar-days"></i><span class="link-text">Horarios</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($canViewReports): ?>
                        <a class="nav-link-custom <?= str_starts_with($currentRoute, 'reportes') ? 'active' : '' ?>" href="<?= e(route_url('reportes')) ?>" title="Reportes">
                            <i class="fa-solid fa-file-lines"></i><span class="link-text">Reportes</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($canUsePersonalAttendance): ?>
                        <a class="nav-link-custom <?= $currentRoute === 'mi-asistencia' ? 'active' : '' ?>" href="<?= e(route_url('mi-asistencia')) ?>" title="Mi asistencia">
                            <i class="fa-solid fa-fingerprint"></i><span class="link-text">Mi asistencia</span>
                        </a>
                    <?php endif; ?>
                </nav>

                <div class="sidebar-heading">SISTEMA</div>
                <div class="sidebar-version">
                    <i class="fa-solid fa-shield-halved"></i><span class="link-text">Workforce360 AI · Fase 6</span>
                </div>
            <?php endif; ?>
        </aside>

        <main class="app-content">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm app-alert" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm app-alert" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>
