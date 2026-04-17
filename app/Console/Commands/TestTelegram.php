<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Notifications\OrderProcessed;
use Illuminate\Console\Command;
use App\Notifications\TransactionProcessed;
use Illuminate\Support\Facades\Notification;

class TestTelegram extends Command
{
    // Le nom de la commande à taper dans le terminal
    protected $signature = 'test:telegram {order_id?}';
    protected $description = 'Envoie une notification de test au groupe Telegram';

    public function handle()
    {
        // 1. On récupère une commande existante ou on en crée une factice
        $orderId = $this->argument('order_id');
        $order = $orderId ? Order::find($orderId) : Order::latest()->first();

        if (!$order) {
            $this->error("Aucune commande trouvée en base de données !");
            return;
        }

        $this->info("Envoi de la notification pour la commande #{$order->id}...");

        try {
            // 2. Envoi manuel vers la route Telegram configurée
            Notification::route('telegram', config('services.telegram-bot-api.group_id'))
                ->notify(new OrderProcessed($order));

            $this->info("✅ Succès ! Vérifiez votre groupe Telegram.");
        } catch (\Exception $e) {
            $this->error("❌ Erreur : " . $e->getMessage());
        }
    }
}
