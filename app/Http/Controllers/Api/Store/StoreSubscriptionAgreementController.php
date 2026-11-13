<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Admin\SubscriptionPlan;
use App\Models\Store\Store;
use App\Models\Store\StoreSubscriptionAgreement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreSubscriptionAgreementController extends Controller
{
    private const VERSION = '2026-11-12-v1';

    public function sign(Request $request)
    {
        $store = $this->ownedStore($request);
        $validated = $request->validate([
            'plan_key' => ['required', 'string', 'exists:subscription_plans,plan_key'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'signer_name' => ['required', 'string', 'max:255'],
            'accepted' => ['required', 'accepted'],
            'signature' => ['required', 'string', 'max:500000'],
        ]);

        $plan = SubscriptionPlan::query()
            ->where('plan_key', $validated['plan_key'])
            ->where('is_active', true)
            ->firstOrFail();
        $price = (float) ($validated['billing_cycle'] === 'yearly' ? $plan->yearly_price : $plan->monthly_price);
        if ($price <= 0) {
            throw ValidationException::withMessages(['plan_key' => 'This plan cannot be used for subscription checkout.']);
        }

        $encoded = $validated['signature'];
        if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $encoded, $matches)) {
            throw ValidationException::withMessages(['signature' => 'Please provide a valid drawn signature.']);
        }
        $signatureBytes = base64_decode($matches[1], true);
        $signatureInfo = $signatureBytes === false ? false : @getimagesizefromstring($signatureBytes);
        if ($signatureBytes === false || strlen($signatureBytes) > 350_000 || $signatureInfo === false || ($signatureInfo['mime'] ?? null) !== 'image/png') {
            throw ValidationException::withMessages(['signature' => 'The signature image is invalid or too large. Please sign again.']);
        }

        $user = $request->user();
        $signerName = trim($validated['signer_name']);
        $signedAt = now();
        $signaturePath = "stores/{$store->id}/agreements/" . $signedAt->format('YmdHis') . '-' . Str::uuid() . '-signature.png';
        if (!Storage::disk('private')->put($signaturePath, $signatureBytes)) {
            throw new \RuntimeException('Unable to securely save the signature image.');
        }

        $agreement = null;
        try {
            StoreSubscriptionAgreement::query()
                ->where('store_id', $store->id)
                ->where('status', 'pending_activation')
                ->update(['status' => 'superseded']);

            $agreement = StoreSubscriptionAgreement::query()->create([
                'store_id' => $store->id,
                'user_id' => $user->id,
                'plan_key' => $plan->plan_key,
                'plan_name' => $plan->name,
                'billing_cycle' => $validated['billing_cycle'],
                'plan_price' => $price,
                'plan_terms' => [
                    'features' => is_array($plan->features) ? $plan->features : [],
                    'limits' => [
                        'accounts' => $plan->max_user_accounts,
                        'branches' => $plan->max_branches,
                        'products' => $plan->max_products,
                        '3d_requests_per_month' => $plan->max_3d_model_requests_per_month,
                    ],
                ],
                'agreement_version' => self::VERSION,
                'signer_name' => $signerName,
                'signer_email' => $user->email,
                'signature_path' => $signaturePath,
                'status' => 'pending_activation',
                'signed_at' => $signedAt,
                'signed_ip' => $request->ip(),
            ]);

            $documentPath = "stores/{$store->id}/agreements/{$agreement->id}-signed-agreement.pdf";
            $agreement->update(['document_path' => $documentPath]);
            $this->renderAgreementPdf($agreement->fresh(), $store);

            return response()->json([
                'success' => true,
                'message' => 'Agreement signed and saved. It will become active once this subscription is activated.',
                'data' => ['agreement_id' => $agreement->id, 'status' => $agreement->status],
            ], 201);
        } catch (\Throwable $exception) {
            if ($agreement) {
                Storage::disk('private')->delete($agreement->document_path);
                $agreement->delete();
            }
            Storage::disk('private')->delete($signaturePath);
            throw $exception;
        }
    }

    public function activate(Request $request)
    {
        $store = $this->ownedStore($request);
        $plan = $store->subscriptionPlan;
        abort_unless($plan && in_array($store->subscription_status, ['trial', 'paid'], true), 409, 'Activate the selected subscription before finalizing its agreement.');

        $agreement = StoreSubscriptionAgreement::query()
            ->where('store_id', $store->id)
            ->where('plan_key', $plan->plan_key)
            ->where('status', 'pending_activation')
            ->latest('signed_at')
            ->first();
        abort_unless($agreement, 409, 'Sign the subscription agreement before continuing.');

        $agreement->update(['status' => 'active', 'activated_at' => now()]);
        $this->renderAgreementPdf($agreement->fresh(), $store);

        return response()->json(['success' => true, 'message' => 'Subscription agreement activated.']);
    }

    public function document(Request $request)
    {
        $store = $this->ownedStore($request);
        $agreement = StoreSubscriptionAgreement::query()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->latest('activated_at')
            ->firstOrFail();
        abort_unless($agreement->document_path && Storage::disk('private')->exists($agreement->document_path), 404);

        return response()->file(Storage::disk('private')->path($agreement->document_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="FurniSync-Agreement-' . $store->store_code . '.pdf"',
        ]);
    }

    private function ownedStore(Request $request): Store
    {
        abort_unless($request->user()?->hasRole('owner') && $request->user()?->store_id, 403);

        return Store::query()->with('subscriptionPlan')->findOrFail((int) $request->user()->store_id);
    }

    private function renderAgreementPdf(StoreSubscriptionAgreement $agreement, Store $store): void
    {
        abort_unless(Storage::disk('private')->exists($agreement->signature_path), 422, 'The saved signature image could not be found.');
        $signatureDataUri = 'data:image/png;base64,' . base64_encode(Storage::disk('private')->get($agreement->signature_path));
        $pdf = Pdf::loadView('store.subscription-agreement-pdf', [
            'agreement' => $agreement,
            'store' => $store,
            'signatureDataUri' => $signatureDataUri,
            'printedAt' => now(),
        ])->setPaper('a4', 'portrait');

        if (!Storage::disk('private')->put($agreement->document_path, $pdf->output())) {
            throw new \RuntimeException('Unable to save the signed agreement document.');
        }
    }
}
