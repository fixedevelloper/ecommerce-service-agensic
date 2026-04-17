<?php


namespace App\Http\Controllers;

use App\Http\Helpers\Helpers;
use App\Models\Address;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    /**
     * Liste des adresses de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $userId = $request->header('X-User-Id');
        $addresses = Address::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->get();

        return Helpers::success($addresses);
    }

    /**
     * Enregistre une nouvelle adresse pour un utilisateur.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        logger($request->all());
        // 1. Validation stricte des données
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|max:20',
            'street'       => 'required|string',
            'city'         => 'required|string',
            'country_code' => 'required|string|size:2', // Format ISO (ex: CG, CM, FR)
            'type'         => 'nullable|string|max:50',
            'postal_code'  => 'nullable|string|max:20',
            'lat'          => 'nullable|numeric',
            'lng'          => 'nullable|numeric',
            'is_default'   => 'sometimes|boolean'
        ]);

        // 2. Identification de l'utilisateur (Header sécurisé)
        $userId = $request->header('X-User-Id');

        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'User ID manquant'], 400);
        }

        try {
            // 3. Début de la transaction atomique
            return DB::transaction(function () use ($userId, $validated) {

                // Vérifier si c'est la toute première adresse de l'utilisateur
                $isFirstAddress = !Address::where('user_id', $userId)->exists();

                // Logique du "par défaut" :
                // - Si c'est la première adresse -> Toujours par défaut.
                // - Sinon -> On regarde la valeur envoyée (ou false par défaut).
                $shouldBeDefault = $isFirstAddress ?: filter_var($validated['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);

                // 4. Si la nouvelle adresse doit être par défaut, on réinitialise les autres
                if ($shouldBeDefault) {
                    Address::where('user_id', $userId)
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
                }

                // 5. Création de l'adresse
                $address = Address::create(array_merge($validated, [
                    'user_id'    => $userId,
                    'is_default' => $shouldBeDefault
                ]));

                // Log de succès en debug
                Log::info("Nouvelle adresse créée", [
                    'user_id' => $userId,
                    'address_id' => $address->id,
                    'is_default' => $shouldBeDefault
                ]);

                return Helpers::success($address);
            });

        } catch (Exception $e) {
            // 6. Logging de l'erreur pour la maintenance
            Log::error("Échec de création d'adresse", [
                'user_id' => $userId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Pour retrouver la ligne exacte
                'payload' => $request->except(['phone']) // On évite de logger les numéros en clair par sécurité
            ]);

            // 7. Réponse d'erreur standardisée pour l'App Android
            return response()->json([
                'status'  => 'error',
                'message' => 'Impossible d\'enregistrer l\'adresse. Un problème technique est survenu.',
                'code'    => 500
            ], 500);
        }
    }



    /**
     * Mettre à jour une adresse.
     * @param Request $request
     * @param Address $address
     * @return JsonResponse|mixed
     */
    public function update(Request $request, Address $address)
    {
        // Vérifier que l'adresse appartient bien à l'utilisateur
        $userId = $request->header('X-User-Id');
        if ($address->user_id !== $userId) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'name'         => 'sometimes|string',
            'phone'        => 'sometimes|string',
            'street'       => 'sometimes|string',
            'is_default'   => 'boolean'
        ]);

        return DB::transaction(function () use ($userId, $validated, $address) {
            if (isset($validated['is_default']) && $validated['is_default']) {
                $this->resetDefaultAddresses($userId);
            }

            $address->update($validated);
            return Helpers::success($address);
        });
    }

    /**
     * Supprimer une adresse.
     * @param Address $address
     * @return JsonResponse
     */
    public function destroy(Request $request,Address $address)
    {
        $userId = $request->header('X-User-Id');
        if ($address->user_id !== $userId) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // Si on a supprimé l'adresse par défaut, on définit la plus récente comme défaut
        if ($wasDefault) {
            $latest = Address::where('user_id',$userId)->latest()->first();
            if ($latest) {
                $latest->update(['is_default' => true]);
            }
        }

        return response()->json(['message' => 'Adresse supprimée']);
    }

    /**
     * Helper pour nettoyer les anciens défauts
     */
    private function resetDefaultAddresses($userId)
    {
        Address::where('user_id', $userId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
