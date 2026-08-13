@extends('layouts.app')
@section('title', 'AI provayderlar')
@section('content')

<p class="mb-5 text-sm text-slate-500 dark:text-slate-400">
    Har bir provayder uchun API kalit va model nomini kiriting. So'ng "Kanallar" bo'limida har bir kanal uchun
    qaysi provayderdan foydalanishni tanlashingiz mumkin.
</p>

<form method="POST" action="{{ route('settings.ai.update') }}" class="space-y-6">
    @csrf

    @foreach($providers as $key => $p)
        @php
            $barMap = [
                'ok'          => ['width' => 100, 'bar' => 'bg-emerald-500',                'badge' => 'on',       'label' => 'Balans mavjud'],
                'empty'       => ['width' => 100, 'bar' => 'bg-red-500',                     'badge' => 'rejected', 'label' => 'Balans tugagan'],
                'invalid_key' => ['width' => 100, 'bar' => 'bg-amber-400',                   'badge' => 'pending',  'label' => "API kalit noto'g'ri"],
                'error'       => ['width' => 100, 'bar' => 'bg-amber-400',                   'badge' => 'pending',  'label' => 'Tekshirib bo\'lmadi'],
                'unknown'     => ['width' => 0,   'bar' => 'bg-slate-300 dark:bg-slate-600', 'badge' => 'off',      'label' => 'Tekshirilmagan'],
            ];
            $bal = $barMap[$p['balance_status']] ?? $barMap['unknown'];
        @endphp
        <x-card title="{{ $p['label'] }}">
            <x-slot name="actions">
                <label class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                    <input type="checkbox" name="providers[{{ $key }}][active]" value="1" {{ $p['active'] ? 'checked' : '' }} class="form-checkbox">
                    Faol
                </label>
            </x-slot>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label">API kalit</label>
                    <input type="password" name="providers[{{ $key }}][api_key]" autocomplete="off"
                           placeholder="{{ $p['has_key'] ? '•••••••••••••••••••• (saqlangan)' : 'API kalitni kiriting' }}"
                           class="form-input">
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ $p['key_hint'] }}.
                        @if($p['has_key'])
                            Kalit saqlangan — o'zgartirish uchungina yangisini kiriting, bo'sh qoldirsangiz eskisi qoladi.
                        @endif
                    </p>
                </div>
                <div>
                    <label class="form-label">Model</label>
                    <input type="text" name="providers[{{ $key }}][model]" value="{{ $p['model'] }}" class="form-input">
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ $p['model_hint'] }}</p>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-3">
                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                    <div class="h-full rounded-full transition-all {{ $bal['bar'] }}" style="width: {{ $bal['width'] }}%"></div>
                </div>
                <x-badge :status="$bal['badge']">{{ $bal['label'] }}</x-badge>
            </div>
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                @if($p['balance_checked_at'])
                    Oxirgi tekshiruv: {{ $p['balance_checked_at'] }}
                @else
                    Hali tekshirilmagan
                @endif
                <button type="submit" formaction="{{ route('settings.ai.check-balance', $key) }}" formmethod="POST"
                        class="ml-2 font-medium text-brand-600 hover:underline dark:text-brand-400">
                    Balansni tekshirish
                </button>
            </p>
        </x-card>
    @endforeach

    <x-btn variant="primary" type="submit">Saqlash</x-btn>
</form>
@endsection
