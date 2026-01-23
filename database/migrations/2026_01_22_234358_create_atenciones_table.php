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
        Schema::create('atenciones', function (Blueprint $table) {
      
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('medico_id')->constrained('medicos')->onDelete('restrict');
            
            $table->string('numero_atencion', 50)->unique(); // Número único de atención
            $table->date('fecha_atencion');
            $table->time('hora_ingreso');
            $table->time('hora_salida')->nullable();
            
            $table->enum('tipo_atencion', [
                'Consulta Externa',
                'Emergencia',
                'Hospitalización',
                'Cirugía',
                'Procedimiento',
                'Control'
            ])->default('Consulta Externa');
            
            $table->enum('tipo_cobertura', [
                'SIS',
                'EsSalud',
                'Privado',
                'Particular',
                'Otro'
            ])->default('Particular');
            
            $table->string('numero_autorizacion', 100)->nullable(); // Para seguros
            
            $table->enum('estado', [
                'Programada',
                'En Espera',
                'En Atención',
                'Atendida',
                'Cancelada',
                'No Asistió'
            ])->default('Programada');
            
            $table->text('motivo_consulta')->nullable();
            $table->text('observaciones')->nullable();
            
            $table->decimal('monto_total', 10, 2)->default(0);
            $table->decimal('monto_cobertura', 10, 2)->default(0);
            $table->decimal('monto_copago', 10, 2)->default(0);
            
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para mejorar búsquedas
            $table->index(['fecha_atencion', 'medico_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atenciones');
    }
};
