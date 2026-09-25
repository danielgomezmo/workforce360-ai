<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\AiPredictionService;
use App\Services\PredictionHistoryService;
use Throwable;

class AiController extends Controller
{
    public function index(): void
    {
        Auth::requireAnyRole([
            'ADMINISTRADOR',
            'RRHH',
            'SUPERVISOR',
            'GERENCIA',
        ]);

        $dailyDate = (string) ($_GET['fecha'] ?? '');
        if ($dailyDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dailyDate)) {
            $dailyDate = '';
        }

        $days = (int) ($_GET['dias'] ?? 7);
        $days = max(1, min(14, $days));

        $payload = [
            'apiAvailable' => false,
            'apiError' => null,
            'historyError' => null,
            'dataset' => [],
            'model' => [],
            'evaluation' => [],
            'daily' => [],
            'weekly' => [],
            'predictionHistory' => [],
            'predictionSummary' => [
                'guardados' => 0,
                'evaluados' => 0,
                'pendientes' => 0,
                'error_medio_pp' => null,
            ],
        ];

        try {
            $service = new AiPredictionService();

            $health = $service->health();
            $payload['apiAvailable'] = (bool) ($health['ok'] ?? false);

            if (!$payload['apiAvailable']) {
                $payload['apiError'] = $health['error'] ?? 'El microservicio de IA no está disponible.';
            } else {
                $dataset = $service->datasetStatus();
                $model = $service->modelStatus();
                $evaluation = $service->evaluationStatus();
                $daily = $service->daily($dailyDate !== '' ? $dailyDate : null);
                $weekly = $service->weekly($days);

                $payload['dataset'] = $dataset['ok'] ? ($dataset['data'] ?? []) : [];
                $payload['model'] = $model['ok'] ? ($model['data'] ?? []) : [];
                $payload['evaluation'] = $evaluation['ok'] ? ($evaluation['data'] ?? []) : [];
                $payload['daily'] = $daily['ok'] ? ($daily['data'] ?? []) : [];
                $payload['weekly'] = $weekly['ok'] ? ($weekly['data'] ?? []) : [];

                foreach ([$dataset, $model, $evaluation, $daily, $weekly] as $response) {
                    if (!($response['ok'] ?? false) && $payload['apiError'] === null) {
                        $payload['apiError'] = $response['error'] ?? 'No fue posible completar una consulta al servicio de IA.';
                    }
                }

                try {
                    $history = new PredictionHistoryService();

                    if (($daily['ok'] ?? false) && is_array($daily['data'] ?? null)) {
                        $history->saveDaily($daily['data']);
                    }

                    if (($weekly['ok'] ?? false) && is_array($weekly['data'] ?? null)) {
                        $history->saveWeekly($weekly['data']);
                    }

                    $payload['predictionHistory'] = $history->dailyHistory(15);
                    $payload['predictionSummary'] = $history->summary($payload['predictionHistory']);
                } catch (Throwable $historyException) {
                    $payload['historyError'] = (bool) config('app.debug', false)
                        ? 'No se pudo actualizar el historial de pronósticos: ' . $historyException->getMessage()
                        : 'No se pudo actualizar el historial de pronósticos.';
                }
            }
        } catch (Throwable $e) {
            $payload['apiError'] = (bool) config('app.debug', false)
                ? $e->getMessage()
                : 'No fue posible consultar el servicio de IA.';
        }

        // El historial puede consultarse incluso si FastAPI está temporalmente apagado.
        if ($payload['predictionHistory'] === []) {
            try {
                $history = new PredictionHistoryService();
                $payload['predictionHistory'] = $history->dailyHistory(15);
                $payload['predictionSummary'] = $history->summary($payload['predictionHistory']);
            } catch (Throwable $historyException) {
                if ($payload['historyError'] === null) {
                    $payload['historyError'] = (bool) config('app.debug', false)
                        ? 'No se pudo leer el historial de pronósticos: ' . $historyException->getMessage()
                        : 'No se pudo leer el historial de pronósticos.';
                }
            }
        }

        $this->view('ai/index', [
            'user' => Auth::user(),
            'selectedDate' => $dailyDate,
            'selectedDays' => $days,
            ...$payload,
        ]);
    }
}
