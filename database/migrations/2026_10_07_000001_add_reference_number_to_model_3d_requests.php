<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('model_3d_requests', function (Blueprint $table) {
            $table->string('reference_number', 32)->nullable()->unique()->after('id');
        });

        DB::table('model_3d_requests')->select('id', 'created_at')->orderBy('id')->chunkById(200, function ($requests) {
            foreach ($requests as $request) {
                $year = $request->created_at ? date('Y', strtotime($request->created_at)) : date('Y');
                DB::table('model_3d_requests')->where('id', $request->id)->update([
                    'reference_number' => sprintf('3DR-%s-%06d', $year, $request->id),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('model_3d_requests', fn (Blueprint $table) => $table->dropColumn('reference_number'));
    }
};
