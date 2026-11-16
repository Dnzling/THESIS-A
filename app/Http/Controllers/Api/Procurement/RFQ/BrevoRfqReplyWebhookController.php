<?php

namespace App\Http\Controllers\Api\Procurement\RFQ;

use App\Http\Controllers\Controller;
use App\Models\Procurement\RFQ\RFQSupplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BrevoRfqReplyWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expectedUsername = (string) config('services.brevo.inbound_webhook_username');
        $expectedPassword = (string) config('services.brevo.inbound_webhook_password');
        abort_if($expectedUsername === '' || $expectedPassword === '', 503, 'Brevo inbound webhook credentials are not configured.');
        abort_unless(
            hash_equals($expectedUsername, (string) $request->getUser())
                && hash_equals($expectedPassword, (string) $request->getPassword()),
            401,
            'Invalid webhook credentials.'
        );

        $items = $request->input('items', []);
        abort_unless(is_array($items), 422, 'Invalid inbound email payload.');
        $processed = 0;

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $token = $this->replyToken($item);
            $from = strtolower(trim((string) data_get($item, 'From.Address')));
            if (!$token || !$from) {
                continue;
            }

            $invitation = RFQSupplier::query()
                ->with('supplier')
                ->where('email_reply_token', $token)
                ->first();
            if (!$invitation || strtolower((string) $invitation->supplier?->email) !== $from) {
                continue;
            }

            $messageId = trim((string) ($item['MessageId'] ?? ''));
            if ($messageId === '') {
                $messageId = hash('sha256', json_encode([
                    $from,
                    $item['Subject'] ?? '',
                    $item['SentAtDate'] ?? '',
                    $item['ExtractedMarkdownMessage'] ?? $item['RawTextBody'] ?? '',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $receivedAt = now();
            if (!empty($item['SentAtDate'])) {
                try {
                    $receivedAt = Carbon::parse($item['SentAtDate']);
                } catch (\Throwable) {
                    // Use webhook processing time for malformed sender date headers.
                }
            }

            $inserted = DB::table('rfq_supplier_email_replies')->insertOrIgnore([
                'rfq_supplier_id' => $invitation->id,
                'message_id' => mb_substr($messageId, 0, 255),
                'from_email' => mb_substr($from, 0, 255),
                'from_name' => mb_substr((string) data_get($item, 'From.Name'), 0, 255) ?: null,
                'subject' => mb_substr((string) ($item['Subject'] ?? ''), 0, 998) ?: null,
                'body' => mb_substr((string) ($item['ExtractedMarkdownMessage'] ?? $item['RawTextBody'] ?? ''), 0, 50000),
                'received_at' => $receivedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!$inserted) {
                continue;
            }

            if (!$invitation->responded_at) {
                $invitation->update(['status' => 'submitted', 'responded_at' => $receivedAt]);
            }
            $rfq = $invitation->rfq;
            if ($rfq && in_array((string) $rfq->status, ['pending', 'sent'], true)) {
                $rfq->update(['status' => 'receiving']);
            }
            $processed++;
        }

        return response()->json(['success' => true, 'processed' => $processed]);
    }

    private function replyToken(array $item): ?string
    {
        $addresses = [];
        foreach ((array) ($item['To'] ?? []) as $mailbox) {
            if (is_array($mailbox) && isset($mailbox['Address'])) {
                $addresses[] = $mailbox['Address'];
            }
        }
        foreach ((array) ($item['Recipients'] ?? []) as $recipient) {
            if (is_string($recipient)) {
                $addresses[] = $recipient;
            }
        }

        foreach ($addresses as $address) {
            if (preg_match('/^rfq\+([a-z0-9]{48})@/i', trim((string) $address), $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
