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
        Schema::create('horario_medicos', function (Blueprint $table) {
            $table->id();
            
            // Relación con médico
            $table->foreignId('medico_id')
                ->constrained('medicos')
                ->onDelete('cascade');
            
            // OPCIÓN 1: Fecha específica (nullable para horarios recurrentes)
            $table->date('fecha')->nullable()->comment('Fecha específica para este horario (si aplica)');
            
            // OPCIÓN 2: Día de la semana para horarios recurrentes (nullable para fechas específicas)
            $table->tinyInteger('dia_semana')->nullable()->comment('1=Lunes, 2=Martes, ..., 7=Domingo (para recurrente)');
            
            // Horarios
            $table->time('hora_inicio');
            $table->time('hora_fin');
            
            // Duración de cada cita en minutos
            $table->integer('duracion_cita')->default(30)->comment('Minutos por consulta');
            
            // Cupo máximo de atenciones
            $table->integer('cupo_maximo')->nullable()->comment('Máximo de pacientes');
            
            // Tipo de horario
            $table->enum('tipo', ['fecha_especifica', 'recurrente'])->default('recurrente')
                ->comment('fecha_especifica: un día puntual | recurrente: se repite semanalmente');
            
            // Estado
            $table->boolean('activo')->default(true);
            
            // Observaciones
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['medico_id', 'fecha']);
            $table->index(['medico_id', 'dia_semana', 'activo']);
            $table->index(['fecha', 'activo']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horario_medicos');
    }
};
