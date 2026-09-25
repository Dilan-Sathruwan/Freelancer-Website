<?php
// Include database connection
include('../config/db.con.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('resolveGigImage') && file_exists(__DIR__ . '/../includes/image_helper.php')) {
    include_once __DIR__ . '/../includes/image_helper.php';
}

// Search and filter parameters
$search = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : 0;
$budget = trim($_GET['budget'] ?? 'all');
$sort = $_GET['sort'] ?? 'newest';

// Base query
$sql = "
    SELECT g.id, g.title, g.description, g.price, g.delivery_time, g.image, g.created_at, g.avg_rating, g.reviews_count,
           u.id AS freelancer_user_id, u.username, u.first_name, u.last_name, u.profile_picture,
           c.name AS category_name
    FROM gigs g
    JOIN users u ON g.freelancer_id = u.id
    LEFT JOIN categories c ON g.category_id = c.id
    WHERE g.status = 'active'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (g.title LIKE ? OR g.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($categoryId > 0) {
    $sql .= " AND g.category_id = ?";
    $params[] = $categoryId;
}

if ($budget === 'under50') {
    $sql .= " AND g.price <= 50";
} elseif ($budget === '50to150') {
    $sql .= " AND g.price BETWEEN 50 AND 150";
} elseif ($budget === 'above150') {
    $sql .= " AND g.price >= 150";
}

switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY g.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY g.price DESC";
        break;
    case 'rating':
        $sql .= " ORDER BY g.avg_rating DESC, g.reviews_count DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY g.created_at DESC";
        break;
}

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch active categories for filter bar
    $stmtCats = $conn->prepare("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
    $stmtCats->execute();
    $categories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching gigs: " . $e->getMessage());
    $gigs = [];
    $categories = [];
}

// Fallback demo gigs if DB is empty or offline
if (empty($gigs) && empty($search) && $categoryId === 0 && $budget === 'all') {
    $gigs = [
        [
            'id' => 1,
            'title' => 'Custom Full-Stack Web Development with React, Node & MySQL',
            'description' => 'Complete enterprise web application development tailored to your business needs.',
            'price' => 180.00,
            'delivery_time' => 3,
            'category_name' => 'Development',
            'avg_rating' => 5.0,
            'reviews_count' => 38,
            'username' => 'alex_dev',
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
            'profile_picture' => null,
            'image' => null
        ],
        [
            'id' => 2,
            'title' => 'Complete Brand Identity, Logo Design & Vector Design System',
            'description' => 'Pixel-perfect typography, color palettes, and social media media kits.',
            'price' => 75.00,
            'delivery_time' => 2,
            'category_name' => 'Design',
            'avg_rating' => 4.9,
            'reviews_count' => 52,
            'username' => 'elena_design',
            'first_name' => 'Elena',
            'last_name' => 'Rostova',
            'profile_picture' => null,
            'image' => null
        ],
        [
            'id' => 3,
            'title' => 'High-Conversion Copywriting, Landing Page Sales Copy & SEO',
            'description' => 'Compelling narrative copy that hooks readers and converts visitors into loyal clients.',
            'price' => 45.00,
            'delivery_time' => 1,
            'category_name' => 'Writing',
            'avg_rating' => 4.8,
            'reviews_count' => 27,
            'username' => 'marcus_v',
            'first_name' => 'Marcus',
            'last_name' => 'Vance',
            'profile_picture' => null,
            'image' => null
        ]
    ];
}

$hasActiveFilters = (!empty($search) || $categoryId > 0 || $budget !== 'all');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Professional Services & Gigs - FreelanceHub</title>
    <meta name="description" content="Discover verified freelance services in web development, graphic design, SEO, copywriting, video editing, and mobile apps.">
    <meta name="theme-color" content="#6366f1">

    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">

    <!-- Suppress Tailwind Play CDN notice -->
    <script>
        (function() {
            var origWarn = console.warn;
            console.warn = function() {
                if (arguments[0] && typeof arguments[0] === 'string' && arguments[0].indexOf('cdn.tailwindcss.com') !== -1) return;
                origWarn.apply(console, arguments);
            };
        })();
    </script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- AOS Animation -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">

    <!-- Tailwind Custom Configuration -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                        accent: '#ec4899',
                        dark: '#0f172a',
                        light: '#f8fafc',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans min-h-screen flex flex-col pt-20 transition-colors duration-300">
    <!-- Navigation header -->
    <?php include('../includes/header.php'); ?>

    <!-- Hero Search & Filter Section -->
    <section class="bg-gradient-to-b from-purple-50/70 via-white to-slate-50 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 border-b border-slate-200/80 dark:border-slate-800 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Ambient Decorative Glow -->
        <div class="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 w-[32rem] h-[32rem] bg-purple-500/10 dark:bg-purple-600/15 rounded-full blur-3xl"></div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-8" data-aos="fade-up">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 mb-3 border border-purple-200/80 dark:border-purple-800/60 shadow-2xs">
                    <i class="ri-sparkling-fill text-amber-400"></i> Curated Marketplace Services
                </span>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                    Find the perfect <span class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 bg-clip-text text-transparent">freelance services</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-slate-600 dark:text-slate-300">
                    Connect with world-class specialists and order guaranteed solutions protected by escrow.
                </p>
            </div>

            <!-- Search Bar Form -->
            <form action="gig.php" method="GET" id="filterForm" class="max-w-4xl mx-auto" data-aos="fade-up" data-aos-delay="50">
                <input type="hidden" name="budget" id="budgetInput" value="<?= htmlspecialchars($budget); ?>">

                <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl p-3 rounded-3xl shadow-xl shadow-purple-950/5 dark:shadow-black/50 border border-slate-200/90 dark:border-slate-800 flex flex-col md:flex-row gap-3">
                    <!-- Search Input with Clear Button -->
                    <div class="relative flex-1">
                        <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none"></i>
                        <input type="text" id="searchInput" name="q" value="<?= htmlspecialchars($search); ?>"
                               placeholder="Try 'modern website', 'logo design', 'python script'..."
                               class="w-full pl-12 pr-10 py-3.5 bg-slate-50 dark:bg-slate-800/80 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 focus:ring-2 focus:ring-purple-500 text-slate-800 dark:text-white placeholder-slate-400 text-sm font-medium transition focus:outline-none">
                        <?php if (!empty($search)): ?>
                            <button type="button" onclick="clearSearch()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1" title="Clear search">
                                <i class="ri-close-circle-fill text-lg"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Category Selector -->
                    <div class="relative md:w-56">
                        <select name="category" onchange="document.getElementById('filterForm').submit()" class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800/80 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 focus:ring-2 focus:ring-purple-500 text-slate-700 dark:text-slate-200 text-sm font-semibold transition cursor-pointer appearance-none focus:outline-none">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>" <?= $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="ri-arrow-down-s-line absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-lg"></i>
                    </div>

                    <!-- Sort By Selector -->
                    <div class="relative md:w-48">
                        <select name="sort" onchange="document.getElementById('filterForm').submit()" class="w-full px-4 py-3.5 bg-slate-50 dark:bg-slate-800/80 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 focus:ring-2 focus:ring-purple-500 text-slate-700 dark:text-slate-200 text-sm font-semibold transition cursor-pointer appearance-none focus:outline-none">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Newest Gigs</option>
                            <option value="rating" <?= $sort === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                        <i class="ri-arrow-down-s-line absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-lg"></i>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="px-7 py-3.5 bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-2xl font-bold text-sm shadow-md shadow-purple-500/20 hover:scale-[1.02] active:scale-[0.98] transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer">
                        <i class="ri-filter-3-line"></i>
                        <span>Apply</span>
                    </button>
                </div>

                <!-- Quick Budget Pills Filter -->
                <div class="flex flex-wrap items-center justify-center gap-2 mt-4 text-xs font-semibold">
                    <span class="text-slate-400 dark:text-slate-500 uppercase tracking-wider text-[10px] mr-1">Budget:</span>
                    <button type="button" onclick="setBudget('all')" class="px-3.5 py-1.5 rounded-full transition-all border cursor-pointer <?= $budget === 'all' ? 'bg-purple-600 text-white border-purple-600 shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">
                        All Budgets
                    </button>
                    <button type="button" onclick="setBudget('under50')" class="px-3.5 py-1.5 rounded-full transition-all border cursor-pointer <?= $budget === 'under50' ? 'bg-purple-600 text-white border-purple-600 shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">
                        Under $50
                    </button>
                    <button type="button" onclick="setBudget('50to150')" class="px-3.5 py-1.5 rounded-full transition-all border cursor-pointer <?= $budget === '50to150' ? 'bg-purple-600 text-white border-purple-600 shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">
                        $50 – $150
                    </button>
                    <button type="button" onclick="setBudget('above150')" class="px-3.5 py-1.5 rounded-full transition-all border cursor-pointer <?= $budget === 'above150' ? 'bg-purple-600 text-white border-purple-600 shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">
                        $150+
                    </button>
                </div>
            </form>

            <!-- Active Filters Chip Bar -->
            <?php if ($hasActiveFilters): ?>
                <div class="flex flex-wrap items-center justify-center gap-2 mt-5" data-aos="fade-up">
                    <span class="text-xs text-slate-400 font-medium">Active filters:</span>
                    <?php if (!empty($search)): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800">
                            Keyword: "<?= htmlspecialchars($search); ?>"
                            <a href="gig.php?<?= http_build_query(array_merge($_GET, ['q' => ''])); ?>" class="hover:text-indigo-900 dark:hover:text-white"><i class="ri-close-line"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($categoryId > 0): ?>
                        <?php
                            $catName = 'Category';
                            foreach ($categories as $c) {
                                if ($c['id'] == $categoryId) { $catName = $c['name']; break; }
                            }
                        ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200/80 dark:border-purple-800">
                            <?= htmlspecialchars($catName); ?>
                            <a href="gig.php?<?= http_build_query(array_merge($_GET, ['category' => 0])); ?>" class="hover:text-purple-900 dark:hover:text-white"><i class="ri-close-line"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($budget !== 'all'): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800">
                            Budget: <?= $budget === 'under50' ? 'Under $50' : ($budget === '50to150' ? '$50–$150' : '$150+'); ?>
                            <a href="gig.php?<?= http_build_query(array_merge($_GET, ['budget' => 'all'])); ?>" class="hover:text-emerald-900 dark:hover:text-white"><i class="ri-close-line"></i></a>
                        </span>
                    <?php endif; ?>

                    <a href="gig.php" class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline ml-1">
                        <i class="ri-refresh-line"></i> Clear All
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Main Results Grid -->
    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">
        <!-- Results Summary Header -->
        <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-200/80 dark:border-slate-800">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                    <?= count($gigs); ?> Services Available
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Showing verified marketplace offerings ready for order</p>
            </div>
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                Sorted by: <span class="font-bold text-slate-800 dark:text-slate-200"><?= ucfirst(str_replace('_', ' ', $sort)); ?></span>
            </div>
        </div>

        <?php if (!empty($gigs)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <?php foreach ($gigs as $idx => $gig): ?>
                    <?php
                    $sellerName = trim(($gig['first_name'] ?? '') . ' ' . ($gig['last_name'] ?? '')) ?: ($gig['username'] ?? 'User');
                    $gigImage = resolveGigImage($gig['image'] ?? null, '../');
                    $userAvatar = resolveAvatarUrl($gig['profile_picture'] ?? null, '../', $sellerName);
                    $rating = (float)($gig['avg_rating'] ?? 5.0);
                    $reviewsCount = (int)($gig['reviews_count'] ?? 0);
                    ?>
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 overflow-hidden shadow-sm hover:shadow-2xl hover:border-purple-400/50 dark:hover:border-purple-500/40 transition-all duration-300 flex flex-col group transform hover:-translate-y-1.5" data-aos="fade-up" data-aos-delay="<?= ($idx % 3) * 60; ?>">
                        <!-- Image Container with Aspect Ratio -->
                        <div class="relative aspect-[16/9] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                            <img src="<?= htmlspecialchars($gigImage); ?>" alt="<?= htmlspecialchars($gig['title']); ?>"
                                 loading="lazy" decoding="async"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                 onerror="this.src='../assets/img/placeholder-gig.svg';">
                            <span class="absolute top-3 left-3 px-3 py-1 rounded-full text-xs font-bold bg-white/95 dark:bg-slate-900/90 backdrop-blur-md text-slate-800 dark:text-slate-200 shadow-sm border border-white/40 dark:border-slate-700">
                                <?= htmlspecialchars($gig['category_name'] ?? 'Service'); ?>
                            </span>
                            <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-900/80 backdrop-blur-md text-white flex items-center gap-1 shadow-sm">
                                <i class="ri-time-line text-xs"></i> <?= $gig['delivery_time']; ?>d delivery
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 flex flex-col flex-grow">
                            <!-- Seller Info -->
                            <div class="flex items-center gap-3 mb-3">
                                <div class="relative w-8 h-8 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 shadow-xs flex-shrink-0">
                                    <img src="<?= htmlspecialchars($userAvatar); ?>" alt="Seller"
                                         loading="lazy" decoding="async"
                                         class="w-full h-full object-cover"
                                         onerror="this.src='../assets/img/placeholder-avatar.svg';">
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($sellerName); ?></div>
                                    <div class="text-[11px] text-slate-400">@<?= htmlspecialchars($gig['username']); ?></div>
                                </div>
                            </div>

                            <!-- Gig Title -->
                            <a href="gig_detail.php?id=<?= $gig['id']; ?>" class="text-base font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors line-clamp-2 mb-2 leading-snug">
                                <?= htmlspecialchars($gig['title']); ?>
                            </a>

                            <!-- Description Snippet -->
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-4 leading-relaxed">
                                <?= htmlspecialchars($gig['description']); ?>
                            </p>

                            <!-- Ratings Bar -->
                            <div class="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400 mb-5">
                                <div class="flex text-amber-400">
                                    <i class="ri-star-fill"></i>
                                </div>
                                <span class="font-bold text-slate-800 dark:text-slate-200"><?= number_format($rating, 1); ?></span>
                                <span class="text-slate-400">(<?= $reviewsCount; ?> reviews)</span>
                            </div>

                            <!-- Bottom Action / Price Bar -->
                            <div class="mt-auto pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                <div>
                                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Starting at</div>
                                    <div class="text-lg font-black text-slate-900 dark:text-white">$<?= number_format((float)$gig['price'], 2); ?></div>
                                </div>
                                <a href="gig_detail.php?id=<?= $gig['id']; ?>" class="px-4 py-2 rounded-xl bg-purple-50 dark:bg-purple-950/50 hover:bg-purple-600 dark:hover:bg-purple-600 text-purple-700 dark:text-purple-300 hover:text-white font-semibold text-xs transition-all shadow-xs flex items-center gap-1">
                                    <span>View Details</span>
                                    <i class="ri-arrow-right-s-line"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Clean Empty State -->
            <div class="text-center py-16 px-4 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm max-w-lg mx-auto" data-aos="fade-up">
                <div class="w-16 h-16 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-3xl mx-auto mb-4">
                    <i class="ri-search-eye-line"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">No matching services found</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">
                    We couldn't find any active gigs matching your search criteria. Try modifying your keywords, budget, or category filter.
                </p>
                <div class="flex justify-center gap-3">
                    <a href="gig.php" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-semibold transition shadow-sm">
                        Reset All Filters
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true });

        function setBudget(val) {
            document.getElementById('budgetInput').value = val;
            document.getElementById('filterForm').submit();
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterForm').submit();
        }
    </script>
</body>

</html>