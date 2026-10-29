<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'test@example.com')->first();
if ($user) {
    $user->password = Illuminate\Support\Facades\Hash::make('password123');
    $user->save();
    echo "USER_UPDATED: test@example.com -> password123\n";
} else {
    App\Models\User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Illuminate\Support\Facades\Hash::make('password123'),
        'role' => 'customer',
    ]);
    echo "USER_CREATED: test@example.com -> password123\n";
}

$admin = App\Models\User::where('email', 'admin@royalvirtus.com')->first();
if ($admin) {
    $admin->password = Illuminate\Support\Facades\Hash::make('password123');
    $admin->save();
    echo "ADMIN_UPDATED: admin@royalvirtus.com -> password123\n";
}
