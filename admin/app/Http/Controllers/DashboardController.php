<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Source;
use App\Services\BotClient;
use App\Support\AiProviders;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $channels = Channel::where('is_active', true)->orderBy('title')->get();

        return view('dashboard', [
            'channelCount' => Channel::where('is_active', true)->count(),
            'sourceCount'  => Source::where('is_active', true)->count(),
            'pendingCount' => Post::where('status', 'pending')->count(),
            'sentCount'    => Post::where('status', 'sent')->count(),
            'autoSend'     => Setting::get('auto_send', '0') === '1',
            'scheduleTime' => str_replace(',', ', ', Setting::get('schedule_time', '09:00')),
            'scheduleOn'   => Setting::get('schedule_enabled', '1') === '1',
            'recent'       => Post::latest()->take(5)->get(),
            'channels'     => $channels,
            'aiProviders'  => AiProviders::active(),
            // Har bir kanalning hozirgi sozlamasi — JS shuni "1 martalik" tanlovlarga boshlang'ich qiymat sifatida ishlatadi.
            'channelSettings' => $channels->mapWithKeys(fn ($ch) => [$ch->id => [
                'ai_provider' => $ch->ai_provider,
                'source_mode' => $ch->source_mode === 'scrape' ? 'scrape' : 'ai',
                'source_url'  => $ch->source_url ? preg_split('/\r?\n/', trim($ch->source_url)) : [],
            ]]),
        ]);
    }

    // Bir martalik override'larni (kanalning saqlangan sozlamasini o'zgartirmasdan,
    // faqat shu chaqiruv uchun) so'rovdan yig'ib oladi.
    private function overridesFromRequest(Request $request): array
    {
        $overrides = [];

        if ($request->filled('ai_provider')) {
            $overrides['ai_provider'] = $request->input('ai_provider');
        }

        if ($request->filled('source_mode')) {
            $overrides['source_mode'] = $request->input('source_mode');
            if ($overrides['source_mode'] === 'scrape') {
                $overrides['source_url'] = collect($request->input('source_url', []))
                    ->map(fn ($u) => trim((string) $u))
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        return $overrides;
    }

    /** "Hozir yaratib yuborish" tugmasi */
    public function runNow(Request $request, BotClient $bot)
    {
        $channelId = $request->input('channel_id');
        $res = $channelId
            ? $bot->runChannel((int) $channelId, $this->overridesFromRequest($request))
            : $bot->runNow();
        return redirect()->route('dashboard')->with(
            $res['ok'] ? 'success' : 'error',
            $res['ok'] ? 'Yangilik yaratildi va yuborildi.' : ('Xato: ' . ($res['error'] ?? 'nomaʼlum'))
        );
    }

    /** "Hozir yaratib tasdiqqa qo'yish" tugmasi */
    public function generateNow(Request $request, BotClient $bot)
    {
        $channelId = $request->input('channel_id');
        $res = $channelId
            ? $bot->generateChannel((int) $channelId, $this->overridesFromRequest($request))
            : $bot->generateNow();
        return redirect()->route('posts.index')->with(
            $res['ok'] ? 'success' : 'error',
            $res['ok'] ? 'Yangilik yaratildi, tasdiqlashni kuting.' : ('Xato: ' . ($res['error'] ?? 'nomaʼlum'))
        );
    }
}
