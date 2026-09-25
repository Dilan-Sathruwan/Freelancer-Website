<?php
/**
 * Global Call to Action (CTA) Section Component
 * High-conversion gradient banner using pure Tailwind CSS
 */
?>
<section class="py-20 bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-700 text-white relative overflow-hidden">
    <!-- Subtle Background Accents -->
    <div class="absolute inset-0 pointer-events-none opacity-20">
        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-white filter blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-white filter blur-3xl"></div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10" data-aos="fade-up">
        <h2 class="text-3xl sm:text-5xl font-black tracking-tight mb-4">Ready to Build Your Next Big Idea?</h2>
        <p class="text-sm sm:text-base text-purple-100 max-w-xl mx-auto mb-8">Join thousands of entrepreneurs and creative professionals already thriving on FreelanceHub.</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="./auth/signup.php" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-white text-purple-700 font-bold text-sm shadow-xl hover:shadow-2xl hover:scale-105 transition-all">
                Get Started Free
            </a>
            <a href="./public/gig.php" class="w-full sm:w-auto px-8 py-3.5 rounded-xl border-2 border-white/80 hover:bg-white/10 text-white font-bold text-sm transition-all">
                Explore Marketplace Gigs
            </a>
        </div>
    </div>
</section>
