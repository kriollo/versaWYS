<?php

declare(strict_types=1);

namespace app\migrations;

use Exception;
use VersaORM\Schema\Blueprint;
use versaWYS\kernel\model\VersaORMModel;

class m2023112801_perfil extends VersaORMModel
{
    public function up(): array
    {
        try {
            $this->orm()->schemaBuilder()->create(
                'versa_perfil',
                static function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('id_empresa');
                    $table->string('nombre', 255);
                    $table->string('pagina_inicio', 255)->default('dashboard');
                    $table->integer('estado')->default(1);
                    $table->timestamps();

                    // Índices
                    $table->index('id_empresa');

                    // Foreign key
                    $table->foreign('id_empresa')->references('id')->on('empresas');
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
            $this->orm()->schemaBuilder()->dropIfExists('versa_perfil');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }
}
