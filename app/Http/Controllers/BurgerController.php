<?php

namespace App\Http\Controllers;

use App\Models\Burger;
use http\Env\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class BurgerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$burgers = Burger::where('archived', false)->get();
        $burgers = Burger::all();
        return response()->json([
            'status' => true,
            'burgers' => $burgers
        ]);
        //return response()->json($burgers, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate(
            [
                'nom' => 'required|string|max:30',
                'description' => 'required|string|max:100',
                'image' => 'required|string',
                'prix' => 'required|numeric',
                'statut' => 'required|boolean',
            ]
        );

        // Debug: Afficher les données reçues
        \Log::info('Données reçues:', $request->all());

        $burger = Burger::create($request->all());

        return response()->json([
            'status' => true,
            'message' => "Burger cree avec succes !",
            'burger' => $burger
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $burger = Burger::findOrFail($id);
            return response()->json($burger, 200);
        } catch (ModelNotFoundException $ex) {
            return response()->json(['error' => 'Burger not found'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $burgers = Burger::findOrFail($id);
            $request->validate(
                [
                    'nom' => 'required|string|max:30',
                    'description' => 'required|string|max:100',
                    'image' => 'required|string',
                    'prix' => 'required|numeric',
                    'statut' => 'required|boolean',
                ]
            );
            $burgers->update($request->all());
            return response()->json([
                'status' => true,
                'message' => "Burger updated successfully !",
                'burger' => $burgers
            ], 204);

        } catch (ModelNotFoundException $ex) {
            return response()->json(['error' => 'Burger not found'], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $burgers = Burger::findOrFail($id);
            $burgers->delete();
            return response()->json(null,204);
//            return response()->json([
//                'status' => true,
//                'message' => "burger deleted !",
//                'burger' => $burgers
//            ],204);
        }catch(ModelNotFoundException $ex){
            return response()->json(['error' => 'Burger not found'], 404);
        }

    }

    public function archive($id){
        $burger = Burger::findOrFail($id);
        $burger->archived = true;
        $burger->save();
        return response()->json(['message' => 'Burger archive avec succes !']);

    }

    // Si vous avez besoin d'une méthode pour désarchiver
    public function unarchive($id)
    {
        $burger = Burger::findOrFail($id);
        $burger->archived = false;
        $burger->save();

        return response()->json(['message' => 'Burger désarchivé avec succès'], 200);
    }
}
