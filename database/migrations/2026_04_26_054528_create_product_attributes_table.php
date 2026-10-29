<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('input_type')->default('Dropdown'); // Dropdown, Swatch, Radio, Text
            $table->string('status')->default('Active'); // Active, Draft
            $table->integer('values_count')->default(0);
            $table->integer('usage_count')->default(0);
            $table->string('updated_by')->default('Admin Team');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
