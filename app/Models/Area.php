<?php
namespace App\Models;

use App\Core\Database;

class Area
{
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT * FROM areas ORDER BY activo DESC, nombre ASC')
            ->fetchAll();
    }

    public static function active(): array
    {
        return Database::connection()
            ->query('SELECT id_area, nombre FROM areas WHERE activo = 1 ORDER BY nombre')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM areas WHERE id_area = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function nameExists(string $nombre, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM areas WHERE LOWER(nombre) = LOWER(:nombre)';
        $params = ['nombre' => $nombre];
        if ($excludeId !== null) {
            $sql .= ' AND id_area <> :id';
            $params['id'] = $excludeId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO areas (nombre, descripcion, activo) VALUES (:nombre, :descripcion, :activo)'
        );
        $stmt->execute($data);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = Database::connection()->prepare(
            'UPDATE areas SET nombre = :nombre, descripcion = :descripcion, activo = :activo WHERE id_area = :id'
        );
        $stmt->execute($data);
    }

    public static function usageCount(int $id): int
    {
        $db = Database::connection();
        $queries = [
            'SELECT COUNT(*) FROM colaboradores WHERE id_area = :id',
            'SELECT COUNT(*) FROM equipos WHERE id_area = :id',
            'SELECT COUNT(*) FROM pronosticos WHERE id_area = :id',
        ];
        $total = 0;
        foreach ($queries as $sql) {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $total += (int) $stmt->fetchColumn();
        }
        return $total;
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM areas WHERE id_area = :id');
        $stmt->execute(['id' => $id]);
    }
}
