<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estudios_medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atencion_id')->constrained('atenciones')->onDelete('cascade');
            $table->foreignId('medico_solicitante_id')->constrained('medicos')->onDelete('restrict');

            $table->string('numero_estudio', 50)->unique();
            $table->date('fecha_solicitud');
            $table->date('fecha_realizacion')->nullable();

            $table->enum('tipo_estudio', [
                'Radiografía',
                'Tomografía',
                'Resonancia Magnética',
                'Ecografía',
                'Mamografía',
                'Densitometría',
                'Endoscopía',
                'Colonoscopía',
                'Electroencefalograma',
                'Electromiografía',
                'Espirometría',
                'Otro'
            ]);

            $table->string('nombre_estudio', 255);
            $table->text('descripcion_estudio')->nullable();
            $table->string('region_anatomica', 100)->nullable();

            $table->enum('prioridad', [
                'Rutina',
                'Urgente',
                'STAT'
            ])->default('Rutina');

            $table->enum('estado', [
                'Solicitado',
                'Programado',
                'En Proceso',
                'Realizado',
                'Informado',
                'Cancelado'
            ])->default('Solicitado');

            // Indicaciones clínicas
            $table->text('indicacion_clinica');
            $table->text('diagnostico_presuntivo')->nullable();

            // Realización del estudio
            $table->string('centro_diagnostico', 255)->nullable();
            $table->string('medico_radiólogo', 255)->nullable();
            $table->string('tecnico_responsable', 255)->nullable();

            // Técnica
            $table->text('tecnica_utilizada')->nullable();
            $table->boolean('contraste_utilizado')->default(false);
            $table->string('tipo_contraste', 100)->nullable();

            // Informe
            $table->longText('hallazgos')->nullable();
            $table->text('impresion_diagnostica')->nullable();
            $table->text('conclusiones')->nullable();
            $table->text('recomendaciones')->nullable();

            // Comparación
            $table->text('comparacion_estudios_previos')->nullable();

            // Validación
            $table->foreignId('informado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_informe')->nullable();

            $table->boolean('informe_enviado')->default(false);

            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['atencion_id', 'fecha_solicitud']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudios_medicos');
    }
};
