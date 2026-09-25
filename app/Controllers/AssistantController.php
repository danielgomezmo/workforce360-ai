<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Services\WorkforceAssistantService;
use Throwable;

final class AssistantController extends Controller
{
    public function query(): never
    {
        Auth::requireLogin();

        $isAdmin = Auth::hasRole('ADMINISTRADOR');
        $isCollaborator = Auth::hasRole('COLABORADOR');

        if (!$isAdmin && !$isCollaborator) {
            $this->json([
                'ok' => false,
                'message' => 'El asistente está habilitado por ahora para Administrador y Colaborador.',
            ], 403);
        }

        if (!Csrf::verify($_POST['_token'] ?? null)) {
            $this->json([
                'ok' => false,
                'message' => 'La sesión de seguridad venció. Recarga la página e inténtalo nuevamente.',
            ], 419);
        }

        $this->enforceRateLimit();

        $message = trim((string) ($_POST['message'] ?? ''));
        if ($message === '') {
            $this->json([
                'ok' => false,
                'message' => 'Escribe una pregunta para poder ayudarte.',
            ], 422);
        }

        if (mb_strlen($message, 'UTF-8') > 500) {
            $this->json([
                'ok' => false,
                'message' => 'La pregunta es demasiado larga. Intenta resumirla en menos de 500 caracteres.',
            ], 422);
        }

        $collaboratorId = $isAdmin ? null : Auth::collaboratorId();
        if (!$isAdmin && $collaboratorId === null) {
            $this->json([
                'ok' => false,
                'message' => 'Tu cuenta no está vinculada a un colaborador. Solicita al administrador revisar tu acceso.',
            ], 403);
        }

        try {
            $assistant = new WorkforceAssistantService();
            $result = $assistant->answer($message, $isAdmin, $collaboratorId);

            $this->json([
                'ok' => true,
                'message' => $result['message'],
                'intent' => $result['intent'] ?? 'general',
                'scope' => $isAdmin ? 'ADMIN' : 'PERSONAL',
            ]);
        } catch (Throwable $e) {
            $message = (bool) config('app.debug', false)
                ? 'Error del asistente: ' . $e->getMessage()
                : 'No pude completar la consulta en este momento. Intenta nuevamente.';

            $this->json([
                'ok' => false,
                'message' => $message,
            ], 500);
        }
    }

    private function enforceRateLimit(): void
    {
        $now = time();
        $window = 60;
        $maxRequests = 30;

        $requests = $_SESSION['_wf_assistant_requests'] ?? [];
        $requests = array_values(array_filter(
            is_array($requests) ? $requests : [],
            static fn ($timestamp): bool => is_int($timestamp) && ($now - $timestamp) < $window
        ));

        if (count($requests) >= $maxRequests) {
            $this->json([
                'ok' => false,
                'message' => 'Has realizado muchas consultas seguidas. Espera unos segundos y vuelve a intentar.',
            ], 429);
        }

        $requests[] = $now;
        $_SESSION['_wf_assistant_requests'] = $requests;
    }
}
