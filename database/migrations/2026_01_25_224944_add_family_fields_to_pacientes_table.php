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
          // Estado Civil (Después de correo)
            if (!Schema::hasColumn('pacientes', 'estado_civil')) {
                $table->string('estado_civil', 50)->nullable()->after('correo_electronico');
            }

            // Datos Familiares (Después de ocupación)
            if (!Schema::hasColumn('pacientes', 'cantidad_hijos')) {
                $table->integer('cantidad_hijos')->nullable()->after('ocupacion');
            }
            
            if (!Schema::hasColumn('pacientes', 'ultimo_embarazo')) {
                $table->string('ultimo_embarazo', 100)->nullable()->after('cantidad_hijos');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn(['estado_civil', 'cantidad_hijos', 'ultimo_embarazo']);
        });
    }
};
