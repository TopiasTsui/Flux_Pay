<?php

declare(strict_types=1);

namespace Tests\Feature\Alert;

use App\Services\Alert\AlertDispatcher;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlertDispatcherTest extends TestCase
{
    #[Test]
    public function dispatches_to_all_configured_channels(): void
    {
        config([
            'fluxpay.alert_recipients' => ['ops@example.com', 'cto@example.com'],
            'fluxpay.alert_slack_webhook' => 'https://hooks.slack.test/services/AAA/BBB/CCC',
            'fluxpay.alert_feishu_webhook' => 'https://open.feishu.test/open-apis/bot/v2/hook/xxx',
        ]);

        Mail::fake();
        Http::fake();

        app(AlertDispatcher::class)->dispatch('Test', 'Hello', ['k' => 'v']);

        Mail::assertSent(\App\Mail\OperationalAlertMail::class, function ($mail) {
            return $mail->hasTo('ops@example.com') && $mail->hasTo('cto@example.com');
        });

        Http::assertSent(fn (HttpRequest $req) => str_contains($req->url(), 'hooks.slack.test')
            && str_contains($req->body(), 'FluxPay Alert'));
        Http::assertSent(fn (HttpRequest $req) => str_contains($req->url(), 'open.feishu.test')
            && str_contains($req->body(), 'msg_type'));
    }

    #[Test]
    public function skips_channels_that_are_unconfigured(): void
    {
        config([
            'fluxpay.alert_recipients' => [],
            'fluxpay.alert_slack_webhook' => null,
            'fluxpay.alert_feishu_webhook' => null,
        ]);

        Mail::fake();
        Http::fake();

        app(AlertDispatcher::class)->dispatch('Test', 'body');

        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    #[Test]
    public function only_slack_when_only_slack_is_configured(): void
    {
        config([
            'fluxpay.alert_recipients' => [],
            'fluxpay.alert_slack_webhook' => 'https://hooks.slack.test/services/X',
            'fluxpay.alert_feishu_webhook' => null,
        ]);

        Mail::fake();
        Http::fake();

        app(AlertDispatcher::class)->dispatch('Test', 'body');

        Mail::assertNothingSent();
        Http::assertSentCount(1);
        Http::assertSent(fn (HttpRequest $req) => str_contains($req->url(), 'hooks.slack.test'));
    }

    #[Test]
    public function failure_in_one_channel_does_not_prevent_others(): void
    {
        config([
            'fluxpay.alert_recipients' => ['ops@example.com'],
            'fluxpay.alert_slack_webhook' => 'https://hooks.slack.test/x',
            'fluxpay.alert_feishu_webhook' => 'https://feishu.test/x',
        ]);

        Mail::fake();
        // Slack will fail; Feishu must still be called.
        Http::fake([
            'hooks.slack.test/*' => Http::response(null, 500),
            'feishu.test/*' => Http::response('{"code":0}', 200),
        ]);

        app(AlertDispatcher::class)->dispatch('Test', 'body');

        Mail::assertSent(\App\Mail\OperationalAlertMail::class);
        Http::assertSent(fn (HttpRequest $req) => str_contains($req->url(), 'feishu.test'));
    }
}
