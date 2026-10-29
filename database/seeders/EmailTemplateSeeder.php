<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Welcome Email',
                'slug' => 'welcome-email',
                'trigger' => 'User Registration',
                'subject' => 'Welcome to RoyalVirtus!',
                'content' => '<h1>Welcome {{name}}!</h1><p>We are excited to have you on board.</p>',
                'tone' => 'Friendly',
                'type' => 'Transactional',
                'status' => 'Active',
            ],
            [
                'name' => 'Order Confirmation',
                'slug' => 'order-confirmation',
                'trigger' => 'New Order',
                'subject' => 'Your Order #{{order_id}} is Confirmed!',
                'content' => '<h1>Order Confirmed!</h1><p>Thank you for your purchase.</p>',
                'tone' => 'Professional',
                'type' => 'Transactional',
                'status' => 'Active',
            ],
            [
                'name' => 'Flash Sale Alert',
                'slug' => 'flash-sale-alert',
                'trigger' => 'Marketing Campaign',
                'subject' => 'Huge Sale Live Now!',
                'content' => '<h1>50% OFF!</h1><p>Don\'t miss out on our biggest sale of the year.</p>',
                'tone' => 'Exciting',
                'type' => 'Marketing',
                'status' => 'Draft',
            ],
        ];

        foreach ($templates as $tmpl) {
            \App\Models\EmailTemplate::create($tmpl);
        }
    }
}
