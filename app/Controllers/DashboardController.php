<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Incidencia;
use App\Models\Marcacion;
use Throwable;

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $isCollaboratorPanel = Auth::hasRole('COLABORADOR')
            && !Auth::hasAnyRole(['ADMINISTRADOR', 'RRHH', 'SUPERVISOR', 'GERENCIA']);

        if ($isCollaboratorPanel) {
            $collaborator = null;
            $personalStats = [
                'total' => 0,
                'puntuales' => 0,
                'tardanzas' => 0,
                'minutos_tardanza' => 0,
                'minutos_trabajados' => 0,
                'solicitudes_pendientes' => 0,
            ];
            $recent = [];
            try {
                $userId = Auth::id();
                $collaborator = $userId ? Marcacion::collaboratorForUser((int)$userId) : null;
                if ($collaborator) {
                    $id = (int)$collaborator['id_colaborador'];
                    $personalStats = array_merge($personalStats, Marcacion::monthlyStatsForCollaborator($id));
                    $personalStats['solicitudes_pendientes'] = Incidencia::pendingCountForCollaborator($id);
                    $recent = Marcacion::recentForCollaborator($id, 5);
                }
            } catch (Throwable) {
                // El resumen personal sigue cargando aunque falte una migración.
            }

            $this->view('dashboard/index', [
                'user' => Auth::user(),
                'isCollaboratorPanel' => true,
                'colaborador' => $collaborator,
                'personalStats' => $personalStats,
                'recent' => $recent,
                'stats' => [],
            ]);
            return;
        }

        $stats = [
            'colaboradores' => 0,
            'activos' => 0,
            'areas' => 0,
            'horarios' => 0,
            'marcaciones_hoy' => 0,
            'tardanzas_hoy' => 0,
            'incidencias_pendientes' => 0,
        ];

        try {
            $db = Database::connection();
            $stats['colaboradores'] = (int) $db->query('SELECT COUNT(*) FROM colaboradores')->fetchColumn();
            $stats['activos'] = (int) $db->query("SELECT COUNT(*) FROM colaboradores WHERE estado = 'ACTIVO'")->fetchColumn();
            $stats['areas'] = (int) $db->query('SELECT COUNT(*) FROM areas WHERE activo = 1')->fetchColumn();
            $stats['horarios'] = (int) $db->query('SELECT COUNT(*) FROM horarios WHERE activo = 1')->fetchColumn();
            $stats['marcaciones_hoy'] = (int) $db->query('SELECT COUNT(*) FROM marcaciones WHERE fecha = CURDATE()')->fetchColumn();
            $stats['tardanzas_hoy'] = (int) $db->query("SELECT COUNT(*) FROM marcaciones WHERE fecha = CURDATE() AND minutos_tardanza > 0")->fetchColumn();
            $stats['incidencias_pendientes'] = (int) $db->query("SELECT COUNT(*) FROM incidencias i INNER JOIN tipos_incidencia t ON t.id_tipo_incidencia = i.id_tipo_incidencia WHERE i.estado = 'PENDIENTE' OR (i.estado = 'REGISTRADA' AND t.requiere_aprobacion = 1)")->fetchColumn();
        } catch (Throwable) {
            // El dashboard sigue cargando aunque la base aún no haya sido actualizada.
        }

        $this->view('dashboard/index', [
            'user' => Auth::user(),
            'isCollaboratorPanel' => false,
            'stats' => $stats,
            'colaborador' => null,
            'personalStats' => [],
            'recent' => [],
        ]);
    }
}
