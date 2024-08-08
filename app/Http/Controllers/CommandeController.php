<?php

namespace App\Http\Controllers;

use App\Models\Burger;
use App\Models\Commande;
use App\Models\DetailsCommande;
use App\Models\Paiement;
use App\Notifications\CommandeReady;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class CommandeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Commande::query();

        //filtrer par burger
        if ($request->has('burger_id')){
            $burgerId = $request->input('burger_id');
            $query->whereHas('details_commandes',function($q) use ($burgerId){
                $q->where('burger_id',$burgerId);
            });
        }

        //filtrer par date
        if($request->has('date')){
            $date = Carbon::parse($request->input('date'))->toDateString();
            $query->whereDate('created_at',$date);
        }

        //filtrer par etat
        if ($request->has('statut')){
            $statut = $request->input('statut');
            $query->where('statut', $statut);
        }

        //filtrer par client(nom ou prenom)
        if ($request->has('client')){
            $client = $request->input('client');
            $query->where(function ($q) use ($client){
                $q->where('nom_client', 'like', '%' . $client . '%')
                    ->orWhere('prenom_client', 'like', '%' . $client . '%');
            });
        }

        // Gestion du tri
        if ($request->has('sort_by') && $request->filled('sort_by')) {
            $sortBy = $request->input('sort_by');
            $order = $request->input('order', 'asc'); // 'asc' par défaut si non spécifié
            $allowedSorts = ['nom_client', 'prenom_client', 'created_at', 'statut', 'burger_id']; // Colonnes autorisées pour le tri
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $order);
            } else {
                // Gérer le cas où la colonne de tri n'est pas valide (optionnel)
                $query->orderBy('created_at', 'desc'); // Ordre par défaut
            }
        }

        // Pagination
        $perPage = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $commandes = $query->with('details_commandes.burger')->paginate($perPage, ['*'], 'page', $page);

//        Récupérer toutes les commandes avec leurs burgers associés
//        $commandes = Commande::with('burgers')->get();

        // Récupérer les commandes avec les relations nécessaires
        //$commandes = $query->with('details_commandes.burger')->get();

        //retourner la reponse json
        return response()->json([
            'status' => true,
            'commandes' => $commandes->map(function($commande) {
                $commande->montant_total = $commande->prix; // Inclure le montant total
                return $commande;
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validez les données de la requête
        $validatedData = $request->validate([
            'nomClient' => 'required|string|max:30',
            'prenomClient' => 'required|string|max:50',
            'telephone' => 'required|string|max:20',
            'email' => 'required|email',
            'statut' => 'required|in:en cours,termine,paye,annule',
            'details' => 'required|array',
            'details.*.burger_id' => 'required|exists:burgers,id',
            'details.*.quantite' => 'required|integer|min:1'
        ]);

        // Créez une nouvelle commande avec les données validées
        $commande = new Commande();
        $commande->nom_client = $validatedData['nomClient'];
        $commande->prenom_client = $validatedData['prenomClient'];
        $commande->telephone_client = $validatedData['telephone'];
        $commande->email_client = $validatedData['email'];
        $commande->statut = $validatedData['statut'];
        $commande->total = 0; // Initialisez à 0, nous allons le calculer après
        $commande->date = Carbon::now(); // Ajoutez la date actuelle
        $commande->save();

        // Parcourez les détails de la commande et créez des détails de commande
        foreach ($validatedData['details'] as $detail) {
            $burger_id = $detail['burger_id'];
            $quantite = $detail['quantite'];

            // Récupérez le prix du burger depuis la base de données
            $burger = Burger::find($burger_id);
            $prix_unitaire = $burger->prix;

            // Calculez le montant total pour ce burger
            $montant = $prix_unitaire * $quantite;

            // Créez un nouveau détail de commande
            $commande->details_commandes()->create([
                'burger_id' => $burger_id,
                'quantite' => $quantite,
                'prix' => $prix_unitaire,
                'montant' => $montant,
            ]);
        }

        // Calculez et mettez à jour le montant total de la commande
        $commande->calculateTotal();

        return response()->json(['message' => 'Commande créée avec succès'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $commande = Commande::with('details_commandes.burger')->findOrFail($id);

            return response()->json([
                'status' => true,
                'commande' => $commande
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Commande not found'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validation des données d'entrée
        $validatedData = $request->validate([
            'nomClient' => 'required|string|max:30',
            'prenomClient' => 'required|string|max:50',
            'telephone' => 'required|string|max:20',
            'email' => 'required|email',
            'statut' => 'required|in:en cours,termine,paye,annule',
            'details' => 'required|array',
            'details.*.burger_id' => 'required|exists:burgers,id',
            'details.*.quantite' => 'required|integer|min:1',
        ]);

        // Trouver la commande par ID
        $commande = Commande::findOrFail($id);

        // Mise à jour des attributs de la commande
        $commande->nom_client = $validatedData['nomClient'];
        $commande->prenom_client = $validatedData['prenomClient'];
        $commande->telephone_client = $validatedData['telephone'];
        $commande->email_client = $validatedData['email'];
        $previousStatus = $commande->statut;
        $commande->statut = $validatedData['statut'];

        // Calcul du total de la commande
        $total = 0;

        // Synchroniser les détails de la commande
        $details = [];
        foreach ($validatedData['details'] as $detail) {
            try {
                $burger = Burger::findOrFail($detail['burger_id']);
                $prix = $burger->prix * $detail['quantite'];
                $total += $prix;

                $details[$detail['burger_id']] = [
                    'quantite' => $detail['quantite'],
                    'prix' => $prix
                ];
            }catch (ModelNotFoundException $e){
                return response()->json(['error' => 'Burger not found: ' . $detail['burger_id']], 404);
            }
        }

        $commande->total = $total;
        $commande->save();

//        // Mise à jour de la commande
//        $commande = Commande::findOrFail($id);
//        $commande->update($request->all());

        // Mise à jour des détails de la commande
        $commande->burgers()->sync($details);

        // Envoyer la notification par email si la commande est terminee
        if ($previousStatus !== 'termine' && $commande->statut === 'termine') {
            Notification::route('mail', $commande->email_client)
                ->notify(new CommandeReady($commande));
        }
        return response()->json(['message' => 'Commande mise à jour avec succès'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $commandes = Commande::findOrFail($id);
            $commandes->delete();
            //return response()->json(null,204);
            return response()->json([
                'status' => true,
                'message' => "commande deleted !",
                'burger' => $commandes
            ],204);
        }catch(ModelNotFoundException $ex){
            return response()->json(['error' => 'Burger not found'], 404);
        }
    }

//    public function listOrders(Request $request)
//    {
//        $query = Commande::query();
//
//        // Recherche par nom, prénom, téléphone
//        if ($request->has('nom')) {
//            $query->where('nom_client', 'like', '%' . $request->input('nom') . '%');
//        }
//        if ($request->has('prenom')) {
//            $query->where('prenom_client', 'like', '%' . $request->input('prenom') . '%');
//        }
//        if ($request->has('telephone')) {
//            $query->where('telephone_client', 'like', '%' . $request->input('telephone') . '%');
//        }
//
//        // Tri
//        if ($request->has('sort_by')) {
//            $sortBy = $request->input('sort_by');
//            $query->orderBy($sortBy, $request->input('order', 'asc'));
//        }
//
//        // Pagination
//        $limit = $request->input('limit', 10);
//        $page = $request->input('page', 1);
//        $offset = ($page - 1) * $limit;
//
//        $commandes = $query->offset($offset)->limit($limit)->get();
//        return response()->json($commandes);
//    }

    public function cancelOrder($id)
    {
        $commande = Commande::findOrFail($id);
        if ($commande->statut === 'en cours') {
            $commande->statut = 'annule';
            $commande->save();
            return response()->json(['message' => 'Commande annulée avec succès']);
        }
        return response()->json(['message' => 'Commande non trouvée ou déjà terminée'], 404);
    }

    public function markAsCompleted($id)
    {
        $commande = Commande::findOrFail($id);

        if ($commande && $commande->statut === 'en cours') {
            $commande->statut = 'termine';
            $commande->save();

            /// Envoi de l'email avec la facture PDF
            Notification::route('mail', $commande->email_client)
                ->notify(new CommandeReady($commande));

            return response()->json(['message' => 'Commande marquée comme terminée'], 200);
        }
        return response()->json(['message' => 'Commande non trouvée ou déjà terminée'], 404);
    }


    public function payer(Request $request, $id){
        $commande = Commande::findOrFail($id);

        if ($commande->isPaid()) {
            return response()->json(['error' => 'Cette commande est déjà payée'], 400);
        }

//        $validatedData = Validator::make($request->all(), [
//            'montant' => 'required|numeric|min:0',
//        ])->validate();


//        if ($validatedData['montant'] != $commande->total) {
//            return response()->json(['error' => 'Le montant payé doit être exactement égal au total de la commande'], 400);
//        }

        $paiement = new Paiement([
            'commande_id' => $commande->id,
            'montant' => $commande->total,
            'date' => now(),
        ]);
        $paiement->save();

        $commande->statut = 'payé';
        $commande->save();

        return response()->json(['message' => 'Paiement enregistré et commande marquée comme payée'], 200);
    }

//    public function updateStatut(Request $request, $id)
//    {
//        // Validation des données d'entrée
//        $validatedData = $request->validate([
//            'statut' => 'required|in:en cours,termine,paye,annule',
//        ]);
//
//        // Trouver la commande par ID
//        $commande = Commande::findOrFail($id);
//
//        // Mise à jour du statut
//        $commande->statut = $validatedData['statut'];
//        $commande->save();
//
//        // Gestion des actions spécifiques en fonction du nouveau statut
//        if ($commande->statut == 'termine') {
//            // Envoyer une notification au client
//            // (Assurez-vous d'avoir configuré le système d'envoi d'email pour la notification)
//            Mail::to($commande->email_client)->send(new CommandeTermineeMail($commande));
//        }
//
//        if ($commande->statut == 'paye') {
//            // Enregistrer le paiement
//            // Assurez-vous d'avoir une méthode pour enregistrer les paiements
//            $commande->paiements()->create([
//                'montant' => $commande->total,
//                'date' => now(),
//            ]);
//        }
//
//        return response()->json([
//            'status' => true,
//            'message' => 'Statut de la commande mis à jour avec succès',
//            'commande' => $commande
//        ], 200);
//    }

    public function commandesParStatut($statut)
    {
        $commandes = Commande::where('statut', $statut)->get();

        return response()->json([
            'status' => true,
            'commandes' => $commandes
        ]);
    }

    public function rapportCommandesEnCours()
    {
        $commandes = Commande::where('statut', 'en cours')
            ->whereDate('created_at', today())
            ->get();

        return response()->json([
            'status' => true,
            'commandes' => $commandes
        ]);
    }

    public function rapportCommandesValidees()
    {
        $commandes = Commande::where('statut', 'termine')
            ->whereDate('created_at', today())
            ->get();

        return response()->json([
            'status' => true,
            'commandes' => $commandes
        ]);
    }

    public function rapportRecettesJournalières()
    {
        $recettes = Commande::where('statut', 'paye')
            ->whereDate('created_at', today())
            ->sum('total');

        return response()->json([
            'status' => true,
            'recettes' => $recettes
        ]);
    }

    public function rapportCommandesAnnulees()
    {
        $commandes = Commande::where('statut', 'annule')
            ->whereDate('created_at', today())
            ->get();

        return response()->json([
            'status' => true,
            'commandes' => $commandes
        ]);
    }




}
