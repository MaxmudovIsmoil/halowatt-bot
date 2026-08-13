<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kirish — Halowatt Admin</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative grid min-h-screen place-items-center overflow-hidden bg-slate-50 px-4 antialiased dark:bg-slate-950">

    <div class="pointer-events-none absolute inset-x-0 -top-40 h-96 bg-[radial-gradient(1200px_600px_at_70%_-10%,rgba(245,179,1,.12),transparent)]"></div>

    <button data-theme-toggle type="button" title="Rejimni almashtirish"
            class="absolute right-4 top-4 rounded-lg border border-slate-200 bg-white p-2 text-slate-500 shadow-sm transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hidden h-5 w-5 dark:block">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 dark:hidden">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
        </svg>
    </button>

    <div class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-7 flex items-center gap-3">
            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-500 text-xl font-extrabold text-slate-900">H</div>
            <div>
                <p class="text-base font-bold leading-tight text-slate-900 dark:text-slate-100">Halowatt Academy</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Bot admin paneli</p>
            </div>
        </div>

        @if($errors->any())
            <div class="alert-error mb-5">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" id="username" value="{{ old('username') }}" autofocus required autocomplete="username" class="form-input">
            </div>
            <div>
                <label for="password" class="form-label">Parol</label>
                <input type="password" name="password" id="password" required autocomplete="current-password" class="form-input">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                <input type="checkbox" name="remember" class="form-checkbox">
                Meni eslab qol
            </label>
            <x-btn variant="primary" class="w-full">Kirish</x-btn>
        </form>
    </div>
</body>
</html>
