<?php

namespace App\Services;

final class AiPredictionService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('ai.base_url', 'http://127.0.0.1:8000'), '/');
        $this->timeout = max(1, (int) config('ai.timeout', 6));
    }

    public function health(): array
    {
        return $this->get('/health');
    }

    public function datasetStatus(): array
    {
        return $this->get('/dataset/status');
    }

    public function modelStatus(): array
    {
        return $this->get('/model/status');
    }

    public function evaluationStatus(): array
    {
        return $this->get('/evaluation/status');
    }

    public function daily(?string $date = null): array
    {
        $query = [];
        if ($date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $query['fecha'] = $date;
        }

        return $this->get('/forecast/daily', $query);
    }

    public function weekly(int $days = 7, ?string $from = null): array
    {
        $query = [
            'dias' => max(1, min(31, $days)),
        ];

        if ($from !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $query['desde'] = $from;
        }

        return $this->get('/forecast/weekly', $query);
    }

    private function get(string $path, array $query = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        if (function_exists('curl_init')) {
            return $this->getWithCurl($url);
        }

        return $this->getWithStream($url);
    }

    private function getWithCurl(string $url): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return $this->failure('No fue posible inicializar cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false || $error !== '') {
            return $this->failure('No se pudo conectar con el servicio de IA: ' . $error, $status);
        }

        return $this->decode($body, $status);
    }

    private function getWithStream(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\n",
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return $this->failure('No se pudo conectar con el servicio de IA.');
        }

        $status = 200;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $status = (int) $match[1];
        }

        return $this->decode($body, $status);
    }

    private function decode(string $body, int $status): array
    {
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            return $this->failure('El servicio de IA devolvió una respuesta no válida.', $status);
        }

        if ($status < 200 || $status >= 300) {
            $detail = $decoded['detail'] ?? 'Error devuelto por el servicio de IA.';
            return $this->failure((string) $detail, $status, $decoded);
        }

        return [
            'ok' => true,
            'status' => $status,
            'data' => $decoded,
            'error' => null,
        ];
    }

    private function failure(string $message, int $status = 0, array $raw = []): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'data' => $raw,
            'error' => $message,
        ];
    }
}
