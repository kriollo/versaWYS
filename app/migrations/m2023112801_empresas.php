<?php

declare(strict_types=1);

namespace app\migrations;

use Exception;
use VersaORM\Schema\Blueprint;
use versaWYS\kernel\model\VersaORMModel;

class m2023112801_empresas extends VersaORMModel
{
    public function up(): array
    {
        try {
            $this->orm()->schemaBuilder()->create(
                'empresas',
                static function (Blueprint $table): void {
                    $table->id();
                    $table->string('token_empresa', 191)->unique();
                    $table->string('rut', 20)->unique();
                    $table->string('nombre', 255);
                    $table->string('telefono', 20)->nullable();
                    $table->string('email', 100)->nullable();
                    $table->boolean('estado')->default(true);
                    $table->string('logo', 255)->nullable();
                    $table->timestamps();
                },
                true,
            );

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }

    public function down(): array
    {
        try {
            $this->orm()->schemaBuilder()->dropIfExists('empresas');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }
}
