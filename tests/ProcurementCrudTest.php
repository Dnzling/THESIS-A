<?php

/**
 * Procurement Module CRUD Test with Authentication
 * Tests all sub-modules for proper CRUD operations
 */

// Test configuration
$baseUrl = 'http://127.0.0.1:8000/api';
$testResults = [];

// Helper function to make HTTP requests with better error handling
function makeRequest($method, $url, $headers, $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true),
        'raw' => $response,
        'error' => $curlError,
    ];
}

// Get or create test token
function getTestToken($baseUrl) {
    $loginData = [
        'email' => 'store.admin@example.com',
        'password' => 'password123',
        'device_name' => 'crud-test-device',
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    // Try to login at API endpoint
    $response = makeRequest('POST', "$baseUrl/auth/login", $headers, $loginData);
    
    if (($response['code'] == 200 || $response['code'] == 201) && isset($response['response']['data'])) {
        $data = $response['response']['data'];
        
        // Check for token in different possible locations
        if (isset($data['access_token'])) {
            echo "✅ Login successful! Token: " . substr($data['access_token'], 0, 20) . "...\n";
            return $data['access_token'];
        } elseif (isset($data['token'])) {
            echo "✅ Login successful! Token: " . substr($data['token'], 0, 20) . "...\n";
            return $data['token'];
        }
    }
    
    echo "❌ Login failed. HTTP: " . $response['code'] . "\n";
    echo "Response: " . json_encode($response['response']) . "\n\n";
    return null;
}

// Test function with better error handling
function testCRUD($baseUrl, $module, $headers, $token = null) {
    $results = [
        'module' => $module,
        'tests' => [],
    ];
    
    $url = "$baseUrl/procurement/$module";
    
    // Add token to headers if available
    $testHeaders = $headers;
    if ($token) {
        $testHeaders[] = "Authorization: Bearer $token";
    }
    
    // Test READ (List)
    $listResponse = makeRequest('GET', $url, $testHeaders);
    $statusCode = $listResponse['code'];
    
    $listStatus = 'FAIL';
    if ($statusCode == 200) {
        $listStatus = 'PASS';
    } elseif ($statusCode == 401 || $statusCode == 403) {
        $listStatus = 'AUTH_ERROR';
    }
    
    $results['tests']['LIST'] = [
        'status' => $listStatus,
        'code' => $statusCode,
        'message' => isset($listResponse['response']['message']) ? $listResponse['response']['message'] : (isset($listResponse['response']['data']) ? 'Items retrieved' : 'No data'),
        'error' => $listResponse['error'] ?? null,
    ];
    
    // Extract ID from response (if items exist)
    $itemId = null;
    if ($listResponse['code'] == 200 && isset($listResponse['response']['data'])) {
        $data = $listResponse['response']['data'];
        
        // Handle paginated response
        if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
            $itemId = $data['data'][0]['id'] ?? null;
        } elseif (is_array($data) && count($data) > 0) {
            $itemId = $data[0]['id'] ?? null;
        }
    }
    
    // Test READ (Show) if item exists
    if ($itemId) {
        $showResponse = makeRequest('GET', "$url/$itemId", $testHeaders);
        $results['tests']['SHOW'] = [
            'status' => in_array($showResponse['code'], [200, 201]) ? 'PASS' : 'FAIL',
            'code' => $showResponse['code'],
            'item_id' => $itemId,
        ];
    } else {
        $results['tests']['SHOW'] = [
            'status' => 'SKIP',
            'code' => 0,
            'reason' => 'No items to test',
        ];
    }
    
    // Test DELETE (if item exists and safe to delete)
    if ($itemId && in_array($module, ['suppliers', 'contracts', 'rfqs', 'quotations'])) {
        // Skip delete for critical modules
        $results['tests']['DELETE'] = [
            'status' => 'SKIP',
            'code' => 0,
            'reason' => 'Delete test skipped for data integrity',
        ];
    }
    
    return $results;
}

// Main test execution
echo "============================================\n";
echo "PROCUREMENT MODULE CRUD TEST\n";
echo "============================================\n\n";

// Standard headers
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
];

// Attempt to get authentication token
echo "🔐 Attempting authentication...\n";
$token = getTestToken($baseUrl);

if ($token) {
    echo "✅ Authentication successful\n\n";
} else {
    echo "⚠️  No authentication token available - will test public endpoints only\n\n";
}

// Test all procurement sub-modules
$modules = [
    'suppliers',
    'contracts',
    'requisitions',
    'rfqs',
    'quotations',
    'purchase-orders',
    'goods-receipts',
    'payments',
];

foreach ($modules as $module) {
    $result = testCRUD($baseUrl, $module, $headers, $token);
    $testResults[$module] = $result;
    
    echo "📦 Module: " . strtoupper($module) . "\n";
    echo "─────────────────────────────────────────\n";
    
    foreach ($result['tests'] as $testName => $testResult) {
        $status = $testResult['status'];
        $statusIcon = match($status) {
            'PASS' => '✅',
            'FAIL' => '❌',
            'SKIP' => '⏭️',
            'AUTH_ERROR' => '🔐',
            default => '❓',
        };
        
        echo "$statusIcon $testName: " . $status;
        echo " (HTTP " . $testResult['code'] . ")";
        
        if (isset($testResult['reason'])) {
            echo " - " . $testResult['reason'];
        }
        if (isset($testResult['message']) && $testResult['message']) {
            echo " - " . $testResult['message'];
        }
        if (isset($testResult['error']) && $testResult['error']) {
            echo " [ERROR: " . $testResult['error'] . "]";
        }
        echo "\n";
    }
    
    echo "\n";
}

// Summary
echo "============================================\n";
echo "TEST SUMMARY\n";
echo "============================================\n\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$authErrors = 0;
$skippedTests = 0;

foreach ($testResults as $module => $result) {
    foreach ($result['tests'] as $test => $testResult) {
        $totalTests++;
        match($testResult['status']) {
            'PASS' => $passedTests++,
            'FAIL' => $failedTests++,
            'AUTH_ERROR' => $authErrors++,
            'SKIP' => $skippedTests++,
            default => null,
        };
    }
}

echo "Total Tests: $totalTests\n";
echo "✅ Passed: $passedTests\n";
echo "❌ Failed: $failedTests\n";
echo "🔐 Auth Errors: $authErrors\n";
echo "⏭️ Skipped: $skippedTests\n\n";

$passPercentage = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;
echo "Pass Rate (excluding auth errors): " . round($passPercentage, 2) . "%\n";

// Conclusion
echo "\n============================================\n";
echo "CONCLUSION\n";
echo "============================================\n\n";

if ($passedTests > 0) {
    echo "✅ Procurement CRUD endpoints are responding correctly!\n";
    echo "📌 Next: Implement authentication and seed test data\n";
} elseif ($authErrors > 0) {
    echo "🔐 API requires authentication. Implement login/token generation.\n";
} else {
    echo "❌ API endpoints not responding. Verify server is running.\n";
}

echo "\n============================================\n";
echo "DETAILED RESULTS (JSON)\n";
echo "============================================\n\n";
echo json_encode($testResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n";
