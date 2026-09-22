<?php
namespace App\Models;

use App\Core\Database;

class Equipo
{
    public static function active(): array
    {
        return Database::connection()->query(
            'SELECT e.id_equipo, e.id_area, e.nombre, a.nombre AS area_nombre
             FROM equipos e
             INNER JOIN areas a ON a.id_area = e.id_area
             WHERE e.activo = 1 AND a.activo = 1
             ORDER BY a.nombre, e.nombre'
        )->fetchAll();
    }

    public static function belongsToArea(int $equipoId, int $areaId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM equipos WHERE id_equipo = :equipo AND id_area = :area AND activo = 1'
        );
        $stmt->execute(['equipo' => $equipoId, 'area' => $areaId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
