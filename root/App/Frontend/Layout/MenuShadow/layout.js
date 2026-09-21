document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.nav--mobile .mobile-item > a').forEach(function (link) {
        var li = link.closest('li');
        var sub = li && li.querySelector('.sub');
        if (!sub) return;

        link.addEventListener('click', function (e) {
            e.preventDefault();
            var isOpen = li.classList.contains('open');
            var toggle = li.querySelector('.submenu-toggle');

            if (isOpen) {
                li.classList.remove('open');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
                sub.setAttribute('aria-hidden', 'true');
            } else {
                li.classList.add('open');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
                sub.setAttribute('aria-hidden', 'false');
            }
        });
    });

    document.querySelectorAll('.dropdown').forEach(function (dropdown) {
        dropdown.addEventListener('mouseenter', function () {
            dropdown.classList.add('open');
        });

        dropdown.addEventListener('focus', function () {
            dropdown.classList.add('open');
        });

        dropdown.addEventListener('mouseleave', function () {
            dropdown.classList.remove('open');
        });

        dropdown.addEventListener('blur', function () {
            dropdown.classList.remove('open');
        });

        dropdown.addEventListener('mouseenter', function () {
            const menu = dropdown.querySelector('.dropdown-menu');
            if (!menu) {
                return;
            }

            menu.style.left = '';
            menu.style.right = '';

            const rect = menu.getBoundingClientRect();
            if (rect.right > window.innerWidth) {
                menu.style.left = 'auto';
                menu.style.right = '-4px';
            }
        });
    });

    if (typeof hljs !== 'undefined' && typeof hljs.highlightAll === 'function') {
        hljs.highlightAll();
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.fade-in-up').forEach(function (el) {
        observer.observe(el);
    });

    var openBtn = document.getElementById('mobile-open');
    var closeBtn = document.getElementById('mobile-close');
    var mobileNav = document.getElementById('nav-mobile');
    var overlay = document.getElementById('nav-overlay');
    if (!openBtn || !mobileNav || !overlay || !closeBtn) {
        return;
    }

    var FOCUSABLE = 'a,button,input,textarea,select,[tabindex]:not([tabindex="-1"])';
    var isMobile = function () { return window.matchMedia('(max-width:768px)').matches; };

    function openNav() {
        if (!isMobile()) {
            return;
        }

        mobileNav.classList.add('is-open');
        overlay.classList.add('is-visible');
        document.documentElement.classList.add('nav-open');
        mobileNav.setAttribute('aria-hidden', 'false');
        openBtn.setAttribute('aria-expanded', 'true');
        var f = mobileNav.querySelector(FOCUSABLE);
        if (f) {
            f.focus();
        }

        document.addEventListener('keydown', onKey);
    }

    function closeNav() {
        mobileNav.classList.remove('is-open');
        overlay.classList.remove('is-visible');
        document.documentElement.classList.remove('nav-open');
        mobileNav.setAttribute('aria-hidden', 'true');
        openBtn.setAttribute('aria-expanded', 'false');
        openBtn.focus();
        document.removeEventListener('keydown', onKey);

        mobileNav.querySelectorAll('li.open').forEach(function (li) { li.classList.remove('open'); });
        mobileNav.querySelectorAll('.sub[aria-hidden="false"]').forEach(function (sm) {
            sm.setAttribute('aria-hidden', 'true');
        });
        mobileNav.querySelectorAll('[aria-expanded="true"]').forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    function onKey(e) {
        if (e.key === 'Escape') {
            closeNav();
        }

        if (e.key !== 'Tab' || !mobileNav.classList.contains('is-open')) {
            return;
        }

        var focusables = Array.from(mobileNav.querySelectorAll(FOCUSABLE));
        if (!focusables.length) {
            return;
        }

        var first = focusables[0], last = focusables[focusables.length - 1];
        if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        } else if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        }
    }

    openBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        openNav();
    });

    closeBtn.addEventListener('click', closeNav);
    overlay.addEventListener('click', closeNav);

    mobileNav.querySelectorAll('.submenu-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var li = btn.closest('li');
            var sub = li.querySelector('.sub');
            var isOpen = li.classList.contains('open');

            if (isOpen) {
                li.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
                if (sub) {
                    sub.setAttribute('aria-hidden', 'true');
                }
            } else {
                li.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
                if (sub) {
                    sub.setAttribute('aria-hidden', 'false');
                }
            }
        });
    });

    var startX = 0, currentX = 0, touching = false;
    mobileNav.addEventListener('touchstart', function (e) {
        if (!mobileNav.classList.contains('is-open') || e.touches.length !== 1) {
            return;
        }

        startX = e.touches[0].clientX;
        touching = true;
        mobileNav.style.transition = 'none';
    }, { passive: true });

    mobileNav.addEventListener('touchmove', function (e) {
        if (!touching) {
            return;
        }

        currentX = e.touches[0].clientX;
        var deltaX = Math.min(0, currentX - startX);
        mobileNav.style.transform = 'translateX(' + Math.max(deltaX, -mobileNav.offsetWidth) + 'px)';
        overlay.style.opacity = String(Math.max(0, 1 + deltaX / mobileNav.offsetWidth));
    }, { passive: true });

    mobileNav.addEventListener('touchend', function () {
        if (!touching) {
            return;
        }

        touching = false;
        mobileNav.style.transition = '';
        if (currentX - startX < -50) {
            closeNav();
        } else {
            mobileNav.style.transform = '';
            overlay.style.opacity = '';
        }

        startX = currentX = 0;
    });

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            closeNav();
        }
    });

    var header = document.querySelector('div.wrapper>header');
    if (!header) {
        return;
    }

    var THRESHOLD = 10;
    var ticking = false;

    function updateHeader(scrollY) {
        if (isMobile()) {
            header.classList.remove('header--small');
            return;
        }

        if (scrollY > THRESHOLD) {
            header.classList.add('header--small');
        } else {
            header.classList.remove('header--small');
        }
    }

    function onScroll() {
        if (ticking) {
            return;
        }

        ticking = true;
        var y = window.scrollY || window.pageYOffset;
        window.requestAnimationFrame(function () {
            updateHeader(y);
            ticking = false;
        });
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', function () { updateHeader(window.scrollY || window.pageYOffset); });
    updateHeader(window.scrollY || window.pageYOffset);
});
/**
 * Slider - a scroll-snap rail with generated controls.
 *
 * The markup only ships a track and its slides; the dot rail and the
 * play/pause toggle are built here so the view stays minimal. Advancing is
 * driven by the CSS progress animation on the active dot rather than a timer,
 * which keeps the indicator and the slide change in sync when the slideshow is
 * paused and resumed.
 */
document.addEventListener('DOMContentLoaded', function () {
    var LABELS = {
        en: { slide: 'Go to slide ', pause: 'Pause slideshow', play: 'Play slideshow' },
        ko: { slide: '\uC2AC\uB77C\uC774\uB4DC \uC774\uB3D9 ', pause: '\uC2AC\uB77C\uC774\uB4DC \uC790\uB3D9 \uC7AC\uC0DD \uBA48\uCDA4', play: '\uC2AC\uB77C\uC774\uB4DC \uC790\uB3D9 \uC7AC\uC0DD' },
        ja: { slide: '\u30B9\u30E9\u30A4\u30C9\u3078\u79FB\u52D5 ', pause: '\u30B9\u30E9\u30A4\u30C9\u30B7\u30E7\u30FC\u3092\u4E00\u6642\u505C\u6B62', play: '\u30B9\u30E9\u30A4\u30C9\u30B7\u30E7\u30FC\u3092\u518D\u751F' }
    };

    var lang = (document.documentElement.lang || 'en').slice(0, 2);
    var text = LABELS[lang] || LABELS.en;
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    Array.prototype.forEach.call(document.querySelectorAll('[data-slider]'), function (root) {
        var track = root.querySelector('[data-slider-track]');
        if (!track) {
            return;
        }

        var slides = Array.prototype.slice.call(track.children);
        if (slides.length < 2) {
            return;
        }

        var current = 0;
        var lockedUntil = 0;
        var userPaused = false;
        var hovered = false;
        var offscreen = false;
        var ui = document.createElement('div');
        ui.className = 'slider__ui';

        var dots = slides.map(function (slide, index) {
            var dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'slider__dot';
            dot.setAttribute('aria-label', text.slide + (index + 1));
            dot.addEventListener('click', function () {
                goTo(index);
            });
            dot.addEventListener('animationend', function (event) {
                if (event.animationName === 'slideProgress' && index === current) {
                    goTo((current + 1) % slides.length);
                }
            });
            ui.appendChild(dot);
            return dot;
        });

        var toggle = null;
        if (reduceMotion) {
            root.classList.add('is-static');
        } else {
            toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'slider__toggle';
            toggle.setAttribute('aria-pressed', 'false');
            toggle.setAttribute('aria-label', text.pause);
            toggle.addEventListener('click', function () {
                userPaused = !userPaused;
                toggle.setAttribute('aria-pressed', userPaused ? 'true' : 'false');
                toggle.setAttribute('aria-label', userPaused ? text.play : text.pause);
                syncPaused();
            });
            ui.appendChild(toggle);
        }

        root.appendChild(ui);

        function syncPaused() {
            var paused = userPaused || hovered || offscreen || document.hidden;
            root.classList.toggle('is-paused', paused);
        }

        function setActive(index) {
            if (index === current && dots[index].classList.contains('is-active')) {
                return;
            }

            current = index;
            dots.forEach(function (dot, i) {
                dot.classList.remove('is-active');
                dot.removeAttribute('aria-current');

                if (i !== index) {
                    return;
                }

                /* Reflow so the progress animation restarts from zero. */
                void dot.offsetWidth;
                dot.classList.add('is-active');
                dot.setAttribute('aria-current', 'true');
            });
        }

        function goTo(index) {
            /* Hold off the scroll listener so the smooth scroll does not
               re-activate the slide it is travelling across. */
            lockedUntil = Date.now() + 700;
            track.scrollTo({
                left: slides[index].offsetLeft - slides[0].offsetLeft,
                behavior: reduceMotion ? 'auto' : 'smooth'
            });
            setActive(index);
        }

        function nearestSlide() {
            var centre = track.scrollLeft + track.clientWidth / 2;
            var best = 0;
            var bestDistance = Infinity;

            slides.forEach(function (slide, index) {
                var distance = Math.abs(slide.offsetLeft + slide.offsetWidth / 2 - centre);
                if (distance < bestDistance) {
                    bestDistance = distance;
                    best = index;
                }
            });

            return best;
        }

        var scrolling = false;
        track.addEventListener('scroll', function () {
            if (scrolling) {
                return;
            }

            scrolling = true;
            window.requestAnimationFrame(function () {
                if (Date.now() >= lockedUntil) {
                    setActive(nearestSlide());
                }

                scrolling = false;
            });
        }, { passive: true });

        track.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                goTo((current + 1) % slides.length);
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                goTo((current - 1 + slides.length) % slides.length);
            }
        });

        ['mouseenter', 'focusin'].forEach(function (name) {
            root.addEventListener(name, function () {
                hovered = true;
                syncPaused();
            });
        });

        ['mouseleave', 'focusout'].forEach(function (name) {
            root.addEventListener(name, function () {
                if (name === 'focusout' && root.contains(document.activeElement)) {
                    return;
                }

                hovered = false;
                syncPaused();
            });
        });

        document.addEventListener('visibilitychange', syncPaused);

        if (typeof IntersectionObserver === 'function') {
            new IntersectionObserver(function (entries) {
                offscreen = !entries[0].isIntersecting;
                syncPaused();
            }, { threshold: 0.35 }).observe(root);
        }

        setActive(0);
        syncPaused();
    });
});
