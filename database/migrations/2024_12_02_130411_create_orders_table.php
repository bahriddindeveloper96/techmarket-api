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
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('delivery_method_id')->constrained();
            $table->foreignId('payment_method_id')->constrained();
            
            // Delivery info
            $table->string('delivery_name');
            $table->string('delivery_phone');
            $table->string('delivery_region');
            $table->string('delivery_district');
            $table->text('delivery_address');
            $table->text('delivery_comment')->nullable();
            $table->decimal('delivery_cost', 10, 2)->default(0);
            $table->timestamp('desired_delivery_date')->nullable();
            
            // Payment info
            $table->string('payment_status')->default('pending');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('total_discount', 10, 2)->default(0);
            $table->json('payment_details')->nullable();
            
            // Order status
            $table->string('status')->default('new');
            $table->json('status_history')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
