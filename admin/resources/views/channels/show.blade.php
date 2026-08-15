@extends('layouts.app')
@section('title', $channel->title . ' – Sozlamalar')
@section('content')

<div class="mb-5">
    <a href="{{ route('channels.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        Kanallar ro'yxati
    </a>
</div>

<x-card title="Kanal sozlamalari">
    <form method="POST" action="{{ route('channels.update', $channel) }}" class="space-y-6">
        @csrf @method('PUT')

        {{-- 1-qator: Nomi + Chat ID --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label">Nomi</label>
                <input type="text" name="title" value="{{ $channel->title }}" required class="form-input">
            </div>
            <div>
                <label class="form-label">Chat ID yoki @username</label>
                <input type="text" name="chat_id" value="{{ $channel->chat_id }}" required class="form-input">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">URL yopishtirsangiz ham avtomatik tozalanadi.</p>
            </div>
        </div>

        {{-- 2-qator: Til + AI provayder --}}
        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label class="form-label">Til</label>
                <select name="language" class="form-input !w-auto min-w-[160px] max-w-[220px]">
                    <option value="uz_latin"    {{ $channel->language === 'uz_latin'    ? 'selected' : '' }}>O'zbek (lotin)</option>
                    <option value="uz_cyrillic" {{ $channel->language === 'uz_cyrillic' ? 'selected' : '' }}>Ўзбек (кирилл)</option>
                    <option value="ru"          {{ $channel->language === 'ru'          ? 'selected' : '' }}>Русский</option>
                    <option value="en"          {{ $channel->language === 'en'          ? 'selected' : '' }}>English</option>
                </select>
            </div>
            <div>
                <label class="form-label">AI provayder</label>
                <select name="ai_provider" class="form-input !w-auto min-w-[160px] max-w-[220px]">
                    @foreach($aiProviders as $key => $meta)
                        <option value="{{ $key }}" {{ $channel->ai_provider === $key ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Kalit va model: <a href="{{ route('settings.ai') }}" class="text-brand-600 hover:underline dark:text-brand-400">AI provayderlar</a>'da sozlanadi.
                </p>
            </div>
        </div>

        {{-- 2b-qator: Kontent manbasi + Yuborish vaqtlari --}}
        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label class="form-label">Kontent manbasi</label>
                <select name="source_mode" data-mode-select class="form-input !w-auto min-w-[280px]">
                    <option value="ai" {{ $channel->source_mode !== 'scrape' ? 'selected' : '' }}>AI qidiradi (kategoriya/prompt bo'yicha)</option>
                    <option value="scrape" {{ $channel->source_mode === 'scrape' ? 'selected' : '' }}>Skriptdan olish (AI faqat tarjima qiladi)</option>
                </select>
            </div>
            <div>
                <label class="form-label">Har kuni yuborish vaqt(lar)i</label>
                <div class="flex flex-wrap items-center gap-2">
                    <div data-time-list class="contents">
                        @foreach($scheduleTimes as $t)
                            <div data-time-row class="flex items-center gap-1.5">
                                <input type="time" name="schedule_time[]" value="{{ $t }}" required
                                       class="form-input !w-auto">
                                <button type="button" data-time-remove title="O'chirish"
                                        class="shrink-0 rounded-lg border border-slate-200 p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:border-slate-700 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" data-time-add
                            class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Qo'shish
                    </button>
                </div>
            </div>
        </div>

        {{-- Skript rejimi uchun manba havolalari --}}
        @php
            $sourceUrls = $channel->source_url ? preg_split('/\r?\n/', trim($channel->source_url)) : [];
            if (empty($sourceUrls)) $sourceUrls = [''];
        @endphp
        <div data-mode-url-wrap class="{{ $channel->source_mode === 'scrape' ? '' : 'hidden' }}">
            <label class="form-label">Manba havolasi (URL)</label>
            <div data-url-list class="space-y-2">
                @foreach($sourceUrls as $u)
                    <div data-url-row class="flex items-center gap-1.5">
                        <input type="url" name="source_url[]" data-mode-url-input value="{{ $u }}"
                               placeholder="https://example.com/maqola" class="form-input flex-1">
                        <button type="button" data-url-remove title="O'chirish"
                                class="shrink-0 rounded-lg border border-slate-200 p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:border-slate-700 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
            <button type="button" data-url-add
                    class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Qo'shish
            </button>
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                Skript shu havoladan(lardan) matn yuklab oladi, AI esa uni faqat tanlangan tilga tarjima/qayta ishlaydi — o'zi qidirmaydi.
            </p>
        </div>

        {{-- 3-qator: AI Prompt --}}
        <div>
            <label class="form-label">AI Prompt</label>
            <textarea name="ai_prompt" rows="12" required
                      class="form-input resize-y font-mono text-xs">{{ $channel->ai_prompt ?: $channel->category }}</textarea>
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                Agar bo'sh qoldirilsa — global sozlamadagi prompt ishlatiladi.
            </p>
        </div>

        {{-- Checkboxlar --}}
        <div class="flex flex-wrap items-center gap-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/40">
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="is_active" {{ $channel->is_active ? 'checked' : '' }} class="form-checkbox">
                Faol
            </label>
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="schedule_enabled" {{ $channel->schedule_enabled ? 'checked' : '' }} class="form-checkbox">
                Jadval yoqilgan
            </label>
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="auto_send" {{ $channel->auto_send ? 'checked' : '' }} class="form-checkbox">
                Avtomatik yuborish
            </label>
        </div>

        {{-- Tugmalar --}}
        <div class="flex flex-wrap items-center gap-3">
            <x-btn variant="primary" type="submit">Saqlash</x-btn>

            <div>
                <form method="POST" action="{{ route('channels.run', $channel) }}">
                    @csrf
                    <x-btn type="submit">⚡ Hozir yaratib yuborish</x-btn>
                </form>
            </div>

            <div class="ml-auto">
                <form method="POST" action="{{ route('channels.destroy', $channel) }}"
                      onsubmit="return confirm('Kanal o\'chirilsinmi?')">
                    @csrf @method('DELETE')
                    <x-btn variant="danger" type="submit">O'chirish</x-btn>
                </form>
            </div>
        </div>
    </form>
</x-card>

@endsection
