/* Masáže v Sásovej – interaktivita (bez externých knižníc) */
(function () {
    'use strict';

    document.documentElement.classList.add('js');

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.addEventListener('DOMContentLoaded', function () {
        initHeader();
        initMobileNav();
        initReveal();
        initCarousels();
        initScrollSpy();
        initBookingLinks();
        initContactForm();
        initMap();
        initLightbox();
        initMobileBar();
        initCookieConsent();

        var flash = document.querySelector('[data-focus-on-load]');
        if (flash) { flash.focus({ preventScroll: true }); }
    });

    /* ---------- Hlavička: pozadie po scrollnutí ---------- */
    function initHeader() {
        var header = document.querySelector('[data-header]');
        if (!header) { return; }
        var update = function () { header.classList.toggle('is-scrolled', window.scrollY > 24); };
        update();
        window.addEventListener('scroll', update, { passive: true });
    }

    /* ---------- Mobilné menu ---------- */
    function initMobileNav() {
        var toggle = document.querySelector('[data-nav-toggle]');
        var nav = document.querySelector('[data-nav]');
        var header = document.querySelector('[data-header]');
        var label = document.querySelector('[data-nav-toggle-label]');
        if (!toggle || !nav) { return; }

        var setOpen = function (open) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            nav.classList.toggle('is-open', open);
            header.classList.toggle('menu-open', open);
            document.body.classList.toggle('no-scroll', open);
            if (label) { label.textContent = open ? 'Zavrieť menu' : 'Otvoriť menu'; }
        };

        toggle.addEventListener('click', function () {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });
        nav.addEventListener('click', function (e) {
            if (e.target.closest('a')) { setOpen(false); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && nav.classList.contains('is-open')) {
                setOpen(false);
                toggle.focus();
            }
        });
        window.matchMedia('(min-width: 961px)').addEventListener('change', function (mq) {
            if (mq.matches) { setOpen(false); }
        });
    }

    /* ---------- Jemné objavenie sekcií ---------- */
    function initReveal() {
        var items = document.querySelectorAll('.reveal');
        if (reduceMotion || !('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
        items.forEach(function (el) { io.observe(el); });
    }

    /* ---------- Carousely: masáže, O mne, galéria (šípky + indikátor; posun prstom zabezpečuje natívny scroll) ---------- */
    function initCarousels() {
        document.querySelectorAll('[data-carousel]').forEach(initCarousel);
    }

    function initCarousel(root) {
        var track = root.querySelector('[data-carousel-track]');
        var prev = root.querySelector('[data-carousel-prev]');
        var next = root.querySelector('[data-carousel-next]');
        var bar = root.querySelector('[data-carousel-bar]');
        if (!track || !prev || !next) { return; }

        var gap = function () {
            var style = window.getComputedStyle(track);
            return parseFloat(style.columnGap || style.gap) || 0;
        };
        // Šírka jednej karty vrátane medzery
        var step = function () {
            var first = track.firstElementChild;
            return first ? first.getBoundingClientRect().width + gap() : track.clientWidth;
        };
        var perView = function () {
            return Math.max(1, Math.round((track.clientWidth + gap()) / step()));
        };
        var go = function (dir) {
            track.scrollBy({ left: dir * step() * perView(), behavior: reduceMotion ? 'auto' : 'smooth' });
        };

        var ticking = false;
        var update = function () {
            ticking = false;
            var max = track.scrollWidth - track.clientWidth;
            var pos = Math.max(0, Math.min(track.scrollLeft, max));
            prev.disabled = pos <= 2;
            next.disabled = pos >= max - 2;
            if (bar && track.scrollWidth > 0) {
                var visible = track.clientWidth / track.scrollWidth;
                bar.style.width = (visible * 100) + '%';
                bar.style.left = (max > 0 ? (pos / max) * (1 - visible) * 100 : 0) + '%';
            }
            root.classList.toggle('is-static', max <= 2);
        };
        var requestUpdate = function () {
            if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
        };

        prev.addEventListener('click', function () { go(-1); });
        next.addEventListener('click', function () { go(1); });
        track.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate, { passive: true });
        window.addEventListener('load', requestUpdate);
        update();
    }

    /* ---------- Zvýraznenie aktívnej položky menu ---------- */
    function initScrollSpy() {
        var links = document.querySelectorAll('[data-nav-link]');
        if (!links.length || !('IntersectionObserver' in window)) { return; }
        var map = {};
        links.forEach(function (a) { map[a.getAttribute('data-nav-link')] = a; });

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting && map[entry.target.id]) {
                    links.forEach(function (a) { a.classList.remove('is-active'); a.removeAttribute('aria-current'); });
                    map[entry.target.id].classList.add('is-active');
                    map[entry.target.id].setAttribute('aria-current', 'true');
                }
            });
        }, { rootMargin: '-45% 0px -50% 0px' });

        Object.keys(map).forEach(function (id) {
            var section = document.getElementById(id);
            if (section) { io.observe(section); }
        });
    }

    /* ---------- „Objednať sa“ pri karte predvyplní masáž vo formulári ---------- */
    function initBookingLinks() {
        var select = document.querySelector('[data-service-select]');
        var contact = document.getElementById('kontakt');
        if (!select || !contact) { return; }

        document.addEventListener('click', function (e) {
            var link = e.target.closest('[data-book]');
            if (!link) { return; }
            e.preventDefault();
            // Ak sa práve zobrazuje potvrdenie (demo), vrátime prázdny formulár
            if (select.form && select.form.hidden) { document.dispatchEvent(new CustomEvent('booking:show')); }
            var slug = link.getAttribute('data-book');
            if (/^permanentka/.test(slug)) {
                if (select.form.setOrderType) { select.form.setOrderType('pass', slug); }
            } else {
                if (select.form.setOrderType) { select.form.setOrderType('single'); }
                select.value = slug;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            contact.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth' });
            history.replaceState(null, '', '#kontakt');
            window.setTimeout(function () {
                var name = document.getElementById('f-name');
                if (name) { name.focus({ preventScroll: true }); }
            }, reduceMotion ? 0 : 700);
        });
    }

    /* ---------- Kalendár termínov (objednávkový formulár) ----------
     * - Čas a dátum vždy podľa slovenského času (Europe/Bratislava), nie podľa nastavenia zariadenia.
     * - data-days = pracovný čas a obsadené úseky po dňoch (v minútach od polnoci; nepracovné dni chýbajú).
     *   Obsadenosť je v demo režime iba UKÁŽKA (includes/booking-demo.php). Server overuje všetko znova.
     * - Výpočet zodpovedá booking_start_times() v includes/booking.php: masáž sa musí celá zmestiť
     *   do pracovného času a od každej rezervácie ju musí deliť prestávka medzi klientmi (data-buffer).
     * - Dĺžka (options.minutes): > 0 = známa, 0 = ešte nevybraná, -1 = neznáma (permanentka, „poraďte mi“)
     *   → pri neznámej dĺžke sa obsadenosť NEOVERUJE a časy sú výslovne označené ako neoverené.
     * - Klávesnica: šípky ←/→ deň, ↑/↓ týždeň, PageUp/PageDown mesiac, Home/End týždeň, Enter/medzerník výber.
     * - Vybraný dátum a čas zapisuje do polí name="date" a name="time". Žiadne sieťové požiadavky.
     */
    function initBookingCalendar(form, options) {
        var root = form.querySelector('[data-booking-calendar]');
        var dateInput = form.elements.date;
        var timeInput = form.elements.time;
        if (!root || !dateInput || !timeInput) { return null; }
        options = options || {};
        var getMinutes = options.minutes || function () { return 0; };

        var MONTHS = ['január', 'február', 'marec', 'apríl', 'máj', 'jún', 'júl', 'august', 'september', 'október', 'november', 'december'];
        var MONTHS_GEN = ['januára', 'februára', 'marca', 'apríla', 'mája', 'júna', 'júla', 'augusta', 'septembra', 'októbra', 'novembra', 'decembra'];
        var WEEKDAYS = ['nedeľa', 'pondelok', 'utorok', 'streda', 'štvrtok', 'piatok', 'sobota'];

        var list = function (v) { return Array.isArray(v) ? v : Object.keys(v || {}).map(function (k) { return v[k]; }); };
        var days = {};
        try { days = JSON.parse(root.getAttribute('data-days') || '{}') || {}; } catch (e) { return null; }
        Object.keys(days).forEach(function (k) {
            days[k] = { open: list(days[k].open).map(list), busy: list(days[k].busy).map(list) };
        });
        var step = parseInt(root.getAttribute('data-step'), 10) || 15;
        var buffer = parseInt(root.getAttribute('data-buffer'), 10) || 0;

        var pad = function (n) { return (n < 10 ? '0' : '') + n; };
        var fmt = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
        var parse = function (s) { var p = s.split('-').map(Number); return new Date(p[0], p[1] - 1, p[2]); };
        var addDays = function (ds, n) { var d = parse(ds); d.setDate(d.getDate() + n); return fmt(d); };
        var toMin = function (hm) { var p = String(hm).split(':').map(Number); return p[0] * 60 + p[1]; };
        var fromMin = function (m) { m = Math.max(0, Math.min(m, 24 * 60 - 1)); return pad(Math.floor(m / 60)) + ':' + pad(m % 60); };
        var short = function (m) { return fromMin(m).replace(/^0/, ''); };

        // ---- Slovenský čas (Europe/Bratislava) ----
        var tz = root.getAttribute('data-tz') || 'Europe/Bratislava';
        var tzFormat = null;
        try {
            tzFormat = new Intl.DateTimeFormat('en-GB', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
        } catch (e) { tzFormat = null; }
        var serverNow = root.getAttribute('data-server-now') || '';
        var loadedAt = Date.now();
        var skNow = function () {
            if (tzFormat && tzFormat.formatToParts) {
                var p = {};
                tzFormat.formatToParts(new Date()).forEach(function (x) { p[x.type] = x.value; });
                return { date: p.year + '-' + p.month + '-' + p.day, time: (p.hour === '24' ? '00' : p.hour) + ':' + p.minute };
            }
            // Náhrada: čas servera (slovenský) + čas strávený na stránke
            var m = serverNow.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/);
            if (!m) { var n = new Date(); return { date: fmt(n), time: pad(n.getHours()) + ':' + pad(n.getMinutes()) }; }
            var d = new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5] + Math.floor((Date.now() - loadedAt) / 60000));
            return { date: fmt(d), time: pad(d.getHours()) + ':' + pad(d.getMinutes()) };
        };

        var maxStr = root.getAttribute('data-max');
        var minStr = function () { var a = root.getAttribute('data-min'), t = skNow().date; return a > t ? a : t; };
        var monthStart = function (ds) { return ds.slice(0, 8) + '01'; };
        var monthEnd = function (ds) { var d = parse(ds); return fmt(new Date(d.getFullYear(), d.getMonth() + 1, 0)); };

        var daysEl = root.querySelector('[data-cal-days]');
        var titleEl = root.querySelector('[data-cal-title]');
        var prevBtn = root.querySelector('[data-cal-prev]');
        var nextBtn = root.querySelector('[data-cal-next]');
        var slotsEl = root.querySelector('[data-cal-slots]');
        var timesTitle = root.querySelector('[data-cal-times-title]');
        var timesEmpty = root.querySelector('[data-cal-times-empty]');
        var hoursEl = root.querySelector('[data-cal-hours]');
        var summary = root.querySelector('[data-cal-summary]');
        var legend = function (k) { return root.querySelector('[data-legend="' + k + '"]'); };
        var notice = document.createElement('p');
        notice.className = 'booking-cal__notice';
        notice.setAttribute('role', 'status');
        notice.hidden = true;
        summary.parentNode.insertBefore(notice, summary);
        // Trvalé upozornenie pri neznámej dĺžke masáže (dostupnosť sa neoveruje)
        var unknownWarn = document.createElement('p');
        unknownWarn.className = 'booking-cal__warn';
        unknownWarn.hidden = true;
        unknownWarn.innerHTML = '<strong>Dostupnosť nevieme overiť.</strong> Pri tejto voľbe ešte nepoznáme dĺžku masáže, preto kalendár neoveruje obsadenosť. '
            + 'Ponúkame iba začiatky v pracovnom čase – termín vám po dohode potvrdíme telefonicky.';
        root.insertBefore(unknownWarn, root.firstChild);

        var selectedDate = '', selectedTime = '', focusDate = '';
        var view = parse(minStr());
        view.setDate(1);

        // ---- Dostupnosť (zhodná s booking_start_times() v PHP) ----
        var isPast = function (ds, start) {
            var now = skNow();
            return ds < now.date || (ds === now.date && start <= toMin(now.time));
        };
        // minutes > 0: overí dĺžku, prestávku a obsadenosť; null: iba pracovný čas (neoverené)
        var startTimes = function (ds, minutes) {
            var day = days[ds];
            if (!day || ds < minStr() || ds > maxStr) { return []; }
            var need = minutes || step, out = [];
            day.open.forEach(function (iv) {
                for (var s = iv[0]; s + need <= iv[1]; s += step) {
                    if (isPast(ds, s)) { continue; }
                    var clash = minutes && day.busy.some(function (b) { return s < b[1] + buffer && b[0] < s + need + buffer; });
                    if (!clash) { out.push(fromMin(s)); }
                }
            });
            return out;
        };
        var mode = function () { var m = getMinutes(); return m > 0 ? 'known' : (m < 0 ? 'unknown' : 'pending'); };
        var startsFor = function (ds) { return mode() === 'known' ? startTimes(ds, getMinutes()) : startTimes(ds, null); };
        // 'free' = voľný začiatok pre zvolenú dĺžku, 'busy' = pracovný deň bez voľného času,
        // 'unknown' / 'pending' = pracovný deň, dostupnosť sa neoveruje / ešte chýba dĺžka, 'off' = nepracovný deň / minulosť
        var dayState = function (ds) {
            if (ds < minStr() || ds > maxStr || !days[ds]) { return 'off'; }
            var working = startTimes(ds, null).length > 0;
            if (!working) { return 'off'; }
            var m = mode();
            if (m !== 'known') { return m; }
            return startTimes(ds, getMinutes()).length ? 'free' : 'busy';
        };
        var selectable = function (state) { return state === 'free' || state === 'unknown' || state === 'pending'; };
        var offReason = function (ds) {
            if (ds < minStr()) { return 'uplynulý deň'; }
            if (ds > maxStr) { return 'mimo obdobia objednávania'; }
            return days[ds] ? 'pracovný čas už uplynul' : 'nepracovný deň';
        };
        var longDate = function (ds, withYear) {
            var d = parse(ds);
            return WEEKDAYS[d.getDay()] + ' ' + d.getDate() + '. ' + MONTHS_GEN[d.getMonth()] + (withYear ? ' ' + d.getFullYear() : '');
        };
        var endOf = function (start) { return fromMin(toMin(start) + getMinutes()); };
        var write = function (input, value) {
            input.value = value;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };
        var showNotice = function (text) { notice.textContent = text || ''; notice.hidden = !text; };

        // ---- Vykreslenie ----
        var renderLegend = function () {
            var m = mode();
            legend('free').hidden = m !== 'known';
            legend('busy').hidden = m !== 'known';
            var u = legend('unknown');
            u.hidden = m === 'known';
            u.lastChild.textContent = m === 'unknown' ? 'Pracovný deň – dostupnosť neoverená' : 'Pracovný deň – vyberte dĺžku masáže';
            unknownWarn.hidden = m !== 'unknown';
        };

        var renderDays = function () {
            renderLegend();
            var y = view.getFullYear(), m = view.getMonth();
            titleEl.textContent = MONTHS[m] + ' ' + y;
            var min = minStr();
            prevBtn.disabled = fmt(view) <= monthStart(min);
            nextBtn.disabled = fmt(view) >= monthStart(maxStr);

            var viewStart = fmt(view), viewEnd = monthEnd(viewStart);
            var tabTarget = '';
            [focusDate, selectedDate].forEach(function (c) { if (!tabTarget && c && c >= viewStart && c <= viewEnd) { tabTarget = c; } });

            daysEl.innerHTML = '';
            var lead = (new Date(y, m, 1).getDay() + 6) % 7; // pondelok = prvý stĺpec
            for (var i = 0; i < lead; i++) {
                var blank = document.createElement('span');
                blank.className = 'booking-cal__blank';
                blank.setAttribute('aria-hidden', 'true');
                daysEl.appendChild(blank);
            }
            var count = new Date(y, m + 1, 0).getDate(), today = skNow().date, minutes = getMinutes();
            var buttons = [];
            for (var day = 1; day <= count; day++) {
                var ds = y + '-' + pad(m + 1) + '-' + pad(day);
                var state = dayState(ds);
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'booking-cal__day is-' + state + (ds === today ? ' is-today' : '') + (ds === selectedDate ? ' is-selected' : '');
                btn.textContent = day;
                btn.setAttribute('data-date', ds);
                btn.tabIndex = -1;
                var stateText = {
                    free: 'voľné termíny pre ' + minutes + ' min',
                    busy: 'bez voľného termínu pre ' + minutes + ' min',
                    unknown: 'pracovný deň, dostupnosť neoverená',
                    pending: 'pracovný deň, najprv vyberte dĺžku masáže',
                    off: offReason(ds)
                }[state];
                btn.setAttribute('aria-label', longDate(ds, true) + ', ' + stateText + (ds === selectedDate ? ', vybraný' : ''));
                if (ds === today) { btn.setAttribute('aria-current', 'date'); }
                if (selectable(state)) {
                    btn.setAttribute('aria-pressed', ds === selectedDate ? 'true' : 'false');
                } else {
                    btn.setAttribute('aria-disabled', 'true');
                }
                if (!tabTarget && selectable(state)) { tabTarget = ds; }
                buttons.push(btn);
                daysEl.appendChild(btn);
            }
            if (!tabTarget) { tabTarget = viewStart; }
            buttons.forEach(function (b) { if (b.getAttribute('data-date') === tabTarget) { b.tabIndex = 0; } });
        };

        var renderSlots = function () {
            slotsEl.innerHTML = '';
            var m = mode(), minutes = getMinutes();
            var lengthText = m === 'known' ? ' pre ' + minutes + ' min' : '';
            var titleBase = m === 'unknown' ? 'Možné začiatky (neoverené)' : 'Dostupné časy' + lengthText;
            hoursEl.hidden = true;
            if (!selectedDate) {
                timesTitle.textContent = titleBase;
                timesEmpty.hidden = false;
                timesEmpty.textContent = 'Najprv vyberte v kalendári deň.';
                summary.hidden = true;
                return;
            }
            timesTitle.textContent = titleBase + ' – ' + longDate(selectedDate, false);
            var day = days[selectedDate];
            if (day) {
                hoursEl.hidden = false;
                hoursEl.textContent = 'Pracovný čas' + (root.hasAttribute('data-demo-schedule') ? ' (ukážka)' : '') + ': '
                    + day.open.map(function (iv) { return short(iv[0]) + ' – ' + short(iv[1]); }).join(', ')
                    + (buffer && m === 'known' ? ' · medzi klientmi ' + buffer + ' min prestávka' : '');
            }
            if (m === 'pending') {
                timesEmpty.hidden = false;
                timesEmpty.textContent = 'Vyberte, prosím, dĺžku masáže – podľa nej zobrazíme voľné začiatky.';
                summary.hidden = true;
                return;
            }
            var free = startsFor(selectedDate);
            timesEmpty.hidden = free.length > 0;
            timesEmpty.textContent = 'V tento deň už nie je voľný žiadny čas' + lengthText + '. Vyberte iný deň.';
            free.forEach(function (s) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'booking-cal__slot' + (m === 'unknown' ? ' is-unverified' : '') + (s === selectedTime ? ' is-selected' : '');
                b.innerHTML = '<span class="booking-cal__slot-start"></span><span class="booking-cal__slot-end"></span>';
                b.firstChild.textContent = s;
                b.lastChild.textContent = m === 'unknown' ? 'neoverené' : '– ' + endOf(s);
                b.setAttribute('data-time', s);
                b.setAttribute('aria-label', m === 'unknown' ? 'začiatok ' + s + ', dostupnosť neoverená' : s + ' až ' + endOf(s));
                b.setAttribute('aria-pressed', s === selectedTime ? 'true' : 'false');
                slotsEl.appendChild(b);
            });
            summary.hidden = !selectedTime;
            summary.innerHTML = selectedTime ? (m === 'unknown' ? 'Požadovaný začiatok: ' : 'Vybraný termín: ') + '<strong></strong>' : '';
            if (selectedTime) {
                summary.querySelector('strong').textContent = longDate(selectedDate, true) + ', '
                    + (m === 'unknown' ? selectedTime + ' (dĺžka neurčená – termín potvrdíme telefonicky)'
                        : selectedTime + ' – ' + endOf(selectedTime) + ' (' + minutes + ' min)');
            }
        };

        var render = function () { renderDays(); renderSlots(); };

        var focusDay = function (ds) {
            var first = monthStart(minStr()), last = monthEnd(maxStr);
            if (ds < first) { ds = first; }
            if (ds > last) { ds = last; }
            focusDate = ds;
            if (monthStart(ds) !== fmt(view)) { view = parse(monthStart(ds)); }
            renderDays();
            var btn = daysEl.querySelector('[data-date="' + ds + '"]');
            if (btn) { btn.focus({ preventScroll: false }); }
        };

        // ---- Výber ----
        var canStart = function (ds, s) { return mode() !== 'pending' && startsFor(ds).indexOf(s) !== -1; };
        var selectDate = function (ds) {
            if (!selectable(dayState(ds))) { return false; }
            selectedDate = ds;
            selectedTime = '';
            focusDate = ds;
            showNotice('');
            write(dateInput, ds);
            write(timeInput, '');
            render();
            return true;
        };
        var selectTime = function (s) {
            if (!selectedDate || !canStart(selectedDate, s)) { return; }
            selectedTime = s;
            showNotice('');
            write(timeInput, s);
            renderSlots();
            var btn = slotsEl.querySelector('[data-time="' + s + '"]');
            if (btn) { btn.focus({ preventScroll: true }); }
        };

        // Prepočet po zmene masáže / dĺžky alebo plynutím času
        var refresh = function (reason) {
            if (selectedDate && !selectable(dayState(selectedDate))) {
                showNotice(reason === 'time'
                    ? 'Vybraný deň už nemá voľný termín. Vyberte, prosím, iný deň.'
                    : 'Pre zvolenú dĺžku masáže nie je vo vybraný deň voľný termín. Vyberte, prosím, iný deň.');
                selectedDate = ''; selectedTime = '';
                write(dateInput, ''); write(timeInput, '');
            } else if (selectedTime && !canStart(selectedDate, selectedTime)) {
                showNotice(reason === 'time'
                    ? 'Vybraný čas už uplynul. Vyberte, prosím, iný čas.'
                    : mode() === 'pending'
                        ? 'Vyberte, prosím, dĺžku masáže a potom znova čas.'
                        : 'Vybraný čas nie je pre zvolenú dĺžku masáže voľný. Vyberte, prosím, iný čas.');
                selectedTime = '';
                write(timeInput, '');
            }
            var active = document.activeElement && root.contains(document.activeElement) ? document.activeElement.getAttribute('data-date') : null;
            render();
            if (active) { var b = daysEl.querySelector('[data-date="' + active + '"]'); if (b) { b.focus({ preventScroll: true }); } }
        };

        daysEl.addEventListener('click', function (e) {
            var btn = e.target.closest('.booking-cal__day');
            if (!btn) { return; }
            var ds = btn.getAttribute('data-date');
            if (btn.getAttribute('aria-disabled') === 'true') { focusDate = ds; return; }
            selectDate(ds);
            var again = daysEl.querySelector('[data-date="' + ds + '"]');
            if (again) { again.focus({ preventScroll: true }); }
        });
        daysEl.addEventListener('keydown', function (e) {
            var btn = e.target.closest('.booking-cal__day');
            if (!btn) { return; }
            var ds = btn.getAttribute('data-date'), d = parse(ds), target = null;
            var weekday = (d.getDay() + 6) % 7;
            switch (e.key) {
                case 'ArrowLeft': target = addDays(ds, -1); break;
                case 'ArrowRight': target = addDays(ds, 1); break;
                case 'ArrowUp': target = addDays(ds, -7); break;
                case 'ArrowDown': target = addDays(ds, 7); break;
                case 'Home': target = addDays(ds, -weekday); break;
                case 'End': target = addDays(ds, 6 - weekday); break;
                case 'PageUp': target = fmt(new Date(d.getFullYear(), d.getMonth() - 1, Math.min(d.getDate(), new Date(d.getFullYear(), d.getMonth(), 0).getDate()))); break;
                case 'PageDown': target = fmt(new Date(d.getFullYear(), d.getMonth() + 1, Math.min(d.getDate(), new Date(d.getFullYear(), d.getMonth() + 2, 0).getDate()))); break;
                default: return; // Enter a medzerník spracuje tlačidlo samo (klik)
            }
            e.preventDefault();
            focusDay(target);
        });
        slotsEl.addEventListener('click', function (e) {
            var btn = e.target.closest('.booking-cal__slot');
            if (btn) { selectTime(btn.getAttribute('data-time')); }
        });
        prevBtn.addEventListener('click', function () { view.setMonth(view.getMonth() - 1); focusDate = ''; renderDays(); });
        nextBtn.addEventListener('click', function () { view.setMonth(view.getMonth() + 1); focusDate = ''; renderDays(); });

        // Kalendár nahradí náhradné polia bez JavaScriptu
        var fallback = form.querySelector('[data-booking-fallback]');
        if (fallback) { fallback.hidden = true; }
        root.hidden = false;

        // Predvyplnený termín (napr. po návrate zo servera), ak je stále možný
        if (dateInput.value && selectable(dayState(dateInput.value))) {
            selectedDate = dateInput.value;
            view = parse(monthStart(selectedDate));
            selectedTime = timeInput.value && canStart(selectedDate, timeInput.value) ? timeInput.value : '';
            timeInput.value = selectedTime;
        } else {
            dateInput.value = '';
            timeInput.value = '';
        }
        render();

        // Plynutím času (slovenského) môžu dnešné časy uplynúť – kontrola každých 30 s
        var lastTick = skNow().date + ' ' + skNow().time;
        window.setInterval(function () {
            var n = skNow(), tick = n.date + ' ' + n.time;
            if (tick !== lastTick) { lastTick = tick; refresh('time'); }
        }, 30000);

        return {
            now: skNow,
            refresh: function () { refresh('duration'); },
            isAvailable: function (ds, s) { return selectable(dayState(ds)) && (mode() === 'pending' ? startTimes(ds, null).indexOf(s) !== -1 : canStart(ds, s)); },
            focus: function (field) {
                var target = field === 'time' && selectedDate
                    ? slotsEl.querySelector('.booking-cal__slot')
                    : daysEl.querySelector('.booking-cal__day[tabindex="0"]') || nextBtn;
                if (target) { target.focus({ preventScroll: true }); }
                root.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
            },
            reset: function () {
                selectedDate = ''; selectedTime = ''; focusDate = '';
                showNotice('');
                view = parse(minStr());
                view.setDate(1);
                render();
            }
        };
    }

    /* ---------- Objednávkový formulár ----------
     * data-mode="demo":  iba overenie v prehliadači a informačné potvrdenie – ŽIADNA sieťová požiadavka,
     *                    nič sa neukladá (ani do localStorage/cookies), po obnovení stránky nič nezostane.
     * data-mode="email": (po schválení) odoslanie na send-form.php bez obnovenia stránky.
     * Pravidlá validácie zodpovedajú includes/booking.php (server overuje všetko znova).
     */
    function initContactForm() {
        var form = document.querySelector('[data-contact-form]');
        if (!form) { return; }

        var isDemo = form.getAttribute('data-mode') !== 'email';
        var status = form.querySelector('[data-form-status]');
        var button = form.querySelector('[data-submit]');
        var buttonLabel = form.querySelector('[data-submit-label]');
        var defaultLabel = buttonLabel ? buttonLabel.textContent : '';
        var done = document.querySelector('[data-booking-done]');
        var fields = ['name', 'phone', 'email', 'pass_type', 'service', 'duration', 'date', 'time', 'note'];

        // Typ objednávky: 'single' = jednorazová masáž (termín v kalendári), 'pass' = permanentka (bez termínu)
        var orderType = function () {
            var r = form.querySelector('[name="order_type"]:checked');
            return r && r.value === 'pass' ? 'pass' : 'single';
        };

        // Dĺžka masáže podľa zvolenej služby (dáta zo services.php → 'lengths')
        var serviceSelect = form.elements.service;
        var durationSelect = form.elements.duration;
        var durationNote = form.querySelector('[data-duration-note]');
        var lengths = {};
        try { lengths = JSON.parse(durationSelect.getAttribute('data-lengths') || '{}'); } catch (e) { lengths = {}; }
        var lengthsOf = function (slug) { return (lengths[slug] && lengths[slug].l) || []; };
        // > 0 = zvolená dĺžka, 0 = ešte nevybraná, -1 = dĺžka nie je známa (permanentka, „poraďte mi“)
        var currentMinutes = function () {
            var slug = serviceSelect.value;
            if (!slug) { return 0; }
            if (!lengthsOf(slug).length) { return -1; }
            return parseInt(durationSelect.value, 10) || 0;
        };
        // Dĺžka a cena pri výbere masáže (údaje zo services.php / cenníka)
        var info = form.querySelector('[data-service-info]');
        var renderInfo = function () {
            var slug = serviceSelect.value, data = orderType() === 'single' && lengths[slug] && lengths[slug].i;
            if (!info) { return; }
            info.hidden = !data;
            if (!data) { return; }
            var chosen = null, m = parseInt(durationSelect.value, 10);
            lengthsOf(slug).forEach(function (l) { if (l[0] === m) { chosen = l; } });
            info.querySelector('[data-service-info-length]').textContent = chosen ? chosen[0] + ' min' : data.lengths;
            info.querySelector('[data-service-info-price]').textContent = chosen ? (chosen[2] || 'zatiaľ neuvedená v cenníku') : data.price;
            info.classList.toggle('is-unknown', !data.known);
        };
        var rebuildDurations = function () {
            var slug = serviceSelect.value, list = lengthsOf(slug), keep = durationSelect.value;
            durationSelect.innerHTML = '';
            var add = function (value, label) {
                var o = document.createElement('option');
                o.value = value; o.textContent = label;
                durationSelect.appendChild(o);
            };
            if (!slug) {
                add('', 'Najprv vyberte masáž');
                durationSelect.disabled = true;
            } else if (!list.length) {
                add('', 'Dohodneme individuálne');
                durationSelect.disabled = true;
            } else {
                if (list.length > 1) { add('', 'Vyberte dĺžku'); }
                list.forEach(function (l) { add(String(l[0]), l[1]); });
                durationSelect.disabled = false;
                var stillValid = list.some(function (l) { return String(l[0]) === keep; });
                durationSelect.value = stillValid ? keep : (list.length === 1 ? String(list[0][0]) : '');
            }
            durationNote.hidden = !(slug && !list.length);
            renderInfo();
        };

        var calendar = initBookingCalendar(form, { minutes: currentMinutes });
        rebuildDurations();
        serviceSelect.addEventListener('change', function () {
            rebuildDurations();
            if (calendar) { calendar.refresh(); }
        });
        durationSelect.addEventListener('change', function () {
            renderInfo();
            if (calendar) { calendar.refresh(); }
        });

        // Prepínanie typu objednávky – skryté polia sa vypnú (nie sú povinné a neodošlú sa),
        // ich hodnoty aj ostatné vyplnené údaje zostanú zachované pre prípad návratu.
        var legendText = form.querySelector('[data-order-legend]');
        var serviceLabel = form.querySelector('[data-service-label]');
        var applyOrderType = function () {
            var type = orderType();
            form.setAttribute('data-order-type', type);
            [].forEach.call(form.querySelectorAll('[data-order]'), function (block) {
                var off = block.getAttribute('data-order') !== type;
                block.hidden = off;
                [].forEach.call(block.querySelectorAll('input, select, textarea'), function (el) {
                    if (off) { el.disabled = true; setError(el.name, ''); } else if (el !== durationSelect) { el.disabled = false; }
                });
            });
            if (type === 'single') { rebuildDurations(); } else { durationSelect.disabled = true; }
            if (legendText) { legendText.textContent = type === 'pass' ? 'Masáž a permanentka' : 'Masáž a termín'; }
            if (serviceLabel) { serviceLabel.textContent = type === 'pass' ? 'Masáž, ktorú chcete s permanentkou využívať' : 'Výber masáže'; }
            renderInfo();
            if (type === 'single' && calendar) { calendar.refresh(); }
        };
        [].forEach.call(form.querySelectorAll('[name="order_type"]'), function (r) {
            r.addEventListener('change', applyOrderType);
        });
        form.setOrderType = function (type, pass) {
            var r = form.querySelector('[name="order_type"][value="' + type + '"]');
            if (r) { r.checked = true; }
            if (pass && form.elements.pass_type) { form.elements.pass_type.value = pass; }
            applyOrderType();
        };

        var messages = {
            name: 'Zadajte, prosím, meno a priezvisko.',
            nameChars: 'Meno môže obsahovať iba písmená, medzery a pomlčky.',
            phoneEmpty: 'Zadajte, prosím, telefónne číslo.',
            phone: 'Zadajte platné telefónne číslo, napr. 0915 123 456 alebo +421 915 123 456.',
            emailEmpty: 'Zadajte, prosím, e-mailovú adresu.',
            email: 'Zadajte platnú e-mailovú adresu, napr. meno@gmail.com.',
            service: 'Vyberte, prosím, masáž.',
            servicePass: 'Vyberte, prosím, masáž, ktorú chcete s permanentkou využívať.',
            passType: 'Vyberte, prosím, typ permanentky.',
            duration: 'Vyberte, prosím, dĺžku masáže.',
            dateEmpty: 'Vyberte, prosím, dátum v kalendári.',
            dateInvalid: 'Zadajte platný dátum.',
            datePast: 'Dátum nemôže byť v minulosti. Vyberte dnešný alebo neskorší dátum.',
            time: 'Vyberte, prosím, čas.',
            timeTaken: 'Tento termín už nie je voľný (podľa slovenského času mohol aj uplynúť). Vyberte, prosím, iný čas alebo deň.',
            note: 'Poznámka obsahuje príliš veľa odkazov.'
        };

        // Dnešný dátum podľa slovenského času (z kalendára); bez kalendára podľa zariadenia
        var pad = function (n) { return (n < 10 ? '0' : '') + n; };
        var todaySk = function () {
            if (calendar) { return calendar.now().date; }
            var n = new Date();
            return n.getFullYear() + '-' + pad(n.getMonth() + 1) + '-' + pad(n.getDate());
        };

        var maxDate = form.elements.date ? form.elements.date.max : '';
        var dateError = function (value, emptyMessage) {
            if (!value) { return emptyMessage || ''; }
            if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) { return messages.dateInvalid; }
            var parts = value.split('-').map(Number);
            var d = new Date(parts[0], parts[1] - 1, parts[2]);
            if (d.getFullYear() !== parts[0] || d.getMonth() !== parts[1] - 1 || d.getDate() !== parts[2]) { return messages.dateInvalid; }
            if (value < todaySk()) { return messages.datePast; }
            if (maxDate && value > maxDate) {
                var m = maxDate.split('-');
                return 'Vyberte dátum najneskôr do ' + Number(m[2]) + '. ' + Number(m[1]) + '. ' + m[0] + '.';
            }
            return '';
        };

        var setError = function (field, message) {
            var input = form.elements[field];
            var holder = form.querySelector('[data-error-for="' + field + '"]');
            if (!input || !holder) { return; }
            var wrap = input.closest('.field');
            holder.textContent = message || '';
            if (!holder.id) { holder.id = 'err-' + field; }
            if (message) {
                wrap.classList.add('has-error');
                input.setAttribute('aria-invalid', 'true');
                input.setAttribute('aria-describedby', holder.id);
            } else {
                var other = [].some.call(wrap.querySelectorAll('[data-error-for]'), function (el) { return el.textContent.trim() !== ''; });
                if (!other) { wrap.classList.remove('has-error'); }
                input.removeAttribute('aria-invalid');
                input.removeAttribute('aria-describedby');
            }
        };

        var showStatus = function (type, text) {
            status.innerHTML = '';
            if (!text) { return; }
            var p = document.createElement('p');
            p.className = 'form-notice form-notice--' + type;
            p.setAttribute('tabindex', '-1');
            p.textContent = text;
            status.appendChild(p);
        };

        var val = function (name) { return (form.elements[name].value || '').trim(); };

        // Počiatočný stav podľa zvoleného typu objednávky (aj po návrate zo servera / z odkazu ?masaz=permanentka-…)
        applyOrderType();

        var validate = function () {
            var errors = {};
            var name = val('name'), phone = val('phone'), email = val('email');
            var digits = phone.replace(/\D/g, '');

            if (name.length < 3) {
                errors.name = messages.name;
            } else if (!/^\p{L}[\p{L} .'\-]*$/u.test(name)) {
                errors.name = messages.nameChars;
            }
            if (!phone) {
                errors.phone = messages.phoneEmpty;
            } else if (!/^\+?[0-9 ()\/\-]+$/.test(phone) || digits.length < 9 || digits.length > 15) {
                errors.phone = messages.phone;
            }
            if (!email) {
                errors.email = messages.emailEmpty;
            } else if (email.length > 150 || !/^[^\s@]+@[^\s@.]+(\.[^\s@.]+)*\.[^\s@.]{2,}$/.test(email)) {
                errors.email = messages.email;
            }
            if (orderType() === 'pass') {
                // Permanentka: typ a masáž; dátum, čas ani dĺžka sa neoverujú (dohodnú sa osobne)
                if (!val('pass_type')) { errors.pass_type = messages.passType; }
                if (!val('service')) { errors.service = messages.servicePass; }
                if ((val('note').match(/(https?:\/\/|www\.)/gi) || []).length > 2) { errors.note = messages.note; }
                return errors;
            }
            if (!val('service')) { errors.service = messages.service; }
            if (val('service') && lengthsOf(val('service')).length && !val('duration')) { errors.duration = messages.duration; }

            var date = val('date'), time = val('time');
            var e1 = dateError(date, messages.dateEmpty);
            if (e1) { errors.date = e1; }
            if (!time) {
                errors.time = messages.time;
            } else if (!e1 && calendar && !calendar.isAvailable(date, time)) {
                errors.time = messages.timeTaken; // minulý alebo obsadený termín
            }

            if ((val('note').match(/(https?:\/\/|www\.)/gi) || []).length > 2) { errors.note = messages.note; }
            return errors;
        };

        // Chyba pri poli zmizne, keď ho používateľ opraví
        fields.forEach(function (field) {
            var input = form.elements[field];
            if (!input) { return; }
            ['input', 'change'].forEach(function (type) {
                input.addEventListener(type, function () {
                    if (this.closest('.field').classList.contains('has-error')) { setError(field, ''); }
                });
            });
        });

        // Zhrnutie v demo potvrdení: typ požiadavky, masáž, permanentka / termín – bez osobných údajov
        var optionText = function (select) {
            var o = select && select.options[select.selectedIndex];
            return o && o.value ? o.textContent.trim() : '';
        };
        var fillSummary = function () {
            var box = document.querySelector('[data-booking-done-summary]');
            if (!box) { return; }
            var lines = [], kind;
            if (orderType() === 'pass') {
                kind = 'Požiadavka na permanentku';
                lines.push(optionText(form.elements.pass_type).replace(/ – zľava.*$/, ''));
                lines.push('Masáž: ' + optionText(serviceSelect));
                lines.push('Termíny by sme dohodli osobne.');
            } else {
                kind = 'Požiadavka na jednorazovú masáž';
                lines.push(optionText(serviceSelect) + (val('duration') ? ', ' + val('duration') + ' min' : ''));
                var sum = form.querySelector('[data-cal-summary] strong');
                lines.push('Termín: ' + (sum ? sum.textContent : val('date') + ' ' + val('time')));
            }
            box.querySelector('[data-booking-done-kind]').textContent = kind;
            var ul = box.querySelector('[data-booking-done-lines]');
            ul.innerHTML = '';
            lines.forEach(function (t) { var li = document.createElement('li'); li.textContent = t; ul.appendChild(li); });
            box.hidden = false;
        };

        var showDone = function () {
            form.hidden = true;
            done.hidden = false;
            done.focus({ preventScroll: true });
            done.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
        };

        var showForm = function () {
            form.reset();
            fields.forEach(function (f) { setError(f, ''); });
            showStatus('', '');
            if (done) { done.hidden = true; }
            form.hidden = false;
        };
        document.addEventListener('booking:show', showForm);
        form.addEventListener('reset', function () {
            window.setTimeout(function () {
                rebuildDurations();
                if (calendar) { calendar.reset(); }
                applyOrderType();
            }, 0);
        });

        var again = document.querySelector('[data-booking-again]');
        if (again) {
            again.addEventListener('click', function (e) {
                e.preventDefault();
                showForm();
                form.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
                form.elements.name.focus({ preventScroll: true });
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            fields.forEach(function (f) { setError(f, ''); });

            var errors = validate();
            var keys = Object.keys(errors);
            if (keys.length) {
                keys.forEach(function (f) { setError(f, errors[f]); });
                showStatus('error', keys.length === 1 ? 'Skontrolujte, prosím, zvýraznené pole.' : 'Skontrolujte, prosím, zvýraznené polia (' + keys.length + ').');
                if (calendar && (keys[0] === 'date' || keys[0] === 'time')) {
                    calendar.focus(keys[0]);
                } else {
                    form.elements[keys[0]].focus();
                }
                return;
            }

            if (isDemo) {
                // DEMO: nič sa neodosiela ani neukladá – údaje sa zahodia.
                fillSummary();
                showStatus('', '');
                form.reset();
                if (done) { showDone(); }
                return;
            }

            // Režim 'email' (po schválení): odoslanie na server
            if (!window.fetch || !window.FormData) { form.submit(); return; }
            button.disabled = true;
            if (buttonLabel) { buttonLabel.textContent = 'Odosielam…'; }

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (res) {
                    return res.json().catch(function () {
                        return { ok: false, message: 'Požiadavku sa nepodarilo odoslať. Skúste to, prosím, neskôr alebo zavolajte.' };
                    });
                })
                .then(function (data) {
                    if (data.errors) {
                        Object.keys(data.errors).forEach(function (f) { setError(f, data.errors[f]); });
                    }
                    showStatus(data.ok ? 'success' : 'error', data.message);
                    var notice = status.firstChild;
                    if (notice) { notice.focus({ preventScroll: true }); notice.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' }); }
                    if (data.ok) { form.reset(); }
                })
                .catch(function () {
                    showStatus('error', 'Nepodarilo sa spojiť so serverom. Skontrolujte pripojenie alebo zavolajte.');
                })
                .finally(function () {
                    button.disabled = false;
                    if (buttonLabel) { buttonLabel.textContent = defaultLabel; }
                });
        });
    }

    /* ---------- Google Maps: načítanie až po kliknutí (GDPR) ---------- */
    function initMap() {
        var frame = document.querySelector('[data-map]');
        var btn = document.querySelector('[data-map-load]');
        if (!frame || !btn) { return; }
        btn.addEventListener('click', function () {
            var iframe = document.createElement('iframe');
            iframe.src = frame.getAttribute('data-src');
            iframe.title = 'Mapa – Rudohorská 31, Banská Bystrica';
            iframe.loading = 'lazy';
            iframe.referrerPolicy = 'no-referrer-when-downgrade';
            iframe.setAttribute('allowfullscreen', '');
            frame.innerHTML = '';
            frame.appendChild(iframe);
        });
    }

    /* ---------- Lightbox galérie ---------- */
    function initLightbox() {
        var dialog = document.querySelector('[data-lightbox-dialog]');
        var links = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]'));
        if (!dialog || !links.length || typeof dialog.showModal !== 'function') { return; }

        var img = dialog.querySelector('[data-lightbox-img]');
        var current = 0;

        var show = function (i) {
            current = (i + links.length) % links.length;
            var source = links[current].querySelector('img');
            img.src = links[current].getAttribute('href');
            img.alt = source ? source.alt : '';
        };

        links.forEach(function (link, i) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                show(i);
                dialog.showModal();
            });
        });
        dialog.querySelector('[data-lightbox-close]').addEventListener('click', function () { dialog.close(); });
        dialog.querySelector('[data-lightbox-prev]').addEventListener('click', function () { show(current - 1); });
        dialog.querySelector('[data-lightbox-next]').addEventListener('click', function () { show(current + 1); });
        dialog.addEventListener('click', function (e) { if (e.target === dialog) { dialog.close(); } });
        dialog.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') { show(current - 1); }
            if (e.key === 'ArrowRight') { show(current + 1); }
        });
        if (links.length < 2) {
            dialog.querySelectorAll('.lightbox__nav').forEach(function (b) { b.hidden = true; });
        }
    }

    /* ---------- Cookie lišta a súhlas (nevyhnutné vždy, analytika až po súhlase) ---------- */
    function initCookieConsent() {
        var bar = document.querySelector('[data-cookie-bar]');
        if (!bar) { return; }

        var NAME = 'mvs_consent';
        var VERSION = 1;
        var MAX_AGE = 60 * 60 * 24 * 182; // približne 6 mesiacov
        var settings = bar.querySelector('[data-cookie-settings]');
        var toggle = bar.querySelector('[data-cookie-toggle]');
        var analyticsBox = bar.querySelector('[data-cookie-analytics]');
        var saveBtn = bar.querySelector('[data-cookie-save]');
        var analyticsId = (bar.getAttribute('data-analytics-id') || '').trim();
        // Web má nepovinné cookies iba pri nastavenej analytike – inak lišta len informuje (tlačidlo „Rozumiem“)
        var optional = bar.getAttribute('data-optional') === '1';
        var acceptBtn = bar.querySelector('[data-cookie-accept]');
        var analyticsLoaded = false;

        var read = function () {
            var match = document.cookie.match(new RegExp('(?:^|; )' + NAME + '=([^;]*)'));
            if (!match) { return null; }
            try {
                var data = JSON.parse(decodeURIComponent(match[1]));
                // Ak web neskôr začne používať nepovinné cookies, treba sa opýtať znova
                return data && data.v === VERSION && (!optional || data.o === true) ? data : null;
            } catch (e) { return null; }
        };

        var write = function (analytics) {
            var data = { v: VERSION, o: optional, necessary: true, analytics: optional && !!analytics, date: new Date().toISOString().slice(0, 10) };
            document.cookie = NAME + '=' + encodeURIComponent(JSON.stringify(data)) +
                '; max-age=' + MAX_AGE + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
            return data;
        };

        // Odstráni cookies analytiky (Google Analytics) po odvolaní súhlasu
        var clearAnalyticsCookies = function () {
            var host = location.hostname;
            var domains = ['', host, '.' + host, '.' + host.split('.').slice(-2).join('.')];
            document.cookie.split('; ').forEach(function (pair) {
                var name = pair.split('=')[0];
                if (/^(_ga|_gid|_gat)/.test(name)) {
                    domains.forEach(function (d) {
                        document.cookie = name + '=; max-age=0; path=/' + (d ? '; domain=' + d : '');
                    });
                }
            });
        };

        var enableAnalytics = function () {
            // Skripty pripravené v HTML ako <script type="text/plain" data-cookie-category="analytics">
            document.querySelectorAll('script[type="text/plain"][data-cookie-category="analytics"]').forEach(function (old) {
                var el = document.createElement('script');
                Array.prototype.forEach.call(old.attributes, function (a) {
                    if (a.name !== 'type' && a.name !== 'data-cookie-category') { el.setAttribute(a.name, a.value); }
                });
                el.text = old.text;
                old.parentNode.replaceChild(el, old);
            });
            if (!analyticsId) { return; }
            window['ga-disable-' + analyticsId] = false;
            if (analyticsLoaded) { return; }
            analyticsLoaded = true;
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { window.dataLayer.push(arguments); };
            window.gtag('js', new Date());
            window.gtag('config', analyticsId, { anonymize_ip: true });
            var tag = document.createElement('script');
            tag.async = true;
            tag.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(analyticsId);
            document.head.appendChild(tag);
        };

        var apply = function (consent) {
            if (optional && consent.analytics) {
                enableAnalytics();
            } else {
                if (analyticsId) { window['ga-disable-' + analyticsId] = true; }
                clearAnalyticsCookies();
            }
            document.dispatchEvent(new CustomEvent('cookieconsent', { detail: consent }));
        };

        var setSettingsOpen = function (open) {
            settings.hidden = !open;
            if (saveBtn) { saveBtn.hidden = !open; }
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.textContent = open ? 'Skryť nastavenia' : 'Nastavenia';
        };

        var show = function (withSettings) {
            var current = read();
            if (analyticsBox) { analyticsBox.checked = !!(current && current.analytics); }
            setSettingsOpen(!!withSettings);
            bar.hidden = false;
        };

        var hide = function () { bar.hidden = true; };

        var choose = function (analytics) {
            apply(write(analytics));
            hide();
        };

        if (acceptBtn) { acceptBtn.addEventListener('click', function () { choose(true); }); }
        bar.querySelector('[data-cookie-reject]').addEventListener('click', function () { choose(false); });
        if (saveBtn) { saveBtn.addEventListener('click', function () { choose(analyticsBox.checked); }); }
        toggle.addEventListener('click', function () { setSettingsOpen(settings.hidden); });

        document.querySelectorAll('[data-cookie-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                show(true);
                (analyticsBox || bar.querySelector('[data-cookie-reject]')).focus({ preventScroll: true });
            });
        });
        bar.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && read()) { hide(); }
        });

        var consent = read();
        if (consent) {
            apply(consent);
        } else {
            show(false);
        }
    }

    /* ---------- Mobilná lišta „Zavolať / Objednať sa“ ---------- */
    function initMobileBar() {
        var bar = document.querySelector('[data-mobile-bar]');
        if (!bar) { return; }
        var hero = document.getElementById('uvod');
        var contact = document.getElementById('kontakt');
        var update = function () {
            var past = hero ? hero.getBoundingClientRect().bottom < 0 : window.scrollY > 300;
            var atContact = false;
            if (contact) {
                var r = contact.getBoundingClientRect();
                atContact = r.top < window.innerHeight * 0.6 && r.bottom > 0;
            }
            bar.classList.toggle('is-visible', past && !atContact);
        };
        update();
        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update, { passive: true });
    }
})();
