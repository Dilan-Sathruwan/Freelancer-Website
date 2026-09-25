/**
 * FreelanceHub - Simple, High-Performance Testimonials Slider
 * Clean native scroll-snap with smooth prev/next navigation,
 * dynamic indicator dots, and optional subtle autoplay.
 */

document.addEventListener('DOMContentLoaded', function () {
    const slider = document.getElementById('testimonials-slider');
    if (!slider) return;

    const cards = slider.querySelectorAll('.testimonial-card-slide');
    if (cards.length === 0) return;

    const prevBtns = [
        document.getElementById('slider-prev-btn'),
        document.getElementById('slider-prev-btn-mobile')
    ].filter(Boolean);

    const nextBtns = [
        document.getElementById('slider-next-btn'),
        document.getElementById('slider-next-btn-mobile')
    ].filter(Boolean);

    const dotsContainer = document.getElementById('slider-dots');

    // Calculate step: 1 card width + 24px gap
    function getStep() {
        const firstCard = cards[0];
        return firstCard ? (firstCard.offsetWidth + 24) : 400;
    }

    function scrollNext() {
        const maxScroll = slider.scrollWidth - slider.clientWidth - 10;
        if (slider.scrollLeft >= maxScroll) {
            slider.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            slider.scrollBy({ left: getStep(), behavior: 'smooth' });
        }
    }

    function scrollPrev() {
        if (slider.scrollLeft <= 10) {
            slider.scrollTo({ left: slider.scrollWidth, behavior: 'smooth' });
        } else {
            slider.scrollBy({ left: -getStep(), behavior: 'smooth' });
        }
    }

    // Bind navigation buttons
    nextBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            scrollNext();
            resetAutoplay();
        });
    });

    prevBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            scrollPrev();
            resetAutoplay();
        });
    });

    // Create simple indicator dots based on total scrollable pages
    function createDots() {
        if (!dotsContainer) return;
        dotsContainer.innerHTML = '';
        const step = getStep();
        const pagesCount = Math.max(1, Math.ceil((slider.scrollWidth - slider.clientWidth) / step) + 1);

        if (pagesCount <= 1) return;

        for (let i = 0; i < pagesCount; i++) {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
            dot.className = i === 0
                ? 'w-6 h-2 rounded-full bg-purple-600 dark:bg-purple-500 transition-all duration-300 cursor-pointer'
                : 'w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-700 hover:bg-slate-400 dark:hover:bg-slate-600 transition-all duration-300 cursor-pointer';

            dot.addEventListener('click', () => {
                slider.scrollTo({ left: i * step, behavior: 'smooth' });
                resetAutoplay();
            });

            dotsContainer.appendChild(dot);
        }
    }

    function updateActiveDot() {
        if (!dotsContainer) return;
        const dots = dotsContainer.querySelectorAll('button');
        if (dots.length === 0) return;

        const step = getStep();
        const activeIndex = Math.min(Math.round(slider.scrollLeft / step), dots.length - 1);

        dots.forEach((dot, idx) => {
            if (idx === activeIndex) {
                dot.className = 'w-6 h-2 rounded-full bg-purple-600 dark:bg-purple-500 transition-all duration-300 cursor-pointer';
            } else {
                dot.className = 'w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-700 hover:bg-slate-400 dark:hover:bg-slate-600 transition-all duration-300 cursor-pointer';
            }
        });
    }

    createDots();

    // Sync dots on scroll (touch swipe or button click)
    let scrollTimer;
    slider.addEventListener('scroll', function () {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(updateActiveDot, 60);
    }, { passive: true });

    // Handle window resize
    window.addEventListener('resize', function () {
        createDots();
        updateActiveDot();
    });

    // Keyboard navigation when slider is focused
    slider.setAttribute('tabindex', '0');
    slider.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') {
            scrollNext();
            resetAutoplay();
        } else if (e.key === 'ArrowLeft') {
            scrollPrev();
            resetAutoplay();
        }
    });

    // Simple, non-intrusive autoplay (pauses on hover)
    let autoplayTimer = null;
    function startAutoplay() {
        stopAutoplay();
        autoplayTimer = setInterval(scrollNext, 5000);
    }

    function stopAutoplay() {
        if (autoplayTimer) {
            clearInterval(autoplayTimer);
            autoplayTimer = null;
        }
    }

    function resetAutoplay() {
        startAutoplay();
    }

    slider.addEventListener('mouseenter', stopAutoplay);
    slider.addEventListener('mouseleave', startAutoplay);
    slider.addEventListener('touchstart', stopAutoplay, { passive: true });
    slider.addEventListener('touchend', resetAutoplay, { passive: true });

    startAutoplay();
});