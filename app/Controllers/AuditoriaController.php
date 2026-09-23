<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Auditoria;

class AuditoriaController extends Controller
{
    private function guard(): void
    {
        Auth::requireAnyRole(['ADMINISTRADOR']);
    }

    public function index(): void
    {
        $this->guard();

        $filters = $this->filters();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Auditoria::search($filters, $page, 25);

        $this->view('auditoria/index', [
            'title' => 'Auditoría | Workforce360 AI',
            'filters' => $filters,
            'result' => $result,
            'summary' => Auditoria::summary(),
            'catalogs' => Auditoria::catalogs(),
        ]);
    }

    public function detail(): void
    {
        $this->guard();

        $id = (int) ($_GET['id'] ?? 0);
        $event = $id > 0 ? Auditoria::find($id) : null;

        if (!$event) {
            flash('error', 'Registro de auditoría no encontrado.');
            $this->redirect('auditoria');
        }

        $this->view('auditoria/detalle', [
            'title' => 'Detalle de auditoría | Workforce360 AI',
            'event' => $event,
            'before' => Auditoria::decodeJson($event['datos_anteriores'] ?? null),
            'after' => Auditoria::decodeJson($event['datos_nuevos'] ?? null),
        ]);
    }

    public function exportCsv(): void
    {
        $this->guard();

        $filters = $this->filters();
        $rows = Auditoria::allForExport($filters);
        $filename = 'Auditoria_Workforce360_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            exit;
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, [
            'ID',
            'Fecha y hora',
            'Usuario',
            'Username',
            'Rol',
            'Acción',
            'Módulo/Entidad',
            'Registro',
            'IP',
            'Motivo',
        ], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id_auditoria'],
                $row['fecha_evento'],
                $row['usuario_nombre'],
                $row['username'] ?? '',
                $row['roles'],
                $row['accion'],
                $row['entidad'],
                $row['entidad_id'] ?? '',
                $row['ip_origen'] ?? '',
                $row['motivo'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

    private function filters(): array
    {
        $desde = trim((string) ($_GET['desde'] ?? date('Y-m-01')));
        $hasta = trim((string) ($_GET['hasta'] ?? date('Y-m-d')));

        if (!$this->validDate($desde)) {
            $desde = date('Y-m-01');
        }
        if (!$this->validDate($hasta)) {
            $hasta = date('Y-m-d');
        }
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'usuario' => max(0, (int) ($_GET['usuario'] ?? 0)),
            'accion' => strtoupper(trim((string) ($_GET['accion'] ?? ''))),
            'entidad' => strtolower(trim((string) ($_GET['entidad'] ?? ''))),
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];
    }

    private function validDate(string $date): bool
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt !== false && $dt->format('Y-m-d') === $date;
    }
}
