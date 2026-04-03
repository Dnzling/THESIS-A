<?php

namespace App\Http\Controllers\Api\ProductCatalog;

use App\Jobs\Run3DReconstructionJob;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\Product3DReconstruction;
use App\Models\ProductCatalog\Product3DReconstructionImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class Product3DReconstructionController extends BaseController
{
    public function index(Request $request)
    {
        $query = Product3DReconstruction::query()
            ->where('store_id', $this->getStoreId())
            ->with('product:id,product_name,sku');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        $records = $query->orderByDesc('id')->paginate($request->get('per_page', 15));

        return $this->successResponse($records, '3D reconstructions retrieved successfully');
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request, [
                'product_id' => 'required|exists:products,id',
                'images' => 'required|array|min:8|max:120',
                'images.*' => 'required|file|mimes:jpeg,jpg,png,webp|max:10240',
                'options' => 'nullable|array',
            ]);

            $product = Product::byStore($this->getStoreId())->find($validated['product_id']);
            if (!$product) {
                return $this->errorResponse('Product not found or does not belong to this store', 404);
            }

            DB::beginTransaction();

            try {
                $reconstruction = Product3DReconstruction::create([
                    'store_id' => $this->getStoreId(),
                    'product_id' => $product->id,
                    'created_by' => $this->getUserId(),
                    'status' => 'queued',
                    'input_count' => count($validated['images']),
                    'progress' => 0,
                    'options' => $validated['options'] ?? null,
                ]);

                $files = $request->file('images');
                $index = 1;
                foreach ($files as $file) {
                    $originalName = $file->getClientOriginalName();
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
                    $fileName = sprintf('%02d_%s', $index, $safeName);

                    $path = "3d/{$this->getStoreId()}/products/{$product->id}/reconstructions/{$reconstruction->id}/raw/{$fileName}";
                    Storage::disk('local')->put($path, file_get_contents($file));

                    Product3DReconstructionImage::create([
                        'reconstruction_id' => $reconstruction->id,
                        'file_name' => $fileName,
                        'file_path' => $path,
                        'file_size_kb' => round($file->getSize() / 1024),
                        'mime_type' => $file->getMimeType(),
                        'display_order' => $index - 1,
                    ]);

                    $index++;
                }

                DB::commit();

                Run3DReconstructionJob::dispatch($reconstruction->id);

                return $this->successResponse(
                    $reconstruction->load('product:id,product_name,sku'),
                    '3D reconstruction queued successfully',
                    201
                );
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('Failed to store reconstruction images', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation error',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to queue reconstruction: ' . $e->getMessage(),
                500,
                [],
                $e
            );
        }
    }

    public function show($id)
    {
        $record = Product3DReconstruction::where('store_id', $this->getStoreId())
            ->with('product:id,product_name,sku', 'images')
            ->findOrFail($id);

        return $this->successResponse($record, '3D reconstruction retrieved successfully');
    }

    public function status($id)
    {
        $record = Product3DReconstruction::where('store_id', $this->getStoreId())
            ->findOrFail($id);

        return $this->successResponse($record, '3D reconstruction status retrieved successfully');
    }

    public function cancel($id)
    {
        $record = Product3DReconstruction::where('store_id', $this->getStoreId())
            ->findOrFail($id);

        if (in_array($record->status, ['ready', 'failed', 'canceled'], true)) {
            return $this->successResponse($record, 'Reconstruction is already finalized');
        }

        $record->update([
            'status' => 'canceled',
            'finished_at' => now(),
        ]);

        return $this->successResponse($record, 'Reconstruction canceled successfully');
    }

    public function result($id)
    {
        $record = Product3DReconstruction::where('store_id', $this->getStoreId())
            ->findOrFail($id);

        if ($record->status !== 'ready' || !$record->output_path) {
            return $this->errorResponse('3D model is not ready yet', 409);
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($record->output_path)) {
            return $this->errorResponse('Output file not found', 404);
        }

        $filePath = $disk->path($record->output_path);
        $mimeType = $disk->mimeType($record->output_path) ?: 'model/gltf-binary';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
