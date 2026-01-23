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
        Schema::create('evaluaciones_cardiologicas', function (Blueprint $table) {
           $table->id();
            $table->foreignId('atencion_id')->constrained('atenciones')->onDelete('cascade');
            $table->foreignId('cardiologo_id')->constrained('medicos')->onDelete('restrict');
            
            $table->string('numero_evaluacion', 50)->unique();
            $table->date('fecha_evaluacion');
            
            $table->enum('tipo_evaluacion', [
                'Preoperatoria',
                'Postoperatoria',
                'Control',
                'Urgencia',
                'Consulta'
            ]);
            
            $table->text('motivo_evaluacion');
            
            // Anamnesis cardiovascular
            $table->text('sintomas_cardiovasculares')->nullable();
            $table->boolean('dolor_toracico')->default(false);
            $table->boolean('disnea')->default(false);
            $table->boolean('palpitaciones')->default(false);
            $table->boolean('sincope')->default(false);
            $table->boolean('edema')->default(false);
            
            $table->text('antecedentes_cardiovasculares')->nullable();
            $table->text('medicacion_cardiovascular')->nullable();
            
            // Examen físico cardiovascular
            $table->string('presion_arterial', 20)->nullable();
            $table->integer('frecuencia_cardiaca')->nullable();
            $table->string('ritmo_cardiaco', 50)->nullable(); // Regular, Irregular
            $table->text('auscultacion_cardiaca')->nullable();
            $table->text('pulsos_perifericos')->nullable();
            
            // Electrocardiograma
            $table->boolean('ecg_realizado')->default(false);
            $table->text('ecg_hallazgos')->nullable();
            $table->string('ecg_ritmo', 100)->nullable();
            $table->integer('ecg_frecuencia')->nullable();
            $table->string('ecg_eje', 50)->nullable();
            $table->text('ecg_interpretacion')->nullable();
            
            // Ecocardiograma
            $table->boolean('ecocardiograma_realizado')->default(false);
            $table->decimal('fraccion_eyeccion', 5, 2)->nullable(); // %
            $table->text('ecocardiograma_hallazgos')->nullable();
            
            // Prueba de esfuerzo
            $table->boolean('prueba_esfuerzo_realizada')->default(false);
            $table->text('prueba_esfuerzo_resultados')->nullable();
            
            // Otros estudios
            $table->boolean('holter_realizado')->default(false);
            $table->text('holter_resultados')->nullable();
            
            $table->boolean('mapa_realizado')->default(false); // Monitoreo Ambulatorio Presión Arterial
            $table->text('mapa_resultados')->nullable();
            
            // Diagnósticos
            $table->text('diagnostico_cardiologico');
            $table->string('cie10_principal', 20)->nullable();
            $table->text('diagnosticos_secundarios')->nullable();
            
            // Clasificación funcional
            $table->enum('clase_funcional_nyha', ['I', 'II', 'III', 'IV'])->nullable(); // New York Heart Association
            
            // Riesgo cardiovascular
            $table->enum('riesgo_cardiovascular', [
                'Bajo',
                'Moderado',
                'Alto',
                'Muy Alto'
            ])->nullable();
            
            // Recomendaciones
            $table->text('recomendaciones');
            $table->text('tratamiento_sugerido')->nullable();
            
            // Aptitud quirúrgica
            $table->enum('aptitud_quirurgica', [
                'Apto',
                'Apto con Riesgo',
                'No Apto',
                'Requiere Optimización'
            ])->nullable();
            
            $table->text('observaciones_aptitud')->nullable();
            
            // Seguimiento
            $table->date('proxima_evaluacion')->nullable();
            
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
        Schema::dropIfExists('evaluaciones_cardiologicas');
    }
};
