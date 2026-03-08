<?php
// app/Http/Controllers/Api/ProductCatalog/BaseController.php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BaseController extends Controller
{
    protected $storeId;
    protected $userId;

    public function __construct(Request $request)
    {
        try {
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
}