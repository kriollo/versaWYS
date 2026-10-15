<?php

declare(strict_types=1);

namespace app\services;

use Throwable;

/**
 * Consola SQL del panel de Configuración (solo superadmin): ejecuta la consulta tal cual y devuelve
 * hasta 500 filas. La auditoría (quién y qué) la registra el controlador.
 */
class DatabaseQueryService
{
    private const int MAX_ROWS = 500;

    public function run(string $sql): array
    {
        $trimmed = trim($sql);
        if ($trimmed === '') {
            return ['success' => false, 'message' => 'La consulta no puede estar vacía.'];
        }

        global $versaOrm;

        try {
            $result = $versaOrm->exec($trimmed);
        } catch (Throwable $throwable) {
            return ['success' => false, 'message' => $throwable->getMessage()];
        }

        if (is_array($result)) {
            $total = count($result);
            $rows = array_slice($result, 0, self::MAX_ROWS);

            return [
                'success' => true,
                'type' => 'rows',
                'columns' => $rows === [] ? [] : array_keys($rows[0]),
                'rows' => $rows,
                'total' => $total,
                'truncated' => $total > self::MAX_ROWS,
            ];
        }

        return ['success' => true, 'type' => 'ack', 'message' => 'Consulta ejecutada correctamente.'];
    }
}
