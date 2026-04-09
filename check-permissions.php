<?php

// Get all permissions from database
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check table structure first
$columns = \DB::getSchemaBuilder()->getColumnListing('permissions');
echo "Columns: " . implode(", ", $columns) . "\n\n";

$permissions = \DB::table('permissions')
    ->select('name')
    ->distinct()
    ->orderBy('name')
    ->get();

echo "═══════════════════════════════════════════════════════════════\n";
echo "ALL PERMISSIONS IN SYSTEM\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Group by module prefix
$grouped = $permissions->groupBy(function($item) {
    return explode('.', $item->name)[0] ?? 'other';
});

foreach ($grouped as $module => $guardPerms) {
    echo "MODULE: $module (" . count($guardPerms) . " permissions)\n";
    echo "─────────────────────────────────────────────\n";
    
    foreach ($guardPerms as $perm) {
        echo "  ✓ $perm->name\n";
    }
    
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
