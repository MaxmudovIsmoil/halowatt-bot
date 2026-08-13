@props(['title' => null])
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900']) }}>
    @if($title)
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h2>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    {{ $slot }}
</div>
