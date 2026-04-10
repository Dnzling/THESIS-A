<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE supplier_contracts
            MODIFY contract_type ENUM('fixed_price','volume_discount','consignment','exclusive','supply','service')
            NOT NULL DEFAULT 'supply'
        ");

        DB::statement("
            UPDATE supplier_contracts
            SET contract_type = CASE
                WHEN contract_type = 'consignment' THEN 'service'
                ELSE 'supply'
            END
        ");

        DB::statement("
            ALTER TABLE supplier_contracts
            MODIFY contract_type ENUM('supply','service') NOT NULL DEFAULT 'supply'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE supplier_contracts
            MODIFY contract_type ENUM('fixed_price','volume_discount','consignment','exclusive','supply','service')
            NOT NULL DEFAULT 'fixed_price'
        ");

        DB::statement("
            UPDATE supplier_contracts
            SET contract_type = CASE
                WHEN contract_type = 'service' THEN 'consignment'
                ELSE 'fixed_price'
            END
        ");
    }
};
