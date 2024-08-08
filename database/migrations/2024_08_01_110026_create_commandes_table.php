<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
//        Schema::create('commandes', function (Blueprint $table) {
//            $table->id();
//            $table->string('nomClient',30);
//            $table->string('prenomClient',50);
//            $table->string('telephone',20);
//            $table->string('email');
//            $table->decimal('prix',8,2)->nullable();
//            $table->timestamp('date')->default(DB::raw('CURRENT_TIMESTAMP'));
//            $table->enum('statut',['en cours','termine','paye','annule'])->default('en cours');
//            $table->timestamps();
//        });

        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('nom_client');
            $table->string('prenom_client');
            $table->string('telephone_client');
            $table->string('email_client');
            $table->decimal('total', 10, 2);
            $table->timestamp('date')->default(DB::raw('CURRENT_TIMESTAMP'));// Total de la commande
            $table->enum('statut',['en cours','termine','paye','annule'])->default('en cours');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
