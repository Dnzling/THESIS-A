<?php

namespace Tests\Unit;

use App\Models\Inventory\Supply;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class SupplyModelTest extends TestCase
{
    public function test_supply_model_uses_soft_deletes_and_targets_products_table(): void
    {
        $this->assertContains(SoftDeletes::class, class_uses(Supply::class));
        $this->assertSame('products', (new Supply())->getTable());
    }
}
