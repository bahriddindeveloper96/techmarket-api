<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->json('images')->nullable(); // Add images column as JSON
        });
    }

    public function down()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('images'); // Remove images column
        });
    }
};
