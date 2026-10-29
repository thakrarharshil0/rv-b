<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('original_price', 10, 2)->nullable()->after('price');
            $table->boolean('is_featured')->default(false)->after('is_new');
            $table->string('fit')->nullable()->after('is_featured');
            $table->string('material')->nullable()->after('fit');
            $table->text('care_instructions')->nullable()->after('material');
            $table->json('sizes')->nullable()->after('care_instructions');
            $table->json('colors')->nullable()->after('sizes');
            $table->json('features')->nullable()->after('colors');
            $table->json('gallery')->nullable()->after('features');
            $table->decimal('rating_average', 4, 2)->default(0)->after('max_stock');
            $table->integer('reviews_count')->default(0)->after('rating_average');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'original_price',
                'is_featured',
                'fit',
                'material',
                'care_instructions',
                'sizes',
                'colors',
                'features',
                'gallery',
                'rating_average',
                'reviews_count',
            ]);
        });
    }
};
