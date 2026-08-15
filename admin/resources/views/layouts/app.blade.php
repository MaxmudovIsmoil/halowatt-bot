<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Halowatt Admin')</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
<div class="min-h-screen lg:flex">

    <div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

    <aside data-sidebar
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex h-[72px] items-center gap-3 border-b border-slate-200 px-5 dark:border-slate-800">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-500 text-lg font-extrabold text-slate-900">H</div>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold leading-tight text-slate-900 dark:text-slate-100">Halowatt</p>
                <p class="truncate text-xs text-slate-500 dark:text-slate-400">Bot boshqaruvi</p>
            </div>
            <button data-sidebar-close type="button" class="ml-auto rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        @php
            $r = request()->route()?->getName();
            $navBase = 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100';
            $navActive = 'bg-brand-500/10 text-brand-700 hover:bg-brand-500/10 hover:text-brand-700 dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/10 dark:hover:text-brand-400';
        @endphp
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <a href="{{ route('dashboard') }}" class="{{ $navBase }} {{ $r==='dashboard' ? $navActive : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                Bosh sahifa
            </a>
            <a href="{{ route('channels.index') }}" class="{{ $navBase }} {{ str_starts_with($r ?? '', 'channels') ? $navActive : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535" />
                </svg>
                Kanallar
            </a>
            <a href="{{ route('posts.index') }}" class="{{ $navBase }} {{ str_starts_with($r ?? '', 'posts') ? $navActive : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                Xabarlar
            </a>

            <a href="{{ route('settings.index') }}" class="{{ $navBase }} {{ $r === 'settings.index' ? $navActive : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Sozlamalar
            </a>
            <a href="{{ route('settings.ai') }}" class="{{ $navBase }} {{ str_starts_with($r ?? '', 'settings.ai') ? $navActive : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                </svg>
                AI provayderlar
            </a>
        </nav>

        <div class="border-t border-slate-200 p-3 dark:border-slate-800">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-red-50 hover:text-red-600 dark:text-slate-400 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H3" />
                    </svg>
                    Chiqish
                </button>
            </form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-[72px] items-center justify-between gap-4 border-b border-slate-200 bg-white/80 px-3 backdrop-blur sm:px-6 dark:border-slate-800 dark:bg-slate-950/80">
            <div class="flex min-w-0 items-center gap-3">
                <button data-sidebar-toggle type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>
                <h1 class="truncate text-lg font-bold text-slate-900 dark:text-slate-100">@yield('title', 'Halowatt Admin')</h1>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                @yield('header_actions')
                <button data-theme-toggle type="button" title="Rejimni almashtirish"
                        class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hidden h-5 w-5 dark:block">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 dark:hidden">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                </button>
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold leading-tight text-slate-900 dark:text-slate-100">{{ auth()->user()->name ?? '' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Administrator</p>
                </div>
                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-500/20 text-sm font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto w-full @yield('content_max_width', 'max-w-full') space-y-6">
                @if(session('success'))
                    <div class="alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert-error">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert-error">
                        @foreach($errors->all() as $e)
                            {{ $e }}<br>
                        @endforeach
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
