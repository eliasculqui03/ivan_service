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
        Schema::create('examenes_laboratorios', function (Blueprint $table) {
          $table->id();
            $table->foreignId('atencion_id')->constrained('atenciones')->onDelete('cascade');
            $table->foreignId('medico_solicitante_id')->constrained('medicos')->onDelete('restrict');
            
            $table->string('numero_orden', 50)->unique();
            $table->date('fecha_solicitud');
            $table->date('fecha_toma_muestra')->nullable();
            $table->date('fecha_resultado')->nullable();
            
            $table->enum('tipo_examen', [
                'Hematología',
                'Bioquímica',
                'Inmunología',
                'Microbiología',
                'Parasitología',
                'Urianálisis',
                'Coagulación',
                'Gasometría',
                'Hormonas',
                'Marcadores Tumorales',
                'Otro'
            ]);
            
            $table->string('nombre_examen', 255);
            $table->text('examenes_detalle')->nullable(); // JSON con lista de exámenes específicos
            
            $table->enum('prioridad', [
                'Rutina',
                'Urgente',
                'STAT'
            ])->default('Rutina');
            
            $table->enum('estado', [
                'Solicitado',
                'Muestra Tomada',
                'En Proceso',
                'Resultado Parcial',
                'Completado',
                'Cancelado'
            ])->default('Solicitado');
            
            // Información de la muestra
            $table->string('tipo_muestra', 100)->nullable(); // Sangre, orina, etc.
            $table->text('condiciones_muestra')->nullable(); // Ayuno, etc.
            
            // Resultados
            $table->longText('resultados')->nullable(); // JSON con todos los valores
            $table->text('valores_criticos')->nullable();
            $table->text('interpretacion')->nullable();
            $table->text('observaciones_laboratorio')->nullable();
            
            // Validación
            $table->foreignId('validado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_validacion')->nullable();
            
            // Referencia
            $table->string('laboratorio_externo', 255)->nullable();
            $table->string('laboratorista', 255)->nullable();
            
            $table->boolean('resultado_impreso')->default(false);
            $table->boolean('resultado_enviado')->default(false);
            
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
        Schema::dropIfExists('examenes_laboratorios');
    }
};
