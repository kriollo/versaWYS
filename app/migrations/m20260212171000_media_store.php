<?php

declare(strict_types=1);

namespace app\migrations;

use Exception;
use VersaORM\Schema\Blueprint;
use versaWYS\kernel\model\VersaORMModel;

class m20260212171000_media_store extends VersaORMModel
{
    public function up(): array
    {
        try {
            $this->orm()->schemaBuilder()->create(
                'media_store',
                static function (Blueprint $table): void {
                    $table->id();

                    $table->foreignId('empresa_id');
                    $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');

                    $table->string('token_empresa', 255);
                    $table->enum('media_type', ['image', 'video', 'audio', 'file'])->default('file');
                    $table->string('original_name', 255);
                    $table->string('stored_name', 255);
                    $table->string('relative_path', 500);
                    $table->string('public_url', 500);
                    $table->string('mime', 255);
                    $table->bigInteger('size_bytes')->default(0);
                    $table->enum('status', ['active', 'deleted'])->default('active');
                    $table->foreignId('folder_id')->nullable();

                    $table->foreignId('created_by_user_id');
                    $table->foreign('created_by_user_id')->references('id')->on('versa_users')->onDelete('cascade');

                    $table->foreignId('updated_by_user_id')->nullable();
                    $table->foreign('updated_by_user_id')->references('id')->on('versa_users')->onDelete('set null');

                    $table->timestamps();

                    $table->index('empresa_id');
                    $table->index('status');
                    $table->index('media_type');
                    $table->index('folder_id');
                    $table->index('created_by_user_id');
                    $table->index('updated_by_user_id');
                },
                true,
            );

            return ['message' => 'Tabla media_store creada con éxito.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }

    public function down(): array
    {
        try {
            $this->orm()->schemaBuilder()->dropIfExists('media_store');

            return ['message' => 'Tabla media_store eliminada con éxito.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }
}
