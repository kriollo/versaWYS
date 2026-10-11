<?php

declare(strict_types=1);

namespace app\migrations;

use VersaORM\Schema\Blueprint;
use VersaORM\VersaORMException;
use versaWYS\kernel\model\VersaORMModel;

class m20240128160457_menu extends VersaORMModel
{
    public function up(): array
    {
        try {
            $this->orm()->schemaBuilder()->create('versa_menu', static function (Blueprint $table): void {
                $table->id();
                $table->string('nombre', 255);
                $table->string('seccion', 255);
                $table->string('descripcion', 255);
                $table->text('icono')->nullable();
                $table->boolean('fill')->default(false);
                $table->integer('posicion')->default(0);
                $table->boolean('estado')->default(true);
                $table->boolean('submenu')->default(false);
                $table->string('url', 255)->nullable();
                $table->timestamps();
            });

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }

    public function down(): array
    {
        try {
            $this->orm()->schemaBuilder()->dropIfExists('versa_menu');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }
}
