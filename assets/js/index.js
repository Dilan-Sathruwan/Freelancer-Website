/**
 * FreelanceHub - Main Frontend Interactions Script
 * Optimized for performance, zero console errors, and smooth micro-interactions.
 */

(function () {
    'use strict';

    // 1. Safe AOS (Animate On Scroll) Initialization
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50,
            disable: 'phone' // Keeps mobile scroll buttery smooth
        });
    }

    // 2. Mobile Navigation & Theme toggles are natively handled by index_header.php using pure Tailwind CSS utility classes

    // 3. Optimized Navbar Scroll Effect (Using requestAnimationFrame for 60fps)
    const nav = document.querySelector('nav');
    const scrollTopBtn = document.getElementById('scrollTop');
    let ticking = false;

    function onScrollUpdate() {
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

        // Navbar shadow adjustment
        if (nav) {
            if (currentScroll > 20) {
                nav.classList.add('shadow-md');
                nav.classList.remove('shadow-sm');
            } else {
                nav.classList.remove('shadow-md');
                nav.classList.add('shadow-sm');
            }
        }

        // Scroll to Top visibility
        if (scrollTopBtn) {
            if (currentScroll > 350) {
                scrollTopBtn.classList.remove('opacity-0', 'invisible');
                scrollTopBtn.classList.add('opacity-100', 'visible');
            } else {
                scrollTopBtn.classList.add('opacity-0', 'invisible');
                scrollTopBtn.classList.remove('opacity-100', 'visible');
            }
        }

        ticking = false;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(onScrollUpdate);
            ticking = true;
        }
    }, { passive: true });

    // Smooth Scroll to Top Click Handler
    if (scrollTopBtn) {
        scrollTopBtn.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // 4. Smooth Anchor Scrolling with Fixed Header Offset
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (!href || href === '#' || href.length < 2) return;

            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                const offset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - offset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // 5. High-Performance Animated Stats Counter
    const animateCounter = (element, start, end, duration) => {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            // Ease-out expo curve for satisfying deceleration
            const easeOutProgress = 1 - Math.pow(1 - progress, 3);
            const value = Math.floor(easeOutProgress * (end - start) + start);
            element.textContent = value.toLocaleString() + '+';
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    };

    if ('IntersectionObserver' in window) {
        const statsObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const numberElements = entry.target.querySelectorAll('.text-3xl');
                    numberElements.forEach(el => {
                        const raw = el.textContent;
                        const match = raw.match(/\d[\d,]*/);
                        if (match) {
                            const targetNum = parseInt(match[0].replace(/,/g, ''), 10);
                            if (!isNaN(targetNum) && targetNum > 0) {
                                animateCounter(el, 0, targetNum, 1800);
                            }
                        }
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        document.querySelectorAll('.grid.grid-cols-2.md\\:grid-cols-4').forEach(section => {
            statsObserver.observe(section);
        });
    }

    // 7. Universal Image Fallback Engine (Runs without physical images)
    window.addEventListener('error', function (e) {
        if (e.target && e.target.tagName === 'IMG') {
            const img = e.target;
            if (img.dataset.fallbackApplied) return;
            img.dataset.fallbackApplied = 'true';

            const isAvatar = img.classList.contains('rounded-full') || 
                             (img.alt && (img.alt.toLowerCase().includes('seller') || 
                                          img.alt.toLowerCase().includes('avatar') || 
                                          img.alt.toLowerCase().includes('user'))) ||
                             (img.classList.contains('w-8') || img.classList.contains('w-12') || img.classList.contains('w-20') || img.classList.contains('w-24') || img.classList.contains('w-28'));

            const prefix = window.location.pathname.includes('/public/') ||
                           window.location.pathname.includes('/client/') ||
                           window.location.pathname.includes('/freelancer/') ||
                           window.location.pathname.includes('/admin/') ||
                           window.location.pathname.includes('/auth/') ? '../' : './';

            img.src = isAvatar ? prefix + 'assets/img/placeholder-avatar.svg' : prefix + 'assets/img/placeholder-gig.svg';
        }
    }, true);
})();