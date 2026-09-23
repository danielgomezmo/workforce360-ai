<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Reporte;
use DateTimeImmutable;

class ReporteController extends Controller
{
    private const ROLES = ['ADMINISTRADOR', 'RRHH', 'SUPERVISOR', 'GERENCIA'];
    private const INCIDENT_STATES = ['REGISTRADA', 'PENDIENTE', 'APROBADA', 'RECHAZADA', 'ANULADA'];

    public function index(): void
    {
        Auth::requireAnyRole(self::ROLES);
        $this->view('reportes/index', [
            'user' => Auth::user(),
        ]);
    }

    public function attendance(): void
    {
        Auth::requireAnyRole(self::ROLES);
        $context = $this->commonContext(['PUNTUAL', 'TARDANZA', 'COMPLETA', 'PARCIAL']);

        $rows = Reporte::attendance($context['filters'], $context['supervisorId']);
        $summary = Reporte::attendanceSummary($rows);

        $this->view('reportes/asistencia', array_merge($context, [
            'rows' => $rows,
            'summary' => $summary,
        ]));
    }

    public function lateness(): void
    {
        Auth::requireAnyRole(self::ROLES);
        $context = $this->commonContext([]);

        $rows = Reporte::lateness($context['filters'], $context['supervisorId']);
        $summary = Reporte::latenessSummary($rows);

        $this->view('reportes/tardanzas', array_merge($context, [
            'rows' => $rows,
            'summary' => $summary,
        ]));
    }

    public function earlyDepartures(): void
    {
        Auth::requireAnyRole(self::ROLES);
        $context = $this->commonContext([]);

        $rows = Reporte::earlyDepartures($context['filters'], $context['supervisorId']);
        $summary = Reporte::earlyDepartureSummary($rows);

        $this->view('reportes/salidas_anticipadas', array_merge($context, [
            'rows' => $rows,
            'summary' => $summary,
        ]));
    }

    public function incidents(): void
    {
        Auth::requireAnyRole(self::ROLES);
        $context = $this->commonContext([]);

        $filters = $context['filters'];
        $filters['tipo'] = isset($_GET['tipo']) && ctype_digit((string) $_GET['tipo'])
            ? (int) $_GET['tipo']
            : null;
        $filters['estado'] = in_array((string) ($_GET['estado'] ?? ''), self::INCIDENT_STATES, true)
            ? (string) $_GET['estado']
            : '';

        $rows = Reporte::incidents($filters, $context['supervisorId']);
        $summary = Reporte::incidentSummary($rows);

        $this->view('reportes/incidencias', array_merge($context, [
            'filters' => $filters,
            'rows' => $rows,
            'summary' => $summary,
            'incidentTypes' => Reporte::incidentTypes(),
        ]));
    }

    public function workedHours(): void
    {
        Auth::requireAnyRole(self::ROLES);
        $context = $this->commonContext(['COMPLETA', 'PARCIAL']);

        $rows = Reporte::workedHours($context['filters'], $context['supervisorId']);
        $summary = Reporte::workedHoursSummary($rows);

        $this->view('reportes/horas_trabajadas', array_merge($context, [
            'rows' => $rows,
            'summary' => $summary,
        ]));
    }

    private function commonContext(array $allowedStatuses): array
    {
        $defaultFrom = date('Y-m-01');
        $defaultTo = date('Y-m-d');
        $from = $this->validDate((string) ($_GET['desde'] ?? $defaultFrom), $defaultFrom);
        $to = $this->validDate((string) ($_GET['hasta'] ?? $defaultTo), $defaultTo);

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $status = (string) ($_GET['estado'] ?? '');
        if ($status !== '' && !in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $filters = [
            'desde' => $from,
            'hasta' => $to,
            'colaborador' => isset($_GET['colaborador']) && ctype_digit((string) $_GET['colaborador'])
                ? (int) $_GET['colaborador']
                : null,
            'area' => isset($_GET['area']) && ctype_digit((string) $_GET['area'])
                ? (int) $_GET['area']
                : null,
            'estado' => $status,
        ];

        $isSupervisorOnly = Auth::hasRole('SUPERVISOR')
            && !Auth::hasAnyRole(['ADMINISTRADOR', 'RRHH', 'GERENCIA']);
        $supervisorId = $isSupervisorOnly ? (Auth::collaboratorId() ?? -1) : null;

        return [
            'filters' => $filters,
            'supervisorId' => $supervisorId,
            'collaborators' => Reporte::collaborators($supervisorId),
            'areas' => Reporte::areas($supervisorId),
            'generatedAt' => new DateTimeImmutable('now'),
        ];
    }

    private function validDate(string $value, string $fallback): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
    }
}
