<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobApplication;
use App\Models\Store\Branch;
use App\Models\Hr\Department;
use App\Models\Core\Role as CoreRole;

class DebugHireInfo extends Command
{
    protected $signature = 'debug:hire-info';
    protected $description = 'Show sample application and available branch/department/role ids for simulate:hire';

    public function handle()
    {
        $app = JobApplication::with('jobPosting')->first();
        $this->line('--- JobApplication (first) ---');
        if ($app) {
            $this->info('id: ' . $app->id);
            $this->info('email: ' . $app->email);
            $this->info('job_posting_id: ' . $app->job_posting_id);
            $this->info('jobPosting department: ' . ($app->jobPosting->department ?? 'N/A'));
        } else {
            $this->error('No JobApplication found');
        }

        $this->line('--- Branches ---');
        Branch::select('id','name')->limit(10)->get()->each(function($b){ $this->info("{$b->id}: {$b->name}"); });

        $this->line('--- Departments ---');
        Department::select('id','name','store_id')->limit(20)->get()->each(function($d){ $this->info("{$d->id}: {$d->name} (store_id: {$d->store_id})"); });

        $this->line('--- Roles ---');
        CoreRole::select('id','name')->limit(20)->get()->each(function($r){ $this->info("{$r->id}: {$r->name}"); });

        return 0;
    }
}
