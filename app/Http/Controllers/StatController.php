<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use Illuminate\Http\Request;

class StatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Afficher les commandes en cours pour aujourd'hui.
     */
    public function commandesEnCours(){
        $commandes = Commande::where('statut', 'en cours')
            ->whereDate('created_at', today())
            ->get();

        return response()->json([
            'satus' => true,
            'commandes' => $commandes
        ]);
    }

    /**
     * Afficher les commandes validées pour aujourd'hui.
     */
    public function commandesValidees(){
        $commandes = Commande::where('statut', 'termine')
            ->whereDate('created_at', today())
            ->get();

        return response()->json([
            'status' => true,
            'commandes' => $commandes
        ]);
    }

    /**
     * Afficher les recettes journalières par jour
     */
    public function recettesJournalieres(){
        $recettes = Commande::where('statut', 'paye')
            ->whereDate('created_at', today())
            ->sum('total');

        return response()->json([
            'status' => true,
            'recettes' => $recettes
        ]);
    }

    /**
     * Afficher les commandes annulées pour aujourd'hui
     */
    public function commandesAnnulees(){
        $commandes = Commande::where('statut', 'annule')
            ->whereDate('created_at', today())
            ->get();

         return response()->json([
             'status' => true,
             'commandes' => $commandes
         ]);
    }

    /**
     * Afficher les recettes mensuelles pour le mois en cours.
     */
    public function recettesMensuelles(){
        $recettes = Commande::where('statut', 'paye')
            ->whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->sum('total');

        return response()->json([
            'status' => true,
            'recettes' => $recettes
        ]);
    }
}
