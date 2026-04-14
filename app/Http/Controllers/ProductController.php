<?php

// App\Http\Controllers\Api\ProductController.php

namespace App\Http\Controllers;

use App\Http\Helpers\Helpers;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{

    public function store(Request $request, $slug)
    {
        $userId = $request->header('X-User-Id');

        // 1. Vérification manuelle du Header
        if (!$userId || $userId === 'undefined') {
            return response()->json([
                'status' => 'error',
                'message' => 'Identification utilisateur manquante (X-User-Id).'
            ], 401);
        }

        try {
            // 2. Validation (Laravel renvoie automatiquement une 422 si ça échoue)
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|unique:products,slug', // Précise la colonne
                'price' => 'required|numeric|min:0',
                'category' => 'required|string|max:50',
                'sku' => 'nullable|string|max:50|unique:products,sku',
                'stock' => 'required|integer|min:0',
                'images' => 'array|max:9',
                'images.*' => 'required|exists:images,id'
            ]);

            // 3. Récupération du Shop avec sécurité
            $shop = Shop::where('slug', $slug)
                ->where('user_id', $userId)
                ->first();

            if (!$shop) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Boutique introuvable ou vous n\'avez pas les droits.'
                ], 404);
            }

            // 4. Utilisation d'une Transaction pour éviter les données orphelines
            return DB::transaction(function () use ($request, $shop) {
                $product = $shop->products()->create($request->only([
                    'name', 'slug', 'price', 'category', 'sku',
                    'stock', 'description', 'dimensions'
                ]));

                // Associer images
                if (!empty($request->images)) {
                    $product->images()->attach($request->images);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Produit créé avec succès',
                    'data' => new ProductResource($product->load('images'))
                ], 201);
            });

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreurs de validation (champs manquants, doublons slug, etc.)
            return response()->json([
                'status' => 'error',
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Erreur imprévue (Base de données, bug code, etc.)
            Log::error("Erreur création produit: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur interne est survenue.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    public function byShop($slug)
    {
        $shop=Shop::query()->where('slug', $slug)->first();
        $products = Product::with('images')
            ->where('shop_id', $shop->id)
            ->where('is_active', true)
            ->latest()
            ->paginate(12);

        return Helpers::success(ProductResource::collection($products));
    }

    public function show($slug)
    {
        $product = Product::with('images')
            ->where('slug', $slug)
            ->firstOrFail();

        return Helpers::success(new ProductResource($product));
    }
}
