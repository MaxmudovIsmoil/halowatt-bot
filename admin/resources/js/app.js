function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    localStorage.setItem('halowatt-theme', theme);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
            applyTheme(next);
        });
    });

    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const openSidebar = () => {
        sidebar?.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
    };
    const closeSidebar = () => {
        sidebar?.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
    };
    document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) => btn.addEventListener('click', openSidebar));
    overlay?.addEventListener('click', closeSidebar);
    document.querySelectorAll('[data-sidebar-close]').forEach((btn) => btn.addEventListener('click', closeSidebar));

    // Jadval vaqti qo'shish/o'chirish
    const timeList = document.querySelector('[data-time-list]');
    document.querySelector('[data-time-add]')?.addEventListener('click', () => {
        const rows = timeList.querySelectorAll('[data-time-row]');
        const clone = rows[rows.length - 1].cloneNode(true);
        clone.querySelector('input').value = '';
        timeList.appendChild(clone);
    });
    timeList?.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('[data-time-remove]');
        if (!removeBtn) return;
        if (timeList.querySelectorAll('[data-time-row]').length > 1) {
            removeBtn.closest('[data-time-row]').remove();
        }
    });

    // Kontent manbasi: AI qidiradi / Skriptdan olish — 2-tanlansa manba URL maydoni ochiladi
    document.querySelectorAll('[data-mode-select]').forEach((select) => {
        const form = select.closest('form');
        const urlWrap = form?.querySelector('[data-mode-url-wrap]');
        const urlInput = urlWrap?.querySelector('[data-mode-url-input]');
        if (!urlWrap) return;

        const sync = () => {
            const isScrape = select.value === 'scrape';
            urlWrap.classList.toggle('hidden', !isScrape);
            if (urlInput) urlInput.required = isScrape;
        };
        select.addEventListener('change', sync);
        sync();
    });

    // Modal: kanal qo'shish
    const modal = document.getElementById('add-channel-modal');
    if (modal) {
        const backdrop = modal.querySelector('[data-modal-backdrop]');
        const panel    = modal.querySelector('[data-modal-panel]');

        const openModal = () => {
            modal.classList.remove('invisible');
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                panel.classList.remove('translate-y-8', 'opacity-0');
            });
            document.body.classList.add('overflow-hidden');
        };

        const closeModal = () => {
            backdrop.classList.add('opacity-0');
            panel.classList.add('translate-y-8', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('invisible');
                document.body.classList.remove('overflow-hidden');
            }, 300);
        };

        document.querySelectorAll('[data-modal-open]').forEach((btn) =>
            btn.addEventListener('click', openModal)
        );
        document.querySelectorAll('[data-modal-close]').forEach((btn) =>
            btn.addEventListener('click', closeModal)
        );
        backdrop.addEventListener('click', () => {
            panel.classList.remove('modal-shake');
            void panel.offsetWidth; // reflow — animatsiyani qayta ishlatish uchun
            panel.classList.add('modal-shake');
            panel.addEventListener('animationend', () => panel.classList.remove('modal-shake'), { once: true });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('invisible')) closeModal();
        });
    }
});
