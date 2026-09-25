<?php
/**
 * Hero Section Component
 * Fully styled with Pure Tailwind CSS & Responsive Glassmorphism
 */
?>
<section class="relative min-h-[90vh] flex items-center justify-center overflow-hidden pt-28 pb-16 bg-gradient-to-b from-purple-50/70 via-white to-slate-50 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900">
    <!-- Ambient Gradient Blobs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-20 left-1/4 w-[32rem] h-[32rem] bg-purple-300/30 dark:bg-purple-900/20 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-3xl animate-pulse"></div>
        <div class="absolute top-1/3 -right-20 w-[30rem] h-[30rem] bg-blue-300/30 dark:bg-blue-900/20 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-10 left-1/3 w-[28rem] h-[28rem] bg-pink-300/25 dark:bg-pink-900/15 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-3xl animate-pulse" style="animation-delay: 4s;"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-12 w-full">
        <div class="grid lg:grid-cols-12 gap-12 items-center">
            
            <!-- Left Hero Content -->
            <div class="lg:col-span-7 text-center lg:text-left" data-aos="fade-right">
                <!-- Badge -->
                <div class="inline-flex items-center space-x-2 bg-white/90 dark:bg-slate-800/90 backdrop-blur-md text-purple-700 dark:text-purple-300 px-4 py-2 rounded-full mb-6 border border-purple-100 dark:border-purple-800/50 shadow-sm">
                    <span class="w-2.5 h-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-full animate-ping"></span>
                    <span class="text-xs sm:text-sm font-semibold tracking-wide">✨ #1 Global Freelance Talent Network</span>
                </div>

                <!-- Main Headline -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 dark:text-white tracking-tight leading-[1.15] mb-6">
                    Hire World-Class
                    <span class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 bg-clip-text text-transparent block mt-1">
                        Freelance Talent
                    </span>
                </h1>

                <!-- Subtitle -->
                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 mb-8 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Connect with vetted specialists across development, design, marketing, and content. Protect your milestones with escrow security and deliver results with confidence.
                </p>

                <!-- Interactive Hero Search Bar -->
                <form action="./public/gig.php" method="GET" class="bg-white/95 dark:bg-slate-800/95 backdrop-blur-xl p-2 rounded-2xl shadow-xl shadow-purple-900/5 dark:shadow-black/40 border border-slate-200/90 dark:border-slate-700/80 flex flex-col sm:flex-row items-stretch sm:items-center gap-2 max-w-xl mx-auto lg:mx-0 mb-6">
                    <div class="flex items-center gap-2.5 px-3 py-2 flex-grow">
                        <i class="ri-search-2-line text-xl text-purple-600 dark:text-purple-400 flex-shrink-0"></i>
                        <input type="text" name="q" placeholder="Try 'WordPress', 'Brand Identity', 'React', 'SEO'..." class="w-full bg-transparent focus:outline-none text-slate-800 dark:text-white text-sm placeholder-slate-400 font-medium">
                    </div>
                    <div class="h-6 w-[1px] bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>
                    <div class="px-2">
                        <select name="category" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none cursor-pointer">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold text-sm shadow-md shadow-purple-200 dark:shadow-none transition-all flex items-center justify-center gap-1.5 flex-shrink-0">
                        <span>Search Gigs</span>
                        <i class="ri-arrow-right-line"></i>
                    </button>
                </form>

                <!-- Quick Keywords -->
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2 mb-8 text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-slate-700 dark:text-slate-300">Trending:</span>
                    <a href="./public/gig.php?q=Web+Design" class="px-3 py-1 bg-white dark:bg-slate-800 hover:bg-purple-50 dark:hover:bg-slate-700 hover:text-purple-700 dark:hover:text-purple-300 rounded-full border border-slate-200 dark:border-slate-700 transition-colors">Web Design</a>
                    <a href="./public/gig.php?q=Logo" class="px-3 py-1 bg-white dark:bg-slate-800 hover:bg-purple-50 dark:hover:bg-slate-700 hover:text-purple-700 dark:hover:text-purple-300 rounded-full border border-slate-200 dark:border-slate-700 transition-colors">Logo Branding</a>
                    <a href="./public/gig.php?q=Fullstack" class="px-3 py-1 bg-white dark:bg-slate-800 hover:bg-purple-50 dark:hover:bg-slate-700 hover:text-purple-700 dark:hover:text-purple-300 rounded-full border border-slate-200 dark:border-slate-700 transition-colors">Full-Stack</a>
                    <a href="./public/gig.php?q=SEO" class="px-3 py-1 bg-white dark:bg-slate-800 hover:bg-purple-50 dark:hover:bg-slate-700 hover:text-purple-700 dark:hover:text-purple-300 rounded-full border border-slate-200 dark:border-slate-700 transition-colors">SEO Growth</a>
                </div>

                <!-- Trust Proof Badges -->
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs font-semibold text-slate-600 dark:text-slate-300 pt-2 border-t border-slate-200/60 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <i class="ri-checkbox-circle-fill text-emerald-500 text-lg"></i>
                        <span>Zero Upfront Risk</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ri-shield-check-fill text-blue-600 text-lg"></i>
                        <span>Milestone Escrow</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ri-star-fill text-amber-400 text-lg"></i>
                        <span>4.9/5 Rating (50K+ Orders)</span>
                    </div>
                </div>
            </div>

            <!-- Right Hero Illustration & Live Interactive Card -->
            <div class="lg:col-span-5 relative" data-aos="fade-left">
                <div class="relative z-10 max-w-md mx-auto">
                    <!-- Main Floating Card -->
                    <div class="bg-white dark:bg-slate-800/90 rounded-3xl shadow-2xl shadow-indigo-950/10 dark:shadow-black/50 p-6 sm:p-7 border border-slate-200/80 dark:border-slate-700">
                        <!-- Card Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-700 mb-5">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-md shadow-purple-200 dark:shadow-none">
                                    <i class="ri-medal-fill"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Top Vetted Freelancers</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Live Active Specialists</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Available Now
                            </span>
                        </div>

                        <!-- Showcase Mini List -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-900/60 hover:bg-purple-50/50 dark:hover:bg-slate-700/50 rounded-2xl border border-slate-100 dark:border-slate-700/60 transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-600 text-white font-bold text-sm flex items-center justify-center shadow-sm">
                                        AR
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-xs">Alex Rivera</p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Senior React & Node Engineer</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center gap-1 text-xs font-bold text-slate-800 dark:text-slate-200">
                                        <i class="ri-star-fill text-amber-400"></i> 5.0
                                    </div>
                                    <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">$45/hr</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-900/60 hover:bg-purple-50/50 dark:hover:bg-slate-700/50 rounded-2xl border border-slate-100 dark:border-slate-700/60 transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white font-bold text-sm flex items-center justify-center shadow-sm">
                                        ER
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-xs">Elena Rostova</p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">UI/UX & Design Systems</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center gap-1 text-xs font-bold text-slate-800 dark:text-slate-200">
                                        <i class="ri-star-fill text-amber-400"></i> 4.9
                                    </div>
                                    <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">$50/hr</div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Metric Badges -->
                        <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span class="flex items-center gap-1.5"><i class="ri-lock-2-line text-purple-600 dark:text-purple-400"></i> Escrow Secured</span>
                            <span class="flex items-center gap-1.5"><i class="ri-flashlight-line text-amber-500"></i> Instant Delivery</span>
                        </div>
                    </div>

                    <!-- Secondary Floating Stat Card -->
                    <div class="absolute -bottom-6 -left-6 bg-white/95 dark:bg-slate-800/95 backdrop-blur-md rounded-2xl shadow-xl p-4 border border-slate-200 dark:border-slate-700 hidden sm:flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xl">
                            <i class="ri-money-dollar-circle-line"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">$2.4M+ Paid</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400">To Verified Freelancers</div>
                        </div>
                    </div>

                    <!-- Secondary Floating Stat Card Top Right -->
                    <div class="absolute -top-6 -right-6 bg-white/95 dark:bg-slate-800/95 backdrop-blur-md rounded-2xl shadow-xl p-4 border border-slate-200 dark:border-slate-700 hidden sm:flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 flex items-center justify-center text-xl">
                            <i class="ri-thumb-up-line"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">99.4%</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400">Job Completion Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
