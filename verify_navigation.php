<?php
// Quick verification script to test navigation endpoint
require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Get a user with HR role
$user = \App\Models\User::query()
    ->whereHas('role', function($q) {
        $q->where('name', 'HR Manager');
    })
    ->first();

if (!$user) {
    echo "❌ No HR Manager user found\n";
    exit(1);
}

echo "✅ Testing with user: {$user->email} (Role: {$user->role->name})\n\n";

// Simulate auth and fetch navigation
Auth::setUser($user);

$controller = new \App\Http\Controllers\Api\UserNavigationController();
$response = $controller->getUserNavigation(new \Illuminate\Http\Request());

$data = json_decode($response->content(), true);

echo "📋 Navigation Items Returned:\n";
if ($data['success']) {
    foreach ($data['navigation'] as $item) {
        if ($item['module'] === 'hr') {
            echo "  - {$item['display_name']} ({$item['module']}) -> {$item['route_path']}\n";
            if ($item['parent_id']) {
                echo "    └─ Parent ID: {$item['parent_id']}\n";
            }
        }
    }
    
    $jobHiringItems = array_filter($data['navigation'], fn($item) => str_contains($item['name'], 'job-hiring'));
    echo "\n📦 Job Hiring Items Found: " . count($jobHiringItems) . "\n";
    
    if (count($jobHiringItems) > 0) {
        echo "✅ Navigation Successfully Returns Job Hiring Items!\n\n";
        foreach ($jobHiringItems as $item) {
            echo "✓ {$item['display_name']} - {$item['route_path']}\n";
        }
    } else {
        echo "❌ Job Hiring items not found in navigation\n";
    }
} else {
    echo "❌ Error: " . $data['message'] . "\n";
}
