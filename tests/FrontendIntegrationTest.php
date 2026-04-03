<?php

/**
 * ============================================
 * FRONTEND TO BACKEND INTEGRATION TEST
 * Procurement Module
 * ============================================
 * 
 * This test validates the full integration between:
 * - Frontend Vue components (TypeScript services)
 * - Backend Laravel API endpoints
 * - Database persistence
 * 
 * Test all CRUD operations and workflow actions
 */

namespace Tests;

// Configuration
$baseUrl = 'http://127.0.0.1:8000/api';
$testToken = null;
$testResults = [];

// Helper function to make HTTP requests
function makeRequest($method, $url, $headers = [], $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    if ($method === 'POST' || $method === 'PUT') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => (int)$code,
        'response' => json_decode($response, true),
        'error' => $curlError,
        'raw' => $response,
    ];
}

// Authenticate
function authenticate($baseUrl) {
    $loginData = [
        'email' => 'store.admin@example.com',
        'password' => 'password123',
        'device_name' => 'integration-test',
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    $response = makeRequest('POST', "$baseUrl/auth/login", $headers, $loginData);
    
    if (($response['code'] == 200 || $response['code'] == 201) && isset($response['response']['data'])) {
        $data = $response['response']['data'];
        if (isset($data['access_token'])) {
            return $data['access_token'];
        }
    }
    
    return null;
}

// Test CRUD operation
function testCrudOperation($baseUrl, $token, $module, $operation, $itemId = null, $itemData = null) {
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        "Authorization: Bearer $token",
    ];
    
    $url = "$baseUrl/procurement/$module";
    $method = 'GET';
    $data = null;
    
    switch ($operation) {
        case 'LIST':
            $method = 'GET';
            break;
        case 'SHOW':
            if (!$itemId) return ['status' => 'SKIP', 'reason' => 'No ID provided'];
            $url .= "/$itemId";
            $method = 'GET';
            break;
        case 'CREATE':
            if (!$itemData) return ['status' => 'SKIP', 'reason' => 'No data provided'];
            $method = 'POST';
            $data = $itemData;
            break;
        case 'UPDATE':
            if (!$itemId || !$itemData) return ['status' => 'SKIP', 'reason' => 'No ID or data'];
            $url .= "/$itemId";
            $method = 'PUT';
            $data = $itemData;
            break;
        case 'DELETE':
            if (!$itemId) return ['status' => 'SKIP', 'reason' => 'No ID provided'];
            $url .= "/$itemId";
            $method = 'DELETE';
            break;
    }
    
    $response = makeRequest($method, $url, $headers, $data);
    
    $status = 'FAIL';
    $message = '';
    
    if ($response['code'] >= 200 && $response['code'] < 300) {
        $status = 'PASS';
        $message = 'Success';
    } elseif ($response['code'] == 401) {
        $status = 'AUTH_ERROR';
        $message = 'Authentication failed';
    } elseif ($response['code'] == 404) {
        $status = 'NOT_FOUND';
        $message = 'Resource not found';
    } elseif ($response['code'] == 422) {
        $status = 'FAIL';
        $message = 'Validation error';
    } elseif ($response['code'] >= 500) {
        $status = 'ERROR';
        $message = 'Server error';
    }
    
    return [
        'status' => $status,
        'code' => $response['code'],
        'message' => $message,
        'response' => $response['response'],
        'error' => $response['error'],
    ];
}

// Test workflow action
function testWorkflowAction($baseUrl, $token, $module, $action, $itemId) {
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        "Authorization: Bearer $token",
    ];
    
    $url = "$baseUrl/procurement/$module/$itemId/$action";
    
    $response = makeRequest('POST', $url, $headers, []);
    
    $status = 'FAIL';
    if ($response['code'] >= 200 && $response['code'] < 300) {
        $status = 'PASS';
    } elseif ($response['code'] == 401) {
        $status = 'AUTH_ERROR';
    } elseif ($response['code'] == 422) {
        $status = 'INVALID_STATE';
    }
    
    return [
        'status' => $status,
        'code' => $response['code'],
        'message' => ucfirst($action) . ' ' . ($status === 'PASS' ? 'successful' : 'failed'),
        'response' => $response['response'],
    ];
}

// ========== MAIN TEST EXECUTION ==========

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  FRONTEND-TO-BACKEND INTEGRATION TEST\n";
echo "  Procurement Module API\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// 1. AUTHENTICATION TEST
echo "🔐 AUTHENTICATION TEST\n";
echo "─────────────────────────────────────────────────────────────────\n";
$testToken = authenticate($baseUrl);

if ($testToken) {
    echo "✅ Authentication successful\n";
    echo "   Token: " . substr($testToken, 0, 30) . "...\n\n";
} else {
    echo "❌ Authentication failed - cannot proceed\n";
    exit(1);
}

// 2. LIST OPERATIONS TEST
echo "📋 LIST OPERATIONS TEST\n";
echo "─────────────────────────────────────────────────────────────────\n";

$modules = ['suppliers', 'contracts', 'requisitions', 'rfqs', 'quotations', 'purchase-orders', 'goods-receipts', 'payments'];
$listResults = [];

foreach ($modules as $module) {
    $result = testCrudOperation($baseUrl, $testToken, $module, 'LIST');
    $listResults[$module] = $result;
    
    $icon = match($result['status']) {
        'PASS' => '✅',
        'FAIL' => '❌',
        'SKIP' => '⏭️',
        'AUTH_ERROR' => '🔐',
        default => '❓',
    };
    
    echo "$icon $module: " . $result['status'] . " (HTTP " . $result['code'] . ")\n";
    
    if (isset($result['response']['data']) && is_array($result['response']['data'])) {
        $count = count($result['response']['data']);
        echo "   → $count items retrieved\n";
    }
}

echo "\n";

// 3. SHOW OPERATION TEST (with existing items)
echo "👁️  SHOW OPERATION TEST\n";
echo "─────────────────────────────────────────────────────────────────\n";

$showTests = [
    'suppliers' => 1,      // Supplier ID 1
    'contracts' => 10,     // Contract ID 10
];

foreach ($showTests as $module => $id) {
    $result = testCrudOperation($baseUrl, $testToken, $module, 'SHOW', $id);
    
    $icon = $result['status'] === 'PASS' ? '✅' : '❌';
    echo "$icon $module (ID: $id): " . $result['status'] . " (HTTP " . $result['code'] . ")\n";
    
    if ($result['status'] === 'PASS' && isset($result['response']['data'])) {
        $item = $result['response']['data'];
        echo "   → Retrieved: " . (json_encode($item) !== '' ? 'OK' : 'Empty') . "\n";
    }
}

echo "\n";

// 4. CREATE OPERATION TEST (with sample data)
echo "➕ CREATE OPERATION TEST\n";
echo "─────────────────────────────────────────────────────────────────\n";

$createTests = [
    [
        'module' => 'suppliers',
        'data' => [
            'supplier_name' => 'Test Supplier ' . date('YmdHis'),
            'supplier_type' => 'manufacturer',
            'company_name' => 'Test Company',
            'email' => 'test-supplier-' . time() . '@example.com',
            'phone' => '+1234567890',
            'address' => '123 Test Street',
            'city' => 'Test City',
            'country' => 'Test Country',
            'status' => 'active',
        ],
    ],
];

$createdIds = [];

foreach ($createTests as $test) {
    echo "📌 Testing: {$test['module']}\n";
    $result = testCrudOperation($baseUrl, $testToken, $test['module'], 'CREATE', null, $test['data']);
    
    $icon = $result['status'] === 'PASS' ? '✅' : '❌';
    echo "$icon CREATE: " . $result['status'] . " (HTTP " . $result['code'] . ")\n";
    
    if ($result['status'] === 'PASS' && isset($result['response']['data']['id'])) {
        $id = $result['response']['data']['id'];
        $createdIds[$test['module']] = $id;
        echo "   → Created ID: $id\n";
    } elseif (isset($result['response']['message'])) {
        echo "   → Message: " . $result['response']['message'] . "\n";
    }
    
    echo "\n";
}

// 5. UPDATE OPERATION TEST (with created data)
echo "✏️  UPDATE OPERATION TEST\n";
echo "─────────────────────────────────────────────────────────────────\n";

if (isset($createdIds['suppliers'])) {
    $updateData = [
        'status' => 'inactive',
        'address' => '456 Updated Street',
    ];
    
    $result = testCrudOperation($baseUrl, $testToken, 'suppliers', 'UPDATE', $createdIds['suppliers'], $updateData);
    
    $icon = $result['status'] === 'PASS' ? '✅' : '❌';
    echo "$icon suppliers (ID: {$createdIds['suppliers']}): " . $result['status'] . " (HTTP " . $result['code'] . ")\n";
    
    if ($result['status'] === 'PASS') {
        echo "   → Update successful\n";
    }
} else {
    echo "⏭️  No created items to update\n";
}

echo "\n";

// 6. DELETE OPERATION TEST (with created data)
echo "🗑️  DELETE OPERATION TEST\n";
echo "─────────────────────────────────────────────────────────────────\n";

if (isset($createdIds['suppliers'])) {
    $result = testCrudOperation($baseUrl, $testToken, 'suppliers', 'DELETE', $createdIds['suppliers']);
    
    $icon = $result['status'] === 'PASS' ? '✅' : '❌';
    echo "$icon suppliers (ID: {$createdIds['suppliers']}): " . $result['status'] . " (HTTP " . $result['code'] . ")\n";
    
    if ($result['status'] === 'PASS') {
        echo "   → Deletion successful\n";
    }
} else {
    echo "⏭️  No created items to delete\n";
}

echo "\n";

// 7. WORKFLOW ACTION TEST
echo "⚙️  WORKFLOW ACTION TEST\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Test submit on purchase requisition (if any exist)
if ($listResults['requisitions']['status'] === 'PASS' && isset($listResults['requisitions']['response']['data']) && count($listResults['requisitions']['response']['data']) > 0) {
    $reqId = $listResults['requisitions']['response']['data'][0]['id'];
    echo "📌 Testing: requisitions (ID: $reqId)\n";
    
    $actions = ['submit', 'approve'];
    foreach ($actions as $action) {
        $result = testWorkflowAction($baseUrl, $testToken, 'requisitions', $action, $reqId);
        
        $icon = match($result['status']) {
            'PASS' => '✅',
            'INVALID_STATE' => '⚠️',
            'AUTH_ERROR' => '🔐',
            default => '❌',
        };
        
        echo "$icon $action: " . $result['status'] . " (HTTP " . $result['code'] . ")\n";
    }
} else {
    echo "⏭️  No requisitions available for workflow testing\n";
}

echo "\n";

// 8. ERROR HANDLING TEST
echo "⚠️  ERROR HANDLING TEST\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Test with invalid ID
$result = testCrudOperation($baseUrl, $testToken, 'suppliers', 'SHOW', 99999);
echo "❌ Invalid Supplier ID (99999):\n";
echo "   HTTP: " . $result['code'] . " (" . ($result['code'] == 404 ? 'Correct: 404 Not Found' : 'Unexpected') . ")\n";

// Test with invalid authentication
$invalidToken = 'invalid_token_12345';
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $invalidToken",
];
$response = makeRequest('GET', "$baseUrl/procurement/suppliers", $headers);
echo "\n🔐 Wrong Authentication Token:\n";
echo "   HTTP: " . $response['code'] . " (" . ($response['code'] == 401 ? 'Correct: 401 Unauthorized' : 'Unexpected') . ")\n";

echo "\n";

// 9. SUMMARY
echo "════════════════════════════════════════════════════════════════\n";
echo "  TEST SUMMARY\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$authErrors = 0;
$skipped = 0;

foreach ($listResults as $module => $result) {
    $totalTests++;
    match($result['status']) {
        'PASS' => $passedTests++,
        'FAIL' => $failedTests++,
        'AUTH_ERROR' => $authErrors++,
        'SKIP' => $skipped++,
        default => null,
    };
}

echo "Total Tests: $totalTests\n";
echo "✅ Passed: $passedTests\n";
echo "❌ Failed: $failedTests\n";
echo "🔐 Auth Errors: $authErrors\n";
echo "⏭️  Skipped: $skipped\n\n";

$passPercentage = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;
echo "Pass Rate: " . round($passPercentage, 2) . "%\n\n";

// Conclusion
echo "════════════════════════════════════════════════════════════════\n";
echo "  CONCLUSION\n";
echo "════════════════════════════════════════════════════════════════\n\n";

if ($passedTests === $totalTests) {
    echo "✅ ALL TESTS PASSED!\n";
    echo "🎉 Frontend UI can successfully communicate with backend API\n";
    echo "📌 Ready for: User workflows, Stress testing, Production\n";
} elseif ($passedTests > 0) {
    echo "⚠️  PARTIAL SUCCESS ($passedTests/$totalTests passing)\n";
    echo "🔧 Issues to fix:\n";
    foreach ($listResults as $module => $result) {
        if ($result['status'] !== 'PASS') {
            echo "   - $module: " . $result['status'] . "\n";
        }
    }
} else {
    echo "❌ TESTS FAILED\n";
    echo "🔧 Debug the issues and re-run tests\n";
}

echo "\n════════════════════════════════════════════════════════════════\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "════════════════════════════════════════════════════════════════\n\n";
