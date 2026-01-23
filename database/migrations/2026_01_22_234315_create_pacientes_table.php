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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_historia', 50)->unique(); // Número de historia clínica

            // Datos personales
            $table->string('nombres', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->string('documento_identidad', 20)->unique();
            $table->enum('tipo_documento', ['DNI', 'CE', 'Pasaporte', 'Otro'])->default('DNI');

            $table->date('fecha_nacimiento');
            $table->enum('genero', ['M', 'F', 'Otro']);
            $table->string('grupo_sanguineo', 10)->nullable(); // A+, O-, etc.

            // Contacto
            $table->string('telefono', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('telefono_domicilio', 20)->nullable(); // Agregado
            $table->string('telefono_oficina', 20)->nullable(); // Agregado
            $table->string('email', 100)->nullable();
            $table->string('correo_electronico', 100)->nullable(); // Alias para el formulario
            $table->text('direccion')->nullable();
            $table->string('distrito', 100)->nullable();
            $table->string('provincia', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('lugar_nacimiento', 255)->nullable(); // Agregado

            // Información adicional
            $table->string('ocupacion', 100)->nullable(); // Agregado

            // Contacto de emergencia
            $table->string('contacto_emergencia_nombre', 100)->nullable();
            $table->string('contacto_emergencia_telefono', 20)->nullable();
            $table->string('contacto_emergencia_parentesco', 50)->nullable();

            // Seguro/Cobertura
            $table->enum('tipo_seguro', ['SIS', 'EsSalud', 'Privado', 'Particular', 'Otro'])->nullable();
            $table->string('numero_seguro', 50)->nullable();

            // Otros
            $table->text('alergias')->nullable();
            $table->text('antecedentes_personales')->nullable();
            $table->text('antecedentes_familiares')->nullable();
            $table->string('foto_url')->nullable();

            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
