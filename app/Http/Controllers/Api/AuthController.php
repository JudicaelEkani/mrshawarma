<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Inscription — toujours en tant que "client". Les comptes livreur/admin
     * sont créés côté serveur (seeder) et ne sont jamais auto-inscriptibles.
     */
    public function register(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');

        if ($name === '') {
            return response()->json(['error' => 'Le nom est requis.'], 400);
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Email invalide.'], 400);
        }
        if (strlen($password) < 6) {
            return response()->json(['error' => 'Mot de passe : 6 caractères minimum.'], 400);
        }
        if (User::where('email', $email)->exists()) {
            return response()->json(['error' => 'Un compte existe déjà avec cet email. Connecte-toi plutôt.'], 409);
        }

        // Le mot de passe est haché automatiquement par le cast "hashed" du modèle User.
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'client',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->publicUser($user),
        ], 201);
    }

    /**
     * Connexion — commune à client / livreur / admin, le rôle vient de la base.
     */
    public function login(Request $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            return response()->json(['error' => 'Email et mot de passe requis.'], 400);
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json(['error' => 'Email ou mot de passe incorrect.'], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->publicUser($user),
        ]);
    }

    private function publicUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }
}
