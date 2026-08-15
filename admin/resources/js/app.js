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

    // Manba havolalari (URL) qo'shish/o'chirish — skript rejimi uchun
    const urlList = document.querySelector('[data-url-list]');
    document.querySelector('[data-url-add]')?.addEventListener('click', () => {
        const rows = urlList.querySelectorAll('[data-url-row]');
        const clone = rows[rows.length - 1].cloneNode(true);
        clone.querySelector('input').value = '';
        urlList.appendChild(clone);
    });
    urlList?.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('[data-url-remove]');
        if (!removeBtn) return;
        if (urlList.querySelectorAll('[data-url-row]').length > 1) {
            removeBtn.closest('[data-url-row]').remove();
        }
    });

    // Kontent manbasi: AI qidiradi / Skriptdan olish — 2-tanlansa manba URL ro'yxati ochiladi
    document.querySelectorAll('[data-mode-select]').forEach((select) => {
        const form = select.closest('form');
        const urlWrap = form?.querySelector('[data-mode-url-wrap]');
        if (!urlWrap) return;

        // Eslatma: bir nechta havoladan kamida bittasi kifoya, shuning uchun
        // HTML `required` qo'yilmaydi — buni server tomon tekshiradi.
        const sync = () => urlWrap.classList.toggle('hidden', select.value !== 'scrape');
        select.addEventListener('change', sync);
        sync();
    });

    // Bosh sahifa "Tezkor amallar": kanal tanlangach AI provayder/Kontent manbasi
    // shu kanalning joriy sozlamalari bilan to'ldirilib ko'rsatiladi — bular faqat
    // shu bir martalik ishga tushirish uchun, kanalning o'zi o'zgarmaydi.
    const quickChannelSelect = document.querySelector('[data-quick-channel-select]');
    if (quickChannelSelect) {
        const overrideWrap = document.querySelector('[data-quick-override-wrap]');
        const providerSelect = document.querySelector('[data-quick-provider-select]');
        const modeSelect = document.querySelector('[data-quick-mode-select]');
        const channelSettings = JSON.parse(quickChannelSelect.dataset.channelSettings || '{}');

        const applyChannel = () => {
            const settings = channelSettings[quickChannelSelect.value];
            if (!settings) {
                overrideWrap?.classList.add('hidden');
                return;
            }

            overrideWrap?.classList.remove('hidden');
            if (providerSelect) providerSelect.value = settings.ai_provider;
            if (modeSelect) {
                modeSelect.value = settings.source_mode;
                modeSelect.dispatchEvent(new Event('change'));
            }

            const quickUrlList = overrideWrap?.querySelector('[data-url-list]');
            const rowTemplate = quickUrlList?.querySelector('[data-url-row]');
            if (quickUrlList && rowTemplate) {
                const urls = settings.source_url?.length ? settings.source_url : [''];
                quickUrlList.innerHTML = '';
                urls.forEach((url) => {
                    const clone = rowTemplate.cloneNode(true);
                    clone.querySelector('input').value = url;
                    quickUrlList.appendChild(clone);
                });
            }
        };

        quickChannelSelect.addEventListener('change', applyChannel);
        applyChannel();
    }

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
