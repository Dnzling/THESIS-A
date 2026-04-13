<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Hr\RecruitmentController;
use App\Models\JobApplication;
use App\Models\Core\User;

class SimulateHireApplicant extends Command
{
    protected $signature = 'simulate:hire {application_id} {--branch=} {--department=} {--role=} {--hire_date=} {--employment_type=full_time} {--salary=0} {--position=} {--phone=} {--address=} {--pay_type=monthly}';

    protected $description = 'Simulate hiring an applicant by calling RecruitmentController::hireApplicant';

    public function handle()
    {
        $appId = $this->argument('application_id');
        $application = JobApplication::find($appId);
        if (!$application) {
            $this->error("Application {$appId} not found.");
            return 1;
        }


        $payload = [
            'branch_id' => $this->option('branch'),
            'department_id' => $this->option('department'),
            'role_id' => $this->option('role'),
            'hire_date' => $this->option('hire_date') ?: now()->toDateString(),
            'employment_type' => $this->option('employment_type'),
            'salary' => $this->option('salary'),
            'position' => $this->option('position'),
            'phone' => $this->option('phone'),
            'address' => $this->option('address'),
            'pay_type' => $this->option('pay_type'),
        ];

        $request = Request::create("/", 'POST', $payload);

        // determine department store and pick a user with that store_id if possible
        $departmentId = $payload['department_id'];
        $department = null;
        if ($departmentId) {
            try {
                $department = \App\Models\Hr\Department::find($departmentId);
            } catch (\Throwable $e) {
                $department = null;
            }
        }


        if ($department && $department->store_id) {
            $user = User::whereNotNull('store_id')
                ->where('store_id', $department->store_id)
                ->where('email', 'like', '%@%')
                ->first();
        } else {
            $user = User::where('email', 'like', '%@%')->first();
        }

        if (!$user) {
            // fallback: any user with valid email
            $user = User::where('email', 'like', '%@%')->first();
        }

        // set user resolver so controller->user() works
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        if (!$user) {
            $this->error('No user available to act as HR user.');
            return 1;
        }

        $controller = new RecruitmentController();

        try {
            $response = $controller->hireApplicant($request, $application);
            $data = $response->getData(true);
            $this->info('Response: ' . json_encode($data));
            return 0;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
