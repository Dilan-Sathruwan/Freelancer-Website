// Initialize AOS
AOS.init({
    duration: 1000,
    easing: 'ease-in-out',
    once: true,
    offset: 100,
    delay: 100,
});

// Enhanced Mobile Menu Toggle
const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const mobileMenuClose = document.getElementById('mobile-menu-close');
const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
const body = document.body;

// Debug: Log elements to see if they exist
console.log('Mobile menu elements:', { mobileMenuBtn, mobileMenuClose, mobileMenuOverlay });

// Toggle mobile menu
function toggleMobileMenu() {
    console.log('Toggling mobile menu');
    body.classList.toggle('mobile-menu-open');
    // Prevent scrolling when menu is open
    if (body.classList.contains('mobile-menu-open')) {
        body.style.overflow = 'hidden';
        console.log('Menu opened');
    } else {
        body.style.overflow = '';
        console.log('Menu closed');
    }
}

// Close mobile menu
function closeMobileMenu() {
    console.log('Closing mobile menu');
    body.classList.remove('mobile-menu-open');
    body.style.overflow = '';
}

// Event listeners
if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    console.log('Mobile menu button event listener added');
}

if (mobileMenuClose) {
    mobileMenuClose.addEventListener('click', closeMobileMenu);
    console.log('Mobile menu close event listener added');
}

if (mobileMenuOverlay) {
    mobileMenuOverlay.addEventListener('click', closeMobileMenu);
    console.log('Mobile menu overlay event listener added');
}

// Ensure mobile menu elements exist
function initMobileMenu() {
    console.log('Initializing mobile menu');
    if (!document.getElementById('mobile-menu-overlay')) {
        console.error('Mobile menu overlay not found');
    }
    if (!document.getElementById('mobile-menu')) {
        console.error('Mobile menu not found');
    }
    
    // Add event listeners after DOM is fully loaded
    // Only add if they haven't been added already
    if (mobileMenuBtn && !mobileMenuBtn.hasAttribute('data-listener-added')) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        mobileMenuBtn.setAttribute('data-listener-added', 'true');
        console.log('Mobile menu button event listener added');
    }

    if (mobileMenuClose && !mobileMenuClose.hasAttribute('data-listener-added')) {
        mobileMenuClose.addEventListener('click', closeMobileMenu);
        mobileMenuClose.setAttribute('data-listener-added', 'true');
        console.log('Mobile menu close event listener added');
    }

    if (mobileMenuOverlay && !mobileMenuOverlay.hasAttribute('data-listener-added')) {
        mobileMenuOverlay.addEventListener('click', closeMobileMenu);
        mobileMenuOverlay.setAttribute('data-listener-added', 'true');
        console.log('Mobile menu overlay event listener added');
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileMenu);
} else {
    // DOM is already loaded
    initMobileMenu();
}

// Close menu on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMobileMenu();
    }
});

// Highlight active nav link
const currentPath = window.location.pathname;
const navLinks = document.querySelectorAll('.nav-link');

navLinks.forEach(link => {
    if (link.getAttribute('href') === currentPath || 
        (currentPath.includes(link.getAttribute('href')) && link.getAttribute('href') !== '#')) {
        link.classList.add('active');
    }
});

// Add scroll effect to navbar
let lastScroll = 0;
const nav = document.querySelector('nav');

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll <= 0) {
        nav.classList.remove('shadow-lg');
        nav.classList.add('shadow-sm');
    } else {
        nav.classList.remove('shadow-sm');
        nav.classList.add('shadow-lg');
    }

    // Optional: Hide navbar on scroll down, show on scroll up
    // Uncomment below if you want this feature
    /*
    if (currentScroll > lastScroll && currentScroll > 100) {
        nav.style.transform = 'translateY(-100%)';
    } else {
        nav.style.transform = 'translateY(0)';
    }
    */
    
    lastScroll = currentScroll;
});

// Add intersection observer for smooth animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-fade-in');
        }
    });
}, observerOptions);

// Observe elements (if you have sections to animate)
document.querySelectorAll('section').forEach(section => {
    observer.observe(section);
});

// Scroll to Top
const scrollTopBtn = document.getElementById('scrollTop');

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        scrollTopBtn.classList.remove('opacity-0', 'invisible');
        scrollTopBtn.classList.add('opacity-100', 'visible');
    } else {
        scrollTopBtn.classList.add('opacity-0', 'invisible');
        scrollTopBtn.classList.remove('opacity-100', 'visible');
    }
});

scrollTopBtn.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            const offset = 80; // Height of fixed navbar
            const targetPosition = target.offsetTop - offset;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
            
            // Close mobile menu if open
            closeMobileMenu();
        }
    });
});

// Navbar background on scroll
window.addEventListener('scroll', () => {
    if (window.pageYOffset > 50) {
        nav.classList.add('shadow-xl');
    } else {
        nav.classList.remove('shadow-xl');
    }
});

// Add number counting animation
const animateValue = (element, start, end, duration) => {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const value = Math.floor(progress * (end - start) + start);
        element.innerHTML = value.toLocaleString() + '+';
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
};

// Trigger counting animation when stats come into view
const statsObserverOptions = {
    threshold: 0.5
};

const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const statElements = entry.target.querySelectorAll('.text-3xl');
            statElements.forEach(el => {
                const text = el.textContent;
                const number = parseInt(text.replace(/\D/g, ''));
                if (number) {
                    animateValue(el, 0, number, 2000);
                }
            });
            statsObserver.unobserve(entry.target);
        }
    });
}, statsObserverOptions);

// Observe stats sections
document.querySelectorAll('.grid.grid-cols-2.lg\\:grid-cols-4').forEach(section => {
    statsObserver.observe(section);
});

// Close menu when clicking on a link
function initMobileMenuLinks() {
    const mobileMenuLinks = document.querySelectorAll('#mobile-menu a');
    mobileMenuLinks.forEach(link => {
        link.addEventListener('click', () => {
            // Small delay for better UX
            setTimeout(closeMobileMenu, 200);
        });
    });
    console.log('Mobile menu links initialized');
}

// Initialize mobile menu links
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileMenuLinks);
} else {
    // DOM is already loaded
    initMobileMenuLinks();
}