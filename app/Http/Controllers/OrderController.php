<?php

// App\Http\Controllers\Api\OrderController.php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
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
