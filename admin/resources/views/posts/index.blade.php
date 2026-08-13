@extends('layouts.app')
@section('title', 'Xabarlar')
@section('content')

<x-card>
    {{-- Sarlavha + qidiruv + per_page --}}
    <form method="GET" action="{{ route('posts.index') }}" class="mb-4 flex items-center gap-4">
        <h2 class="shrink-0 text-sm font-semibold text-slate-900 dark:text-slate-100">Barcha xabarlar</h2>

        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5 text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>
            <input name="q" id="posts-search" type="search"
                   value="{{ $q }}"
                   placeholder="Sana, manba, matn yoki kanal..."
                   class="form-input !pl-14 !pr-5">
        </div>

        <select name="per_page" onchange="this.form.submit()"
                class="form-input !w-auto min-w-[110px] cursor-pointer">
            @foreach([50, 100, 500] as $n)
                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} ta</option>
            @endforeach
        </select>

        <button type="submit"
                class="shrink-0 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-brand-400 active:scale-95">
            Qidirish
        </button>

        @if($q)
            <a href="{{ route('posts.index', ['per_page' => $perPage]) }}"
               class="shrink-0 text-sm text-slate-500 hover:text-red-500 dark:text-slate-400 dark:hover:text-red-400">
                ✕ Tozalash
            </a>
        @endif
    </form>

    @if($posts->isEmpty())
        <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ $q ? "«{$q}» bo'yicha hech narsa topilmadi." : 'Hali xabar yo\'q.' }}
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sana</th>
                        <th>Manba</th>
                        <th>Boshlanishi</th>
                        <th>Kanallar</th>
                        <th>Holat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($posts as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td class="whitespace-nowrap">{{ $p->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="text-slate-500 dark:text-slate-400">{{ strtoupper($p->source_type) }}</td>
                            <td class="max-w-xs text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::limit(strip_tags($p->content), 60) }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($p->channels as $ch)
                                        <span class="inline-block rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $ch->title }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td><x-badge :status="$p->status" /></td>
                            <td><x-btn as="a" href="{{ route('posts.show', $p) }}" size="sm">Ochish</x-btn></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between gap-4 text-sm text-slate-500 dark:text-slate-400">
            <span>Jami {{ $posts->total() }} ta xabar{{ $q ? ", «{$q}» bo'yicha" : '' }}</span>
            {{ $posts->links() }}
        </div>
    @endif
</x-card>

@endsection
