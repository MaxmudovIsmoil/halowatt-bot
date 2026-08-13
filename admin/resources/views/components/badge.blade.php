@props(['status'])
@php
    $map = [
        'on' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
        'sent' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
        'off' => 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-400',
        'pending' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400',
        'rejected' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
        'failed' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    ];
    $classes = $map[$status] ?? $map['off'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap $classes"]) }}>
    {{ $slot->isEmpty() ? $status : $slot }}
</span>
