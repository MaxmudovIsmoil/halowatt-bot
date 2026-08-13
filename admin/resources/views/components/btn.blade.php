@props(['variant' => 'default', 'size' => 'md', 'as' => 'button'])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:cursor-not-allowed disabled:opacity-50';
    $sizes = [
        'md' => 'px-4 py-2.5 text-sm',
        'sm' => 'px-3 py-1.5 text-xs',
    ];
    $variants = [
        'default' => 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700',
        'primary' => 'bg-brand-500 text-slate-900 hover:bg-brand-400 focus:ring-brand-500',
        'success' => 'bg-emerald-500 text-white hover:bg-emerald-400 focus:ring-emerald-500',
        'danger' => 'border border-red-200 bg-transparent text-red-600 hover:bg-red-50 focus:ring-red-400 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10',
    ];
    $classes = $base . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['default']);
@endphp
@if($as === 'a')
    <a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
