<?php
/**
 * Featured & Trending Gigs Section Component
 * Fully responsive gig cards using pure Tailwind CSS & SVG Placeholders
 */
?>
<section class="py-20 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800" id="featured-gigs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-12" data-aos="fade-up">
            <div>
                <div class="inline-flex items-center space-x-2 text-purple-600 dark:text-purple-400 font-semibold text-xs uppercase tracking-wider mb-2">
                    <i class="ri-fire-fill text-amber-500"></i> Top-Rated Offerings
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Trending Marketplace Gigs</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-xl">Book top-performing services directly with guaranteed delivery timelines and escrow protection.</p>
            </div>
            <div>
                <a href="./public/gig.php?sort=rating" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-700 transition-all shadow-xs">
                    <span>View All Gigs</span>
                    <i class="ri-arrow-right-line"></i>
                </a>
            </div>
        </div>

        <!-- Gigs Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            <?php foreach ($featuredGigs as $idx => $fgig): ?>
                <?php
                $gigImg = resolveGigImage($fgig['image'] ?? null, './');
                $sellerName = trim(($fgig['first_name'] ?? '') . ' ' . ($fgig['last_name'] ?? '')) ?: ($fgig['username'] ?? 'User');
                $sellerAvatar = resolveAvatarUrl($fgig['profile_picture'] ?? null, './', $sellerName);
                $fgigRating = (float)($fgig['avg_rating'] ?? 5.0);
                $fgigReviews = (int)($fgig['reviews_count'] ?? 0);
                ?>
                <div class="bg-white dark:bg-slate-800/90 rounded-3xl border border-slate-200/90 dark:border-slate-700/80 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col group transform hover:-translate-y-1" data-aos="fade-up" data-aos-delay="<?= $idx * 80; ?>">
                    <!-- Image Container with Aspect Ratio -->
                    <div class="relative aspect-[16/9] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                        <img src="<?= htmlspecialchars($gigImg); ?>" alt="<?= htmlspecialchars($fgig['title']); ?>" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" onerror="this.src='./assets/img/placeholder-gig.svg'">
                        <span class="absolute top-3 left-3 px-3 py-1 rounded-full text-xs font-bold bg-white/95 dark:bg-slate-900/90 backdrop-blur-md text-slate-800 dark:text-slate-200 shadow-sm border border-white/40 dark:border-slate-700">
                            <?= htmlspecialchars($fgig['category_name'] ?? 'Service'); ?>
                        </span>
                        <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-900/80 backdrop-blur-md text-white flex items-center gap-1">
                            <i class="ri-time-line text-xs"></i> <?= $fgig['delivery_time']; ?>d delivery
                        </span>
                    </div>

                    <!-- Card Content -->
                    <div class="p-6 flex flex-col flex-grow">
                        <!-- Seller Snapshot -->
                        <div class="flex items-center gap-3 mb-3">
                            <img src="<?= htmlspecialchars($sellerAvatar); ?>" alt="Seller" loading="lazy" decoding="async" class="w-8 h-8 rounded-full object-cover border border-slate-200 dark:border-slate-700 shadow-sm" onerror="this.src='./assets/img/placeholder-avatar.svg'">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($sellerName); ?></div>
                                <div class="text-[11px] text-slate-400">@<?= htmlspecialchars($fgig['username']); ?></div>
                            </div>
                        </div>

                        <!-- Gig Title -->
                        <a href="./public/gig_detail.php?id=<?= $fgig['id']; ?>" class="text-base font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors line-clamp-2 mb-4 leading-snug">
                            <?= htmlspecialchars($fgig['title']); ?>
                        </a>

                        <!-- Star Rating -->
                        <div class="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400 mb-5">
                            <div class="flex text-amber-400">
                                <i class="ri-star-fill"></i>
                            </div>
                            <span class="font-bold text-slate-800 dark:text-slate-200"><?= number_format($fgigRating, 1); ?></span>
                            <span class="text-slate-400">(<?= $fgigReviews; ?> reviews)</span>
                        </div>

                        <!-- Bottom Action / Price Bar -->
                        <div class="mt-auto pt-4 border-t border-slate-100 dark:border-slate-700/80 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Starting at</div>
                                <div class="text-lg font-black text-slate-900 dark:text-white">$<?= number_format((float)$fgig['price'], 2); ?></div>
                            </div>
                            <a href="./public/gig_detail.php?id=<?= $fgig['id']; ?>" class="px-4 py-2 rounded-xl bg-purple-50 dark:bg-purple-950/40 hover:bg-purple-600 hover:text-white dark:hover:bg-purple-600 text-purple-700 dark:text-purple-300 font-semibold text-xs transition-all shadow-xs flex items-center gap-1">
                                <span>View Gig</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
