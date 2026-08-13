<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Source;
use App\Services\BotClient;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'channelCount' => Channel::where('is_active', true)->count(),
            'sourceCount'  => Source::where('is_active', true)->count(),
            'pendingCount' => Post::where('status', 'pending')->count(),
            'sentCount'    => Post::where('status', 'sent')->count(),
            'autoSend'     => Setting::get('auto_send', '0') === '1',
            'scheduleTime' => str_replace(',', ', ', Setting::get('schedule_time', '09:00')),
            'scheduleOn'   => Setting::get('schedule_enabled', '1') === '1',
            'recent'       => Post::latest()->take(5)->get(),
            'channels'     => Channel::where('is_active', true)->orderBy('title')->get(),
        ]);
    }

    /** "Hozir yaratib yuborish" tugmasi */
    public function runNow(Request $request, BotClient $bot)
    {
        $channelId = $request->input('channel_id');
        $res = $channelId ? $bot->runChannel((int) $channelId) : $bot->runNow();
        return redirect()->route('dashboard')->with(
            $res['ok'] ? 'success' : 'error',
            $res['ok'] ? 'Yangilik yaratildi va yuborildi.' : ('Xato: ' . ($res['error'] ?? 'nomaʼlum'))
        );
    }

    /** "Hozir yaratib tasdiqqa qo'yish" tugmasi */
    public function generateNow(Request $request, BotClient $bot)
    {
        $channelId = $request->input('channel_id');
        $res = $channelId ? $bot->generateChannel((int) $channelId) : $bot->generateNow();
        return redirect()->route('posts.index')->with(
            $res['ok'] ? 'success' : 'error',
            $res['ok'] ? 'Yangilik yaratildi, tasdiqlashni kuting.' : ('Xato: ' . ($res['error'] ?? 'nomaʼlum'))
        );
    }
}
