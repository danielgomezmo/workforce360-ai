<?php
namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;

final class AttendanceRuleEngine
{
    public static function scheduleWindow(array $horario, string $workDate): array
    {
        $start = new DateTimeImmutable($workDate . ' ' . $horario['hora_inicio']);
        $end = new DateTimeImmutable($workDate . ' ' . $horario['hora_fin']);

        if ((int) ($horario['cruza_medianoche'] ?? 0) === 1 || $end <= $start) {
            $end = $end->modify('+1 day');
        }

        $tolerance = max(0, (int) ($horario['minutos_tolerancia'] ?? 0));
        $toleranceEnd = $start->modify('+' . $tolerance . ' minutes');

        return [
            'inicio' => $start,
            'fin' => $end,
            'fin_tolerancia' => $toleranceEnd,
            'tolerancia_minutos' => $tolerance,
        ];
    }

    public static function evaluateEntry(array $horario, string $workDate, DateTimeImmutable $entry): array
    {
        $window = self::scheduleWindow($horario, $workDate);
        $late = $entry > $window['fin_tolerancia'];
        $lateMinutes = $late ? max(0, intdiv($entry->getTimestamp() - $window['inicio']->getTimestamp(), 60)) : 0;

        return [
            'resultado' => $late ? 'TARDANZA' : 'PUNTUAL',
            'minutos_tardanza' => $lateMinutes,
            'estado' => $late ? 'TARDANZA' : 'PUNTUAL',
            'inicio_programado' => $window['inicio']->format('Y-m-d H:i:s'),
            'fin_programado' => $window['fin']->format('Y-m-d H:i:s'),
            'tolerancia_minutos' => $window['tolerancia_minutos'],
        ];
    }

    public static function evaluateExit(array $mark, DateTimeImmutable $exit): array
    {
        if (empty($mark['hora_entrada']) || empty($mark['salida_programada'])) {
            throw new InvalidArgumentException('La marcación no tiene datos suficientes para calcular la salida.');
        }

        $entry = new DateTimeImmutable($mark['hora_entrada']);
        $scheduledEnd = new DateTimeImmutable($mark['salida_programada']);
        if ($exit < $entry) {
            throw new InvalidArgumentException('La salida no puede ser anterior al ingreso.');
        }

        $earlySeconds = max(0, $scheduledEnd->getTimestamp() - $exit->getTimestamp());
        $earlyMinutes = intdiv($earlySeconds, 60);
        $grossMinutes = max(0, intdiv($exit->getTimestamp() - $entry->getTimestamp(), 60));
        $breakMinutes = max(0, (int) round(((float) ($mark['horas_descanso_programadas'] ?? 0)) * 60));
        $workedMinutes = max(0, $grossMinutes - $breakMinutes);
        $early = $earlyMinutes > 0;

        return [
            'resultado' => $early ? 'ANTICIPADA' : 'A_TIEMPO',
            'minutos_salida_anticipada' => $earlyMinutes,
            'minutos_trabajados' => $workedMinutes,
            'estado' => $early ? 'PARCIAL' : 'COMPLETA',
        ];
    }
}
