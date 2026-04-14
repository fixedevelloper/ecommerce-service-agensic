<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        // 1. Shops (boutiques marchands)
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index(); // Propriétaire
            $table->string('name');
            $table->string('category')->index();
            $table->string('logo')->nullable();
            $table->string('slug')->unique()->index();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']); // Requêtes courantes
        });

        // 2. Addresses (livraison/facturation)
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type')->default('billing'); // billing, shipping
            $table->string('name');
            $table->string('phone');
            $table->string('street');
            $table->string('city');
            $table->string('postal_code')->nullable();
            $table->string('country_code', 2)->default('CM'); // ISO2
            $table->decimal('lat', 10, 8)->nullable(); // Géoloc
            $table->decimal('lng', 11, 8)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'type', 'is_default']);
            $table->index(['user_id', 'is_default']);
        });

        // 3. Images (images produits)
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path'); // storage/app/public/images/
            $table->unsignedBigInteger('user_id');
            $table->string('mime_type'); // image/jpeg
            $table->integer('size'); // bytes
            $table->string('alt')->nullable();
            $table->timestamps();

            $table->index('mime_type');
        });

        // 4. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade')->index();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('category')->nullable()->index();
            $table->string('sku')->unique()->index();
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // {canal_product: 'ACCESS'}
            $table->timestamps();

            $table->index(['shop_id', 'is_active', 'category']);
        });

        // 5. Images Products (polymorphique)
        Schema::create('images_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id')->constrained('images')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('position')->default(0); // Ordre affichage
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['image_id', 'product_id']);
            $table->index(['product_id', 'is_primary']);
        });

        // 6. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('customer_id')->index(); // Micro-service users
            $table->foreignId('shop_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('billing_address_id')->nullable()->constrained('addresses');
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses');

            $table->string('reference')->unique()->index(); // #ORD-20260414-001
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('XAF')->index();
            $table->enum('status', ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('pending')->index();

            $table->string('payment_method')->index(); // momo, om, card, bank_transfer
            $table->string('external_transaction_id')->nullable()->unique(); // Canal+/MoMo ID

            $table->json('api_response_log')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['shop_id', 'status']);
            $table->index('created_at');
        });

        // 7. Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name'); // Snapshot nom produit
            $table->string('sku'); // Snapshot SKU
            $table->decimal('price', 12, 2); // Prix au moment commande
            $table->integer('quantity');
            $table->decimal('total', 12, 2); // price * quantity
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('images_products');
        Schema::dropIfExists('products');
        Schema::dropIfExists('images');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('shops');
    }

};
