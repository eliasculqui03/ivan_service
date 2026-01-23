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
        Schema::create('medicos', function (Blueprint $table) {
            Schema::create('medicos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('especialidad_id')->constrained('especialidades')->onDelete('restrict');
                $table->string('numero_colegiatura', 50)->unique();
                $table->string('rne', 50)->nullable(); // Registro Nacional de Especialistas
                $table->string('documento_identidad', 20)->unique();
                $table->string('telefono', 20)->nullable();
                $table->text('direccion')->nullable();

                $table->date('fecha_nacimiento')->nullable();
                $table->enum('genero', ['M', 'F', 'Otro'])->nullable();

                $table->text('firma_digital')->nullable(); // URL o path de la firma
                $table->text('sello_digital')->nullable(); // URL o path del sello

                $table->boolean('status')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};
