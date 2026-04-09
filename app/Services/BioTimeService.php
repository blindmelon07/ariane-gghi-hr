<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BioTimeService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('biotime.url'), '/');
    }

    /**
     * POST to BioTime JWT auth endpoint and return the token string.
     */
    public function fetchToken(): string
    {
        $response = Http::post("{$this->baseUrl}/jwt-api-token-auth/", [
            'username' => config('biotime.username'),
            'password' => config('biotime.password'),
        ]);

        $response->throw();

        return $response->json('token');
    }

    /**
     * Get a cached JWT token (cached for 55 minutes).
     */
    public function getToken(): string
    {
        return Cache::remember('biotime_jwt_token', now()->addMinutes(55), function () {
            return $this->fetchToken();
        });
    }

    /**
     * Clear cached token and fetch a fresh one.
     */
    public function refreshToken(): string
    {
        Cache::forget('biotime_jwt_token');

        return $this->getToken();
    }

    /**
     * Return an HTTP client pre-configured with the JWT Authorization header.
     */
    public function client(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'JWT ' . $this->getToken(),
        ])->baseUrl($this->baseUrl);
    }

    /**
     * GET a BioTime API endpoint. Retries once on 401 after refreshing the token.
     */
    public function get(string $uri, array $filters = []): array
    {
        $response = $this->client()->get($uri, $filters);

        if ($response->status() === 401) {
            Log::info('BioTime: Token expired, refreshing and retrying.');
            $this->refreshToken();
            $response = $this->client()->get($uri, $filters);
        }

        $response->throw();

        return $response->json();
    }

    /**
     * Fetch all pages from a paginated BioTime endpoint.
     * Returns the merged "data" arrays from all pages.
     */
    public function getAllPages(string $uri, array $filters = []): array
    {
        $filters = array_merge(['page' => 1, 'page_size' => 100], $filters);
        $allData = [];

        do {
            $response = $this->get($uri, $filters);

            $allData = array_merge($allData, $response['data'] ?? []);

            if (!empty($response['next'])) {
                $filters['page']++;
            }
        } while (!empty($response['next']));

        return $allData;
    }

    /**
     * Fetch all employees from BioTime.
     */
    public function getEmployees(array $filters = []): array
    {
        return $this->getAllPages('/personnel/api/employees/', $filters);
    }

    /**
     * Fetch attendance transactions from BioTime.
     */
    public function getTransactions(array $filters = []): array
    {
        return $this->getAllPages('/iclock/api/transactions/', $filters);
    }
}
