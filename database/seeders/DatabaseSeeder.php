<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\FitQuizResponse;
use App\Models\ProductReview;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('secret123'),
            'role' => 'customer',
        ]);

        $testUserId = User::where('email', 'test@example.com')->value('id');

        $legacyDbPath = base_path('../server/data/db.json');
        if (file_exists($legacyDbPath)) {
            $legacy = json_decode(file_get_contents($legacyDbPath), true);
            foreach (($legacy['products'] ?? []) as $row) {
                Product::updateOrCreate(
                    ['id' => (int) $row['id']],
                    [
                        'name' => $row['name'] ?? 'Untitled',
                        'category' => $row['category'] ?? 'General',
                        'price' => (float) ($row['price'] ?? 0),
                        'quantity' => (int) ($row['quantity'] ?? 0),
                        'image' => $row['image'] ?? null,
                        'sku' => $row['sku'] ?? null,
                        'description' => $row['description'] ?? null,
                        'is_new' => (bool) ($row['isNew'] ?? false),
                        'min_stock' => 10,
                        'max_stock' => 100,
                    ]
                );
            }
        }

        Discount::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'name' => 'Welcome Offer',
                'type' => 'percentage',
                'value' => 10,
                'minimum_order_total' => 1000,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(3),
                'usage_limit' => 500,
                'usage_count' => 0,
                'is_active' => true,
            ]
        );

        Discount::updateOrCreate(
            ['code' => 'SAVE250'],
            [
                'name' => 'Flat Savings',
                'type' => 'fixed',
                'value' => 250,
                'minimum_order_total' => 2000,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(2),
                'usage_limit' => 200,
                'usage_count' => 0,
                'is_active' => true,
            ]
        );

        $firstProduct = Product::query()->first();
        $secondProduct = Product::query()->skip(1)->first();

        if ($testUserId && $firstProduct) {
            ProductReview::updateOrCreate(
                [
                    'user_id' => $testUserId,
                    'product_id' => $firstProduct->id,
                ],
                [
                    'rating' => 5,
                    'title' => 'Great fit and finish',
                    'comment' => 'Strong product quality, reliable sizing, and solid finish.',
                    'is_approved' => true,
                    'approved_at' => now(),
                ]
            );

            $firstProduct->update([
                'rating_average' => 5,
                'reviews_count' => 1,
            ]);
        }

        if ($testUserId && $secondProduct) {
            ProductReview::updateOrCreate(
                [
                    'user_id' => $testUserId,
                    'product_id' => $secondProduct->id,
                ],
                [
                    'rating' => 4,
                    'title' => 'Consistent quality',
                    'comment' => 'Comfortable, well-priced, and true to size.',
                    'is_approved' => true,
                    'approved_at' => now(),
                ]
            );

            $secondProduct->update([
                'rating_average' => 4,
                'reviews_count' => 1,
            ]);
        }

        if ($testUserId) {
            FitQuizResponse::updateOrCreate(
                ['user_id' => $testUserId],
                [
                    'answers' => ['1' => 'regular', '2' => 'avg', '3' => 'levis'],
                    'recommendation' => [
                        'productType' => 'Jeans',
                        'fit' => 'Regular Fit',
                        'size' => '32',
                        'message' => 'Recommended for balanced everyday wear.',
                    ],
                    'fit_label' => 'Regular Fit',
                    'size_label' => '32',
                    'score' => 90,
                ]
            );
        }

        $this->call([
            AdminSeeder::class,
        ]);
    }
}
