<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ecommerce_order_returns', 'return_number')) {
            Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
                $table->string('return_number', 24)->nullable()->after('id');
            });
        }

        $usedNumbers = DB::table('ecommerce_order_returns')
            ->whereNotNull('return_number')
            ->pluck('return_number')
            ->flip()
            ->all();
        DB::table('ecommerce_order_returns')
            ->whereNull('return_number')
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function ($return) use (&$usedNumbers): void {
                $timestamp = $return->created_at
                    ? Carbon::parse($return->created_at)
                    : now();

                do {
                    $number = 'RET-' . $timestamp->format('YmdHis');
                    $timestamp->addSecond();
                } while (isset($usedNumbers[$number]));

                $usedNumbers[$number] = true;
                DB::table('ecommerce_order_returns')
                    ->where('id', $return->id)
                    ->update(['return_number' => $number]);
            });

        Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
            $table->unique('return_number', 'ecommerce_order_returns_return_number_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('ecommerce_order_returns', 'return_number')) {
            Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
                $table->dropUnique('ecommerce_order_returns_return_number_unique');
                $table->dropColumn('return_number');
            });
        }
    }
};
