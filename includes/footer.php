<?php
/**
 * FreelanceHub - Universal Global Footer
 * World-Class UI/UX Design with pure Tailwind CSS
 * Features: Ambient glow borders, interactive newsletter card, trust signals,
 * live system status pill, category deep-links, dynamic root paths, and rich micro-interactions.
 */

if (!isset($root_path)) {
    $root_path = file_exists('./config/db.con.php') ? './' : '../';
}
?>

<footer class="relative bg-slate-950 text-slate-300 overflow-hidden border-t border-slate-800/80 mt-auto selection:bg-purple-500 selection:text-white">
    <!-- Top Gradient Accent Border -->
    <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-purple-500/60 to-transparent"></div>

    <!-- Ambient Glowing Orbs -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden opacity-30">
        <div class="absolute -top-40 left-1/3 w-[36rem] h-[36rem] bg-gradient-to-br from-purple-600/20 to-indigo-600/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-10 w-[28rem] h-[28rem] bg-gradient-to-tr from-blue-600/15 to-purple-600/15 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 pt-16 pb-12">
        
        <!-- Pre-Footer: Interactive Community & Pro Digest Banner -->
        <div class="mb-16 p-8 sm:p-10 rounded-3xl bg-gradient-to-br from-slate-900/90 via-slate-900/70 to-purple-950/40 border border-slate-800/80 shadow-2xl backdrop-blur-xl relative overflow-hidden">
            <!-- Background Decorative Grid Lines -->
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b0f_1px,transparent_1px),linear-gradient(to_bottom,#1e293b0f_1px,transparent_1px)] bg-[size:24px_24px] pointer-events-none"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <!-- Left: Pitch -->
                <div class="lg:col-span-7">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-300 text-xs font-bold tracking-wide mb-3">
                        <i class="ri-sparkling-fill text-amber-400"></i>
                        <span>JOIN 45,000+ CREATORS & FOUNDERS</span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                        Scale your ideas with weekly curated talent & insights.
                    </h3>
                    <p class="text-slate-400 text-sm mt-2 max-w-xl leading-relaxed">
                        Receive hand-picked top freelancers, emerging gig categories, and escrow tips delivered straight to your inbox. No spam, unsubscribe anytime.
                    </p>
                </div>

                <!-- Right: Interactive Form -->
                <div class="lg:col-span-5">
                    <form id="footer-newsletter-form" onsubmit="event.preventDefault(); handleFooterNewsletter();" class="flex flex-col sm:flex-row gap-2.5">
                        <div class="relative flex-1">
                            <i class="ri-mail-send-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-base"></i>
                            <input 
                                type="email" 
                                id="footer-email-input"
                                required 
                                placeholder="Enter your business email" 
                                class="w-full pl-11 pr-4 py-3.5 rounded-2xl bg-slate-800/80 border border-slate-700/80 text-white placeholder-slate-400 text-sm focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all font-medium"
                            >
                        </div>
                        <button 
                            type="submit" 
                            id="footer-submit-btn"
                            class="px-6 py-3.5 rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-sm shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 flex-shrink-0 cursor-pointer"
                        >
                            <span>Subscribe</span>
                            <i class="ri-arrow-right-line text-sm"></i>
                        </button>
                    </form>
                    <!-- Success alert message -->
                    <div id="footer-newsletter-success" class="hidden mt-3 text-xs text-emerald-400 font-semibold flex items-center gap-1.5 animate-slide-up">
                        <i class="ri-checkbox-circle-fill text-sm"></i>
                        <span>Thank you! You're now subscribed to the FreelanceHub Pro Digest.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Footer Navigation Columns -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 lg:gap-8 pb-16 border-b border-slate-800/80">
            
            <!-- Brand & Trust Block (4 cols) -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Brand Logo -->
                <a href="<?php echo $root_path; ?>index.php" class="inline-flex items-center gap-3 group">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-500 flex items-center justify-center text-white shadow-lg shadow-purple-500/25 group-hover:scale-105 group-hover:rotate-3 transition-transform">
                        <i class="ri-flashlight-fill text-xl"></i>
                    </div>
                    <span class="text-2xl font-black text-white tracking-tight">
                        Freelance<span class="bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">Hub</span>
                    </span>
                </a>

                <p class="text-slate-400 text-sm leading-relaxed max-w-sm">
                    The premier global freelance marketplace empowering visionary startups and elite creators to collaborate with escrow safety, transparency, and speed.
                </p>

                <!-- Live System Status Badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-slate-300 font-medium">All Systems Operational</span>
                    <span class="text-slate-600">&bull;</span>
                    <span class="text-emerald-400 font-semibold">99.98% Uptime</span>
                </div>

                <!-- Social Media Clusters -->
                <div class="flex items-center gap-2.5 pt-2">
                    <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 hover:border-purple-500/50 hover:bg-purple-600/20 text-slate-400 hover:text-white flex items-center justify-center transition-all hover:scale-110 shadow-xs" title="Twitter / X">
                        <i class="ri-twitter-x-line text-lg"></i>
                    </a>
                    <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 hover:border-purple-500/50 hover:bg-purple-600/20 text-slate-400 hover:text-white flex items-center justify-center transition-all hover:scale-110 shadow-xs" title="GitHub">
                        <i class="ri-github-line text-lg"></i>
                    </a>
                    <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 hover:border-purple-500/50 hover:bg-purple-600/20 text-slate-400 hover:text-white flex items-center justify-center transition-all hover:scale-110 shadow-xs" title="LinkedIn">
                        <i class="ri-linkedin-fill text-lg"></i>
                    </a>
                    <a href="https://discord.com" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 hover:border-purple-500/50 hover:bg-purple-600/20 text-slate-400 hover:text-white flex items-center justify-center transition-all hover:scale-110 shadow-xs" title="Discord Community">
                        <i class="ri-discord-line text-lg"></i>
                    </a>
                    <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 hover:border-purple-500/50 hover:bg-purple-600/20 text-slate-400 hover:text-white flex items-center justify-center transition-all hover:scale-110 shadow-xs" title="Instagram">
                        <i class="ri-instagram-line text-lg"></i>
                    </a>
                </div>
            </div>

            <!-- Col 2: Marketplace Navigation (2 cols) -->
            <div class="lg:col-span-2 space-y-4">
                <h4 class="text-xs font-black uppercase tracking-wider text-white">Marketplace</h4>
                <ul class="space-y-3 text-sm">
                    <li>
                        <a href="<?php echo $root_path; ?>public/gig.php" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1.5 group">
                            <span>Browse Gigs</span>
                            <span class="px-1.5 py-0.5 text-[9px] font-black uppercase rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30">Hot</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $root_path; ?>public/gig.php?sort=rating" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1">
                            <span>Top Rated Services</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $root_path; ?>index.php#features" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1">
                            <span>How It Works</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $root_path; ?>index.php#testimonials" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1">
                            <span>Client Reviews</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $root_path; ?>auth/signup.php?role=freelancer" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1">
                            <span>Become a Seller</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Col 3: Popular Categories (3 cols) -->
            <div class="lg:col-span-3 space-y-4">
                <h4 class="text-xs font-black uppercase tracking-wider text-white">Top Categories</h4>
                <ul class="space-y-3 text-sm">
                    <li>
                        <a href="<?php echo $root_path; ?>public/gig.php?category=1" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1.5">
                            <i class="ri-code-s-slash-line text-purple-400 text-xs"></i>
                            <span>Web & Software Development</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $root_path; ?>public/gig.php?category=2" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1.5">
                            <i class="ri-palette-line text-pink-400 text-xs"></i>
                            <span>UI/UX & Graphic Design</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $root_path; ?>public/gig.php" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1.5">
                            <i class="ri-cpu-line text-blue-400 text-xs"></i>
                            <span>AI, Machine Learning & Data</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $root_path; ?>public/gig.php?category=3" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1.5">
                            <i class="ri-quill-pen-line text-emerald-400 text-xs"></i>
                            <span>Content Writing & Copy</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $root_path; ?>public/gig.php?category=4" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1.5">
                            <i class="ri-megaphone-line text-amber-400 text-xs"></i>
                            <span>Digital Growth & SEO</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Col 4: Trust, Safety & Company (3 cols) -->
            <div class="lg:col-span-3 space-y-4">
                <h4 class="text-xs font-black uppercase tracking-wider text-white">Trust & Security</h4>
                <ul class="space-y-3 text-sm">
                    <li>
                        <a href="#" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1.5">
                            <i class="ri-shield-check-line text-emerald-400 text-xs"></i>
                            <span>Milestone Escrow Protection</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1.5">
                            <i class="ri-lock-2-line text-indigo-400 text-xs"></i>
                            <span>256-Bit SSL Payment Security</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1.5">
                            <i class="ri-customer-service-2-line text-cyan-400 text-xs"></i>
                            <span>24/7 Dedicated Concierge</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1.5">
                            <i class="ri-file-shield-line text-slate-400 text-xs"></i>
                            <span>Freelancer Verification Standard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-slate-400 hover:text-white hover:translate-x-1 transition-all inline-flex items-center gap-1.5">
                            <i class="ri-scales-3-line text-slate-400 text-xs"></i>
                            <span>Fair Dispute Resolution</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom Bar -->
        <div class="pt-8 flex flex-col md:flex-row items-center justify-between gap-6 text-xs text-slate-400">
            <!-- Left: Copyright & Manifesto -->
            <div class="flex flex-col sm:flex-row items-center gap-2 text-center md:text-left">
                <p>&copy; <?= date('Y'); ?> FreelanceHub Inc. All rights reserved.</p>
                <span class="hidden sm:inline text-slate-700">&bull;</span>
                <p class="text-slate-400">Architected for the global borderless workforce.</p>
            </div>

            <!-- Right: Currency Pill & Legal Links -->
            <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6">
                <!-- Region & Currency Selector Indicator -->
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 font-medium">
                    <i class="ri-global-line text-purple-400"></i>
                    <span>EN &bull; USD ($)</span>
                </div>

                <a href="#" class="hover:text-purple-400 transition-colors">Privacy Policy</a>
                <span class="text-slate-700">&bull;</span>
                <a href="#" class="hover:text-purple-400 transition-colors">Terms of Service</a>
                <span class="text-slate-700">&bull;</span>
                <a href="#" class="hover:text-purple-400 transition-colors">Trust & Safety</a>
                <span class="text-slate-700">&bull;</span>
                <a href="#" class="hover:text-purple-400 transition-colors">Security</a>
            </div>
        </div>
    </div>
</footer>

<!-- Newsletter Handler Script -->
<script>
    function handleFooterNewsletter() {
        var input = document.getElementById('footer-email-input');
        var successMsg = document.getElementById('footer-newsletter-success');
        var submitBtn = document.getElementById('footer-submit-btn');

        if (!input || !input.value) return;

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin text-sm"></i><span>Joining...</span>';
        }

        setTimeout(function() {
            if (input) input.value = '';
            if (successMsg) successMsg.classList.remove('hidden');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ri-check-line text-sm"></i><span>Joined!</span>';
                setTimeout(function() {
                    submitBtn.innerHTML = '<span>Subscribe</span><i class="ri-arrow-right-line text-sm"></i>';
                }, 4000);
            }
        }, 600);
    }
</script>