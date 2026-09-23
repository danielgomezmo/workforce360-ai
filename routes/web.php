<?php
use App\Controllers\ApiController;
use App\Controllers\AreaController;
use App\Controllers\AuthController;
use App\Controllers\ColaboradorController;
use App\Controllers\DashboardController;
use App\Controllers\HorarioController;
use App\Controllers\MarcacionController;
use App\Controllers\IncidenciaController;
use App\Controllers\ReporteController;
use App\Controllers\AuditoriaController;

$router->get('login', [AuthController::class, 'showLogin']);
$router->post('login', [AuthController::class, 'login']);
$router->post('logout', [AuthController::class, 'logout']);

$router->get('dashboard', [DashboardController::class, 'index']);

$router->get('areas', [AreaController::class, 'index']);
$router->get('areas/nuevo', [AreaController::class, 'create']);
$router->post('areas/guardar', [AreaController::class, 'store']);
$router->get('areas/editar', [AreaController::class, 'edit']);
$router->post('areas/actualizar', [AreaController::class, 'update']);
$router->post('areas/eliminar', [AreaController::class, 'delete']);

$router->get('horarios', [HorarioController::class, 'index']);
$router->get('horarios/nuevo', [HorarioController::class, 'create']);
$router->post('horarios/guardar', [HorarioController::class, 'store']);
$router->get('horarios/editar', [HorarioController::class, 'edit']);
$router->post('horarios/actualizar', [HorarioController::class, 'update']);
$router->post('horarios/eliminar', [HorarioController::class, 'delete']);

$router->get('colaboradores', [ColaboradorController::class, 'index']);
$router->get('colaboradores/nuevo', [ColaboradorController::class, 'create']);
$router->post('colaboradores/guardar', [ColaboradorController::class, 'store']);
$router->get('colaboradores/editar', [ColaboradorController::class, 'edit']);
$router->post('colaboradores/actualizar', [ColaboradorController::class, 'update']);
$router->post('colaboradores/eliminar', [ColaboradorController::class, 'delete']);

$router->get('mi-asistencia', [MarcacionController::class, 'mine']);
$router->get('mi-historial', [MarcacionController::class, 'history']);
$router->post('marcaciones/entrada', [MarcacionController::class, 'entry']);
$router->post('marcaciones/salida', [MarcacionController::class, 'exit']);
$router->get('marcaciones', [MarcacionController::class, 'index']);

$router->get('mis-solicitudes', [IncidenciaController::class, 'mine']);
$router->post('mis-solicitudes/guardar', [IncidenciaController::class, 'storeMine']);
$router->get('incidencias', [IncidenciaController::class, 'index']);
$router->get('incidencias/nueva', [IncidenciaController::class, 'create']);
$router->post('incidencias/guardar', [IncidenciaController::class, 'store']);
$router->post('incidencias/revisar', [IncidenciaController::class, 'review']);
$router->post('incidencias/anular', [IncidenciaController::class, 'annul']);

$router->get('reportes', [ReporteController::class, 'index']);
$router->get('reportes/asistencia', [ReporteController::class, 'attendance']);
$router->get('reportes/tardanzas', [ReporteController::class, 'lateness']);
$router->get('reportes/salidas-anticipadas', [ReporteController::class, 'earlyDepartures']);
$router->get('reportes/incidencias', [ReporteController::class, 'incidents']);
$router->get('reportes/horas-trabajadas', [ReporteController::class, 'workedHours']);

$router->get('auditoria', [AuditoriaController::class, 'index']);
$router->get('auditoria/detalle', [AuditoriaController::class, 'detail']);
$router->get('auditoria/exportar', [AuditoriaController::class, 'exportCsv']);

$router->get('api/status', [ApiController::class, 'status']);
