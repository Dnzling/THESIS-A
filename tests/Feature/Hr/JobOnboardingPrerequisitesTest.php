<?php

use Illuminate\Support\Facades\Schema;

test('test database contains hiring prerequisite storage', function () {
    expect(config('database.connections.mysql.database'))->toBe('furnitureplatform_db_1_test');
    expect(Schema::hasColumn('job_postings', 'hiring_documents'))->toBeTrue();
    expect(Schema::hasTable('job_onboarding_prerequisites'))->toBeTrue();
});
