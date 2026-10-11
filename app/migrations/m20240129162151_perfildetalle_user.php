<?php

declare(strict_types=1);

namespace app\migrations;

use VersaORM\Schema\Blueprint;
use VersaORM\VersaORMException;
use versaWYS\kernel\model\VersaORMModel;

class m20240129162151_perfildetalle_user extends VersaORMModel
{
    public function up(): array
    {
        try {
            $this->orm()->schemaBuilder()->create('versa_perfildetalleuser', static function (Blueprint $table): void {
                $table->id();
                $table->foreignId('id_user');
                $table->foreignId('menu_id');
                $table->foreignId('submenu_id')->nullable();
                $table->timestamps();

                $table->index(['id_user'], 'fk_perfildetalleuser_user_idx');
                $table->index(['menu_id'], 'fk_perfildetalleuser_menu_idx');
                $table->index(['submenu_id'], 'fk_perfildetalleuser_submenu_idx');

                $table->foreign(['id_user'])->references('id')->on('versa_users');
                $table->foreign(['menu_id'])->references('id')->on('versa_menu');
                $table->foreign(['submenu_id'])->references('id')->on('versa_submenu');
            });

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }

    public function down(): array
    {
        try {
            $this->orm()->schemaBuilder()->dropIfExists('versa_perfildetalleuser');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }
}
