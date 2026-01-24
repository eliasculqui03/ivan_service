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

            // Día de la semana (1=Lunes, 7=Domingo)
            $table->tinyInteger('dia_semana')->comment('1=Lunes, 2=Martes, ..., 7=Domingo');

            // Horarios
            $table->time('hora_inicio');
            $table->time('hora_fin');

            // Duración de cada cita en minutos
            $table->integer('duracion_cita')->default(30)->comment('Minutos por consulta');

            // Cupo máximo de atenciones
            $table->integer('cupo_maximo')->nullable()->comment('Máximo de pacientes por turno');

            // Estado
            $table->boolean('activo')->default(true);

            // Observaciones
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['medico_id', 'dia_semana', 'activo']);
            $table->index('dia_semana');
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
