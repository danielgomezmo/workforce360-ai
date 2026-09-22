<?php
namespace App\Core;

use Throwable;

final class Audit
{
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
            $sql = 'INSERT INTO auditoria
                (id_usuario, accion, entidad, entidad_id, datos_anteriores, datos_nuevos, motivo, ip_origen, user_agent, fecha_evento)
                VALUES
                (:id_usuario, :accion, :entidad, :entidad_id, :anterior, :nuevo, :motivo, :ip, :agent, NOW())';
            $stmt = Database::connection()->prepare($sql);
            $stmt->execute([
                'id_usuario' => $usuarioId,
                'accion' => $accion,
                'entidad' => $entidad,
                'entidad_id' => $entidadId,
                'anterior' => $anterior ? json_encode($anterior, JSON_UNESCAPED_UNICODE) : null,
                'nuevo' => $nuevo ? json_encode($nuevo, JSON_UNESCAPED_UNICODE) : null,
                'motivo' => $motivo,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (Throwable) {
            // La auditoría no debe impedir el flujo principal en esta base inicial.
        }
    }
}
