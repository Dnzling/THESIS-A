<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Core\User;
use App\Models\Customer\CustomerVerificationDocument;
use Illuminate\Support\Facades\DB;

class SimulateCustomerReview extends Command
{
    protected $signature = 'simulate:customer-review {userId} {action : approve|reject} {--reason=}';
    protected $description = 'Simulate admin review of customer verification (approve|reject) for debugging';

    public function handle()
    {
        $userId = (int) $this->argument('userId');
        $action = $this->argument('action');
        $reason = $this->option('reason');

        $this->info("Simulating review: user={$userId} action={$action}");

        try {
            $user = User::with(['customer', 'customerVerificationDocuments'])->findOrFail($userId);

            $reviewedAt = now();

            DB::transaction(function () use ($user, $action, $reason, $reviewedAt) {
                $customer = $user->customer()->firstOrCreate(
                    ['user_id' => $user->id],
                    ['verification_status' => 'unverified']
                );

                if ($action === 'approve') {
                    $payload = [
                        'verification_status' => 'verified',
                        'verification_required' => false,
                        'verification_rejection_reason' => null,
                        'verification_reviewed_by' => auth()->id(),
                        'verification_reviewed_at' => $reviewedAt,
                    ];
                    $customer->update($payload);
                    \App\Models\Customer\Customer::query()->where('user_id', $user->id)->update($payload);

                    CustomerVerificationDocument::query()->where('user_id', $user->id)->update([
                        'status' => 'approved',
                        'rejection_reason' => null,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => $reviewedAt,
                        'updated_at' => $reviewedAt,
                    ]);

                    $this->info('Approved.');
                } else {
                    $payload = [
                        'verification_status' => 'rejected',
                        'verification_required' => false,
                        'verification_rejection_reason' => $reason,
                        'verification_reviewed_by' => auth()->id(),
                        'verification_reviewed_at' => $reviewedAt,
                    ];
                    $customer->update($payload);
                    \App\Models\Customer\Customer::query()->where('user_id', $user->id)->update($payload);

                    CustomerVerificationDocument::query()->where('user_id', $user->id)->update([
                        'status' => 'rejected',
                        'rejection_reason' => $reason,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => $reviewedAt,
                        'updated_at' => $reviewedAt,
                    ]);

                    $this->info('Rejected.');
                }
            });

            $this->info('Simulation complete.');
        } catch (\Throwable $e) {
            $this->error('Exception: ' . $e->getMessage());
            report($e);
            return 1;
        }

        return 0;
    }
}
