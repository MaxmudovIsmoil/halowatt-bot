<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\BotClient;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private const DEFAULT_PROMPT_HINT = 'Bo\'sh qoldirilsa, bot ichidagi standart Halowatt prompti ishlatiladi.';

    public function index()
    {
        $scheduleTimes = array_filter(explode(',', Setting::get('schedule_time', '09:00')));

        return view('settings.index', [
            'auto_send'        => Setting::get('auto_send', '1'),
            'schedule_times'   => $scheduleTimes ?: ['09:00'],
            'schedule_enabled' => Setting::get('schedule_enabled', '1'),
            'ai_prompt'        => Setting::get('ai_prompt', ''),
            'promptHint'       => self::DEFAULT_PROMPT_HINT,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'auto_send'          => ['nullable'],
            'schedule_enabled'   => ['nullable'],
            'schedule_time'      => ['required', 'array', 'min:1'],
            'schedule_time.*'    => ['required', 'date_format:H:i'],
            'ai_prompt'          => ['nullable', 'string'],
        ]);

        // Takrorlanmagan, tartiblangan vaqtlar — vergul bilan bitta qatorda saqlanadi.
        $times = collect($data['schedule_time'])->unique()->sort()->values()->implode(',');

        Setting::set('auto_send', $request->boolean('auto_send') ? '1' : '0');
        Setting::set('schedule_enabled', $request->boolean('schedule_enabled') ? '1' : '0');
        Setting::set('schedule_time', $times);
        Setting::set('ai_prompt', $data['ai_prompt'] ?? '');

        return back()->with('success', 'Sozlamalar saqlandi.');
    }
}
