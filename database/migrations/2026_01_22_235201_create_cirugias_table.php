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
        Schema::create('cirugias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atencion_id')->constrained('atenciones')->onDelete('cascade');
            $table->foreignId('medico_cirujano_id')->constrained('medicos')->onDelete('restrict');

            $table->string('codigo_cirugia', 50)->unique();
            $table->string('nombre_cirugia', 255);
            $table->text('descripcion_procedimiento');

            $table->enum('tipo_cirugia', [
                'Electiva',
                'Urgencia',
                'Emergencia'
            ]);

            $table->enum('clasificacion', [
                'Menor',
                'Mayor',
                'Especializada'
            ]);

            // Fechas y tiempos
            $table->date('fecha_programada');
            $table->time('hora_programada');
            $table->timestamp('fecha_inicio_real')->nullable();
            $table->timestamp('fecha_fin_real')->nullable();
            $table->integer('duracion_minutos')->nullable();

            // Sala y equipo
            $table->string('sala_operaciones', 50)->nullable();
            $table->text('equipo_quirurgico')->nullable(); // JSON con médicos, anestesiólogo, enfermeras

            // Anestesia
            $table->enum('tipo_anestesia', [
                'General',
                'Regional',
                'Local',
                'Sedación'
            ])->nullable();

            $table->text('medicamentos_anestesia')->nullable();

            // Diagnósticos
            $table->text('diagnostico_preoperatorio');
            $table->string('cie10_preoperatorio', 20)->nullable();
            $table->text('diagnostico_postoperatorio');
            $table->string('cie10_postoperatorio', 20)->nullable();

            // Procedimiento
            $table->text('descripcion_tecnica_quirurgica');
            $table->text('hallazgos_operatorios')->nullable();
            $table->text('complicaciones')->nullable();

            // Muestras y patología
            $table->text('muestras_enviadas_patologia')->nullable();
            $table->boolean('requiere_estudio_patologico')->default(false);

            // Post-operatorio
            $table->text('indicaciones_postoperatorias')->nullable();
            $table->text('pronostico')->nullable();

            $table->enum('estado_cirugia', [
                'Programada',
                'En Proceso',
                'Completada',
                'Suspendida',
                'Cancelada'
            ])->default('Programada');

            // Consentimiento
            $table->boolean('consentimiento_firmado')->default(false);
            $table->timestamp('fecha_consentimiento')->nullable();

            // Costos
            $table->decimal('costo_estimado', 10, 2)->nullable();
            $table->decimal('costo_real', 10, 2)->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cirugias');
    }
};
