// ===================================
// TESTIMONIALS SLIDER FUNCTIONALITY
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.review-slider');
    const slides = document.querySelectorAll('.review-slide');
    const dotsContainer = document.querySelector('.slider-dots');
    const prevBtn = document.querySelector('.slider-prev');
    const nextBtn = document.querySelector('.slider-next');
    const autoplayToggle = document.querySelector('.autoplay-toggle');
    const progressBar = document.querySelector('.progress-bar');
    
    if (!slider || slides.length === 0) return;
    
    let currentIndex = 0;
    let isAutoplay = true;
    let autoplayInterval;
    let progressInterval;
    const autoplayDuration = 5000; // 5 seconds
    const isMobile = window.innerWidth < 641;
    
    // Get slides per view based on screen size
    function getSlidesPerView() {
        if (window.innerWidth >= 1025) return 3;
        if (window.innerWidth >= 641) return 2;
        return 1;
    }
    
    let slidesPerView = getSlidesPerView();
    const maxIndex = Math.max(0, slides.length - slidesPerView);
    
    // Create dots
    function createDots() {
        if (!dotsContainer) return;
        dotsContainer.innerHTML = '';
        const dotsCount = Math.ceil(slides.length / slidesPerView);
        
        for (let i = 0; i < dotsCount; i++) {
            const dot = document.createElement('button');
            dot.classList.add('slider-dot');
            dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
            if (i === 0) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(i * slidesPerView));
            dotsContainer.appendChild(dot);
        }
    }
    
    // Update dots
    function updateDots() {
        if (!dotsContainer) return;
        const dots = document.querySelectorAll('.slider-dot');
        const activeDotIndex = Math.floor(currentIndex / slidesPerView);
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === activeDotIndex);
        });
    }
    
    // Go to specific slide
    function goToSlide(index) {
        currentIndex = Math.max(0, Math.min(index, slides.length - 1));
        updateSlider();
        resetAutoplay();
    }
    
    // Update slider position
    function updateSlider() {
        if (isMobile) {
            // Mobile: use scroll
            const slideWidth = slides[0].offsetWidth + 16; // width + gap
            slider.scrollTo({
                left: currentIndex * slideWidth,
                behavior: 'smooth'
            });
        } else {
            // Desktop: already in grid, just update dots
            updateDots();
        }
        
        // Update button states
        if (prevBtn && nextBtn) {
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex >= slides.length - slidesPerView;
        }
    }
    
    // Next slide
    function nextSlide() {
        if (currentIndex < slides.length - slidesPerView) {
            currentIndex += slidesPerView;
        } else {
            currentIndex = 0; // Loop back to start
        }
        updateSlider();
    }
    
    // Previous slide
    function prevSlide() {
        if (currentIndex > 0) {
            currentIndex = Math.max(0, currentIndex - slidesPerView);
        } else {
            currentIndex = slides.length - slidesPerView; // Loop to end
        }
        updateSlider();
    }
    
    // Autoplay functionality
    function startAutoplay() {
        if (!isAutoplay) return;
        
        autoplayInterval = setInterval(nextSlide, autoplayDuration);
        
        // Progress bar animation
        if (progressBar) {
            progressBar.style.width = '0%';
            let progress = 0;
            progressInterval = setInterval(() => {
                progress += 100 / (autoplayDuration / 100);
                progressBar.style.width = Math.min(progress, 100) + '%';
            }, 100);
        }
    }
    
    function stopAutoplay() {
        clearInterval(autoplayInterval);
        clearInterval(progressInterval);
        if (progressBar) {
            progressBar.style.width = '0%';
        }
    }
    
    function resetAutoplay() {
        stopAutoplay();
        if (isAutoplay) {
            startAutoplay();
        }
    }
    
    function toggleAutoplay() {
        isAutoplay = !isAutoplay;
        if (autoplayToggle) {
            autoplayToggle.classList.toggle('playing', isAutoplay);
        }
        
        if (isAutoplay) {
            startAutoplay();
        } else {
            stopAutoplay();
        }
    }
    
    // Event listeners
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            resetAutoplay();
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            resetAutoplay();
        });
    }
    
    if (autoplayToggle) {
        autoplayToggle.addEventListener('click', toggleAutoplay);
    }
    
    // Touch swipe support for mobile
    if (isMobile) {
        let touchStartX = 0;
        let touchEndX = 0;
        
        slider.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        slider.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const swipeThreshold = 50;
            if (touchStartX - touchEndX > swipeThreshold) {
                nextSlide();
                resetAutoplay();
            } else if (touchEndX - touchStartX > swipeThreshold) {
                prevSlide();
                resetAutoplay();
            }
        }
        
        // Update current index based on scroll position
        let isScrolling = false;
        slider.addEventListener('scroll', debounce(() => {
            if (isScrolling) return;
            isScrolling = true;
            
            const slideWidth = slides[0].offsetWidth + 16; // width + gap
            const scrollPosition = slider.scrollLeft;
            const newIndex = Math.round(scrollPosition / slideWidth);
            
            if (newIndex !== currentIndex && newIndex >= 0 && newIndex < slides.length) {
                currentIndex = newIndex;
                updateDots();
            }
            
            setTimeout(() => {
                isScrolling = false;
            }, 100);
        }, 150));
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevSlide();
            resetAutoplay();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
            resetAutoplay();
        }
    });
    
    // Pause autoplay on hover (desktop only)
    if (!isMobile) {
        slider.addEventListener('mouseenter', stopAutoplay);
        slider.addEventListener('mouseleave', () => {
            if (isAutoplay) startAutoplay();
        });
    }
    
    // Like button functionality
    const likeButtons = document.querySelectorAll('.like-btn');
    likeButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.toggle('liked');
            const icon = this.querySelector('i');
            if (this.classList.contains('liked')) {
                icon.classList.remove('ri-thumb-up-line');
                icon.classList.add('ri-thumb-up-fill');
            } else {
                icon.classList.remove('ri-thumb-up-fill');
                icon.classList.add('ri-thumb-up-line');
            }
        });
    });
    
    // Resize handler
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            const newIsMobile = window.innerWidth < 641;
            const newSlidesPerView = getSlidesPerView();
            
            // Only reset if the view has actually changed
            if (newSlidesPerView !== slidesPerView || newIsMobile !== isMobile) {
                slidesPerView = newSlidesPerView;
                isMobile = newIsMobile;
                currentIndex = 0;
                createDots();
                updateSlider();
                resetAutoplay();
            }
        }, 250);
    });
    
    // Debounce utility
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Initialize
    createDots();
    updateSlider();
    if (isAutoplay && autoplayToggle) {
        autoplayToggle.classList.add('playing');
        startAutoplay();
    }
    
    // Intersection Observer for animation on scroll
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    slides.forEach((slide, index) => {
        slide.style.opacity = '0';
        slide.style.transform = 'translateY(20px)';
        slide.style.transition = `all 0.6s ease ${index * 0.1}s`;
        observer.observe(slide);
    });
});