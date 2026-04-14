<?php


// App\Http\Controllers\Api\ShopController.php

namespace App\Http\Controllers;

use App\Http\Helpers\Helpers;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{
    public function store(Request $request)
    {
        try {
            Log::info('Shop creation attempt', [
                'user_id' => $request->header('X-User-Id'),
                'ip' => $request->ip(),
                'data' => $request->except('logo')  // Sécurité
            ]);

            // 1. Validation stricte
            $request->validate([
                'name' => 'required|string|max:255|min:3',
                'description' => 'nullable|string|max:500',  // ✅ Nullable
                'location' => 'required|string|max:255|min:3',
                'category' => 'required|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2120'  // 5Mo
            ]);

            $userId = $request->header('X-User-Id');

            // 2. Vérifier user existe
            if (!$userId || !is_numeric($userId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User ID invalide'
                ], 400);
            }

            // 3. Slug unique
            $baseSlug = Str::slug($request->name);
            $slug = $baseSlug;
            $counter = 1;
            while (Shop::where('slug', $slug)->where('user_id', '!=', $userId)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            // 4. Transaction
            $shop = DB::transaction(function () use ($request, $userId, $slug) {
                $shop = Shop::create([
                    'user_id' => $userId,
                    'name' => $request->name,
                    'description' => $request->description,
                    'location' => $request->location,
                    'category' => $request->category,
                    'slug' => $slug,
                    'is_active' => true,
                ]);

                // 5. Upload logo
                if ($request->hasFile('logo')) {
                    $path = $request->file('logo')->store('shops', 'public');
                    $shop->update(['logo' => $path]);
                }

                return $shop;
            });

            Log::info('Shop created', ['shop_id' => $shop->id, 'slug' => $shop->slug]);

            return response()->json([
                'status' => 'success',
                'message' => 'Boutique créée avec succès',
                'data' => new ShopResource($shop)
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('Shop validation failed', [
                'user_id' => $request->header('X-User-Id'),
                'errors' => $e->errors()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Shop creation failed', [
                'user_id' => $request->header('X-User-Id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur interne serveur',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    public function index()
    {
        $shops = Shop::withCount('products')
            ->where('is_active', true)
            ->latest()
            ->paginate(10);

        return ShopResource::collection($shops);
    }

    public function show($slug)
    {
        $shop = Shop::with(['products.images'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ShopResource($shop);
    }
    public function showBySlug($slug) {
        logger($slug);
        $shop = Shop::with(['products'])
            ->where('slug', $slug)
            ->firstOrFail();

        return Helpers::success(new ShopResource($shop));
    }
}
