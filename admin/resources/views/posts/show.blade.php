@extends('layouts.app')
@section('title', 'Xabar #'.$post->id)
@section('content')

@php
    $deleteConfirmMsg = $post->status === 'sent'
        ? "Bu post Telegram kanallaridan ham o'chiriladi. Davom etasizmi?"
        : "O'chirilsinmi?";
@endphp

<x-card>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
            Xabar #{{ $post->id }}
            <x-badge :status="$post->status" />
        </h2>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ strtoupper($post->source_type) }} · {{ $post->created_at?->format('d.m.Y H:i') }}</span>
    </div>

    <form method="POST" action="{{ route('posts.update', $post) }}">
        @csrf @method('PUT')
        <label for="content" class="form-label">Matn (tahrirlash mumkin)</label>
        <textarea name="content" id="content" rows="14" class="form-input">{{ $post->content }}</textarea>
        @if($post->status === 'sent')
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                ⚠️ Bu xabar allaqachon yuborilgan — saqlasangiz, Telegram kanallaridagi xabar(lar) ham tahrirlanadi.
            </p>
        @endif
        <div class="mt-3">
            <x-btn>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
                Matnni saqlash
            </x-btn>
        </div>
    </form>
</x-card>

<x-card title="Amallar">
    <div class="flex flex-wrap gap-3">
        @if($post->status !== 'sent')
            <form method="POST" action="{{ route('posts.approve', $post) }}" onsubmit="return confirm('Barcha faol kanallarga yuborilsinmi?')">
                @csrf
                <x-btn variant="success">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Tasdiqlab yuborish
                </x-btn>
            </form>
            <form method="POST" action="{{ route('posts.reject', $post) }}">
                @csrf
                <x-btn>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Rad etish
                </x-btn>
            </form>
        @endif
        <form method="POST" action="{{ route('posts.destroy', $post) }}"
              onsubmit="return confirm({{ \Illuminate\Support\Js::from($deleteConfirmMsg) }})">
            @csrf @method('DELETE')
            <x-btn variant="danger">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                O'chirish
            </x-btn>
        </form>
    </div>
    @if($post->status === 'sent')
        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">O'chirsangiz, Telegram kanallaridagi xabar(lar) ham o'chiriladi.</p>
    @endif
</x-card>

@if($post->channels->count())
<x-card title="Yuborish holati (kanal bo'yicha)">
    <div class="overflow-x-auto">
        <table class="table-base">
            <tr><th>Kanal</th><th>Holat</th><th>Xato</th><th>Vaqt</th></tr>
            @foreach($post->channels as $ch)
                <tr>
                    <td>{{ $ch->title }} <span class="text-slate-500 dark:text-slate-400">({{ $ch->chat_id }})</span></td>
                    <td><x-badge :status="$ch->pivot->status === 'sent' ? 'sent' : 'failed'">{{ $ch->pivot->status }}</x-badge></td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $ch->pivot->error ?? '—' }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $ch->pivot->sent_at ? \Illuminate\Support\Carbon::parse($ch->pivot->sent_at)->format('d.m.Y H:i') : '—' }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</x-card>
@endif

<x-btn as="a" href="{{ route('posts.index') }}" size="sm">← Ro'yxatga qaytish</x-btn>
@endsection
