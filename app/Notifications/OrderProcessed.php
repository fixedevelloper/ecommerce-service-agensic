<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http; // Importation du client HTTP
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Telegram\TelegramChannel;

class OrderProcessed extends Notification
{
    use Queueable;

    protected $order;
    protected $customerData;

    public function __construct($order,$customerData)
    {
        $this->order = $order;
        $this->customerData=$customerData;

        // Optionnel : On peut appeler le microservice ici ou dans toTelegram
        // Mais pour les Queues, il est parfois préférable de le faire dans toTelegram
        // pour avoir les données les plus fraîches au moment de l'envoi.
    }

    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {
/*        // 1. Appel au microservice Client
        // On suppose que l'URL est définie dans votre .env
        $microserviceUrl = env('USER_SERVICE_URL') . "/users/" . $this->order->customer_id;

        $response = Http::withToken(env('API_SERVICE_TOKEN')) // Si vous avez une auth
        ->get($microserviceUrl);

        if ($response->successful()) {
            $customer = $response->json();
            logger($customer);
            $customerName = $customer['data']['name'] ?? 'Inconnu';
            $customerPhone = $customer['data']['phone'] ?? 'N/A';
        } else {
            $customerName = "Erreur de récupération";
            $customerPhone = "N/A";
        }*/

        // 2. Préparation des variables
        $amount = number_format($this->order->amount, 2) . ' ' . ($this->order->currency ?? '€');
        $orderId = $this->order->id;
        $country = $this->order->country;
        $customerName=$this->customerData['name'];
        $customerPhone=$this->customerData['phone'];
        // 3. Construction du message Telegram
        return TelegramMessage::create()
            ->to(config('services.telegram-bot-api.group_id'))
            ->content(
                "💰 *NOUVELLE TRANSACTION* \n" .
                "───────────────────\n" .
                "🆔 *Commande:* #`{$orderId}`\n\n" .
                "👤 *CLIENT    :*\n" .
                "└ Nom: *{$customerName}*\n" .
                "└ Tel: {$customerPhone}\n" .
                "└ Pays: {$country}\n\n" .
                "💵 *DÉTAILS :*\n" .
                "└ Montant: *{$amount}*\n" .
                "───────────────────\n" .
                "✅ Traitée avec succès."
            )
            ->button('Détails de la commande', env('FRONTEND_URL') . "orders/{$orderId}");
    }
}
