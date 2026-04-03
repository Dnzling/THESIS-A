<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL implicitly commits on ALTER TABLE, so avoid wrapping in a transaction.
        // Expand enum to allow both old and new values before remapping
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM(
            'draft',
            'pending_approval',
            'partially_approved',
            'fully_approved',
            'finance_review',
            'finance_approved',
            'ordered',
            'partially_received',
            'received',
            'cancelled',
            'rejected',
            'pending_finance_approval',
            'approved',
            'sent_to_supplier',
            'supplier_accepted',
            'in_transit',
            'delivered',
            'rejected_finance',
            'declined_supplier',
            'revision_requested'
        ) DEFAULT 'draft'");

        // Map old statuses to new flow
        DB::table('purchase_orders')->whereIn('status', ['pending_approval', 'partially_approved', 'finance_review'])
            ->update(['status' => 'pending_finance_approval']);
        DB::table('purchase_orders')->whereIn('status', ['fully_approved', 'finance_approved'])
            ->update(['status' => 'approved']);
        DB::table('purchase_orders')->where('status', 'ordered')
            ->update(['status' => 'sent_to_supplier']);
        DB::table('purchase_orders')->where('status', 'partially_received')
            ->update(['status' => 'in_transit']);
        DB::table('purchase_orders')->where('status', 'received')
            ->update(['status' => 'delivered']);
        DB::table('purchase_orders')->where('status', 'rejected')
            ->update(['status' => 'rejected_finance']);

        // Shrink enum to new-only values
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM(
            'draft',
            'pending_finance_approval',
            'approved',
            'sent_to_supplier',
            'supplier_accepted',
            'in_transit',
            'delivered',
            'rejected_finance',
            'declined_supplier',
            'revision_requested',
            'cancelled'
        ) DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM(
            'draft',
            'pending_approval',
            'partially_approved',
            'fully_approved',
            'finance_review',
            'finance_approved',
            'ordered',
            'partially_received',
            'received',
            'cancelled',
            'rejected'
        ) DEFAULT 'draft'");
    }
};
