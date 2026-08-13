<script>
    (function () {
        var saved = localStorage.getItem('halowatt-theme');
        var theme = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        if (theme === 'dark') document.documentElement.classList.add('dark');
    })();
</script>
