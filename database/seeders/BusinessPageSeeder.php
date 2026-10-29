<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BusinessPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            ['title' => 'About Us', 'is_default' => true],
            ['title' => 'Terms And Conditions', 'is_default' => true],
            ['title' => 'Privacy Policy', 'is_default' => true],
            ['title' => 'Refund Policy', 'is_default' => true],
            ['title' => 'Return Policy', 'is_default' => true],
            ['title' => 'Cancellation Policy', 'is_default' => true],
            ['title' => 'Shipping Policy', 'is_default' => true],
        ];

        foreach ($pages as $p) {
            \App\Models\BusinessPage::create([
                'title' => $p['title'],
                'slug' => \Illuminate\Support\Str::slug($p['title']),
                'content' => '<h1>' . $p['title'] . '</h1><p>Content for ' . $p['title'] . ' goes here.</p>',
                'is_default' => $p['is_default'],
                'is_active' => true,
            ]);
        }
    }
}
