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
        Schema::table('pacientes', function (Blueprint $table) {
            // Lugar de Nacimiento
            if (!Schema::hasColumn('pacientes', 'lugar_nacimiento')) {
                $table->string('lugar_nacimiento', 100)->nullable()->after('fecha_nacimiento');
            }

            // Teléfonos Adicionales
            if (!Schema::hasColumn('pacientes', 'celular')) {
                $table->string('celular', 20)->nullable()->after('telefono');
            }
            if (!Schema::hasColumn('pacientes', 'telefono_domicilio')) {
                $table->string('telefono_domicilio', 20)->nullable()->after('celular');
            }
            if (!Schema::hasColumn('pacientes', 'telefono_oficina')) {
                $table->string('telefono_oficina', 20)->nullable()->after('telefono_domicilio');
            }

            // Correo adicional (Si ya tienes 'email', este sería uno secundario)
            if (!Schema::hasColumn('pacientes', 'correo_electronico')) {
                $table->string('correo_electronico', 100)->nullable()->after('email');
            }

            // Ubicación Geográfica
            if (!Schema::hasColumn('pacientes', 'distrito')) {
                $table->string('distrito', 50)->nullable()->after('direccion');
            }
            if (!Schema::hasColumn('pacientes', 'provincia')) {
                $table->string('provincia', 50)->nullable()->after('distrito');
            }
            if (!Schema::hasColumn('pacientes', 'departamento')) {
                $table->string('departamento', 50)->nullable()->after('provincia');
            }

            // Ocupación
            if (!Schema::hasColumn('pacientes', 'ocupacion')) {
                $table->string('ocupacion', 100)->nullable()->after('departamento');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn([
                'lugar_nacimiento',
                'celular',
                'telefono_domicilio',
                'telefono_oficina',
                'correo_electronico',
                'distrito',
                'provincia',
                'departamento',
                'ocupacion'
            ]);
        });
    }
};
