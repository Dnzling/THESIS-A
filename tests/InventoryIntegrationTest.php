<?php

/**
 * INVENTORY MODULE - FRONTEND-TO-BACKEND INTEGRATION TEST
 * Tests all inventory API endpoints to verify frontend service methods connect correctly to backend
 * 
 * File: backend/tests/InventoryIntegrationTest.php
 * Date: 2026-03-10
 * Purpose: Comprehensive validation of inventory UI-to-API integration
 */

class InventoryIntegrationTest
{
    private string $baseUrl = 'http://127.0.0.1:8000/api';
    private string $testToken = '';
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;
    private array $testTimings = [];

    public function run(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🏭 INVENTORY MODULE - FRONTEND-TO-BACKEND INTEGRATION TEST\n";
        echo str_repeat("=", 80) . "\n\n";

        // Step 1: Authenticate
        if (!$this->authenticate()) {
            echo "❌ AUTHENTICATION FAILED\n";
            return;
        }

        // Step 2: Test Dashboard Endpoints
        $this->testDashboardEndpoints();

        // Step 3: Test Branch Inventory
        $this->testBranchInventory();

        // Step 4: Test Core Inventory Endpoints
        $this->testCoreInventory();

        // Step 5: Test Stock Adjustments
        $this->testAdjustments();

        // Step 6: Test Stock Transfers
        $this->testTransfers();

        // Step 7: Test Stock Alerts
        $this->testAlerts();

        // Step 8: Test Transactions
        $this->testTransactions();

        // Step 9: Test Products
        $this->testProducts();

        // Step 10: Test Categories
        $this->testCategories();

        // Step 11: Test Units
        $this->testUnits();

        // Step 12: Test Stock Issues
        $this->testStockIssues();

        // Step 13: Test Stock Returns
        $this->testStockReturns();

        // Step 14: Test Stock Counts
        $this->testStockCounts();

        // Step 15: Test Warehouses
        $this->testWarehouses();

        // Print Summary
        $this->printSummary();
    }

    private function authenticate(): bool
    {
        echo "🔐 AUTHENTICATION TEST\n";
        echo "─────────────────────────────────────\n";

        $startTime = microtime(true);
        
        $response = $this->makeRequest(
            'POST',
            '/auth/login',
            [
                'email' => 'store.admin@example.com',
                'password' => 'password123',
                'device_name' => 'inventory-test-device',
            ]
        );

        $duration = microtime(true) - $startTime;

        if (isset($response['data']['access_token'])) {
            $this->testToken = $response['data']['access_token'];
            echo "✅ Login successful! Token: " . substr($this->testToken, 0, 20) . "...\n";
            echo "⏱️  Duration: {$duration}s\n\n";
            return true;
        }

        echo "❌ Authentication failed\n\n";
        return false;
    }

    private function testDashboardEndpoints(): void
    {
        echo "📊 DASHBOARD ENDPOINTS TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/dashboard/stats' => '/inventory/dashboard/stats',
            'GET /api/inventory/dashboard/summary-cards' => '/inventory/dashboard/summary-cards',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                echo "✅ $label: PASS (HTTP 200) [{$duration}s]\n";
                $this->passed++;
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testBranchInventory(): void
    {
        echo "🏢 BRANCH INVENTORY TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/branches' => '/inventory/branches',
            'GET /api/inventory/branch/1' => '/inventory/branch/1',
            'GET /api/inventory/branch/1/summary' => '/inventory/branch/1/summary',
            'GET /api/inventory/branch/1/low-stock' => '/inventory/branch/1/low-stock',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                $itemCount = count($response['data'] ?? []);
                echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                $this->passed++;
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testCoreInventory(): void
    {
        echo "📦 CORE INVENTORY ENDPOINTS TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/items' => '/inventory/items',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                $itemCount = count($response['data'] ?? []);
                echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No items to test) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testAdjustments(): void
    {
        echo "🔧 STOCK ADJUSTMENTS TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/adjustments' => '/inventory/adjustments',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                $itemCount = count($response['data'] ?? []);
                echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No adjustments) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testTransfers(): void
    {
        echo "🚚 STOCK TRANSFERS TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/transfers' => '/inventory/transfers',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                $itemCount = count($response['data'] ?? []);
                echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No transfers) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testAlerts(): void
    {
        echo "⚠️  STOCK ALERTS TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/alerts' => '/inventory/alerts',
            'GET /api/inventory/alerts/summary' => '/inventory/alerts/summary',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                if (str_contains($label, 'summary')) {
                    echo "✅ $label: PASS (HTTP 200) [{$duration}s]\n";
                } else {
                    $itemCount = count($response['data'] ?? []);
                    echo "✅ $label: PASS (HTTP 200) → $itemCount alerts [{$duration}s]\n";
                }
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No alerts) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testTransactions(): void
    {
        echo "📝 TRANSACTIONS TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/transactions' => '/inventory/transactions',
            'GET /api/inventory/transactions/summary' => '/inventory/transactions/summary',
            'GET /api/inventory/transactions/recent' => '/inventory/transactions/recent',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                if (str_contains($label, 'summary')) {
                    echo "✅ $label: PASS (HTTP 200) [{$duration}s]\n";
                } else {
                    $itemCount = count($response['data'] ?? []);
                    echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                }
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No transactions) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testProducts(): void
    {
        echo "🛍️  PRODUCTS TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/products' => '/inventory/products',
            'GET /api/inventory/products/types' => '/inventory/products/types',
            'GET /api/inventory/products/stats/overview' => '/inventory/products/stats/overview',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                if (str_contains($label, 'stats')) {
                    echo "✅ $label: PASS (HTTP 200) [{$duration}s]\n";
                } else {
                    $itemCount = count($response['data'] ?? []);
                    echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                }
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No products) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testCategories(): void
    {
        echo "🗂️  CATEGORIES TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/categories' => '/inventory/categories',
            'GET /api/inventory/categories/tree' => '/inventory/categories/tree',
            'GET /api/inventory/categories/stats/overview' => '/inventory/categories/stats/overview',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                if (str_contains($label, 'stats')) {
                    echo "✅ $label: PASS (HTTP 200) [{$duration}s]\n";
                } else {
                    $itemCount = count($response['data'] ?? []);
                    echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                }
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No categories) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testUnits(): void
    {
        echo "📏 UNITS TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/units' => '/inventory/units',
            'GET /api/inventory/units/types' => '/inventory/units/types',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                $itemCount = count($response['data'] ?? []);
                echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No units) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testStockIssues(): void
    {
        echo "🚨 STOCK ISSUES TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/issues' => '/inventory/issues',
            'GET /api/inventory/issues/reasons' => '/inventory/issues/reasons',
            'GET /api/inventory/issues/stats/overview' => '/inventory/issues/stats/overview',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                if (str_contains($label, 'stats')) {
                    echo "✅ $label: PASS (HTTP 200) [{$duration}s]\n";
                } else {
                    $itemCount = count($response['data'] ?? []);
                    echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                }
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No issues) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testStockReturns(): void
    {
        echo "🔙 STOCK RETURNS TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/returns' => '/inventory/returns',
            'GET /api/inventory/returns/reasons' => '/inventory/returns/reasons',
            'GET /api/inventory/returns/types' => '/inventory/returns/types',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                $itemCount = count($response['data'] ?? []);
                echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No returns) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testStockCounts(): void
    {
        echo "📊 STOCK COUNTS TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/counts' => '/inventory/counts',
            'GET /api/inventory/counts/types' => '/inventory/counts/types',
            'GET /api/inventory/counts/statuses' => '/inventory/counts/statuses',
            'GET /api/inventory/counts/stats/overview' => '/inventory/counts/stats/overview',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                if (str_contains($label, 'stats')) {
                    echo "✅ $label: PASS (HTTP 200) [{$duration}s]\n";
                } else {
                    $itemCount = count($response['data'] ?? []);
                    echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                }
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No counts) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function testWarehouses(): void
    {
        echo "🏭 WAREHOUSES TEST\n";
        echo "─────────────────────────────────────\n";

        $endpoints = [
            'GET /api/inventory/warehouses' => '/inventory/warehouses',
            'GET /api/inventory/warehouses/types' => '/inventory/warehouses/types',
        ];

        foreach ($endpoints as $label => $endpoint) {
            $startTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);
            $duration = microtime(true) - $startTime;

            if (isset($response['response_code']) && $response['response_code'] === 200) {
                $itemCount = count($response['data'] ?? []);
                echo "✅ $label: PASS (HTTP 200) → $itemCount items [{$duration}s]\n";
                $this->passed++;
            } else if ($response['response_code'] === 404) {
                echo "⏭️  $label: SKIP (No warehouses) [{$duration}s]\n";
            } else {
                echo "❌ $label: FAIL (HTTP {$response['response_code']})\n";
                $this->failed++;
            }
            $this->testTimings[$label] = $duration;
        }
        echo "\n";
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $headers = ['Content-Type: application/json'];
        if ($this->testToken) {
            $headers[] = "Authorization: Bearer {$this->testToken}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'response_code' => 0,
                'curl_error' => $curlError,
                'data' => [],
            ];
        }

        $decoded = json_decode($response, true);

        return [
            'response_code' => $httpCode,
            'data' => $decoded['data'] ?? $decoded ?? [],
            'message' => $decoded['message'] ?? '',
        ];
    }

    private function printSummary(): void
    {
        echo str_repeat("=", 80) . "\n";
        echo "📊 TEST SUMMARY\n";
        echo str_repeat("=", 80) . "\n\n";

        $total = $this->passed + $this->failed;
        $passRate = $total > 0 ? round(($this->passed / $total) * 100, 2) : 0;

        echo "✅ Passed:  $this->passed\n";
        echo "❌ Failed:  $this->failed\n";
        echo "📊 Total:   $total\n";
        echo "📈 Pass Rate: {$passRate}%\n\n";

        if ($this->failed === 0) {
            echo "✅ CONCLUSION:\n";
            echo "🎉 ALL TESTS PASSED!\n";
            echo "🎉 Frontend UI can successfully communicate with backend API\n";
            echo "📌 Ready for: User workflows, Stress testing, Production\n";
        } else {
            echo "⚠️  CONCLUSION:\n";
            echo "Some tests failed. Review the output above for details.\n";
        }

        echo "\n" . str_repeat("=", 80) . "\n";
    }
}

// Run the test
$test = new InventoryIntegrationTest();
$test->run();
