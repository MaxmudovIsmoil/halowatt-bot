@extends('layouts.app')
@section('title', 'Bosh sahifa')
@section('content')

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <x-card class="!p-4">
        <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $channelCount }}</div>
        <div class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Faol kanal</div>
    </x-card>
    <x-card class="!p-4">
        <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $sourceCount }}</div>
        <div class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Faol manba</div>
    </x-card>
    <x-card class="!p-4">
        <div class="text-2xl font-bold text-brand-600 dark:text-brand-400">{{ $pendingCount }}</div>
        <div class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Tasdiq kutmoqda</div>
    </x-card>
    <x-card class="!p-4">
        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $sentCount }}</div>
        <div class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Yuborilgan</div>
    </x-card>
</div>

<x-card title="Tezkor amallar">
    <form method="POST" class="flex flex-wrap items-center gap-3">
        @csrf
        <select name="channel_id" class="form-input !w-auto min-w-[180px] max-w-xs">
            <option value="">Barcha kanallar</option>
            @foreach($channels as $ch)
                <option value="{{ $ch->id }}">{{ $ch->title }}</option>
            @endforeach
        </select>
        <button type="submit" formaction="{{ route('run.now') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/50">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
            </svg>
            Hozir yaratib yuborish
        </button>
        <button type="submit" formaction="{{ route('generate.now') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-500/50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
            </svg>
            Yaratib tasdiqqa qo'yish
        </button>
    </form>
    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
        Yuborish: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $autoSend ? 'Avtomatik' : 'Admin tasdiqlaydi' }}</span> ·
        Jadval: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $scheduleOn ? "Yoqilgan, har kuni $scheduleTime" : "O'chirilgan" }}</span>
        — <a href="{{ route('settings.index') }}" class="font-medium text-brand-600 hover:underline dark:text-brand-400">sozlash</a>
    </p>
</x-card>

<x-card title="Oxirgi xabarlar">
    @if($recent->isEmpty())
        <p class="text-sm text-slate-500 dark:text-slate-400">Hali xabar yo'q.</p>
    @else
        <div class="overflow-x-auto">
            <table class="table-base">
                <tr><th>#</th><th>Sana</th><th>Manba</th><th>Holat</th><th></th></tr>
                @foreach($recent as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>{{ $p->created_at?->format('d.m.Y H:i') }}</td>
                        <td class="text-slate-500 dark:text-slate-400">{{ strtoupper($p->source_type) }}</td>
                        <td><x-badge :status="$p->status" /></td>
                        <td><x-btn as="a" href="{{ route('posts.show', $p) }}" size="sm">Ko'rish</x-btn></td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
</x-card>
@endsection
