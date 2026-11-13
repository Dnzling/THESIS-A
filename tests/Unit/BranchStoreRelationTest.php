<?php

namespace Tests\Unit;

use App\Models\Store\Branch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class BranchStoreRelationTest extends TestCase
{
    public function test_branch_store_relation_uses_stores_primary_key(): void
    {
        $relation = (new Branch(['store_id' => 38]))->store();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('store_id', $relation->getForeignKeyName());
        $this->assertSame('id', $relation->getOwnerKeyName());
        $this->assertSame('stores', $relation->getRelated()->getTable());
    }
}
