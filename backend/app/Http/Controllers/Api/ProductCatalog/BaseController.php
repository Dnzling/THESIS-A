<?php
// app/Http/Controllers/Api/ProductCatalog/BaseController.php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Http\Controllers\Controller;
use App\Models\Core\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class BaseController extends Controller
{
    protected $storeId;
    protected $userId;

    public function __construct(Request $request)
    {
        try {
            // Allow public asset serving endpoint (used by ecommerce 3D/image viewers)
            if ($request->is('api/product-catalog/assets/*/serve')) {
                $this->storeId = Auth::user()?->store_id;
                $this->userId = Auth::id();
                return;
            }

            // Get store_id from authenticated user
            if (Auth::check()) {
                $user = Auth::user();
                
                // Check if user has store_id
                if (!$user->store_id) {
                    abort(403, 'User is not associated with any store');
                }
                
                $this->storeId = $user->store_id;
                $this->userId = $user->id;
                
                // Log for debugging (optional)
                Log::info('API Request', [
                    'user_id' => $this->userId,
                    'store_id' => $this->storeId,
                    'path' => $request->path(),
                    'method' => $request->method()
                ]);
            } else {
                abort(401, 'Unauthenticated');
            }
        } catch (HttpExceptionInterface $e) {
            // Preserve intended HTTP exceptions (401/403/etc) without converting to 500
            throw $e;
        } catch (\Exception $e) {
            Log::error('BaseController error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Authentication error: ' . $e->getMessage());
        }
    }

    protected function getStoreId()
    {
        return $this->storeId;
    }

    protected function getUserId()
    {
        return $this->userId;
    }

    protected function successResponse($data, $message = 'Success', $code = 200)
    {
        $this->recordCatalogActivity('success', $message, $code);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'store_id' => $this->storeId,
                'timestamp' => now()->toIso8601String()
            ]
        ], $code);
    }

    protected function errorResponse($message, $code = 400, $errors = [], $exception = null)
    {
        $this->recordCatalogActivity('error', $message, $code, $errors);

        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'meta' => [
                'store_id' => $this->storeId,
                'timestamp' => now()->toIso8601String()
            ]
        ];

        // Log error for debugging
        Log::error('API Error Response', [
            'message' => $message,
            'code' => $code,
            'errors' => $errors,
            'exception' => $exception ? $exception->getMessage() : null,
            'store_id' => $this->storeId,
            'user_id' => $this->userId
        ]);

        return response()->json($response, $code);
    }

    protected function validateRequest(Request $request, array $rules)
    {
        $validator = validator($request->all(), $rules);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
        
        return $validator->validated();
    }

    protected function recordCatalogActivity(string $result, string $message, int $statusCode, array $errors = []): void
    {
        try {
            if (!Auth::check()) {
                return;
            }

            $req = request();
            $action = sprintf(
                'merchandising.%s.%s',
                strtolower($req->method()),
                trim(str_replace('/', '.', $req->path()), '.')
            );

            $entityId = null;
            foreach (['id', 'product', 'productId', 'category', 'tag', 'attribute', 'variation'] as $routeKey) {
                $value = $req->route($routeKey);
                if (is_numeric($value)) {
                    $entityId = (int) $value;
                    break;
                }
            }

            ActivityLog::record(
                $action,
                $message,
                [
                    'module' => 'product_catalog',
                    'result' => $result,
                    'status_code' => $statusCode,
                    'path' => $req->path(),
                    'method' => $req->method(),
                    'query' => $req->query(),
                    'has_errors' => !empty($errors),
                ],
                'product_catalog',
                $entityId
            );
        } catch (\Throwable $e) {
            Log::warning('ProductCatalog activity log failed', [
                'error' => $e->getMessage(),
                'path' => request()->path(),
            ]);
        }
    }
}
