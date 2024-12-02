<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_group_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type')->default('text'); // text, number, boolean, select
            $table->integer('position')->default(0);
            $table->boolean('required')->default(false);
            $table->boolean('filterable')->default(false);
            $table->json('options')->nullable(); // for select type
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attributes');
    }
};
