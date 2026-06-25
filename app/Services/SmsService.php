<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $number, string $message): bool
    {
        $apiKey = config('services.pinassms.api_key');

        if (! $apiKey || ! $number) {
            return false;
        }

        // Normalize to PinasSMS format: 639XXXXXXXXX
        $number = preg_replace('/\D/', '', $number);
        if (str_starts_with($number, '0')) {
            $number = '63' . substr($number, 1);
        } elseif (! str_starts_with($number, '63')) {
            $number = '63' . $number;
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY'    => $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://pinassms.com/api/sms/send', [
                'recipient' => $number,
                'message'   => $message,
            ]);

            if (! $response->successful()) {
                Log::warning('SMS failed', ['number' => $number, 'response' => $response->body()]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS exception', ['number' => $number, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
