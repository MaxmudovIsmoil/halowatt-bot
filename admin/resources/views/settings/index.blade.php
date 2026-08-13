@extends('layouts.app')
@section('title', 'Sozlamalar')
@section('content')

<form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
    @csrf @method('PUT')

    <x-card title="Yuborish rejimi">
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
            <input type="checkbox" name="auto_send" {{ $auto_send === '1' ? 'checked' : '' }} class="form-checkbox">
            Avtomatik yuborish (tasdiqsiz)
        </label>
        <p class="mt-2.5 text-sm text-slate-500 dark:text-slate-400">
            O'chirilgan bo'lsa — har xabar avval tasdiqqa tushadi, siz "Xabarlar" bo'limida ko'rib, tahrirlab, keyin yuborasiz.
        </p>
    </x-card>

    <x-card title="Jadval">
        <label class="mb-4 flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
            <input type="checkbox" name="schedule_enabled" {{ $schedule_enabled === '1' ? 'checked' : '' }} class="form-checkbox">
            Jadval yoqilgan
        </label>
        <div class="max-w-sm">
            <label class="form-label">Har kuni yuborish vaqt(lar)i (server vaqti)</label>
            <div data-time-list class="space-y-2">
                @foreach($schedule_times as $t)
                    <div data-time-row class="flex items-center gap-2">
                        <input type="time" name="schedule_time[]" value="{{ $t }}" required class="form-input">
                        <button type="button" data-time-remove title="O'chirish"
                                class="shrink-0 rounded-lg border border-slate-200 p-2.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:border-slate-700 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
            <button type="button" data-time-add
                    class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Yana vaqt qo'shish
            </button>
            <p class="mt-2.5 text-xs text-slate-500 dark:text-slate-400">Har kuni bir nechta vaqtda yuborish uchun bir nechta vaqt qo'shishingiz mumkin.</p>
        </div>
    </x-card>

    <x-card title="AI prompt">
        <div>
            <label for="ai_prompt" class="form-label">Prompt matni</label>
            <textarea name="ai_prompt" id="ai_prompt" rows="14" class="form-input resize-y font-mono text-xs">{{ $ai_prompt }}</textarea>
        </div>
        <p class="mt-2.5 text-sm text-slate-500 dark:text-slate-400">
            Bu global prompt — kanalning o'z AI prompti bo'lmasa shu ishlatiladi.
        </p>
    </x-card>

    <x-btn variant="primary" type="submit">Sozlamalarni saqlash</x-btn>
</form>
@endsection
