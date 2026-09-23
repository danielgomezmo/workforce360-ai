<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Dashboard;
use App\Models\Incidencia;
use App\Models\Marcacion;
use Throwable;

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $isCollaboratorPanel =
            Auth::hasRole('COLABORADOR')
            &&
            !Auth::hasAnyRole([
                'ADMINISTRADOR',
                'RRHH',
                'SUPERVISOR',
                'GERENCIA'
            ]);

        /*
        |--------------------------------------------------------------------------
        | PANEL DEL COLABORADOR
        |--------------------------------------------------------------------------
        */

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

                $collaborator =
                    $userId
                    ? Marcacion::collaboratorForUser(
                        (int) $userId
                    )
                    : null;

                if ($collaborator) {

                    $id =
                        (int) $collaborator[
                            'id_colaborador'
                        ];

                    $personalStats =
                        array_merge(
                            $personalStats,
                            Marcacion::
                            monthlyStatsForCollaborator(
                                $id
                            )
                        );

                    $personalStats[
                        'solicitudes_pendientes'
                    ] =
                        Incidencia::
                        pendingCountForCollaborator(
                            $id
                        );

                    $recent =
                        Marcacion::
                        recentForCollaborator(
                            $id,
                            5
                        );
                }

            } catch (Throwable) {
            }

            $this->view(
                'dashboard/index',
                [
                    'user' => Auth::user(),

                    'isCollaboratorPanel' => true,

                    'colaborador' =>
                        $collaborator,

                    'personalStats' =>
                        $personalStats,

                    'recent' =>
                        $recent,

                    'operational' => [],

                    'selectedDate' =>
                        date('Y-m-d'),
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD OPERATIVO
        |--------------------------------------------------------------------------
        */

        $selectedDate =
            (string) (
                $_GET['fecha']
                ??
                date('Y-m-d')
            );

        if (
            !preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $selectedDate
            )
        ) {
            $selectedDate = date('Y-m-d');
        }

        $operational = [
            'programados' => 0,
            'presentes' => 0,
            'ausentes' => 0,

            'faltas_por_validar' => 0,

            'puntuales' => 0,
            'tardanzas' => 0,

            'salidas_anticipadas' => 0,

            'vacaciones' => 0,
            'descansos_medicos' => 0,
            'licencias' => 0,
            'permisos' => 0,

            'minutos_tardanza' => 0,

            'minutos_salida_anticipada' => 0,

            'minutos_programados' => 0,

            'minutos_trabajados' => 0,

            'indice_asistencia' => 0,

            'indice_puntualidad' => 0,

            'indice_tardanza' => 0,

            'cumplimiento_jornada' => 0,

            'absentismo' => 0,
        ];

        try {

            /*
             * Si es supervisor:
             * solo verá indicadores de su equipo.
             */

            $isSupervisorOnly =
                Auth::hasRole('SUPERVISOR')
                &&
                !Auth::hasAnyRole([
                    'ADMINISTRADOR',
                    'RRHH',
                    'GERENCIA'
                ]);

            $supervisorId =
                $isSupervisorOnly
                ? (Auth::collaboratorId() ?? -1)
                : null;

            $operational =
                array_merge(
                    $operational,

                    Dashboard::
                    operationalMetrics(
                        $selectedDate,
                        $supervisorId
                    )
                );

        } catch (Throwable $e) {

            if (
                (bool)
                config(
                    'app.debug',
                    false
                )
            ) {

                flash(
                    'error',

                    'No se pudieron calcular '
                    . 'los indicadores: '
                    . $e->getMessage()
                );
            }
        }

        $this->view(
            'dashboard/index',
            [
                'user' =>
                    Auth::user(),

                'isCollaboratorPanel' =>
                    false,

                'operational' =>
                    $operational,

                'selectedDate' =>
                    $selectedDate,

                'colaborador' =>
                    null,

                'personalStats' =>
                    [],

                'recent' =>
                    [],
            ]
        );
    }
}