@extends('layouts.app')
@section('title', 'Kanallar')

@section('content_max_width', 'max-w-full')

@section('content')

<x-card title="Kanallar ro'yxati">
    <x-slot name="actions">
        <button data-modal-open type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-brand-400 active:scale-95">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Kanal qo'shish
        </button>
    </x-slot>
    @if($channels->isEmpty())
        <p class="text-sm text-slate-500 dark:text-slate-400">Hali kanal yo'q.</p>
    @else
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Nomi</th>
                        <th>Chat ID</th>
                        <th>Vaqt(lar)</th>
                        <th>Manbalar</th>
                        <th>Holat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($channels as $c)
                        <tr>
                            <td class="font-medium">
                                <a href="{{ route('channels.show', $c) }}" class="text-brand-600 hover:underline dark:text-brand-400">
                                    {{ $c->title }}
                                </a>
                            </td>
                            <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $c->chat_id }}</td>
                            <td class="text-slate-500 dark:text-slate-400 text-sm">
                                {{ $c->schedule_enabled ? str_replace(',', ', ', $c->schedule_time) : '—' }}
                            </td>
                            <td class="text-slate-500 dark:text-slate-400 text-sm">{{ $c->sources_count }} ta manba</td>
                            <td><x-badge :status="$c->is_active ? 'on' : 'off'">{{ $c->is_active ? 'faol' : "o'chiq" }}</x-badge></td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('channels.show', $c) }}">
                                        <x-btn size="sm">Sozlamalar</x-btn>
                                    </a>
                                    <form method="POST" action="{{ route('channels.toggle', $c) }}">@csrf
                                        <x-btn size="sm" type="submit">{{ $c->is_active ? "O'chirish" : 'Yoqish' }}</x-btn>
                                    </form>
                                    <form method="POST" action="{{ route('channels.destroy', $c) }}" onsubmit="return confirm('O\'chirilsinmi?')">@csrf @method('DELETE')
                                        <x-btn size="sm" variant="danger" type="submit">✕</x-btn>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>

{{-- Kanal qo'shish modali --}}
<div id="add-channel-modal"
     role="dialog" aria-modal="true" aria-labelledby="modal-title"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 sm:p-6 invisible">

    {{-- Backdrop --}}
    <div data-modal-backdrop
         class="absolute inset-0 bg-slate-900/60 opacity-0 backdrop-blur-sm transition-opacity duration-300"></div>

    {{-- Panel --}}
    <div data-modal-panel
         class="relative w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900 translate-y-8 opacity-0 transition-all duration-300 ease-out">

        {{-- Modal sarlavhasi --}}
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4 dark:border-slate-800 dark:bg-slate-900">
            <h2 id="modal-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">
                Yangi kanal qo'shish
            </h2>
            <button data-modal-close type="button"
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Forma --}}
        <form method="POST" action="{{ route('channels.store') }}" class="space-y-6 p-6">
            @csrf

            {{-- 1-qator: Nomi + Chat ID --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label">Nomi</label>
                    <input type="text" name="title" placeholder="Halowatt Academy" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Chat ID yoki @username</label>
                    <input type="text" name="chat_id" placeholder="@halowatt yoki -1001234567890" required class="form-input">
                </div>
            </div>

            {{-- 2-qator: Til + AI provayder --}}
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label class="form-label">Til</label>
                    <select name="language" class="form-input">
                        <option value="uz_latin" selected>O'zbek (lotin)</option>
                        <option value="uz_cyrillic">Ўзбек (кирилл)</option>
                        <option value="ru">Русский</option>
                        <option value="en">English</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">AI provayder</label>
                    <select name="ai_provider" class="form-input">
                        @foreach($aiProviders as $key => $meta)
                            <option value="{{ $key }}" {{ $key === 'claude' ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- 3-qator: Yuborish vaqtlari --}}
            <div>
                <label class="form-label">Har kuni yuborish vaqt(lar)i</label>
                <div class="flex flex-wrap items-center gap-2">
                    <div data-time-list class="contents">
                        <div data-time-row class="flex items-center gap-1.5">
                            <input type="time" name="schedule_time[]" value="09:00" required
                                   class="form-input !w-auto">
                            <button type="button" data-time-remove title="O'chirish"
                                    class="shrink-0 rounded-lg border border-slate-200 p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:border-slate-700 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
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

            {{-- 3-qator: Kategoriya / AI prompt --}}
            <div>
                <label class="form-label">Kategoriya / AI prompt</label>
                <textarea name="category" rows="7"
                          placeholder="Masalan: Hikvision kameralar va Access Control tizimlari bo'yicha kunlik yangiliklar, mahsulot taqdimotlari va amaliy maslahatlar..."
                          class="form-input resize-y"></textarea>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    AI shu prompt asosida ma'lumot qidiradi va post yaratadi.
                </p>
            </div>

            {{-- Qo'shimcha sozlamalar --}}
            <div class="flex flex-wrap items-center gap-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/40">
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="is_active" checked class="form-checkbox"> Faol
                </label>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="schedule_enabled" checked class="form-checkbox"> Jadval yoqilgan
                </label>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="auto_send" class="form-checkbox"> Avtomatik yuborish
                </label>

                <select name="source_mode" data-mode-select class="form-input !w-auto min-w-[240px] ml-auto">
                    <option value="ai" selected>AI qidiradi (kategoriya/prompt bo'yicha)</option>
                    <option value="scrape">Skriptdan olish (AI faqat tarjima qiladi)</option>
                </select>
            </div>

            {{-- Skript rejimi uchun manba havolasi --}}
            <div data-mode-url-wrap class="hidden">
                <label class="form-label">Manba havolasi (URL)</label>
                <input type="url" name="source_url" data-mode-url-input placeholder="https://example.com/maqola"
                       class="form-input">
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    Skript shu havoladan matn yuklab oladi, AI esa uni faqat tanlangan tilga tarjima/qayta ishlaydi — o'zi qidirmaydi.
                </p>
            </div>

            {{-- Tugmalar --}}
            <div class="flex flex-wrap items-center gap-3">
                <x-btn variant="primary" type="submit">Saqlash</x-btn>
                <button type="button" data-modal-close
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100">
                    Yopish
                </button>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    ⚠️ Botni kanalga <b class="text-slate-600 dark:text-slate-300">administrator</b> qilib qo'shing.
                </p>
            </div>
        </form>
    </div>
</div>

@endsection
