<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('client_id')->constrained('users');
            $table->json('items_json');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('promo_discount')->default(0);
            $table->unsignedInteger('promo_free')->default(0);
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->text('address');
            $table->decimal('address_lat', 10, 6)->nullable();
            $table->decimal('address_lng', 10, 6)->nullable();
            $table->unsignedInteger('total');
            $table->string('status')->default('received');
            $table->unsignedBigInteger('placed_at');
            $table->unsignedBigInteger('status_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
