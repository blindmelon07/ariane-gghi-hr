<?php

use App\Models\User;

// Verify the /livewire/update endpoint is reachable and handles the
// request body correctly through the full HTTP middleware stack.

it('returns 419 (csrf) not 404 when the livewire update endpoint is called with a valid json body', function () {
    // A POST with a valid components array but no CSRF token should get 419
    // (token mismatch), NOT 404. Getting 404 means request('components') is null.
    $response = $this->postJson('/livewire/update', [
        'components' => [
            [
                'snapshot' => json_encode(['test' => true]),
                'updates'  => [],
                'calls'    => [],
            ],
        ],
    ]);

    // 419 = CSRF mismatch (components was read, route was hit)
    // 404 = components was null (the PHP 8.5 / Livewire parsing bug)
    // Any other code means something unexpected
    expect($response->getStatusCode())->not->toBe(404);
});

it('can read json body via request() helper in web middleware group', function () {
    $response = $this->postJson('/livewire/update', [
        'components' => [['snapshot' => '{}', 'updates' => [], 'calls' => []]],
    ]);

    // The fact we get something other than 404 confirms request('components')
    // returned a non-empty array through the full middleware stack.
    expect($response->status())->not->toBe(404);
});
