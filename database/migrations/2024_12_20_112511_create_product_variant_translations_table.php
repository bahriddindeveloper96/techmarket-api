<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_variant_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->string('locale');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['product_variant_id', 'locale']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variant_translations');
    }
};
