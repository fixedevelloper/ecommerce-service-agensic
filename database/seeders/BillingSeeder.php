<?php
namespace Database\Seeders;
// database/seeders/BillingSeeder.php
use App\Models\BillType;
use App\Models\ServiceProvider;
use Illuminate\Database\Seeder;


class BillingSeeder extends Seeder
{
    public function run(): void
    {
        // --- CAMEROUN (CM) ---
        $eneo = ServiceProvider::create([
            'country_code' => 'CM',
            'name'         => 'ENEO',
            'category'     => 'electricity',
            'api_handler'  => 'EneoHandler'
        ]);

        BillType::create([
            'service_provider_id' => $eneo->id,
            'label'               => 'Facture Postpayée',
            'service_fee'         => 100,
            'input_placeholder'   => 'Numéro de contrat (9 chiffres)'
        ]);

        $canalCM = ServiceProvider::create([
            'country_code' => 'CM',
            'name'         => 'Canal+ Cameroun',
            'category'     => 'tv',
            'api_handler'  => 'CanalPlusHandler'
        ]);

        BillType::create([
            'service_provider_id' => $canalCM->id,
            'label'               => 'Réabonnement',
            'service_fee'         => 0,
            'input_placeholder'   => 'Numéro de carte'
        ]);


        // --- CONGO-BRAZZAVILLE (CG) ---
        $e2c = ServiceProvider::create([
            'country_code' => 'CG',
            'name'         => 'E2C (Energie du Congo)',
            'category'     => 'electricity',
            'api_handler'  => 'E2CHandler'
        ]);

        BillType::create([
            'service_provider_id' => $e2c->id,
            'label'               => 'Paiement Facture',
            'service_fee'         => 150,
            'input_placeholder'   => 'Référence client'
        ]);


        // --- RD CONGO (CD) ---
        $sncl = ServiceProvider::create([
            'country_code' => 'CD',
            'name'         => 'SNEL',
            'category'     => 'electricity',
            'api_handler'  => 'SnelHandler'
        ]);

        BillType::create([
            'service_provider_id' => $sncl->id,
            'label'               => 'Achat d\'unités (Prépayé)',
            'service_fee'         => 200,
            'input_placeholder'   => 'Numéro de compteur'
        ]);

        $regideso = ServiceProvider::create([
            'country_code' => 'CD',
            'name'         => 'REGIDESO',
            'category'     => 'water',
            'api_handler'  => 'RegidesoHandler'
        ]);


        // --- GABON (GA) ---
        // Note: J'ai utilisé GA pour le Gabon car GB est le code de la Grande-Bretagne
        $seeg = ServiceProvider::create([
            'country_code' => 'GA',
            'name'         => 'SEEG',
            'category'     => 'electricity',
            'api_handler'  => 'SeegHandler'
        ]);

        BillType::create([
            'service_provider_id' => $seeg->id,
            'label'               => 'EDAN (Prépayé)',
            'service_fee'         => 0,
            'input_placeholder'   => 'Numéro de compteur'
        ]);

        $canalGA = ServiceProvider::create([
            'country_code' => 'GA',
            'name'         => 'Canal+ Gabon',
            'category'     => 'tv',
            'api_handler'  => 'CanalPlusHandler'
        ]);
    }
}
