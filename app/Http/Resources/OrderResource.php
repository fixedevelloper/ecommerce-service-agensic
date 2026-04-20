<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'reference' => $this->reference,

            // 💵 Détails financiers
            'financials' => [
                'amount'   => (float) $this->amount,
                'currency' => $this->currency,
                'display'  => number_format($this->amount, 2, '.', ' ') . ' ' . $this->currency,
            ],

            // 🚦 Statut avec helpers visuels
            'status' => [
                'value' => $this->status,
                'label' => $this->getStatusLabel(),
                'color' => $this->getStatusColor(),
            ],

            // 💳 Paiement
            'payment' => [
                'method' => $this->payment_method,
                'transaction_id' => $this->external_transaction_id,
                'is_paid' => in_array($this->status, ['paid', 'completed', 'shipped']),
            ],

            // 🏪 Boutique
            'shop' => new ShopResource($this->whenLoaded('shop')),

            // 👤 Client (Microservice User)
            // On injecte manuellement les données si elles existent (user_data)
            'customer' => $this->user_data ?? [
                    'id' => $this->user_id,
                    'name' => 'Chargement...'
                ],

            // 📍 Adresses
            'addresses' => [
                'billing'  => new AddressResource($this->whenLoaded('billingAddress')),
                'shipping' => new AddressResource($this->whenLoaded('shippingAddress')),
            ],

            // 🧾 Articles
            'items_count' => $this->items_count ?? $this->items->count(),
            'items'       => OrderItemResource::collection($this->whenLoaded('items')),

            // 📅 Dates
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }

    private function getStatusLabel(): string
    {
        return match($this->status) {
        'pending'   => 'En attente',
            'paid'      => 'Payée',
            'shipped'   => 'Expédiée',
            'completed' => 'Livrée',
            'cancelled' => 'Annulée',
            'refunded'  => 'Remboursée',
            default     => ucfirst($this->status),
        };
    }

    private function getStatusColor(): string
    {
        return match($this->status) {
        'pending'   => '#f59e0b', // Amber
            'paid'      => '#3b82f6', // Blue
            'shipped'   => '#8b5cf6', // Violet
            'completed' => '#10b981', // Emerald
            'cancelled' => '#ef4444', // Red
            default     => '#64748b', // Slate
        };
    }
}
