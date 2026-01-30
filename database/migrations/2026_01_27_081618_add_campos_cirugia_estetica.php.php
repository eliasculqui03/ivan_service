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
          Schema::table('consulta_externas', function (Blueprint $table) {
            
            // === 1. NUEVOS MOTIVOS (TEXTO LIBRE) ===
            // Agregamos esto porque reemplazaste los checkboxes por textareas
            if (!Schema::hasColumn('consulta_externas', 'motivo_facial')) {
                $table->text('motivo_facial')->nullable()->after('otros_motivos');
            }
            if (!Schema::hasColumn('consulta_externas', 'motivo_corporal')) {
                $table->text('motivo_corporal')->nullable()->after('motivo_facial');
            }
            if (!Schema::hasColumn('consulta_externas', 'motivo_capilar')) {
                $table->text('motivo_capilar')->nullable()->after('motivo_corporal');
            }
            if (!Schema::hasColumn('consulta_externas', 'motivos_zonas')) {
                $table->text('motivos_zonas')->nullable()->after('motivo_capilar');
            }
            if (!Schema::hasColumn('consulta_externas', 'motivos_tratamientos_previos')) {
                $table->text('motivos_tratamientos_previos')->nullable()->after('motivos_zonas');
            }
            if (!Schema::hasColumn('consulta_externas', 'expectativa_paciente')) {
                $table->text('expectativa_paciente')->nullable()->after('motivos_tratamientos_previos');
            }

            // === 2. VITALES BÁSICOS ===
            if (!Schema::hasColumn('consulta_externas', 'presion_arterial')) {
                $table->string('presion_arterial', 20)->nullable()->after('expectativa_paciente');
            }
            if (!Schema::hasColumn('consulta_externas', 'frecuencia_cardiaca')) {
                $table->string('frecuencia_cardiaca', 20)->nullable()->after('presion_arterial');
            }
            if (!Schema::hasColumn('consulta_externas', 'peso')) {
                $table->decimal('peso', 5, 2)->nullable()->after('frecuencia_cardiaca');
            }
            if (!Schema::hasColumn('consulta_externas', 'talla')) {
                $table->decimal('talla', 4, 2)->nullable()->after('peso');
            }
            if (!Schema::hasColumn('consulta_externas', 'imc')) {
                $table->decimal('imc', 4, 2)->nullable()->after('talla');
            }

            // === 3. EVALUACIÓN Y PLAN ===
            if (!Schema::hasColumn('consulta_externas', 'evaluacion_zona')) {
                $table->text('evaluacion_zona')->nullable()->after('imc');
            }
            if (!Schema::hasColumn('consulta_externas', 'procedimiento_propuesto')) {
                $table->string('procedimiento_propuesto', 500)->nullable()->after('evaluacion_zona');
            }
            if (!Schema::hasColumn('consulta_externas', 'tecnica_utilizar')) {
                $table->string('tecnica_utilizar', 500)->nullable()->after('procedimiento_propuesto');
            }
            if (!Schema::hasColumn('consulta_externas', 'productos_usar')) {
                $table->text('productos_usar')->nullable()->after('tecnica_utilizar');
            }
            if (!Schema::hasColumn('consulta_externas', 'numero_sesiones')) {
                $table->integer('numero_sesiones')->nullable()->default(1)->after('productos_usar');
            }
            if (!Schema::hasColumn('consulta_externas', 'precio_estimado')) {
                $table->decimal('precio_estimado', 10, 2)->nullable()->after('numero_sesiones');
            }

            // === 4. INDICACIONES E INFORMACIÓN ===
            if (!Schema::hasColumn('consulta_externas', 'indicaciones_pre')) {
                $table->text('indicaciones_pre')->nullable()->after('precio_estimado');
            }
            if (!Schema::hasColumn('consulta_externas', 'indicaciones_post')) {
                $table->text('indicaciones_post')->nullable()->after('indicaciones_pre');
            }
            
            // === 5. CONSENTIMIENTO Y SEGUIMIENTO ===
            if (!Schema::hasColumn('consulta_externas', 'consentimiento_informado')) {
                $table->boolean('consentimiento_informado')->default(false)->after('indicaciones_post');
            }
            if (!Schema::hasColumn('consulta_externas', 'consentimiento_fecha')) {
                $table->datetime('consentimiento_fecha')->nullable()->after('consentimiento_informado');
            }
            if (!Schema::hasColumn('consulta_externas', 'consentimiento_archivo_id')) {
                $table->unsignedBigInteger('consentimiento_archivo_id')->nullable()->after('consentimiento_fecha');
                // Asumiendo que la tabla archivos_adjuntos ya existe
                $table->foreign('consentimiento_archivo_id')->references('id')->on('archivos_adjuntos')->onDelete('set null');
            }
            if (!Schema::hasColumn('consulta_externas', 'proxima_cita')) {
                $table->date('proxima_cita')->nullable()->after('consentimiento_archivo_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consulta_externas', function (Blueprint $table) {
            // Eliminar FK primero
            if (Schema::hasColumn('consulta_externas', 'consentimiento_archivo_id')) {
                $table->dropForeign(['consentimiento_archivo_id']);
            }

            // Eliminar columnas
            $columns = [
                'motivo_facial', 'motivo_corporal', 'motivo_capilar', 
                'motivos_zonas', 'motivos_tratamientos_previos', 'expectativa_paciente',
                'presion_arterial', 'frecuencia_cardiaca', 'peso', 'talla', 'imc',
                'evaluacion_zona', 'procedimiento_propuesto', 'tecnica_utilizar',
                'productos_usar', 'numero_sesiones', 'precio_estimado',
                'indicaciones_pre', 'indicaciones_post',
                'consentimiento_informado', 'consentimiento_fecha', 'consentimiento_archivo_id',
                'proxima_cita'
            ];
            
            foreach ($columns as $col) {
                if (Schema::hasColumn('consulta_externas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
