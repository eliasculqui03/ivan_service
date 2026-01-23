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
        Schema::create('consulta_externas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atencion_id')->constrained('atenciones')->onDelete('cascade');

            // ==================== DATOS DE LA CONSULTA ACTUAL ====================
            // Estos datos se llenan en cada consulta porque pueden cambiar
            $table->integer('cantidad_hijos')->nullable();
            $table->string('ultimo_embarazo')->nullable();
            $table->string('telefono_consulta', 20)->nullable(); // Teléfono al momento de la consulta
            $table->text('direccion_consulta')->nullable(); // Dirección al momento de la consulta
            $table->string('ocupacion_actual', 100)->nullable(); // Ocupación al momento de la consulta

            // ==================== ANTECEDENTES CLÍNICOS ====================
            // Diabetes
            $table->boolean('diabetes')->default(false);
            $table->boolean('hipertension_arterial')->default(false);

            // Cáncer
            $table->boolean('cancer')->default(false);
            $table->boolean('artritis')->default(false);

            // Otros
            $table->text('otros_antecedentes')->nullable();

            // Tratamiento actual
            $table->text('tratamiento_actual')->nullable();
            $table->text('intervenciones_quirurgicas')->nullable();

            // ==================== ENFERMEDADES INFECCIOSAS ====================
            $table->boolean('enfermedades_infectocontagiosas')->default(false);
            $table->boolean('infecciones_urinarias')->default(false);
            $table->text('infecciones_urinarias_detalle')->nullable();

            $table->boolean('pulmones')->default(false);
            $table->boolean('infec_gastrointestinal')->default(false);
            $table->boolean('enf_transmision_sexual')->default(false);

            $table->boolean('hepatitis')->default(false);
            $table->string('hepatitis_tipo', 50)->nullable();

            $table->boolean('hiv')->default(false);

            $table->text('otros_enfermedades')->nullable();

            // ==================== ALERGIAS ====================
            $table->boolean('medicamentos_alergia')->default(false);
            $table->text('medicamentos_alergia_detalle')->nullable();

            $table->boolean('alimentos_alergia')->default(false);
            $table->text('alimentos_alergia_detalle')->nullable();

            $table->text('otros_alergias')->nullable();

            // ==================== FISIOLÓGICOS ====================
            $table->date('fecha_ultima_regla')->nullable();
            $table->boolean('regular')->default(false);
            $table->boolean('irregular')->default(false);

            // ==================== HÁBITOS NOCIVOS ====================
            $table->boolean('tabaco')->default(false);
            $table->boolean('alcohol')->default(false);
            $table->boolean('farmacos')->default(false);

            // ==================== RECOMENDADO POR ====================
            $table->boolean('instagram_dr_ivan_pareja')->default(false);
            $table->boolean('facebook_dr_ivan_pareja')->default(false);
            $table->boolean('radio')->default(false);
            $table->boolean('tv')->default(false);
            $table->boolean('internet')->default(false);
            $table->string('referencia_otro', 255)->nullable();

            // ==================== MOTIVO DE CONSULTA ====================
            // Marcas/Manchas
            $table->boolean('marcas_manchas_4k')->default(false);
            $table->boolean('flacidez')->default(false);

            // Rellenos faciales
            $table->boolean('rellenos_faciales_corporales')->default(false);
            $table->boolean('aumento_labios')->default(false);

            // Aumento de senos
            $table->boolean('aumento_senos')->default(false);
            $table->boolean('ojeras')->default(false);

            // Ptosis facial
            $table->boolean('ptosis_facial')->default(false);
            $table->boolean('gluteos')->default(false);

            // Levantamiento de mama
            $table->boolean('levantamiento_mama')->default(false);
            $table->boolean('modelado_corporal')->default(false);

            // Proptoplastia
            $table->boolean('proptoplastia')->default(false);
            $table->boolean('lifting_facial')->default(false);

            // Liposucción
            $table->boolean('liposuccion')->default(false);
            $table->boolean('arrugas_alisox')->default(false);

            // Rejuvenecimiento facial
            $table->boolean('rejuvenecimiento_facial')->default(false);
            $table->boolean('capilar')->default(false);

            $table->text('otros_motivos')->nullable();

            // ==================== CAMPOS ADICIONALES ====================
            // Examen Físico (si se necesita posteriormente)
            $table->text('examen_fisico')->nullable();

            // Diagnóstico
            $table->text('diagnostico')->nullable();
            $table->string('cie10', 20)->nullable();

            // Plan de Tratamiento
            $table->text('plan_tratamiento')->nullable();
            $table->text('indicaciones')->nullable();

            // Observaciones
            $table->text('observaciones')->nullable();

            // Control de calidad
            $table->timestamp('fecha_firma')->nullable();
            $table->boolean('ficha_completada')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consulta_externas');
    }
};
