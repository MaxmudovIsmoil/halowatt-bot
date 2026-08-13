<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AiBalanceChecker;
use Illuminate\Http\Request;

class AiSettingController extends Controller
{
    public function index()
    {
        $providers = [];
        foreach (config('ai_providers') as $key => $meta) {
            $providers[$key] = $meta + [
                'has_key'            => Setting::get("ai_{$key}_api_key", '') !== '',
                'model'              => Setting::get("ai_{$key}_model", $meta['model_default']),
                'active'             => Setting::get("ai_{$key}_active", '1') !== '0',
                'balance_status'     => Setting::get("ai_{$key}_balance_status", 'unknown'),
                'balance_checked_at' => Setting::get("ai_{$key}_balance_checked_at"),
            ];
        }

        return view('settings.ai', ['providers' => $providers]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'providers'            => ['required', 'array'],
            'providers.*.api_key'  => ['nullable', 'string', 'max:1000'],
            'providers.*.model'    => ['nullable', 'string', 'max:255'],
            'providers.*.active'   => ['nullable', 'boolean'],
        ]);

        foreach (config('ai_providers') as $key => $meta) {
            $incoming = $data['providers'][$key] ?? [];

            // Bo'sh qoldirilsa — avval saqlangan API kalit o'zgarmaydi (qayta kiritish shart emas).
            $apiKey = trim((string) ($incoming['api_key'] ?? ''));
            if ($apiKey !== '') {
                Setting::set("ai_{$key}_api_key", $apiKey);
            }

            $model = trim((string) ($incoming['model'] ?? ''));
            Setting::set("ai_{$key}_model", $model !== '' ? $model : $meta['model_default']);

            Setting::set("ai_{$key}_active", !empty($incoming['active']) ? '1' : '0');
        }

        return back()->with('success', 'AI provayder sozlamalari saqlandi.');
    }

    public function checkBalance(string $key)
    {
        abort_unless(array_key_exists($key, config('ai_providers')), 404);

        $apiKey = Setting::get("ai_{$key}_api_key", '');
        if ($apiKey === '') {
            return back()->with('error', "Avval API kalitni kiritib saqlang.");
        }

        $model = Setting::get("ai_{$key}_model");
        $status = AiBalanceChecker::check($key, $apiKey, $model);

        Setting::set("ai_{$key}_balance_status", $status);
        Setting::set("ai_{$key}_balance_checked_at", now()->format('Y-m-d H:i'));

        return back()->with('success', 'Balans tekshirildi.');
    }
}
