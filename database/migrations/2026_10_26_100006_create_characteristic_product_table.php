<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characteristic_product', function (Blueprint $table) {
            $table->foreignId('characteristic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('value')->index();
            $table->timestamps();

            $table->primary(['characteristic_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characteristic_product');
    }
};
