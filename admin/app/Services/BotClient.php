<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BotClient
{
    private string $base;
    private string $secret;

    public function __construct()
    {
        $this->base   = rtrim(config('services.bot.url'), '/');
        $this->secret = config('services.bot.secret');
    }

    private function post(string $path, array $body = []): array
    {
        try {
            // Bot tomonidagi AI so'rovlari (90s) + skript rejimida bir nechta URL skrab qilish
            // vaqtini qamrab olishi uchun bot tomonidan biroz kattaroq qilib qo'yilgan.
            $res = Http::withHeaders(['X-Internal-Secret' => $this->secret])
                ->timeout(180)
                ->post($this->base . $path, $body);
            return $res->json() ?? ['ok' => false, 'error' => 'bo\'sh javob'];
        } catch (ConnectionException $e) {
            return ['ok' => false, 'error' => "Bot serveriga ulanib bo'lmadi ({$this->base}). Bot ishga tushirilganini tekshiring."];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** Barcha aktiv kanallar uchun hozir yaratib to'g'ridan-to'g'ri yuborish */
    public function runNow(): array
    {
        return $this->post('/run-now');
    }

    /** Barcha aktiv kanallar uchun hozir yaratib navbatga qo'yish */
    public function generateNow(): array
    {
        return $this->post('/generate-now');
    }

    /**
     * Bitta kanal uchun hozir yaratib yuborish.
     * $overrides — faqat shu bir martalik chaqiruv uchun (kanalning saqlangan
     * sozlamalarini o'zgartirmaydi): ai_provider, source_mode, source_url (array).
     */
    public function runChannel(int $channelId, array $overrides = []): array
    {
        return $this->post('/run-channel', ['channel_id' => $channelId] + $overrides);
    }

    /** Bitta kanal uchun hozir yaratib navbatga qo'yish (tasdiq kutadi) — $overrides yuqoridagi kabi */
    public function generateChannel(int $channelId, array $overrides = []): array
    {
        return $this->post('/generate-channel', ['channel_id' => $channelId] + $overrides);
    }

    /** Tasdiqlangan postni yuborish */
    public function sendPost(int $postId): array
    {
        return $this->post('/send-post', ['post_id' => $postId]);
    }

    /** Yuborilgan postni Telegram kanallaridan va bazadan o'chirish */
    public function deletePost(int $postId): array
    {
        return $this->post('/delete-post', ['post_id' => $postId]);
    }

    /** Yuborilgan post matnini Telegram kanallarida ham yangilash */
    public function editPost(int $postId, string $content): array
    {
        return $this->post('/edit-post', ['post_id' => $postId, 'content' => $content]);
    }
}
