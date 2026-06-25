<?php

use App\Services\SmsService;
use Illuminate\Support\Facades\Http;

describe('SmsService', function () {
    it('returns false when api key is not configured', function () {
        config(['services.pinassms.api_key' => null]);
        $result = app(SmsService::class)->send('09171234567', 'Hello');
        expect($result)->toBeFalse();
    });

    it('returns false when number is empty', function () {
        config(['services.pinassms.api_key' => 'fake_key']);
        $result = app(SmsService::class)->send('', 'Hello');
        expect($result)->toBeFalse();
    });

    it('normalizes 09XX number to 639XX format', function () {
        config(['services.pinassms.api_key' => 'fake_key']);

        Http::fake(['https://pinassms.com/*' => Http::response(['status' => 'ok'], 200)]);

        app(SmsService::class)->send('09171234567', 'Test');

        Http::assertSent(fn ($req) => $req->data()['recipient'] === '639171234567');
    });

    it('normalizes +63 prefixed number to 639XX format', function () {
        config(['services.pinassms.api_key' => 'fake_key']);

        Http::fake(['https://pinassms.com/*' => Http::response(['status' => 'ok'], 200)]);

        app(SmsService::class)->send('+639171234567', 'Test');

        Http::assertSent(fn ($req) => $req->data()['recipient'] === '639171234567');
    });

    it('returns true on successful API response', function () {
        config(['services.pinassms.api_key' => 'fake_key']);

        Http::fake(['https://pinassms.com/*' => Http::response(['status' => 'ok'], 200)]);

        $result = app(SmsService::class)->send('09171234567', 'Hello');

        expect($result)->toBeTrue();
    });

    it('returns false and logs on API error response', function () {
        config(['services.pinassms.api_key' => 'fake_key']);

        Http::fake(['https://pinassms.com/*' => Http::response(['error' => 'Invalid key'], 401)]);

        $result = app(SmsService::class)->send('09171234567', 'Hello');

        expect($result)->toBeFalse();
    });

    it('returns false and logs on network exception', function () {
        config(['services.pinassms.api_key' => 'fake_key']);

        Http::fake(['https://pinassms.com/*' => fn () => throw new \Exception('Connection refused')]);

        $result = app(SmsService::class)->send('09171234567', 'Hello');

        expect($result)->toBeFalse();
    });
});
