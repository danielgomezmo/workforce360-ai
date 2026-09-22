<?php
namespace App\Models;

use App\Core\Database;

class Horario
{
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT * FROM horarios ORDER BY activo DESC, hora_inicio ASC, nombre ASC')
            ->fetchAll();
    }

    public static function active(): array
    {
        return Database::connection()
            ->query('SELECT id_horario, nombre, hora_inicio, hora_fin FROM horarios WHERE activo = 1 ORDER BY hora_inicio, nombre')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM horarios WHERE id_horario = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function nameExists(string $nombre, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM horarios WHERE LOWER(nombre) = LOWER(:nombre)';
        $params = ['nombre' => $nombre];
        if ($excludeId !== null) {
            $sql .= ' AND id_horario <> :id';
            $params['id'] = $excludeId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO horarios
             (nombre, hora_inicio, hora_fin, minutos_tolerancia, horas_descanso, cruza_medianoche, dias_semana, activo)
             VALUES
             (:nombre, :hora_inicio, :hora_fin, :minutos_tolerancia, :horas_descanso, :cruza_medianoche, :dias_semana, :activo)'
        );
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = Database::connection()->prepare(
            'UPDATE horarios SET
             nombre = :nombre, hora_inicio = :hora_inicio, hora_fin = :hora_fin,
             minutos_tolerancia = :minutos_tolerancia, horas_descanso = :horas_descanso,
             cruza_medianoche = :cruza_medianoche, dias_semana = :dias_semana, activo = :activo
             WHERE id_horario = :id'
        );
        $stmt->execute($data);
    }

    public static function usageCount(int $id): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM asignacion_horarios WHERE id_horario = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM horarios WHERE id_horario = :id');
        $stmt->execute(['id' => $id]);
    }
}
