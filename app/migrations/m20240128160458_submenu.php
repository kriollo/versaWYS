<?php

declare(strict_types=1);

namespace app\migrations;

use VersaORM\Schema\Blueprint;
use VersaORM\VersaORMException;
use versaWYS\kernel\model\VersaORMModel;

class m20240128160458_submenu extends VersaORMModel
{
    public function up(): array
    {
        try {
            $this->orm()->schemaBuilder()->create('versa_submenu', static function (Blueprint $table): void {
                $table->id();
                $table->foreignId('id_menu');
                $table->string('nombre', 255);
                $table->string('descripcion', 255);
                $table->string('url', 255);
                $table->string('icono', 255)->default('bi bi-circle');
                $table->integer('posicion')->default(0);
                $table->boolean('estado')->default(true);
                $table->timestamps();

                $table->index(['id_menu'], 'fk_submenu_menu_idx');
                $table->foreign(['id_menu'])->references('id')->on('versa_menu');
            });

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }

    public function down(): array
    {
        try {
            $this->orm()->schemaBuilder()->dropIfExists('versa_submenu');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }
}
