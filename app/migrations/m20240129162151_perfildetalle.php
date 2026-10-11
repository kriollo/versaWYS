<?php

declare(strict_types=1);

namespace app\migrations;

use VersaORM\Schema\Blueprint;
use VersaORM\VersaORMException;
use versaWYS\kernel\model\VersaORMModel;

class m20240129162151_perfildetalle extends VersaORMModel
{
    public function up(): array
    {
        try {
            $this->orm()->schemaBuilder()->create('versa_perfildetalle', static function (Blueprint $table): void {
                $table->id();
                $table->foreignId('perfil_id');
                $table->foreignId('menu_id');
                $table->foreignId('submenu_id')->nullable();
                $table->boolean('estado')->default(true);
                $table->timestamps();

                $table->index(['perfil_id'], 'fk_perfildetalle_perfil_idx');
                $table->index(['menu_id'], 'fk_perfildetalle_menu_idx');
                $table->index(['submenu_id'], 'fk_perfildetalle_submenu_idx');

                $table->foreign(['perfil_id'])->references('id')->on('versa_perfil');
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
            $this->orm()->schemaBuilder()->dropIfExists('versa_perfildetalle');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }
}
