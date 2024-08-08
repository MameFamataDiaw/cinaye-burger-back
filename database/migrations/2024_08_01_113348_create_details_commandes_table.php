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
//        Schema::create('details_commandes', function (Blueprint $table) {
//            $table->id();
//            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
//            $table->foreignId('burger_id')->constrained('burgers')->onDelete('cascade');
//            $table->integer('quantite');
//            $table->decimal('prix',8,2);
//            $table->decimal('montant',8,2);
//            $table->timestamps();
//        });

        Schema::create('details_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained()->onDelete('cascade');
            $table->foreignId('burger_id')->constrained()->onDelete('cascade');
            $table->integer('quantite');
            $table->decimal('prix', 10, 2);  // Prix unitaire du burger
            $table->decimal('montant', 10, 2);  // Montant total pour cet article (quantité * prix)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('details_commandes');
    }
};
