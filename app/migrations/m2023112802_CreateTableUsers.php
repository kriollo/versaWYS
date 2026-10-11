<?php

declare(strict_types=1);

namespace app\migrations;

use Exception;
use VersaORM\Schema\Blueprint;
use versaWYS\kernel\model\VersaORMModel;

class m2023112802_CreateTableUsers extends VersaORMModel
{
    public function up(): array
    {
        try {
            $this->orm()->schemaBuilder()->create(
                'versa_users',
                static function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('id_empresa');
                    $table->string('tokenid', 255)->unique();
                    $table->string('name', 255);
                    $table->string('email', 255)->unique();
                    $table->string('password', 255);
                    $table->timestamp('expiration_pass')->nullable();
                    $table->string('role', 255);
                    $table->boolean('status')->default(true);
                    $table->string('restore_token', 255)->nullable();
                    $table->timestamp('last_login')->nullable();
                    $table->string('avatar', 255)->nullable();
                    $table->foreignId('id_perfil')->nullable();
                    $table->string('pagina_inicio', 255)->default('dashboard');
                    $table->timestamps();

                    // Índices
                    $table->index('id_empresa');
                    $table->index('id_perfil');

                    // Foreign keys
                    $table->foreign('id_empresa')->references('id')->on('empresas');
                    $table->foreign('id_perfil')->references('id')->on('versa_perfil');
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
            $this->orm()->schemaBuilder()->dropIfExists('versa_users');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }
}
