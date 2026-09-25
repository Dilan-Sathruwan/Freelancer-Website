<?php
/**
 * Categories Section Component
 * Interactive catalog grid using pure Tailwind CSS
 */
?>
<section class="py-20 bg-slate-50 dark:bg-slate-950 border-b border-slate-200/80 dark:border-slate-800" id="categories">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-12" data-aos="fade-up">
            <div>
                <div class="inline-flex items-center space-x-2 text-purple-600 dark:text-purple-400 font-semibold text-xs uppercase tracking-wider mb-2">
                    <i class="ri-grid-fill"></i> Marketplace Catalog
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Explore Popular Service Categories</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-xl">Find vetted specialists across high-demand digital crafts ready to bring your vision to reality.</p>
            </div>
            <div>
                <a href="./public/gig.php" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                    Browse all categories <i class="ri-arrow-right-line"></i>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 sm:gap-6">
            <?php
            $catMeta = [
                'Development' => ['icon' => 'ri-code-s-slash-line', 'color' => 'from-indigo-500 to-purple-600'],
                'Design' => ['icon' => 'ri-palette-line', 'color' => 'from-pink-500 to-rose-600'],
                'Writing' => ['icon' => 'ri-article-line', 'color' => 'from-emerald-500 to-teal-600'],
                'Marketing' => ['icon' => 'ri-line-chart-line', 'color' => 'from-amber-500 to-orange-600'],
                'Video & Animation' => ['icon' => 'ri-video-line', 'color' => 'from-blue-500 to-cyan-600'],
                'Music & Audio' => ['icon' => 'ri-music-2-line', 'color' => 'from-violet-500 to-fuchsia-600'],
            ];

            foreach ($categories as $index => $cat):
                $name = $cat['name'];
                $meta = $catMeta[$name] ?? ['icon' => 'ri-briefcase-line', 'color' => 'from-purple-500 to-indigo-600'];
            ?>
                <a href="./public/gig.php?category=<?= $cat['id']; ?>" class="group bg-white dark:bg-slate-900 hover:bg-white dark:hover:bg-slate-800 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 hover:border-purple-300 dark:hover:border-purple-700 shadow-sm hover:shadow-xl transition-all duration-300 text-center transform hover:-translate-y-1 block" data-aos="zoom-in" data-aos-delay="<?= $index * 50; ?>">
                    <div class="w-14 h-14 bg-gradient-to-br <?= $meta['color']; ?> rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 shadow-md transition duration-300 text-white text-2xl">
                        <i class="<?= $meta['icon']; ?>"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm mb-1 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors line-clamp-1"><?= htmlspecialchars($name); ?></h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium"><?= $cat['gig_count'] ?? '10'; ?>+ Available</p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
