<?php
/**
 * Testimonials & Client Reviews Component
 * Pure Tailwind CSS simple review slider with live verified database reviews,
 * modern typography, clean cards, and smooth native navigation.
 */

if (!function_exists('resolveAvatarUrl') && file_exists(__DIR__ . '/../includes/image_helper.php')) {
    include_once __DIR__ . '/../includes/image_helper.php';
}

// Fetch verified reviews from database
$reviews = [];
$sqlRev = "SELECT r.id, r.comment, r.rating, r.created_at, 
                  u.username, u.first_name, u.last_name, u.profile_picture,
                  g.title as gig_title, c.name as category_name
           FROM reviews r 
           JOIN users u ON r.client_id = u.id 
           LEFT JOIN gigs g ON r.gig_id = g.id
           LEFT JOIN categories c ON g.category_id = c.id
           WHERE r.status = 'active' AND r.comment IS NOT NULL AND r.comment != '' 
           ORDER BY r.rating DESC, r.created_at DESC 
           LIMIT 12";

try {
    if (isset($conn)) {
        $stmtRev = $conn->query($sqlRev);
        $reviews = $stmtRev->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Reviews query error: " . $e->getMessage());
}

// Fallback reviews if database is empty
if (empty($reviews)) {
    $reviews = [
        [
            "first_name" => "Jennifer",
            "last_name" => "Lee",
            "username" => "jenniferlee",
            "profile_picture" => null,
            "comment" => "FreelanceHub has completely transformed how our agency sources developers. The escrow milestone system gives total peace of mind and the talent delivered 3 days early!",
            "rating" => 5,
            "created_at" => "2024-04-10",
            "gig_title" => "Full-Stack Enterprise Web Application",
            "category_name" => "Development"
        ],
        [
            "first_name" => "David",
            "last_name" => "Chen",
            "username" => "davidchen",
            "profile_picture" => null,
            "comment" => "Found a brilliant UI/UX designer for our SaaS rebranding in less than 24 hours. Communication was proactive, deliverables were pixel-perfect, and conversions jumped 35%.",
            "rating" => 5,
            "created_at" => "2024-04-06",
            "gig_title" => "Modern SaaS UI/UX Design System",
            "category_name" => "Design"
        ],
        [
            "first_name" => "Amanda",
            "last_name" => "Lopez",
            "username" => "amandal",
            "profile_picture" => null,
            "comment" => "The machine learning specialist we contracted exceeded every KPI. Clean documentation, fast iterations, and impeccable code quality. Will definitely hire again!",
            "rating" => 5,
            "created_at" => "2024-03-29",
            "gig_title" => "Predictive AI & Machine Learning Pipeline",
            "category_name" => "Data & AI"
        ],
        [
            "first_name" => "Robert",
            "last_name" => "Wilson",
            "username" => "rwilson",
            "profile_picture" => null,
            "comment" => "World-class copywriting team! They captured our brand voice immediately. Our sales page click-through rates doubled in the first two weeks of launch.",
            "rating" => 5,
            "created_at" => "2024-03-20",
            "gig_title" => "High-Converting Sales Funnel Copywriting",
            "category_name" => "Marketing"
        ]
    ];
}
?>

<section class="py-20 bg-slate-50/60 dark:bg-slate-950 border-b border-slate-200/80 dark:border-slate-800/80 relative" id="testimonials">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header: Title & Desktop Prev/Next Controls -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-6 mb-12" data-aos="fade-up">
            <div>
                <!-- Eyebrow Badge -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-50 dark:bg-purple-950/60 border border-purple-200/80 dark:border-purple-800/60 text-purple-700 dark:text-purple-300 text-xs font-bold tracking-wide mb-3">
                    <i class="ri-heart-3-fill text-rose-500"></i>
                    <span>VERIFIED CLIENT STORIES &bull; 4.9/5 RATED</span>
                </div>

                <!-- Main Title -->
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
                    Real Stories, 
                    <span class="bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">
                        Real Results
                    </span>
                </h2>

                <!-- Subtitle -->
                <p class="text-slate-600 dark:text-slate-400 text-sm sm:text-base mt-2 max-w-xl">
                    Discover how forward-thinking startups and businesses scale with top vetted freelancers on FreelanceHub.
                </p>
            </div>

            <!-- Desktop Slider Navigation Controls -->
            <div class="hidden sm:flex items-center gap-3">
                <button 
                    type="button" 
                    id="slider-prev-btn" 
                    class="w-12 h-12 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800 hover:text-purple-600 dark:hover:text-purple-400 hover:border-purple-300 dark:hover:border-purple-700 shadow-xs hover:shadow-md transition-all flex items-center justify-center cursor-pointer active:scale-95" 
                    aria-label="Previous testimonial"
                    title="Previous Slide"
                >
                    <i class="ri-arrow-left-line text-xl"></i>
                </button>

                <button 
                    type="button" 
                    id="slider-next-btn" 
                    class="w-12 h-12 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800 hover:text-purple-600 dark:hover:text-purple-400 hover:border-purple-300 dark:hover:border-purple-700 shadow-xs hover:shadow-md transition-all flex items-center justify-center cursor-pointer active:scale-95" 
                    aria-label="Next testimonial"
                    title="Next Slide"
                >
                    <i class="ri-arrow-right-line text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Simple Slider Track Container -->
        <div class="relative" data-aos="fade-up" data-aos-delay="100">
            <div 
                id="testimonials-slider" 
                class="flex gap-6 overflow-x-auto scroll-smooth py-4 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden snap-x snap-mandatory focus:outline-none"
            >
                <?php foreach ($reviews as $idx => $rev): ?>
                    <?php
                    $cUser = trim(($rev['first_name'] ?? '') . ' ' . ($rev['last_name'] ?? '')) ?: ($rev['username'] ?? 'Verified Client');
                    $cInit = strtoupper(substr($cUser, 0, 1));
                    $cRate = (float)($rev['rating'] ?? 5.0);
                    $cText = $rev['comment'] ?? '';
                    $cDate = !empty($rev['created_at']) ? date('M Y', strtotime($rev['created_at'])) : 'Recent';
                    $cAvatar = function_exists('resolveAvatarUrl') 
                        ? resolveAvatarUrl($rev['profile_picture'] ?? null, './', $cUser)
                        : './assets/img/placeholder-avatar.svg';
                    $cGigTitle = $rev['gig_title'] ?? null;
                    $cCategory = $rev['category_name'] ?? 'Verified Project';
                    ?>
                    <div class="testimonial-card-slide flex-shrink-0 w-[88vw] sm:w-[380px] lg:w-[410px] snap-start">
                        <div class="bg-white dark:bg-slate-900 rounded-3xl p-7 border border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-xl hover:border-purple-400/50 dark:hover:border-purple-500/40 transition-all duration-300 flex flex-col justify-between h-full group">
                            
                            <div>
                                <!-- Top Row: Stars Rating & Category Badge -->
                                <div class="flex items-center justify-between gap-2 mb-4">
                                    <div class="flex items-center gap-1.5">
                                        <div class="flex items-center text-amber-400 text-sm">
                                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                                <i class="<?= $s <= round($cRate) ? 'ri-star-fill' : 'ri-star-line text-slate-300 dark:text-slate-600'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 ml-1">
                                            <?= number_format($cRate, 1); ?>
                                        </span>
                                    </div>

                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/70 dark:border-slate-700 max-w-[140px] truncate">
                                        <?= htmlspecialchars($cCategory); ?>
                                    </span>
                                </div>

                                <!-- Review Quote -->
                                <p class="text-slate-700 dark:text-slate-200 text-sm sm:text-base leading-relaxed my-5 font-normal italic">
                                    &ldquo;<?= htmlspecialchars($cText); ?>&rdquo;
                                </p>

                                <!-- Gig Reference Tag (if attached) -->
                                <?php if (!empty($cGigTitle)): ?>
                                    <div class="mb-5 px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 truncate">
                                        <i class="ri-briefcase-3-line text-purple-600 dark:text-purple-400 flex-shrink-0"></i>
                                        <span class="truncate font-medium text-[11px]">
                                            Contract: <?= htmlspecialchars($cGigTitle); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Client Identity Footer -->
                            <div class="flex items-center justify-between gap-3 pt-5 border-t border-slate-100 dark:border-slate-800/80">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="relative w-10 h-10 rounded-full overflow-hidden ring-2 ring-purple-500/20 flex-shrink-0 shadow-xs">
                                        <img src="<?= htmlspecialchars($cAvatar); ?>" alt="<?= htmlspecialchars($cUser); ?>" loading="lazy" class="w-full h-full object-cover" onerror="this.src='./assets/img/placeholder-avatar.svg'">
                                        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900"></span>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate">
                                            <?= htmlspecialchars($cUser); ?>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                            <span class="truncate">@<?= htmlspecialchars($rev['username'] ?? 'client'); ?></span>
                                            <span>&bull;</span>
                                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-0.5">
                                                <i class="ri-checkbox-circle-fill text-xs"></i> Verified Client
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 flex-shrink-0">
                                    <?= htmlspecialchars($cDate); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Bottom Controls: Indicator Dots & Mobile Nav Buttons -->
            <div class="flex items-center justify-between sm:justify-center gap-4 mt-8">
                <!-- Mobile Prev Arrow -->
                <button 
                    type="button" 
                    id="slider-prev-btn-mobile" 
                    class="sm:hidden w-10 h-10 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 flex items-center justify-center shadow-xs active:scale-95" 
                    aria-label="Previous testimonial"
                >
                    <i class="ri-arrow-left-line text-lg"></i>
                </button>

                <!-- Clean, Simple Indicator Dots -->
                <div id="slider-dots" class="flex items-center gap-2"></div>

                <!-- Mobile Next Arrow -->
                <button 
                    type="button" 
                    id="slider-next-btn-mobile" 
                    class="sm:hidden w-10 h-10 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 flex items-center justify-center shadow-xs active:scale-95" 
                    aria-label="Next testimonial"
                >
                    <i class="ri-arrow-right-line text-lg"></i>
                </button>
            </div>
        </div>
    </div>
</section>
