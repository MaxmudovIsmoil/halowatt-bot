@extends('layouts.app')
@section('title', "Manbalar (RSS)")
@section('content')

<x-card title="Yangi RSS manba qo'shish">
    <form method="POST" action="{{ route('sources.store') }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="title" class="form-label">Nomi</label>
                <input type="text" name="title" id="title" placeholder="Hikvision Blog" required class="form-input">
            </div>
            <div>
                <label for="url" class="form-label">RSS URL</label>
                <input type="url" name="url" id="url" placeholder="https://www.hikvision.com/.../rss" required class="form-input">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
            <input type="checkbox" name="is_active" checked class="form-checkbox"> Faol
        </label>
        <x-btn variant="primary" type="submit">Qo'shish</x-btn>
    </form>
    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
        Bu manbalar <b class="text-slate-700 dark:text-slate-300">RSS</b> rejimida (yoki AI ishlamaganda zaxira sifatida) ishlatiladi. Rejimni Sozlamalardan tanlaysiz.
    </p>
</x-card>

<x-card title="Manbalar ro'yxati">
    @if($sources->isEmpty())
        <p class="text-sm text-slate-500 dark:text-slate-400">Hali manba yo'q.</p>
    @else
        <div class="overflow-x-auto">
            <table class="table-base">
                <tr><th>Nomi</th><th>URL</th><th>Holat</th><th></th></tr>
                @foreach($sources as $s)
                    <tr>
                        <td>
                            <form method="POST" action="{{ route('sources.update', $s) }}" class="flex flex-wrap items-end gap-2">
                                @csrf @method('PUT')
                                <input type="text" name="title" value="{{ $s->title }}" class="form-input w-36">
                                <input type="url" name="url" value="{{ $s->url }}" class="form-input w-56">
                                <label class="flex items-center gap-1.5 text-xs font-medium text-slate-600 dark:text-slate-400">
                                    <input type="checkbox" name="is_active" {{ $s->is_active ? 'checked' : '' }} class="form-checkbox"> faol
                                </label>
                                <x-btn size="sm" type="submit">Saqlash</x-btn>
                            </form>
                        </td>
                        <td class="max-w-[200px] truncate text-slate-500 dark:text-slate-400">{{ $s->url }}</td>
                        <td><x-badge :status="$s->is_active ? 'on' : 'off'">{{ $s->is_active ? "faol" : "o'chiq" }}</x-badge></td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('sources.toggle', $s) }}">@csrf
                                    <x-btn size="sm" type="submit">{{ $s->is_active ? "O'chirish" : 'Yoqish' }}</x-btn>
                                </form>
                                <form method="POST" action="{{ route('sources.destroy', $s) }}" onsubmit="return confirm('O\'chirilsinmi?')">@csrf @method('DELETE')
                                    <x-btn size="sm" variant="danger" type="submit">✕</x-btn>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
</x-card>
@endsection
