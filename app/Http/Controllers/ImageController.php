<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Helpers;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    // ==========================
    // 📤 UPLOAD IMAGE
    // ==========================
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'name' => 'required|string|max:255',
            'alt' => 'nullable|string|max:255',
        ]);

        $file = $request->file('image');

        $path = $file->store('images', 'public');
        $userId = $request->header('X-User-Id');
        $image = Image::create([
            'name' => $request->name,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt' => $request->alt,
            'user_id'=>$userId
        ]);

        return response()->json([
            'status' => 'success',
            'data' => new ImageResource($image)
        ]);
    }

    // ==========================
    // 📥 LIST IMAGES
    // ==========================
    public function getImageByUser()
    {
        {
            try {
                // 1. 🔐 X-User-Id VALIDATION
                $userId = $request->header('X-User-Id');
                if (!$userId || !is_numeric($userId)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User ID invalide',
                    ], 400);
                }


                // 3. QUERY OPTIMISÉE (évite N+1)
                $query = Image::query()
                    ->select([
                        'images.id',
                        'images.name',
                        'images.url',
                        'images.path',
                        'images.size',
                        'images.created_at',
                        'images.updated_at',
                        DB::raw('COUNT(images_products.image_id) as products_count')
                    ])
                    ->leftJoin('images_products', 'images.id', '=', 'images_products.image_id')
                    ->leftJoin('products', 'images_products.product_id', '=', 'products.id')
                    ->leftJoin('shops', 'products.shop_id', '=', 'shops.id')
                    ->where('shops.user_id', $userId)
                    ->groupBy([
                        'images.id', 'images.name', 'images.url',
                        'images.path', 'images.size',
                        'images.created_at', 'images.updated_at'
                    ])
                    ->orderBy('images.created_at', 'desc');

                // 4. 🔍 FILTRES (search, shop_id, etc.)
                if ($request->filled('search')) {
                    $query->where(function($q) use ($request) {
                        $q->where('images.name', 'like', "%{$request->search}%")
                            ->orWhere('images.url', 'like', "%{$request->search}%");
                    });
                }

                if ($request->filled('shop_id')) {
                    $query->where('shops.id', $request->shop_id);
                }

                // 5. PAGINATION + SORT
                $perPage = $request->get('per_page', 20);
                $images = $query->paginate($perPage);

                Log::info('Images fetched', [
                    'user_id' => $userId,
                    'count' => $images->count(),
                    'search' => $request->search ?? 'none'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Images récupérées',
                    'data' => ImageResource::collection($images),
                    'meta' => [
                        'current_page' => $images->currentPage(),
                        'last_page' => $images->lastPage(),
                        'total' => $images->total(),
                    ]
                ]);

            } catch (\Exception $e) {
                Log::error('Images index error', [
                    'user_id' => $request->header('X-User-Id'),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur serveur',
                ], 500);
            }
        }
    }

    public function index(Request $request)
    {
        {
            try {
                // 1. 🔐 X-User-Id VALIDATION
                $userId = $request->header('X-User-Id');
                if (!$userId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User ID invalide',
                    ], 400);
                }


                // 3. QUERY OPTIMISÉE (évite N+1)
                $query = Image::query()
                    ->where('user_id', $userId)
                    ->orderBy('images.created_at', 'desc');

                // 4. 🔍 FILTRES (search, shop_id, etc.)
                if ($request->filled('search')) {
                    $query->where(function($q) use ($request) {
                        $q->where('images.name', 'like', "%{$request->search}%")
                            ->orWhere('images.path', 'like', "%{$request->search}%");
                    });
                }

                // 5. PAGINATION + SORT
                $perPage = $request->get('per_page', 20);
                $images = $query->paginate($perPage);

                Log::info('Images fetched', [
                    'user_id' => $userId,
                    'count' => $images->count(),
                    'search' => $request->search ?? 'none'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Images récupérées',
                    'data' => ImageResource::collection($images),
                    'meta' => [
                        'current_page' => $images->currentPage(),
                        'last_page' => $images->lastPage(),
                        'total' => $images->total(),
                    ]
                ]);

            } catch (\Exception $e) {
                Log::error('Images index error', [
                    'user_id' => $request->header('X-User-Id'),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur serveur',
                ], 500);
            }
        }
    }

    // ==========================
    // 👁 SHOW IMAGE
    // ==========================
    public function show($id)
    {
        $image = Image::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $image->id,
                'url' => asset('storage/' . $image->path),
                'alt' => $image->alt,
            ]
        ]);
    }

    // ==========================
    // 🗑 DELETE IMAGE
    // ==========================
    public function destroy($id)
    {
        $image = Image::findOrFail($id);

        Storage::disk('public')->delete($image->path);

        $image->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Image supprimée'
        ]);
    }
}
