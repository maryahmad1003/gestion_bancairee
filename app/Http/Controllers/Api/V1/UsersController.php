<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filtres optionnels
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $users = $query->paginate(15);

        return response()->json([
            'data' => $users,
            'message' => 'Liste des utilisateurs récupérée avec succès'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());

        return response()->json([
            'data' => $user,
            'message' => 'Utilisateur créé avec succès'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json([
            'data' => $user,
            'message' => 'Utilisateur récupéré avec succès'
        ]);
    }

    /**
     * Display the authenticated user (singleton route).
     */
    public function showAuthenticated()
    {
        $user = auth()->user();

        return response()->json([
            'data' => $user,
            'message' => 'Utilisateur authentifié récupéré avec succès'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return response()->json([
            'data' => $user,
            'message' => 'Utilisateur mis à jour avec succès'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }
}
