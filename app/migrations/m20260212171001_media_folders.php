<?php

declare(strict_types=1);

namespace app\migrations;

use Exception;
use VersaORM\Schema\Blueprint;
use versaWYS\kernel\model\VersaORMModel;

/**
 * Tabla de carpetas para organizar archivos del media store.
 * Soporta jerarquía ilimitada mediante parent_folder_id autorreferencial.
 */
class m20260212171001_media_folders extends VersaORMModel
{
    public function up(): array
    {
        try {
            $this->orm()->schemaBuilder()->create(
                'media_folders',
                static function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('empresa_id');
                    $table->string('token_empresa', 255);
                    $table->string('name', 255);
                    $table->foreignId('parent_folder_id')->nullable();
                    $table->enum('status', ['active', 'deleted'])->default('active');
                    $table->foreignId('created_by_user_id');
                    $table->timestamps();

                    $table->index(['empresa_id']);
                    $table->index(['empresa_id', 'parent_folder_id']);
                    $table->index('parent_folder_id');
                    $table->index('status');

                    $table->foreign('empresa_id')->references('id')->on('empresas');
                    $table->foreign('parent_folder_id')->references('id')->on('media_folders');
                    $table->foreign('created_by_user_id')->references('id')->on('versa_users');
                },
                true,
            );

            return ['message' => 'Tabla media_folders creada con éxito.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }

    public function down(): array
    {
        try {
            $this->orm()->schemaBuilder()->dropIfExists('media_folders');

            return ['message' => 'Tabla media_folders eliminada.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }
}
