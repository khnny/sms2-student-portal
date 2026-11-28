/* Student Portal shell: clock, dark mode, sidebar groups and phone menu (same behaviour as CRAD). */
(function () {
    var clock = document.querySelector('[data-sp-clock]');
    if (clock) {
        var fmt = new Intl.DateTimeFormat('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: 'Asia/Manila' });
        var tick = function () { clock.textContent = fmt.format(new Date()); };
        tick();
        setInterval(tick, 1000);
    }

    var root = document.documentElement;
    var themeBtn = document.querySelector('[data-sp-theme]');
    var paintTheme = function () {
        if (!themeBtn) return;
        var dark = root.getAttribute('data-theme') === 'dark';
        themeBtn.innerHTML = dark ? '<i class="fa-regular fa-sun"></i>' : '<i class="fa-regular fa-moon"></i>';
        themeBtn.setAttribute('aria-label', dark ? 'Light mode' : 'Dark mode');
    };
    paintTheme();
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            try { localStorage.setItem('portal-theme', next); } catch (e) {}
            paintTheme();
        });
    }

    document.querySelectorAll('[data-sp-group]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var li = btn.closest('.sp-group');
            var open = li.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    document.querySelectorAll('[data-sp-menu]').forEach(function (el) {
        el.addEventListener('click', function () { document.body.classList.toggle('sp-nav-open'); });
    });
})();
