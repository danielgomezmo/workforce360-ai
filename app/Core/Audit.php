<?php
namespace App\Core;

use Throwable;

final class Audit
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_hash',
        'contrasena',
        'contraseña',
        'token',
        '_token',
        'api_key',
        'apikey',
        'secret',
    ];

    public static function log(
        string $accion,
        string $entidad,
        ?int $entidadId = null,
        ?array $anterior = null,
        ?array $nuevo = null,
        ?string $motivo = null,
        ?int $usuarioId = null
    ): void {
        try {
            $anterior = $anterior !== null ? self::sanitize($anterior) : null;
            $nuevo = $nuevo !== null ? self::sanitize($nuevo) : null;

            $sql = 'INSERT INTO auditoria
                (id_usuario, accion, entidad, entidad_id, datos_anteriores, datos_nuevos, motivo, ip_origen, user_agent, fecha_evento)
                VALUES
                (:id_usuario, :accion, :entidad, :entidad_id, :anterior, :nuevo, :motivo, :ip, :agent, NOW())';

            $stmt = Database::connection()->prepare($sql);
            $stmt->execute([
                'id_usuario' => $usuarioId,
                'accion' => strtoupper(trim($accion)),
                'entidad' => strtolower(trim($entidad)),
                'entidad_id' => $entidadId,
                'anterior' => $anterior ? json_encode($anterior, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'nuevo' => $nuevo ? json_encode($nuevo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'motivo' => $motivo !== null ? mb_substr(trim($motivo), 0, 255) : null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (Throwable) {
            // La auditoría no debe impedir el flujo principal de la operación auditada.
        }
    }

    private static function sanitize(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $normalized = strtolower((string) $key);

            if (in_array($normalized, self::SENSITIVE_KEYS, true)) {
                $clean[$key] = '[PROTEGIDO]';
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = self::sanitize($value);
                continue;
            }

            if (is_string($value) && mb_strlen($value) > 2000) {
                $clean[$key] = mb_substr($value, 0, 2000) . '…';
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }
}
