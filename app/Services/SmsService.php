<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * UniSMS gateway (https://unismsapi.com/docs/sms).
 *
 * Every message is written to sms_logs. Callers can pass a context array:
 *   employee_id  – who the message is for
 *   type         – leave, overtime, payslip, announcement, custom, attendance, credentials
 *   context_key  – de-duplication key (e.g. "payslip:21")
 *   log_content  – what to store instead of the real text (used to mask passwords)
 *   sent_by      – user id; defaults to the logged-in user
 */
class SmsService
{
    public const MIN_LENGTH = 10;   // UniSMS rejects shorter content

    public const MAX_LENGTH = 670;

    /** UniSMS allows one /bulk request every 2 seconds. */
    private const BULK_CHUNK = 100;

    public function isConfigured(): bool
    {
        return (bool) config('services.unisms.secret_key') && (bool) config('services.unisms.sender_id');
    }

    /**
     * Normalize a Philippine mobile number to 09XXXXXXXXX, or null if it isn't one.
     */
    public static function normalize(?string $number): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $number);

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }

        return preg_match('/^09\d{9}$/', $digits) ? $digits : null;
    }

    public function send(string $number, string $message, array $context = []): bool
    {
        $message   = mb_substr($message, 0, self::MAX_LENGTH);
        $recipient = self::normalize($number);
        $log       = $this->log($recipient ?? $number, $message, $context);

        if (! $recipient) {
            $log->update(['status' => 'skipped', 'error' => 'Invalid Philippine mobile number']);
            return false;
        }

        if (! $this->isConfigured()) {
            $log->update(['status' => 'failed', 'error' => 'UniSMS is not configured']);
            return false;
        }

        try {
            $response = $this->client()->post($this->url('/sms'), [
                'recipient' => $recipient,
                'content'   => $message,
                'sender_id' => config('services.unisms.sender_id'),
            ]);

            if (! $response->successful()) {
                Log::warning('SMS failed', ['recipient' => $recipient, 'response' => $response->body()]);
                $log->update(['status' => 'failed', 'error' => $this->errorText($response)]);
                return false;
            }

            $log->update([
                'status'       => 'queued',
                'reference_id' => $response->json('message.reference_id'),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS exception', ['recipient' => $recipient, 'error' => $e->getMessage()]);
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send many (possibly different) messages through /bulk.
     *
     * @param  array<int, array{number: ?string, message: string}&array<string, mixed>>  $messages
     *         each item may also carry the context keys documented on the class
     * @return array{queued: int, failed: int, skipped: int}
     */
    public function sendMany(array $messages): array
    {
        $result = ['queued' => 0, 'failed' => 0, 'skipped' => 0];
        $valid  = [];

        foreach ($messages as $m) {
            $text      = mb_substr($m['message'], 0, self::MAX_LENGTH);
            $recipient = self::normalize($m['number'] ?? null);
            $log       = $this->log($recipient ?? (string) ($m['number'] ?? ''), $text, $m);

            if (! $recipient) {
                $log->update(['status' => 'skipped', 'error' => 'Invalid or missing mobile number']);
                $result['skipped']++;
                continue;
            }

            $valid[] = ['log' => $log, 'recipient' => $recipient, 'content' => $text];
        }

        if ($valid && ! $this->isConfigured()) {
            SmsLog::whereIn('id', array_map(fn ($v) => $v['log']->id, $valid))
                ->update(['status' => 'failed', 'error' => 'UniSMS is not configured']);
            $result['failed'] += count($valid);
            return $result;
        }

        foreach (array_chunk($valid, self::BULK_CHUNK) as $i => $chunk) {
            if ($i > 0) {
                sleep(2);
            }

            try {
                $response = $this->client()->post($this->url('/bulk'), [
                    'messages' => array_map(fn ($v) => [
                        'recipient' => $v['recipient'],
                        'content'   => $v['content'],
                        'sender_id' => config('services.unisms.sender_id'),
                    ], $chunk),
                ]);
            } catch (\Throwable $e) {
                Log::error('SMS bulk exception', ['error' => $e->getMessage()]);
                $this->markChunk($chunk, 'failed', $e->getMessage());
                $result['failed'] += count($chunk);
                continue;
            }

            if (! $response->successful()) {
                Log::warning('SMS bulk failed', ['response' => $response->body()]);
                $this->markChunk($chunk, 'failed', $this->errorText($response));
                $result['failed'] += count($chunk);
                continue;
            }

            $skipped = collect($response->json('skipped', []))->keyBy('index');
            $bulkId  = $response->json('bulk_id');

            foreach ($chunk as $index => $v) {
                if ($skipped->has($index)) {
                    $v['log']->update(['status' => 'skipped', 'error' => json_encode($skipped[$index]['errors'] ?? [])]);
                    $result['skipped']++;
                } else {
                    $v['log']->update(['status' => 'queued', 'reference_id' => $bulkId ? "bulk:{$bulkId}" : null]);
                    $result['queued']++;
                }
            }
        }

        return $result;
    }

    /**
     * Remaining SMS credits, or null when the account can't be reached.
     */
    public function credits(): ?int
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client()->get($this->url('/account'));

            return $response->successful() ? (int) $response->json('sms_credits', $response->json('data.sms_credits')) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function client()
    {
        return Http::withBasicAuth((string) config('services.unisms.secret_key'), '')
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    private function url(string $path): string
    {
        return rtrim(config('services.unisms.base_url'), '/') . $path;
    }

    private function log(string $recipient, string $message, array $context): SmsLog
    {
        return SmsLog::create([
            'employee_id' => $context['employee_id'] ?? null,
            'recipient'   => mb_substr($recipient, 0, 20),
            'content'     => $context['log_content'] ?? $message,
            'type'        => $context['type'] ?? 'custom',
            'context_key' => $context['context_key'] ?? null,
            'status'      => 'pending',
            'sent_by'     => $context['sent_by'] ?? auth()->id(),
        ]);
    }

    private function markChunk(array $chunk, string $status, string $error): void
    {
        SmsLog::whereIn('id', array_map(fn ($v) => $v['log']->id, $chunk))
            ->update(['status' => $status, 'error' => $error]);
    }

    /**
     * Turn a UniSMS error response into readable text, e.g.
     * {"errors":{"content":["Links are prohibited…"]}} → "Links are prohibited…".
     * Bulk rejections carry the reasons per message under skipped[].errors.
     */
    private function errorText($response): string
    {
        $reasons = collect([$response->json('errors'), $response->json('skipped.*.errors')])
            ->flatten()
            ->filter(fn ($e) => is_string($e) && ! str_starts_with($e, 'None of the messages can be sent'))
            ->unique()
            ->values();

        $text = $reasons->isNotEmpty()
            ? $reasons->implode(' ')
            : ($response->json('message') ?? $response->body());

        return mb_substr("{$response->status()}: {$text}", 0, 500);
    }
}
