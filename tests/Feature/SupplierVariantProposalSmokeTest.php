<?php

use App\Http\Controllers\Api\Procurement\RFQ\RequestForQuotationController;
use App\Http\Controllers\Api\ProductCatalog\ProductVariationController;
use App\Models\Core\Role;
use App\Models\Core\User;
use App\Models\Hr\Employee;
use App\Models\Procurement\RFQ\RequestForQuotation;
use App\Models\Procurement\RFQ\RFQItem;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Procurement\SupplierPortal\SupplierRFQFeedback;
use App\Models\ProductCatalog\Category;
use App\Models\ProductCatalog\Product;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

it('moves an approved supplier variant quote into merchandising and completes it when the variant is created', function () {
    $store = Store::create([
        'name' => 'Variant Smoke Store',
        'store_code' => 'VST-' . uniqid(),
        'type' => 'furniture',
        'status' => 'active',
        'subscription_tier' => DB::table('subscription_plans')->where('plan_key', 'free')->value('id'),
    ]);

    $branch = Branch::create([
        'store_id' => $store->id,
        'name' => 'Main Branch',
        'branch_code' => 'VBR-' . uniqid(),
        'contact_number' => '09170000000',
        'is_main_branch' => true,
        'status' => 'active',
    ]);

    $merchandisingRole = Role::create([
        'name' => 'variant_smoke_merchandiser_' . uniqid(),
        'display_name' => 'Merchandiser',
        'code' => 'VSM-' . uniqid(),
        'store_id' => $store->id,
        'is_active' => true,
    ]);

    $user = User::create([
        'fname' => 'Merchandising',
        'lname' => 'Tester',
        'email' => 'variant-smoke-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'store_id' => $store->id,
        'branch_id' => $branch->id,
        'role_id' => $merchandisingRole->id,
        'is_active' => true,
    ]);

    $employee = Employee::create([
        'user_id' => $user->id,
        'store_id' => $store->id,
        'branch_id' => $branch->id,
        'role_id' => $merchandisingRole->id,
        'employee_number' => 'VEMP-' . uniqid(),
        'hire_date' => now()->toDateString(),
        'employment_type' => 'full_time',
        'status' => 'active',
    ]);

    $category = Category::create([
        'store_id' => $store->id,
        'category_code' => 'VCAT-' . uniqid(),
        'category_name' => 'Furniture',
        'is_active' => true,
    ]);

    $product = Product::create([
        'store_id' => $store->id,
        'sku' => 'CHAIR-' . uniqid(),
        'product_name' => 'Lounge Chair',
        'category_id' => $category->id,
        'product_type' => 'finished_good',
        'unit_of_measurement' => 'piece',
        'base_price' => 8000,
        'is_active' => true,
    ]);

    $supplier = Supplier::create([
        'store_id' => $store->id,
        'supplier_code' => 'VSUP-' . uniqid(),
        'supplier_name' => 'Variant Supplier',
        'phone' => '09171111111',
        'status' => 'active',
    ]);

    $supplierRole = Role::create([
        'name' => 'variant_smoke_supplier_' . uniqid(),
        'display_name' => 'Supplier',
        'code' => 'VSS-' . uniqid(),
        'is_active' => true,
    ]);
    $supplierUser = User::create([
        'fname' => 'Supplier',
        'lname' => 'Tester',
        'email' => 'supplier-variant-smoke-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'role_id' => $supplierRole->id,
        'is_active' => true,
    ]);
    $portal = SupplierPortal::create([
        'user_id' => $supplierUser->id,
        'supplier_id' => $supplier->id,
        'status' => 'approved',
        'verified_at' => now(),
    ]);

    $rfq = RequestForQuotation::create([
        'rfq_number' => 'VRFQ-' . uniqid(),
        'store_id' => $store->id,
        'title' => 'Variant smoke RFQ',
        'issue_date' => now()->toDateString(),
        'deadline_date' => now()->addDays(7)->toDateString(),
        'status' => 'receiving',
        'created_by' => $employee->id,
    ]);
    $rfqItem = RFQItem::create([
        'rfq_id' => $rfq->id,
        'product_id' => $product->id,
        'quantity' => 4,
    ]);

    $feedback = SupplierRFQFeedback::create([
        'supplier_portal_id' => $portal->id,
        'rfq_id' => $rfq->id,
        'rfq_item_id' => $rfqItem->id,
        'quoted_price' => 4250,
        'available_quantity' => 10,
        'has_variant' => true,
        'variant_name' => 'Walnut Brown - Large',
        'supplier_sku' => 'SUP-WAL-L',
        'variant_size' => 'Large',
        'variant_color' => 'Walnut Brown',
        'variant_texture' => 'Wood Grain',
        'variant_finish' => 'Matte',
        'variant_material' => 'Acacia Wood',
        'unit_of_measurement' => 'piece',
        'length_cm' => 80,
        'width_cm' => 75,
        'height_cm' => 95,
        'weight_kg' => 18.5,
        'status' => 'pending',
        'merchandising_status' => 'awaiting_procurement_approval',
        'submitted_at' => now(),
    ]);

    $this->actingAs($user);
    $approvalRequest = Request::create('/api/procurement/rfqs/review', 'POST', ['status' => 'approved']);
    $approvalRequest->setUserResolver(fn () => $user);
    $approvalResponse = app(RequestForQuotationController::class)
        ->reviewPortalFeedback($approvalRequest, $rfq->id, $feedback->id);

    expect($approvalResponse->getStatusCode())->toBe(200);
    expect($feedback->fresh()->merchandising_status)->toBe('pending');

    $requestResponse = app(ProductVariationController::class)->requests($approvalRequest);
    $requestPayload = $requestResponse->getData(true);
    expect(collect($requestPayload['data'])->pluck('id'))->toContain($feedback->id);

    $createRequest = Request::create('/api/product-catalog/variations', 'POST', [
        'proposal_id' => $feedback->id,
        'product_id' => $product->id,
        'variation_sku' => $product->sku . '-WAL-L',
        'variation_name' => $feedback->variant_name,
        'color' => $feedback->variant_color,
        'size' => $feedback->variant_size,
        'material' => $feedback->variant_material,
        'texture' => $feedback->variant_texture,
        'finish' => $feedback->variant_finish,
        'cost_price' => $feedback->quoted_price,
        'unit_of_measurement' => $feedback->unit_of_measurement,
        'length_cm' => $feedback->length_cm,
        'width_cm' => $feedback->width_cm,
        'height_cm' => $feedback->height_cm,
        'weight_kg' => $feedback->weight_kg,
        'initial_stock' => 0,
        'is_active' => true,
    ]);
    $createRequest->setUserResolver(fn () => $user);
    $createResponse = app(ProductVariationController::class)->store($createRequest);

    expect($createResponse->getStatusCode())->toBe(201);
    $feedback->refresh();
    expect($feedback->merchandising_status)->toBe('created');
    expect($feedback->created_variation_id)->not->toBeNull();

    $this->assertDatabaseHas('product_variations', [
        'id' => $feedback->created_variation_id,
        'product_id' => $product->id,
        'variation_name' => 'Walnut Brown - Large',
        'texture' => 'Wood Grain',
        'finish' => 'Matte',
        'cost_price' => '4250.00',
    ]);

    $afterCreation = app(ProductVariationController::class)->requests($approvalRequest)->getData(true);
    expect(collect($afterCreation['data'])->pluck('id'))->not->toContain($feedback->id);
});
