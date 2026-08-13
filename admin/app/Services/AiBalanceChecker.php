<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

// Provayderlarning to'liq balansini API orqali olishning imkoni yo'q (bu faqat konsolda ko'rinadi),
// shu sababli har bir provayderga arzon/bepul so'rov yuborib, kvota tugagan-tugamaganini aniqlaymiz.
class AiBalanceChecker
{
    public static function check(string $provider, string $apiKey, ?string $model): string
    {
        try {
            return match ($provider) {
                'claude'  => static::checkClaude($apiKey, $model),
                'chatgpt' => static::checkOpenAiCompatible('https://api.openai.com/v1/chat/completions', $apiKey, $model ?: 'gpt-4o-mini'),
                'grok'    => static::checkOpenAiCompatible('https://api.x.ai/v1/chat/completions', $apiKey, $model ?: 'grok-4'),
                'gemini'  => static::checkGemini($apiKey, $model ?: 'gemini-2.5-flash'),
                default   => 'error',
            };
        } catch (\Throwable $e) {
            return 'error';
        }
    }

    protected static function checkClaude(string $apiKey, ?string $model): string
    {
        $resp = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(15)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $model ?: 'claude-3-5-haiku-20241022',
            'max_tokens' => 1,
            'messages'   => [['role' => 'user', 'content' => 'hi']],
        ]);

        if ($resp->successful()) {
            return 'ok';
        }
        if ($resp->status() === 401) {
            return 'invalid_key';
        }
        if (str_contains(strtolower((string) $resp->json('error.message')), 'credit balance is too low')) {
            return 'empty';
        }

        return 'error';
    }

    protected static function checkOpenAiCompatible(string $url, string $apiKey, string $model): string
    {
        $resp = Http::withToken($apiKey)->timeout(15)->post($url, [
            'model'      => $model,
            'max_tokens' => 1,
            'messages'   => [['role' => 'user', 'content' => 'hi']],
        ]);

        if ($resp->successful()) {
            return 'ok';
        }
        if (in_array($resp->status(), [401, 403], true)) {
            return 'invalid_key';
        }
        $code = strtolower((string) $resp->json('error.code'));
        $message = strtolower((string) $resp->json('error.message'));
        if ($resp->status() === 429 && (str_contains($code, 'quota') || str_contains($message, 'quota') || str_contains($message, 'credit'))) {
            return 'empty';
        }

        return 'error';
    }

    protected static function checkGemini(string $apiKey, string $model): string
    {
        $resp = Http::timeout(15)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            ['contents' => [['parts' => [['text' => 'hi']]]]],
        );

        if ($resp->successful()) {
            return 'ok';
        }
        $status = strtolower((string) $resp->json('error.status'));
        $message = strtolower((string) $resp->json('error.message'));
        if (str_contains($status, 'permission_denied') || str_contains($message, 'api key not valid')) {
            return 'invalid_key';
        }
        if ($resp->status() === 429 || str_contains($status, 'resource_exhausted') || str_contains($message, 'quota')) {
            return 'empty';
        }

        return 'error';
    }
}
