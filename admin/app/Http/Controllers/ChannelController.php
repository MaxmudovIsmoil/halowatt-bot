<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Setting;
use App\Services\BotClient;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    // https://t.me/username → @username; raqamli ID ni o'zgartirmaydi
    private function normalizeChatId(string $raw): string
    {
        $v = trim($raw);
        if (preg_match('#t\.me/([^/?]+)#i', $v, $m)) {
            return '@' . $m[1];
        }
        if ($v !== '' && $v[0] !== '@' && $v[0] !== '-' && !ctype_digit($v)) {
            return '@' . $v;
        }
        return $v;
    }

    // Nofaol qilingan AI provayderlarni ro'yxatdan chiqaradi; agar $mustInclude
    // (masalan kanal hozir foydalanayotgan provayder) nofaol bo'lsa ham saqlab qoladi.
    private function activeAiProviders(?string $mustInclude = null): array
    {
        $all = config('ai_providers');
        $active = array_filter(
            $all,
            fn ($key) => Setting::get("ai_{$key}_active", '1') !== '0',
            ARRAY_FILTER_USE_KEY
        );

        if ($mustInclude && !isset($active[$mustInclude]) && isset($all[$mustInclude])) {
            $active[$mustInclude] = $all[$mustInclude];
        }

        return $active;
    }

    public function index()
    {
        return view('channels.index', [
            'channels'    => Channel::withCount('sources')->latest()->get(),
            'aiProviders' => $this->activeAiProviders(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['chat_id' => $this->normalizeChatId($request->input('chat_id', ''))]);
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'chat_id'          => ['required', 'string', 'max:255', 'unique:channels,chat_id'],
            'ai_provider'      => ['nullable', 'in:claude,chatgpt,grok,gemini'],
            'schedule_time'    => ['required', 'array', 'min:1'],
            'schedule_time.*'  => ['required', 'date_format:H:i'],
            'source_mode'      => ['nullable', 'in:ai,scrape'],
            'source_url'       => ['nullable', 'required_if:source_mode,scrape', 'url', 'max:2048'],
        ]);

        $times = collect($data['schedule_time'])->unique()->sort()->values()->implode(',');
        $sourceMode = $data['source_mode'] ?? 'ai';

        $channel = Channel::create([
            'title'            => $data['title'],
            'chat_id'          => $data['chat_id'],
            'is_active'        => $request->boolean('is_active', true),
            'schedule_enabled' => $request->boolean('schedule_enabled'),
            'auto_send'        => $request->boolean('auto_send'),
            'schedule_time'    => $times,
            'language'         => $request->input('language', 'uz_latin'),
            'ai_provider'      => $data['ai_provider'] ?? 'claude',
            'category'         => $request->input('category', ''),
            'source_mode'      => $sourceMode,
            'source_url'       => $sourceMode === 'scrape' ? $data['source_url'] : null,
        ]);

        return redirect()->route('channels.show', $channel)->with('success', 'Kanal qo\'shildi. RSS manbalar va AI promptni sozlash uchun quyidagi sozlamalarni to\'ldiring.');
    }

    public function show(Channel $channel)
    {
        $scheduleTimes = array_filter(explode(',', $channel->schedule_time ?: '09:00'));
        return view('channels.show', [
            'channel'       => $channel->load('sources'),
            'scheduleTimes' => $scheduleTimes ?: ['09:00'],
            'aiProviders'   => $this->activeAiProviders($channel->ai_provider),
        ]);
    }

    public function update(Request $request, Channel $channel)
    {
        $request->merge(['chat_id' => $this->normalizeChatId($request->input('chat_id', ''))]);
        $data = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'chat_id'         => ['required', 'string', 'max:255', 'unique:channels,chat_id,' . $channel->id],
            'language'        => ['required', 'in:uz_latin,uz_cyrillic,ru,en'],
            'ai_provider'     => ['required', 'in:claude,chatgpt,grok,gemini'],
            'schedule_time'   => ['required', 'array', 'min:1'],
            'schedule_time.*' => ['required', 'date_format:H:i'],
            'ai_prompt'       => ['required', 'string'],
            'source_mode'     => ['nullable', 'in:ai,scrape'],
            'source_url'      => ['nullable', 'required_if:source_mode,scrape', 'url', 'max:2048'],
        ]);

        $times = collect($data['schedule_time'])->unique()->sort()->values()->implode(',');
        $sourceMode = $data['source_mode'] ?? 'ai';

        $channel->update([
            'title'            => $data['title'],
            'chat_id'          => $data['chat_id'],
            'language'         => $data['language'],
            'ai_provider'      => $data['ai_provider'],
            'schedule_time'    => $times,
            'ai_prompt'        => $data['ai_prompt'],
            'is_active'        => $request->boolean('is_active'),
            'schedule_enabled' => $request->boolean('schedule_enabled'),
            'auto_send'        => $request->boolean('auto_send'),
            'source_mode'      => $sourceMode,
            'source_url'       => $sourceMode === 'scrape' ? $data['source_url'] : null,
        ]);

        return back()->with('success', 'Saqlandi.');
    }

    public function updateSettings(Request $request, Channel $channel)
    {
        return $this->update($request, $channel);
    }

    public function toggle(Channel $channel)
    {
        $channel->update(['is_active' => !$channel->is_active]);
        return back()->with('success', 'Kanal holati o\'zgartirildi.');
    }

    public function runNow(Channel $channel, BotClient $bot)
    {
        $res = $bot->runChannel($channel->id);
        return back()->with(
            $res['ok'] ? 'success' : 'error',
            $res['ok'] ? 'Yangilik yaratildi va yuborildi.' : ('Xato: ' . ($res['error'] ?? 'noma\'lum'))
        );
    }

    public function destroy(Channel $channel)
    {
        $channel->delete();
        return redirect()->route('channels.index')->with('success', 'Kanal o\'chirildi.');
    }
}
