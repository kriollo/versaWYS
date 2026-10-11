<?php

declare(strict_types=1);

namespace app\migrations;

use VersaORM\Schema\Blueprint;
use VersaORM\VersaORMException;
use versaWYS\kernel\model\VersaORMModel;

class m20250902224730_empresa_modulo extends VersaORMModel
{
    public function up(): array
    {
        try {
            $this->orm()->schemaBuilder()->create('empresa_modulo', static function (Blueprint $table): void {
                $table->id();
                $table->foreignId('id_empresa');
                $table->foreignId('id_modulo');
                $table->timestamps();

                $table->index(['id_empresa'], 'idx_empresa_empresa_modulo');
                $table->index(['id_modulo'], 'idx_modulo_empresa_modulo');

                $table->foreign(['id_empresa'])->references('id')->on('empresas')->onDelete('CASCADE');
                $table->foreign(['id_modulo'])->references('id')->on('versa_menu')->onDelete('CASCADE');
            });

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }

    public function down(): array
    {
        try {
            $this->orm()->schemaBuilder()->dropIfExists('empresa_modulo');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }
}
