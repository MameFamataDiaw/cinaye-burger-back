<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BurgerController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\StatController;
use App\Models\Commande;
use App\Notifications\CommandeReady;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Authentification
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Routes publiques pour les clients
Route::apiResource('burgers', BurgerController::class)->only([
    'index', 'show'  // Accès public pour la liste et les détails des burgers
]);

Route::post('/commandes', [CommandeController::class, 'store']);  // Passer une commande

// Routes protégées par authentification pour les gestionnaires
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Routes pour la gestion des burgers
    Route::apiResource('burgers', BurgerController::class)->except([
        'index', 'show'  // Les routes index et show sont accessibles publiquement
    ]);
    Route::patch('/burgers/{id}/archive', [BurgerController::class, 'archive']);
    Route::patch('/burgers/{id}/unarchive', [BurgerController::class, 'unarchive']);

    // Routes pour la gestion des commandes
    Route::apiResource('commandes', CommandeController::class);
    Route::post('/commandes/{id}/cancel', [CommandeController::class, 'cancelOrder']);
    Route::post('/commandes/{id}/complete', [CommandeController::class, 'markAsCompleted']);
    Route::post('/commandes/{id}/payer', [CommandeController::class, 'payer']);

    // Notification email et PDF
    Route::post('/test-notification', function (Request $request) {
        $commande = Commande::with('details_commandes.burger')->find($request->commande_id); // ID de la commande passé via la requête
        Notification::route('mail', $commande->email_client)->notify(new CommandeReady($commande));
        return response()->json(['message' => 'Notification envoyée']);
    });

    // Statistiques
    Route::get('/statistiques/commandes-en-cours', [StatController::class, 'commandesEnCours']);
    Route::get('/statistiques/commandes-validees', [StatController::class, 'commandesValidees']);
    Route::get('/statistiques/recettes-journalieres', [StatController::class, 'recettesJournalieres']);
    Route::get('/statistiques/commandes-annulees', [StatController::class, 'commandesAnnulees']);
    Route::get('/statistiques/recettes-mensuelles', [StatController::class, 'recettesMensuelles']);
});









/**
 * Commandes par client
 */
//Route::get('/commandes', [CommandeController::class, 'listOrders']);

