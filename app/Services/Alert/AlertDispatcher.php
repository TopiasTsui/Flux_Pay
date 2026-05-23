<?php

declare(strict_types=1);

namespace App\Services\Alert;

use App\Mail\OperationalAlertMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Fans an operational alert out to every configured channel (mail, Slack, Feishu).
 * Missing/empty channels are silently skipped — alerts must never throw or
 * block the caller (they are best-effort signals, not transactions).
 */
class AlertDispatcher
{
    public function dispatch(string $title, string $body, array $context = []): void
    {
        $contextLines = '';
        foreach ($context as $k => $v) {
            $contextLines .= "\n{$k}: ".(is_scalar($v) ? (string) $v : json_encode($v));
        }
        $fullBody = $body.($contextLines !== '' ? "\n".$contextLines : '');

        $this->mail($title, $fullBody);
        $this->slack($title, $fullBody);
        $this->feishu($title, $fullBody);
    }

    private function mail(string $title, string $body): void
    {
        $recipients = (array) config('fluxpay.alert_recipients', []);
        if (empty($recipients)) {
            return;
        }

        try {
            Mail::to($recipients)->send(new OperationalAlertMail($title, $body));
        } catch (\Throwable $e) {
            Log::error('AlertDispatcher: mail failed', ['error' => $e->getMessage()]);
        }
    }

    private function slack(string $title, string $body): void
    {
        $webhook = config('fluxpay.alert_slack_webhook');
        if (! $webhook) {
            return;
        }

        try {
            Http::timeout(5)->post($webhook, [
                'text' => "*[FluxPay Alert] {$title}*\n```\n{$body}\n```",
            ]);
        } catch (\Throwable $e) {
            Log::error('AlertDispatcher: Slack failed', ['error' => $e->getMessage()]);
        }
    }

    private function feishu(string $title, string $body): void
    {
        $webhook = config('fluxpay.alert_feishu_webhook');
        if (! $webhook) {
            return;
        }

        try {
            Http::timeout(5)->post($webhook, [
                'msg_type' => 'text',
                'content' => ['text' => "[FluxPay Alert] {$title}\n\n{$body}"],
            ]);
        } catch (\Throwable $e) {
            Log::error('AlertDispatcher: Feishu failed', ['error' => $e->getMessage()]);
        }
    }
}
