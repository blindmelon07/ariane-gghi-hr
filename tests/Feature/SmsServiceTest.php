<?php

use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.unisms.secret_key' => 'sk_test',
        'services.unisms.sender_id'  => 'GGHI',
        'services.unisms.base_url'   => 'https://unismsapi.com/api',
    ]);
});

describe('SmsService::normalize', function () {
    it('accepts the common Philippine mobile formats', function (string $input) {
        expect(SmsService::normalize($input))->toBe('09171234567');
    })->with(['09171234567', '0917-123-4567', '+639171234567', '639171234567', '9171234567']);

    it('rejects anything that is not a PH mobile number', function (?string $input) {
        expect(SmsService::normalize($input))->toBeNull();
    })->with([null, '', '991959607', '0212345678', '091712345678']);
});

describe('SmsService::send', function () {
    it('posts to UniSMS with basic auth and logs the queued message', function () {
        Http::fake(['unismsapi.com/api/sms' => Http::response(['message' => ['reference_id' => 'msg_123', 'status' => 'pending']], 201)]);

        $ok = app(SmsService::class)->send('+639171234567', 'Hello', ['type' => 'leave']);

        expect($ok)->toBeTrue();
        Http::assertSent(fn ($req) => $req->url() === 'https://unismsapi.com/api/sms'
            && $req->hasHeader('Authorization', 'Basic ' . base64_encode('sk_test:'))
            && $req['recipient'] === '09171234567'
            && $req['content'] === 'Hello'
            && $req['sender_id'] === 'GGHI');

        $log = SmsLog::sole();
        expect($log->status)->toBe('queued')
            ->and($log->reference_id)->toBe('msg_123')
            ->and($log->type)->toBe('leave');
    });

    it('does not call the API when it is not configured', function () {
        config(['services.unisms.secret_key' => null]);
        Http::fake();

        expect(app(SmsService::class)->send('09171234567', 'Hello'))->toBeFalse();

        Http::assertNothingSent();
        expect(SmsLog::sole()->status)->toBe('failed');
    });

    it('skips invalid numbers without calling the API', function () {
        Http::fake();

        expect(app(SmsService::class)->send('12345', 'Hello'))->toBeFalse();

        Http::assertNothingSent();
        expect(SmsLog::sole()->status)->toBe('skipped');
    });

    it('records API errors', function () {
        Http::fake(['unismsapi.com/*' => Http::response(['message' => 'Invalid key'], 401)]);

        expect(app(SmsService::class)->send('09171234567', 'Hello'))->toBeFalse();
        expect(SmsLog::sole())->status->toBe('failed')->error->toContain('401');
    });

    it('turns UniSMS validation errors into readable text', function () {
        Http::fake(['unismsapi.com/*' => Http::response(['errors' => [
            'content'   => ['Links are prohibited under this SenderID'],
            'recipient' => ["This Sender ID can't send to Smart, TNT, Sun numbers"],
        ]], 422)]);

        app(SmsService::class)->send('09508367473', 'see https://x.com');

        expect(SmsLog::sole()->error)
            ->toBe("422: Links are prohibited under this SenderID This Sender ID can't send to Smart, TNT, Sun numbers");
    });

    it('explains why a whole bulk request was rejected', function () {
        Http::fake(['unismsapi.com/*' => Http::response([
            'errors'  => 'None of the messages can be sent. See skipped for the reasons.',
            'queued'  => 0,
            'skipped' => [['index' => 0, 'recipient' => '09810795445', 'errors' => ['recipient' => ["This Sender ID can't send to Smart, TNT, Sun numbers"]]]],
        ], 422)]);

        app(SmsService::class)->sendMany([['number' => '09810795445', 'message' => 'Hi']]);

        expect(SmsLog::sole()->error)->toBe("422: This Sender ID can't send to Smart, TNT, Sun numbers");
    });

    it('records network exceptions', function () {
        Http::fake(['unismsapi.com/*' => fn () => throw new \Exception('Connection refused')]);

        expect(app(SmsService::class)->send('09171234567', 'Hello'))->toBeFalse();
        expect(SmsLog::sole())->status->toBe('failed')->error->toBe('Connection refused');
    });

    it('stores the masked text instead of the real one when given', function () {
        Http::fake(['unismsapi.com/*' => Http::response(['message' => ['reference_id' => 'x']], 201)]);

        app(SmsService::class)->send('09171234567', 'Password: abc123', ['log_content' => 'Password: ********']);

        Http::assertSent(fn ($req) => $req['content'] === 'Password: abc123');
        expect(SmsLog::sole()->content)->toBe('Password: ********');
    });
});

describe('SmsService::sendMany', function () {
    it('sends valid numbers through /bulk and records skips', function () {
        Http::fake(['unismsapi.com/api/bulk' => Http::response([
            'total' => 2, 'queued' => 1, 'bulk_id' => 'blk_1',
            'skipped' => [['index' => 1, 'recipient' => '09281234567', 'errors' => ['sender not allowed']]],
        ], 200)]);

        $result = app(SmsService::class)->sendMany([
            ['number' => '09171234567', 'message' => 'A'],
            ['number' => '09281234567', 'message' => 'B'],
            ['number' => null,          'message' => 'C'],
        ]);

        expect($result)->toBe(['queued' => 1, 'failed' => 0, 'skipped' => 2]);
        Http::assertSent(fn ($req) => count($req['messages']) === 2 && $req['messages'][0]['sender_id'] === 'GGHI');
        expect(SmsLog::where('status', 'queued')->value('reference_id'))->toBe('bulk:blk_1');
    });
});
