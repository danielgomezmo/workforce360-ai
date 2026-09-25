<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Dashboard;
use PDO;
use Throwable;

final class PredictionHistoryService
{
    public function saveDaily(array $daily): void
    {
        if (($daily['dia_operativo'] ?? false) !== true) {
            return;
        }

        $date = (string) ($daily['fecha'] ?? '');
        if (!$this->isDate($date)) {
            return;
        }

        $value = isset($daily['indice_asistencia_estimado'])
            ? (float) $daily['indice_asistencia_estimado']
            : null;

        $detail = [
            'modelo' => (string) ($daily['modelo'] ?? 'desconocido'),
            'estado_modelo' => (string) ($daily['estado_modelo'] ?? 'EXPERIMENTAL'),
            'programados' => (int) ($daily['programados'] ?? 0),
            'presentes_estimados' => (int) ($daily['presentes_estimados'] ?? 0),
            'ausentes_estimados' => (int) ($daily['ausentes_estimados'] ?? 0),
            'intervalo_aprox_90' => $daily['intervalo_aprox_90'] ?? null,
            'error_mae_holdout' => isset($daily['error_mae_holdout']) ? (float) $daily['error_mae_holdout'] : null,
            'supera_baseline_holdout' => (bool) ($daily['supera_baseline_holdout'] ?? false),
            'supera_baseline_temporal' => (bool) ($daily['supera_baseline_temporal'] ?? false),
            'factores_contexto' => is_array($daily['factores_contexto'] ?? null)
                ? $daily['factores_contexto']
                : [],
        ];

        $this->upsert(
            'ASISTENCIA_DIARIA',
            $date,
            null,
            $value,
            'PORCENTAJE',
            null,
            $detail,
            (string) ($daily['version_modelo'] ?? 'desconocida')
        );
    }

    public function saveWeekly(array $weekly): void
    {
        $detailRows = is_array($weekly['detalle'] ?? null) ? $weekly['detalle'] : [];
        if ($detailRows === []) {
            return;
        }

        $first = $detailRows[0] ?? [];
        $last = $detailRows[count($detailRows) - 1] ?? [];
        $start = (string) ($first['fecha'] ?? '');
        $end = (string) ($last['fecha'] ?? '');

        if (!$this->isDate($start) || !$this->isDate($end)) {
            return;
        }

        $version = (string) ($first['version_modelo'] ?? 'desconocida');
        $detail = [
            'dias_operativos' => (int) ($weekly['dias_operativos'] ?? count($detailRows)),
            'programados_acumulados' => (int) ($weekly['programados_acumulados'] ?? 0),
            'presentes_estimados_acumulados' => (int) ($weekly['presentes_estimados_acumulados'] ?? 0),
            'ausentes_estimados_acumulados' => (int) ($weekly['ausentes_estimados_acumulados'] ?? 0),
            'detalle' => $detailRows,
        ];

        $this->upsert(
            'DISPONIBILIDAD_SEMANAL',
            $start,
            $end,
            isset($weekly['asistencia_promedio_estimada']) ? (float) $weekly['asistencia_promedio_estimada'] : null,
            'PORCENTAJE',
            null,
            $detail,
            $version
        );
    }

    public function dailyHistory(int $limit = 15): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = Database::connection()->query(
            "SELECT id_pronostico, tipo, fecha_generacion, fecha_objetivo_inicio, fecha_objetivo_fin,
                    valor_estimado, unidad, confianza, detalle, version_modelo
             FROM pronosticos
             WHERE tipo = 'ASISTENCIA_DIARIA'
               AND id_area IS NULL
               AND id_equipo IS NULL
             ORDER BY fecha_objetivo_inicio DESC, fecha_generacion DESC, id_pronostico DESC
             LIMIT 150"
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $unique = [];

        foreach ($rows as $row) {
            $date = (string) ($row['fecha_objetivo_inicio'] ?? '');
            if (!$this->isDate($date) || isset($unique[$date])) {
                continue;
            }
            $unique[$date] = $this->hydrateDailyRow($row);
            if (count($unique) >= $limit) {
                break;
            }
        }

        return array_values($unique);
    }

    public function summary(?array $history = null): array
    {
        $history ??= $this->dailyHistory(50);
        $evaluated = array_values(array_filter(
            $history,
            static fn (array $row): bool => ($row['estado_comparacion'] ?? '') === 'EVALUADO'
        ));

        $errors = array_map(
            static fn (array $row): float => (float) ($row['error_absoluto_pp'] ?? 0),
            $evaluated
        );

        $pending = count(array_filter(
            $history,
            static fn (array $row): bool => in_array(($row['estado_comparacion'] ?? ''), ['FUTURO', 'EN_CURSO'], true)
        ));

        return [
            'guardados' => count($history),
            'evaluados' => count($evaluated),
            'pendientes' => $pending,
            'error_medio_pp' => $errors === [] ? null : round(array_sum($errors) / count($errors), 2),
        ];
    }

    public function comparisonForDate(string $date): ?array
    {
        if (!$this->isDate($date)) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            "SELECT id_pronostico, tipo, fecha_generacion, fecha_objetivo_inicio, fecha_objetivo_fin,
                    valor_estimado, unidad, confianza, detalle, version_modelo
             FROM pronosticos
             WHERE tipo = 'ASISTENCIA_DIARIA'
               AND fecha_objetivo_inicio = :fecha
               AND id_area IS NULL
               AND id_equipo IS NULL
             ORDER BY fecha_generacion DESC, id_pronostico DESC
             LIMIT 1"
        );
        $stmt->execute(['fecha' => $date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateDailyRow($row) : null;
    }

    private function hydrateDailyRow(array $row): array
    {
        $date = (string) ($row['fecha_objetivo_inicio'] ?? '');
        $detail = json_decode((string) ($row['detalle'] ?? ''), true);
        $detail = is_array($detail) ? $detail : [];

        $result = [
            'id_pronostico' => (int) ($row['id_pronostico'] ?? 0),
            'fecha_generacion' => (string) ($row['fecha_generacion'] ?? ''),
            'fecha' => $date,
            'prediccion' => isset($row['valor_estimado']) ? round((float) $row['valor_estimado'], 2) : null,
            'version_modelo' => (string) ($row['version_modelo'] ?? ''),
            'programados_estimados' => (int) ($detail['programados'] ?? 0),
            'presentes_estimados' => (int) ($detail['presentes_estimados'] ?? 0),
            'ausentes_estimados' => (int) ($detail['ausentes_estimados'] ?? 0),
            'real' => null,
            'programados_reales' => null,
            'presentes_reales' => null,
            'ausentes_reales' => null,
            'error_absoluto_pp' => null,
            'estado_comparacion' => 'FUTURO',
        ];

        $today = date('Y-m-d');
        if ($date > $today) {
            return $result;
        }

        if ($date === $today) {
            $result['estado_comparacion'] = 'EN_CURSO';
            return $result;
        }

        try {
            $actual = Dashboard::operationalMetrics($date);
            if ((int) ($actual['programados'] ?? 0) <= 0) {
                $result['estado_comparacion'] = 'SIN_DATOS';
                return $result;
            }

            $real = round((float) ($actual['indice_asistencia'] ?? 0), 2);
            $pred = (float) ($result['prediccion'] ?? 0);

            $result['real'] = $real;
            $result['programados_reales'] = (int) ($actual['programados'] ?? 0);
            $result['presentes_reales'] = (int) ($actual['presentes'] ?? 0);
            $result['ausentes_reales'] = (int) ($actual['ausentes'] ?? 0);
            $result['error_absoluto_pp'] = round(abs($pred - $real), 2);
            $result['estado_comparacion'] = 'EVALUADO';
        } catch (Throwable) {
            $result['estado_comparacion'] = 'SIN_DATOS';
        }

        return $result;
    }

    private function upsert(
        string $type,
        string $start,
        ?string $end,
        ?float $value,
        string $unit,
        ?float $confidence,
        array $detail,
        string $version
    ): void {
        $db = Database::connection();
        $json = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $endCondition = $end === null
            ? 'fecha_objetivo_fin IS NULL'
            : 'fecha_objetivo_fin = :fin';

        $find = $db->prepare(
            "SELECT id_pronostico
             FROM pronosticos
             WHERE tipo = :tipo
               AND fecha_objetivo_inicio = :inicio
               AND {$endCondition}
               AND id_area IS NULL
               AND id_equipo IS NULL
               AND COALESCE(version_modelo, '') = :version
             ORDER BY id_pronostico DESC
             LIMIT 1"
        );
        $findParams = [
            'tipo' => $type,
            'inicio' => $start,
            'version' => $version,
        ];
        if ($end !== null) {
            $findParams['fin'] = $end;
        }
        $find->execute($findParams);
        $id = $find->fetchColumn();

        if ($id !== false) {
            $update = $db->prepare(
                "UPDATE pronosticos
                 SET fecha_generacion = NOW(),
                     valor_estimado = :valor,
                     unidad = :unidad,
                     confianza = :confianza,
                     detalle = :detalle,
                     version_modelo = :version
                 WHERE id_pronostico = :id"
            );
            $update->execute([
                'valor' => $value,
                'unidad' => $unit,
                'confianza' => $confidence,
                'detalle' => $json,
                'version' => $version,
                'id' => (int) $id,
            ]);
            return;
        }

        $insert = $db->prepare(
            "INSERT INTO pronosticos
                (tipo, fecha_generacion, fecha_objetivo_inicio, fecha_objetivo_fin,
                 id_area, id_equipo, valor_estimado, unidad, confianza, detalle, version_modelo)
             VALUES
                (:tipo, NOW(), :inicio, :fin, NULL, NULL, :valor, :unidad, :confianza, :detalle, :version)"
        );
        $insert->execute([
            'tipo' => $type,
            'inicio' => $start,
            'fin' => $end,
            'valor' => $value,
            'unidad' => $unit,
            'confianza' => $confidence,
            'detalle' => $json,
            'version' => $version,
        ]);
    }

    private function isDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $time = strtotime($date);
        return $time !== false && date('Y-m-d', $time) === $date;
    }
}
