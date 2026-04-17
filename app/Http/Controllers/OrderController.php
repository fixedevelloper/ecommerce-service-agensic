<?php

// App\Http\Controllers\Api\OrderController.php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{


    public function storeOrder(Request $request)
    {
        // 1. Validation des données entrantes
        $validated = $request->validate([
            'address_id' => 'required|string',
            'currency' => 'required|string|max:3',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'total' => 'required|numeric',
        ]);

        try {

            // 2. Utilisation d'une transaction pour la sécurité des données
            return DB::transaction(function () use ($validated, $request) {
                $userId = $request->header('X-User-Id');
                // 3. Création de la commande principale
                $order = Order::create([
                    'customer_id' => $userId,
                    'billing_address_id' => $validated['address_id'],
                    'shipping_address_id' => $validated['address_id'],
                    'currency' => $validated['currency'],
                    'delivery_note' => $request->delivery_note,
                    'payment_method' => $validated['payment_method'],
                    'amount' => $validated['total'],
                    'reference' => Str::uuid(),
                    'status' => 'pending', // Statut initial
                ]);

                // 4. Enregistrement des produits de la commande (Items)
                foreach ($validated['items'] as $item) {
                    $product = Product::find($item['product_id']);

                    OrderItem::create([
                        'name' => $product->name,
                        'sku'=>$product->sku,
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $product->price, // On stocke le prix au moment de l'achat
                        'total'=>$product->price*$item['quantity']
                    ]);

                    // Optionnel : Déduire le stock ici
                     $product->decrement('stock', $item['quantity']);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Commande enregistrée avec succès',
                    'order_id' => $order->id
                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la commande : ' . $e->getMessage()
            ], 500);
        }
    }
    public function index(Request $request)
    {
        $orders = Order::with(['shop', 'items'])
            ->where('customer_id', $request->user_id)
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show($id)
    {
        $order = Order::with(['shop', 'items'])
            ->findOrFail($id);

        return new OrderResource($order);
    }
}
