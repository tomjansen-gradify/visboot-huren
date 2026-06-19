import { initBookingWizards } from './booking-wizard.js';

document.addEventListener('DOMContentLoaded', () => {
    initBookingWizards();

    // Dynamische header-hoogte → zet --header-height op de werkelijke gerenderde hoogte
    const siteHeader = document.querySelector('.site-header');
    if (siteHeader) {
        const applyHeaderHeight = () => {
            const h = siteHeader.getBoundingClientRect().height;
            if (h > 0) {
                document.documentElement.style.setProperty('--header-height', h + 'px');
            }
        };
        applyHeaderHeight();
        window.addEventListener('resize', applyHeaderHeight);
        window.addEventListener('orientationchange', applyHeaderHeight);
        if ('ResizeObserver' in window) {
            new ResizeObserver(applyHeaderHeight).observe(siteHeader);
        }
        // Webfonts laden later → herbereken na load
        window.addEventListener('load', () => setTimeout(applyHeaderHeight, 50));
    }

    const hamburger = document.querySelector('.site-header__hamburger');
    const mobileMenu = document.getElementById('site-mobile-menu');

    if (hamburger && mobileMenu) {
        hamburger.addEventListener('click', () => {
            const expanded = hamburger.getAttribute('aria-expanded') === 'true';
            hamburger.setAttribute('aria-expanded', String(!expanded));
            if (expanded) {
                mobileMenu.setAttribute('hidden', '');
            } else {
                mobileMenu.removeAttribute('hidden');
            }
        });
    }

    // Contact form (with SweetAlert)
    const SwalAvailable = typeof window.Swal !== 'undefined';
    document.querySelectorAll('[data-contact-form]').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button[type=submit]');
            const successMsg = form.dataset.success || 'Bedankt voor je bericht!';

            const fd = new FormData(form);
            const payload = {
                name:           (fd.get('name')           || '').toString().trim(),
                email:          (fd.get('email')          || '').toString().trim(),
                phone:          (fd.get('phone')          || '').toString().trim(),
                message:        (fd.get('message')        || '').toString().trim(),
                preference_date:(fd.get('preference_date')|| '').toString().trim(),
                preference_time:(fd.get('preference_time')|| '').toString().trim(),
                email_to:       (fd.get('email_to')       || '').toString().trim(),
            };

            if (SwalAvailable) {
                Swal.fire({
                    title: 'Even geduld…',
                    text: 'We versturen je bericht.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading(),
                });
            } else if (btn) {
                btn.disabled = true;
                btn.dataset.orig = btn.innerHTML;
                btn.innerHTML = 'Versturen…';
            }

            try {
                const res = await fetch(form.dataset.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce':   form.dataset.nonce,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) throw new Error(data.message || 'Er ging iets mis.');

                if (SwalAvailable) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Verzonden!',
                        text: data.message || successMsg,
                        confirmButtonColor: '#71BF44',
                    });
                }
                form.reset();
                const container = form.closest('.contact-form-block__container') || form.closest('.cta-block__form-wrap') || form.parentElement;
                const success = container ? container.querySelector('[data-contact-success]') : null;
                if (success) {
                    form.hidden = true;
                    success.hidden = false;
                    const msg = success.querySelector('[data-contact-success-msg]');
                    if (msg) msg.textContent = data.message || successMsg;
                }
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
                if (btn) {
                    btn.disabled = false;
                    if (btn.dataset.orig) btn.innerHTML = btn.dataset.orig;
                }
            }
        });
    });

    // FAQ accordion
    document.querySelectorAll('.visboot-faq__question').forEach((btn) => {
        btn.addEventListener('click', () => {
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            const item = btn.closest('.visboot-faq__item');
            const panelId = btn.getAttribute('aria-controls');
            const panel = panelId ? document.getElementById(panelId) : null;

            btn.setAttribute('aria-expanded', String(!expanded));
            if (item) item.classList.toggle('is-open', !expanded);
            if (panel) {
                if (expanded) panel.setAttribute('hidden', '');
                else panel.removeAttribute('hidden');
            }
        });
    });

    // Legal TOC scrollspy
    const tocLinks = document.querySelectorAll('.legal-toc [data-toc-link]');
    if (tocLinks.length) {
        const targets = Array.from(tocLinks)
            .map((link) => document.getElementById(link.getAttribute('href').slice(1)))
            .filter(Boolean);

        const setActive = (id) => {
            tocLinks.forEach((link) => {
                const li = link.closest('.legal-toc__item');
                if (!li) return;
                li.classList.toggle('is-active', link.getAttribute('href') === '#' + id);
            });
        };

        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((e) => e.isIntersecting)
                    .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
                if (visible) setActive(visible.target.id);
            },
            { rootMargin: '-20% 0px -70% 0px', threshold: 0 }
        );

        targets.forEach((t) => observer.observe(t));
    }
});
