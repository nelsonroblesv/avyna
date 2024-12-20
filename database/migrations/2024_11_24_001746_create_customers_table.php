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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('alias')->nullable();
            $table->string('name')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->timestamp('birthday')->nullable();
            $table->string('avatar')->nullable();
            $table->string('address')->nullable();
            $table->string('state_id')->nullable();
            $table->string('municipality_id')->nullable();
            $table->string('locality')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('front_image')->nullable();
            $table->string('inside_image')->nullable();
            $table->string('coordinate')->nullable();
            $table->enum('type' , ['par', 'non'])->default('par');
            $table->string('extra')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            //Facturacion
            $table->string('name_facturacion')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('address_facturacion')->nullable();
            $table->string('postal_code_facturacion')->nullable();
            $table->enum('tipo_cfdi', ['Ninguno', 'Ingreso', 'Egreso', 'Traslado', 'Nomina'])
                    ->default('Ninguno')->nullable();
            $table->enum('tipo_razon_social', ['Ninguna', 'Sociedad Anonima', 'Sociedad Civil'])
                        ->default('Ninguna')->nullable();
            $table->string('cfdi_document')->nullable();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
