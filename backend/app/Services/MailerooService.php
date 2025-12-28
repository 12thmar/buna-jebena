<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MailerooService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://smtp.maileroo.com/api/v2';
        $this->apiKey = (string) config('services.maileroo.sending_key');
    }

    public function sendBasicEmail(array $payload): array
    {
        // Maileroo supports both X-Api-Key and Authorization: Bearer
        $resp = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . '/emails', $payload);

        if (!$resp->successful()) {
            return [
                'success' => false,
                'status' => $resp->status(),
                'body' => $resp->json(),
            ];
        }

        return $resp->json();
    }
}
