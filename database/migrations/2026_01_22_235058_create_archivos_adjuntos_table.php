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
        Schema::create('archivos_adjuntos', function (Blueprint $table) {
           $table->id();
            
            // Relación polimórfica - puede ser de consulta, cirugía, examen, etc.
            $table->morphs('adjuntable'); // crea adjuntable_id y adjuntable_type
            
            $table->string('nombre_original', 255);
            $table->string('nombre_archivo', 255); // nombre en servidor
            $table->string('ruta_archivo', 500);
            $table->string('tipo_mime', 100);
            $table->string('extension', 10);
            $table->bigInteger('tamanio')->comment('Tamaño en bytes');
            
            $table->enum('categoria', [
                'Foto Paciente',
                'Foto Procedimiento',
                'Video Cirugía',
                'Consentimiento Informado',
                'Examen Laboratorio',
                'Examen Imagen',
                'Estudio Cardiológico',
                'Receta Médica',
                'Informe Médico',
                'Epicrisis',
                'Documento Identidad',
                'Otro'
            ]);
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0); // Para ordenar las imágenes
            
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('fecha_captura')->nullable(); // Fecha real de la foto/video
            
            $table->boolean('es_confidencial')->default(true);
            $table->boolean('visible_paciente')->default(false);
            $table->timestamps();
            $table->softDeletes();
            // Índices
            $table->index(['adjuntable_type', 'adjuntable_id', 'categoria']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivos_adjuntos');
    }
};
