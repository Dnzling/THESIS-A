<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'user_id')) {
            $users = DB::table('users')->select('id', 'user_id', 'fname', 'lname', 'created_at')->orderBy('id')->get();
            $nextByYear = [];
            foreach ($users as $user) {
                $year = (int) date('Y', strtotime($user->created_at ?: 'now'));
                $nextByYear[$year] = ($nextByYear[$year] ?? 0) + 1;
                DB::table('users')->where('id', $user->id)->update([
                    'user_id' => sprintf('USR-%d-%05d', $year, $nextByYear[$year]),
                ]);
            }
        }

        if (Schema::hasColumn('users', 'phone_number') && Schema::hasColumn('employees', 'phone')) {
            DB::statement('UPDATE users u JOIN employees e ON e.user_id = u.id SET u.phone_number = COALESCE(NULLIF(u.phone_number, ""), e.phone) WHERE e.phone IS NOT NULL');
        }
        if (Schema::hasColumn('users', 'birthday') && Schema::hasColumn('employees', 'date_of_birth')) {
            DB::statement('UPDATE users u JOIN employees e ON e.user_id = u.id SET u.birthday = COALESCE(u.birthday, e.date_of_birth) WHERE e.date_of_birth IS NOT NULL');
        }

        Schema::table('employees', function (Blueprint $table): void {
            foreach (['fname', 'lname', 'phone', 'date_of_birth'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
        });
    }
};
