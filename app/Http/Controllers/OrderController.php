<?php

// App\Http\Controllers\Api\OrderController.php

namespace App\Http\Controllers;

use App\Http\Helpers\Helpers;
use App\Http\Resources\OrderResource;
use App\Http\Services\microService\UserServiceClient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Notifications\OrderProcessed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class OrderController extends Controller
{

    protected $userService;
    public function __construct(
        UserServiceClient $userServiceClient
    )
    {
        $this->userService=$userServiceClient;
    }
    public function index(Request $request)
    {
        // 1. Initialisation de la requête avec les relations locales indispensables
        // On utilise withCount pour les items pour éviter de charger tous les produits en liste
        $query = Order::with(['shop'])
            ->withCount('items');

        // 2. Recherche par référence ou client (ID)
        if ($request->filled('search')) {
            $query->where('reference', 'like', "%{$request->search}%");
        }

        // 3. Filtres avancés
        $query->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->shop_id, fn($q) => $q->where('shop_id', $request->shop_id))
            ->when($request->payment_method, fn($q) => $q->where('payment_method', $request->payment_method));

        // 4. Pagination
        $orders = $query->latest()->paginate($request->per_page ?? 15);

        // 5. Hydratation Cross-Service (Users)
        // On récupère tous les IDs clients uniques de la page actuelle
        $userIds = $orders->pluck('user_id')->unique()->filter()->toArray();

        if (!empty($userIds)) {
            // Appel au microservice User (via votre client HTTP interne)
            $usersFromServer = $this->userService->getUsersByIds($userIds);

            // On injecte les données dans chaque commande
            $orders->getCollection()->transform(function ($order) use ($usersFromServer) {
                $order->user_data = $usersFromServer[$order->user_id] ?? [
                        'id' => $order->user_id,
                        'name' => 'Utilisateur #' . $order->user_id
                    ];
                return $order;
            });
        }

        // 6. Retour via la Resource
        return OrderResource::collection($orders);
    }
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
                $customerData=[];
                // 1. Appel au microservice Client
                // On suppose que l'URL est définie dans votre .env
                $microserviceUrl = env('USER_SERVICE_URL') . "/users/" . $userId;

                $response = Http::withToken(env('API_SERVICE_TOKEN')) // Si vous avez une auth
                ->get($microserviceUrl);

                if ($response->successful()) {
                    $customer = $response->json();
                    $customerName = $customer['data']['name'] ?? 'Inconnu';
                    $customerPhone = $customer['data']['phone'] ?? 'N/A';
                } else {
                    $customerName = "Erreur de récupération";
                    $customerPhone = "N/A";
                }
                $customerData=[
                    'country_name'=>$customer['customer_name'] ??'N/A',
                    'country_code'=>$customer['country_code'] ??'N/A',
                  'name'=>$customerName,
                  'phone'=>$customerPhone
                ];
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

                Notification::route('telegram', config('services.telegram-bot-api.group_id'))
                    ->notify(new OrderProcessed($order,$customerData));
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
    public function Myindex(Request $request)
    {
        $orders = Order::with(['shop', 'items'])
            ->where('customer_id', $request->user_id)
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show($id)
    {
        $order = Order::with(['shop', 'billingAddress', 'shippingAddress', 'items.product'])
            ->findOrFail($id);

        // Récupération optionnelle du customer via microservice
        $order->user_data = $this->userService->getUserById($order->user_id);

        return Helpers::success(new OrderResource($order));
    }
}
