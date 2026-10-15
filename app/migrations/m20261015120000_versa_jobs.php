<?php

declare(strict_types=1);

namespace app\migrations;

use Exception;
use versaWYS\kernel\model\VersaORMModel;
use versaWYS\kernel\Queue;

/**
 * Tabla de la cola de trabajos (Queue::push / php versaCLI app:queue:work). La estructura la define el kernel.
 */
class m20261015120000_versa_jobs extends VersaORMModel
{
    public function up(): array
    {
        try {
            Queue::createTable($this->orm());

            return ['message' => 'Tabla ' . Queue::TABLE . ' creada con éxito.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }

    public function down(): array
    {
        try {
            $this->orm()->schemaBuilder()->dropIfExists(Queue::TABLE);

            return ['message' => 'Tabla ' . Queue::TABLE . ' eliminada.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }
}
