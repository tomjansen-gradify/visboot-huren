const MONTH_NAMES = [
    'januari', 'februari', 'maart', 'april', 'mei', 'juni',
    'juli', 'augustus', 'september', 'oktober', 'november', 'december',
];
const WEEKDAYS_LABEL = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

const pad = (n) => String(n).padStart(2, '0');
const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const parseYmd = (s) => {
    if (!s) return null;
    const [y, m, d] = s.split('-').map(Number);
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
};
const fmtEur = (n) => '€ ' + n.toFixed(2).replace('.', ',');
const fmtDate = (s) => {
    const d = parseYmd(s);
    if (!d) return s;
    return `${d.getDate()} ${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`;
};

class BookingWizard {
    constructor(root) {
        this.root = root;
        let cfg = {};
        try {
            cfg = JSON.parse(root.dataset.config || '{}');
        } catch (e) {
            console.warn('Booking wizard: invalid config', e);
        }
        this.cfg = cfg;
        this.state = {
            step: 1,
            dates: new Set(),
            slotId: null,
            extras: new Map(),
            depositMethod: cfg.mollie_enabled === false ? 'cash' : 'online',
            calendarOffset: 0,
        };

        this.els = {
            steps:           root.querySelectorAll('[data-step]'),
            panels:          root.querySelectorAll('[data-panel]'),
            prev:            root.querySelector('[data-prev]'),
            next:            root.querySelector('[data-next]'),
            submit:          root.querySelector('[data-submit]'),
            calendar:        root.querySelector('[data-calendar]'),
            slotsSection:    root.querySelector('[data-slots-section]'),
            slots:           root.querySelector('[data-slots]'),
            extras:          root.querySelector('[data-extras]'),
            form:            root.querySelector('[data-booking-form]'),
            success:         root.querySelector('[data-booking-success]'),
            successMsg:      root.querySelector('[data-success-message]'),
            sumDates:        root.querySelector('[data-summary-dates]'),
            sumSlot:         root.querySelector('[data-summary-slot]'),
            sumExtrasWrap:   root.querySelector('[data-summary-extras-wrap]'),
            sumExtras:       root.querySelector('[data-summary-extras]'),
            sumTotal:        root.querySelector('[data-summary-total]'),
        };

        this.init();
    }

    init() {
        this.renderCalendar();
        this.renderExtras();
        this.bindNav();
        this.updateNavState();
        this.updateSummary();
    }

    // -- Calendar ----------------------------------------------------------
    renderCalendar() {
        const today = parseYmd(this.cfg.today) || new Date();
        today.setDate(1);
        const startMonth = new Date(today.getFullYear(), today.getMonth() + this.state.calendarOffset, 1);
        const nextMonth  = new Date(startMonth.getFullYear(), startMonth.getMonth() + 1, 1);

        const closedSet = new Set(this.cfg.closed_dates || []);
        const weekdays  = new Set((this.cfg.weekdays || []).map(Number));
        const seasonStart = parseYmd(this.cfg.season_start);
        const seasonEnd   = parseYmd(this.cfg.season_end);
        const realToday   = parseYmd(this.cfg.today) || new Date();

        const monthMarkup = (month) => {
            const year = month.getFullYear();
            const m    = month.getMonth();
            const monthLabel = `${MONTH_NAMES[m]} ${year}`;
            const firstDay = new Date(year, m, 1);
            const offset   = (firstDay.getDay() + 6) % 7; // Mon=0
            const daysInMonth = new Date(year, m + 1, 0).getDate();

            let days = '';
            for (let i = 0; i < offset; i++) {
                days += '<button type="button" class="booking-calendar__day is-blank" tabindex="-1"></button>';
            }
            for (let d = 1; d <= daysInMonth; d++) {
                const cur = new Date(year, m, d);
                const key = ymd(cur);
                const isPast    = cur < new Date(realToday.getFullYear(), realToday.getMonth(), realToday.getDate());
                const isBefore  = seasonStart && cur < seasonStart;
                const isAfter   = seasonEnd && cur > seasonEnd;
                const isClosed  = closedSet.has(key);
                const isOffDay  = !weekdays.has(cur.getDay());
                const disabled  = isPast || isBefore || isAfter || isClosed || isOffDay;
                const selected  = this.state.dates.has(key);

                let cls = 'booking-calendar__day';
                if (isPast || isBefore || isAfter) cls += ' is-past';
                else if (disabled) cls += ' is-closed';
                if (selected) cls += ' is-selected';

                days += `<button type="button" class="${cls}" data-day="${key}" ${disabled ? 'disabled aria-disabled="true"' : ''}>${d}</button>`;
            }

            return `
                <div class="booking-calendar__month">
                    <div class="booking-calendar__month-title">${monthLabel}</div>
                    <div class="booking-calendar__weekdays">
                        ${WEEKDAYS_LABEL.map((w) => `<span class="booking-calendar__weekday">${w}</span>`).join('')}
                    </div>
                    <div class="booking-calendar__days">${days}</div>
                </div>
            `;
        };

        const minOffset = -this._monthDiff(realToday, today);
        const prevDisabled = this.state.calendarOffset <= 0;

        this.els.calendar.innerHTML = `
            <div class="booking-calendar__nav">
                <button type="button" class="booking-calendar__nav-btn" data-cal-prev ${prevDisabled ? 'disabled' : ''}>
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <span></span>
                <button type="button" class="booking-calendar__nav-btn" data-cal-next>
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
            <div class="booking-calendar__grid">
                ${monthMarkup(startMonth)}
                ${monthMarkup(nextMonth)}
            </div>
        `;

        this.els.calendar.querySelectorAll('[data-day]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                const key = btn.dataset.day;
                const selected = [...this.state.dates].sort();

                if (selected.length === 0) {
                    this.state.dates.add(key);
                } else if (this.state.dates.has(key)) {
                    const first = selected[0];
                    const last  = selected[selected.length - 1];
                    if (key === first || key === last) {
                        this.state.dates.delete(key);
                    } else {
                        this.state.dates.clear();
                        this.state.dates.add(key);
                    }
                } else {
                    const first = selected[0];
                    const last  = selected[selected.length - 1];
                    if (key === this._shiftYmd(first, -1) || key === this._shiftYmd(last, 1)) {
                        this.state.dates.add(key);
                    } else {
                        this.state.dates.clear();
                        this.state.dates.add(key);
                    }
                }

                this.renderCalendar();
                this.renderSlots();
                this.updateNavState();
                this.updateSummary();
            });
        });
        const prev = this.els.calendar.querySelector('[data-cal-prev]');
        const next = this.els.calendar.querySelector('[data-cal-next]');
        prev && prev.addEventListener('click', () => {
            if (this.state.calendarOffset > 0) {
                this.state.calendarOffset--;
                this.renderCalendar();
            }
        });
        next && next.addEventListener('click', () => {
            this.state.calendarOffset++;
            this.renderCalendar();
        });

        this.renderSlots();
    }

    _monthDiff(a, b) {
        return (b.getFullYear() - a.getFullYear()) * 12 + (b.getMonth() - a.getMonth());
    }

    _shiftYmd(s, n) {
        const d = parseYmd(s);
        if (!d) return s;
        d.setDate(d.getDate() + n);
        return ymd(d);
    }

    renderSlots() {
        const slots = this.cfg.slots || [];
        const useTiers = (this.cfg.tiers || []).length > 0;

        // Tiered pricing: no slot UI, auto-select "full_day"
        if (useTiers || slots.length <= 1) {
            this.els.slotsSection.hidden = true;
            this.els.slots.hidden = true;
            this.els.slots.innerHTML = '';
            if (this.state.dates.size > 0) {
                this.state.slotId = useTiers ? 'tiered' : (slots[0] ? slots[0].id : null);
            } else {
                this.state.slotId = null;
            }
            return;
        }

        if (this.state.dates.size === 0) {
            this.els.slotsSection.hidden = true;
            this.els.slots.hidden = true;
            this.els.slots.innerHTML = '';
            return;
        }
        this.els.slotsSection.hidden = false;
        this.els.slots.hidden = false;

        this.els.slots.innerHTML = slots.map((s) => {
            const selected = this.state.slotId === s.id;
            const timeLabel = (s.start && s.end) ? `${s.start} – ${s.end}` : '';
            return `
                <button type="button" class="booking-slots__option ${selected ? 'is-selected' : ''}" data-slot="${s.id}">
                    <span class="booking-slots__label">${s.label}</span>
                    ${timeLabel ? `<span class="booking-slots__time">${timeLabel}</span>` : ''}
                    <span class="booking-slots__price">${fmtEur(Number(s.price || 0))}</span>
                </button>
            `;
        }).join('');

        this.els.slots.querySelectorAll('[data-slot]').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.state.slotId = btn.dataset.slot;
                this.renderSlots();
                this.updateNavState();
                this.updateSummary();
            });
        });
    }

    pickTierForDays(days) {
        const tiers = (this.cfg.tiers || []).slice().sort((a, b) => a.min_days - b.min_days);
        let pick = null;
        for (const t of tiers) {
            if (days >= t.min_days) pick = t;
        }
        return pick || tiers[0] || null;
    }

    // -- Extras ------------------------------------------------------------
    renderExtras() {
        const extras = this.cfg.extras || [];
        if (extras.length === 0) {
            this.els.extras.innerHTML = '<p class="booking-extras__empty">Geen extra opties beschikbaar voor deze boot.</p>';
            return;
        }
        this.els.extras.innerHTML = extras.map((e, i) => {
            const key = String(i);
            const qty = this.state.extras.get(key) || 0;
            const selected = qty > 0;
            const isQuantity = e.type === 'quantity';
            const priceLabel = isQuantity
                ? `+ ${fmtEur(Number(e.price || 0))} <small>p/s</small>`
                : `+ ${fmtEur(Number(e.price || 0))}`;
            const control = isQuantity
                ? `<span class="booking-extras__qty">
                       <button type="button" class="booking-extras__qty-btn" data-qty-dec="${key}" aria-label="Minder">−</button>
                       <input type="number" class="booking-extras__qty-input" data-qty-input="${key}" min="0" step="1" value="${qty}" inputmode="numeric">
                       <button type="button" class="booking-extras__qty-btn" data-qty-inc="${key}" aria-label="Meer">+</button>
                   </span>`
                : `<input type="checkbox" class="booking-extras__checkbox" data-extra="${key}" ${selected ? 'checked' : ''}>`;
            const tag = isQuantity ? 'div' : 'label';
            const itemCls = `booking-extras__item${selected ? ' is-selected' : ''}${isQuantity ? ' booking-extras__item--qty' : ''}`;
            return `
                <${tag} class="${itemCls}" data-extra-wrap="${key}">
                    ${!isQuantity ? control : ''}
                    <span class="booking-extras__body">
                        <span class="booking-extras__label">${e.label}</span>
                        ${e.description ? `<span class="booking-extras__desc">${e.description}</span>` : ''}
                    </span>
                    <span class="booking-extras__price">${priceLabel}</span>
                    ${isQuantity ? control : ''}
                </${tag}>
            `;
        }).join('');

        const setQty = (key, qty) => {
            const next = Math.max(0, Math.floor(qty));
            if (next === 0) this.state.extras.delete(key);
            else this.state.extras.set(key, next);
            const wrap = this.els.extras.querySelector(`[data-extra-wrap="${key}"]`);
            if (wrap) wrap.classList.toggle('is-selected', next > 0);
            const input = this.els.extras.querySelector(`[data-qty-input="${key}"]`);
            if (input) input.value = String(next);
            this.updateSummary();
        };

        this.els.extras.querySelectorAll('[data-extra]').forEach((box) => {
            box.addEventListener('change', () => {
                const key = box.dataset.extra;
                if (box.checked) this.state.extras.set(key, 1);
                else this.state.extras.delete(key);
                const wrap = box.closest('[data-extra-wrap]');
                if (wrap) wrap.classList.toggle('is-selected', box.checked);
                this.updateSummary();
            });
        });

        this.els.extras.querySelectorAll('[data-qty-inc]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.qtyInc;
                setQty(key, (this.state.extras.get(key) || 0) + 1);
            });
        });
        this.els.extras.querySelectorAll('[data-qty-dec]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.qtyDec;
                setQty(key, (this.state.extras.get(key) || 0) - 1);
            });
        });
        this.els.extras.querySelectorAll('[data-qty-input]').forEach((input) => {
            input.addEventListener('change', () => {
                const key = input.dataset.qtyInput;
                setQty(key, parseInt(input.value, 10) || 0);
            });
        });
    }

    // -- Navigation --------------------------------------------------------
    bindNav() {
        this.els.next.addEventListener('click', () => this.goToStep(this.state.step + 1));
        this.els.prev.addEventListener('click', () => this.goToStep(this.state.step - 1));
        this.els.submit.addEventListener('click', () => this.submit());

        if (this.els.form) {
            this.els.form.querySelectorAll('[name=deposit_method]').forEach((radio) => {
                radio.addEventListener('change', () => {
                    this.state.depositMethod = radio.value;
                    this.updateSummary();
                    this.updateSubmitLabel();
                });
            });
        }
    }

    updateSubmitLabel() {
        if (!this.els.submit) return;
        const mollieOn = this.cfg.mollie_enabled !== false;
        if (this.state.depositMethod === 'cash' || !mollieOn) {
            this.els.submit.innerHTML = 'Reservering versturen <i class="fa-solid fa-paper-plane"></i>';
        } else {
            this.els.submit.innerHTML = 'Naar betaling <i class="fa-solid fa-credit-card"></i>';
        }
    }

    canAdvance(step) {
        if (step === 1) {
            const useTiers = (this.cfg.tiers || []).length > 0;
            if (useTiers) return this.state.dates.size > 0;
            return this.state.dates.size > 0 && this.state.slotId !== null;
        }
        return true;
    }

    goToStep(n) {
        if (n < 1 || n > 3) return;
        if (n > this.state.step && !this.canAdvance(this.state.step)) return;
        this.state.step = n;

        this.els.steps.forEach((el) => {
            const s = Number(el.dataset.step);
            el.classList.toggle('is-active', s === n);
            el.classList.toggle('is-complete', s < n);
        });
        this.els.panels.forEach((p) => {
            p.hidden = Number(p.dataset.panel) !== n;
        });

        this.updateNavState();
        if (n === 3) this.updateSubmitLabel();
        this.root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    updateNavState() {
        this.els.prev.hidden = this.state.step === 1;
        this.els.next.hidden = this.state.step === 3;
        this.els.submit.hidden = this.state.step !== 3;
        this.els.next.disabled = !this.canAdvance(this.state.step);
    }

    // -- Summary -----------------------------------------------------------
    calcTotal() {
        const dateCount = this.state.dates.size;
        const useTiers = (this.cfg.tiers || []).length > 0;
        let pricePerDay = 0;
        let tierLabel = '';
        if (useTiers && dateCount > 0) {
            const tier = this.pickTierForDays(dateCount);
            if (tier) {
                pricePerDay = Number(tier.price || 0);
                tierLabel = tier.label || '';
            }
        } else {
            const slot = (this.cfg.slots || []).find((s) => s.id === this.state.slotId);
            pricePerDay = slot ? Number(slot.price || 0) : 0;
        }
        const dateTotal   = pricePerDay * dateCount;
        const extrasArr   = [];
        let extrasTotal   = 0;
        this.state.extras.forEach((qty, key) => {
            const ex = (this.cfg.extras || [])[Number(key)];
            if (!ex || qty <= 0) return;
            const lineTotal = Number(ex.price || 0) * qty;
            extrasArr.push({ ...ex, qty, lineTotal });
            extrasTotal += lineTotal;
        });
        const depositAmt  = Number(this.cfg.deposit_amount || 0);
        const depositTotal = (this.state.depositMethod === 'online' && depositAmt > 0) ? depositAmt : 0;
        return {
            dateTotal,
            extrasTotal,
            depositTotal,
            total: dateTotal + extrasTotal + depositTotal,
            extrasArr,
            pricePerDay,
            tierLabel,
        };
    }

    updateSummary() {
        const dates = Array.from(this.state.dates).sort();
        if (dates.length === 0) {
            this.els.sumDates.textContent = '—';
        } else if (dates.length === 1) {
            this.els.sumDates.textContent = fmtDate(dates[0]);
        } else {
            this.els.sumDates.textContent = `${dates.length} dagen — ${fmtDate(dates[0])} t/m ${fmtDate(dates[dates.length - 1])}`;
        }

        const { total, dateTotal, extrasArr, pricePerDay, tierLabel, depositTotal } = this.calcTotal();
        const useTiers = (this.cfg.tiers || []).length > 0;
        if (useTiers && dates.length > 0) {
            const tierTxt = tierLabel ? tierLabel : `${dates.length} ${dates.length === 1 ? 'dag' : 'dagen'} × ${fmtEur(pricePerDay)}`;
            this.els.sumSlot.textContent = `Hele dag (${tierTxt})`;
        } else {
            const slot = (this.cfg.slots || []).find((s) => s.id === this.state.slotId);
            this.els.sumSlot.textContent = slot ? slot.label : '—';
        }

        const lineItems = [];
        if (extrasArr.length > 0) {
            extrasArr.forEach((e) => {
                const isQty = e.type === 'quantity' || e.qty > 1;
                const label = isQty ? `${e.label} (×${e.qty})` : e.label;
                lineItems.push(`<li><span>${label}</span><span>+${fmtEur(e.lineTotal)}</span></li>`);
            });
        }
        if (depositTotal > 0) {
            lineItems.push(`<li><span>Borg (online)</span><span>+${fmtEur(depositTotal)}</span></li>`);
        }
        if (lineItems.length > 0) {
            this.els.sumExtrasWrap.hidden = false;
            this.els.sumExtras.innerHTML = lineItems.join('');
        } else {
            this.els.sumExtrasWrap.hidden = true;
            this.els.sumExtras.innerHTML = '';
        }

        this.els.sumTotal.textContent = fmtEur(total);
    }

    // -- Submit ------------------------------------------------------------
    async submit() {
        const formData = new FormData(this.els.form);
        const first = (formData.get('first_name') || '').toString().trim();
        const last  = (formData.get('last_name')  || '').toString().trim();
        const email = (formData.get('email') || '').toString().trim();
        const phone = (formData.get('phone') || '').toString().trim();
        const address = (formData.get('address') || '').toString().trim();
        const postal  = (formData.get('postal_code') || '').toString().trim();
        const city    = (formData.get('city') || '').toString().trim();
        const acceptTerms = formData.get('accept_terms') === 'on';
        const depositMethod = (formData.get('deposit_method') || 'cash').toString();

        const requiredCheck = [
            ['first_name', first], ['last_name', last], ['email', email],
            ['phone', phone], ['address', address], ['postal_code', postal], ['city', city],
        ];
        for (const [name, value] of requiredCheck) {
            if (!value) { this.els.form.querySelector(`[name=${name}]`).focus(); return; }
        }
        if (!email.includes('@')) { this.els.form.querySelector('[name=email]').focus(); return; }
        if (!acceptTerms) {
            alert('Je moet akkoord gaan met de algemene voorwaarden om door te gaan.');
            this.els.form.querySelector('[name=accept_terms]').focus();
            return;
        }

        this.els.submit.disabled = true;
        this.els.submit.innerHTML = 'Bezig…';

        const SwalAvailable = typeof window.Swal !== 'undefined';
        if (SwalAvailable) {
            Swal.fire({
                title: 'Je reservering wordt verwerkt…',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading(),
            });
        }

        const { total } = this.calcTotal();
        const payload = {
            boat_id: this.cfg.boat_id,
            dates:   Array.from(this.state.dates).sort(),
            slot_id: this.state.slotId,
            extras:  Array.from(this.state.extras.entries()).map(([i, qty]) => {
                const ex = (this.cfg.extras || [])[Number(i)];
                if (!ex || qty <= 0) return null;
                const lbl = (ex.type === 'quantity' || qty > 1) ? `${ex.label} (×${qty})` : ex.label;
                return lbl;
            }).filter(Boolean),
            name:    (first + ' ' + last).trim(),
            first_name: first,
            last_name:  last,
            email,
            phone,
            address,
            postal_code: postal,
            city,
            accept_terms: true,
            deposit_method: depositMethod,
            message: (formData.get('message') || '').toString().trim(),
            total,
        };

        try {
            const res = await fetch(this.cfg.rest_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.cfg.rest_nonce,
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Er ging iets mis.');
            }
            if (data.mode === 'redirect' && data.checkout_url) {
                if (SwalAvailable) {
                    Swal.fire({
                        title: 'Doorsturen naar Mollie…',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading(),
                    });
                }
                window.location.href = data.checkout_url;
                return;
            }
            if (SwalAvailable) {
                Swal.fire({
                    icon: 'success',
                    title: 'Reservering ontvangen!',
                    text: data.message || 'We nemen zo snel mogelijk contact met je op.',
                    confirmButtonColor: '#71BF44',
                });
            }
            this.els.form.hidden = true;
            this.els.success.hidden = false;
            if (data.message) this.els.successMsg.textContent = data.message;
            this.root.querySelector('[data-nav]').hidden = true;
        } catch (err) {
            if (SwalAvailable) {
                Swal.fire({
                    icon: 'error',
                    title: 'Er ging iets mis',
                    text: err.message || 'Probeer het later opnieuw.',
                    confirmButtonColor: '#71BF44',
                });
            } else {
                alert('Er ging iets mis: ' + (err.message || 'Onbekende fout'));
            }
            this.els.submit.disabled = false;
            this.updateSubmitLabel();
        }
    }
}

export function initBookingWizards() {
    document.querySelectorAll('[data-booking-wizard]').forEach((root) => {
        if (!root.__bw) {
            root.__bw = new BookingWizard(root);
        }
    });
}
