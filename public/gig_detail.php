<?php
// Include database connection and session management
include('../config/db.con.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('resolveGigImage') && file_exists(__DIR__ . '/../includes/image_helper.php')) {
    include_once __DIR__ . '/../includes/image_helper.php';
}

// Check if 'id' is provided in the query string
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $gigId = (int)$_GET['id'];

    try {
        // Fetch gig details along with category and seller info
        $sql = "SELECT g.*, u.id AS freelancer_user_id, u.username, u.first_name, u.last_name, u.profile_picture, c.name AS category_name
                FROM gigs g
                JOIN users u ON g.freelancer_id = u.id
                LEFT JOIN categories c ON g.category_id = c.id
                WHERE g.id = :gigId AND g.status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':gigId', $gigId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $gig = $stmt->fetch(PDO::FETCH_ASSOC);

            $title = htmlspecialchars($gig['title']);
            $description = htmlspecialchars($gig['description']);
            $category = htmlspecialchars($gig['category_name'] ?? 'General Service');
            $price = number_format((float)$gig['price'], 2);
            $deliveryTime = (int)($gig['delivery_time'] ?? 3);

            // Resolve gig image & profile picture
            $image = resolveGigImage($gig['image'] ?? null, '../');
            $username = htmlspecialchars($gig['username']);
            $displayName = trim(($gig['first_name'] ?? '') . ' ' . ($gig['last_name'] ?? '')) ?: $username;
            $profilePic = resolveAvatarUrl($gig['profile_picture'] ?? null, '../', $displayName);

            $createdAt = date('F j, Y', strtotime($gig['created_at']));
            $rating = (float)($gig['avg_rating'] ?? 5.0);
            $reviewsCount = (int)($gig['reviews_count'] ?? 0);
        } else {
            $_SESSION['error'] = "Gig not found or is no longer active.";
            header("Location: gig.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching gig details: " . $e->getMessage());
        $_SESSION['error'] = "Error loading gig details.";
        header("Location: gig.php");
        exit;
    }
} else {
    header("Location: gig.php");
    exit;
}

// Fetch gig reviews
$reviews = [];
try {
    $stmtRev = $conn->prepare("
        SELECT r.*, u.first_name, u.last_name, u.username, u.profile_picture
        FROM reviews r
        JOIN users u ON r.client_id = u.id
        WHERE r.gig_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmtRev->execute([$gigId]);
    $reviews = $stmtRev->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading reviews: " . $e->getMessage());
}

// Fetch Similar Gigs in same category
$similarGigs = [];
try {
    $stmtSim = $conn->prepare("
        SELECT g.id, g.title, g.price, g.delivery_time, g.image, g.avg_rating, u.username, u.first_name, u.last_name, c.name as category_name
        FROM gigs g
        JOIN users u ON g.freelancer_id = u.id
        LEFT JOIN categories c ON g.category_id = c.id
        WHERE g.status = 'active' AND g.id != ?
        ORDER BY (g.category_id = ?) DESC, g.avg_rating DESC
        LIMIT 3
    ");
    $stmtSim->execute([$gigId, $gig['category_id'] ?? 0]);
    $similarGigs = $stmtSim->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading similar gigs: " . $e->getMessage());
}

$isClient = isset($_SESSION['role']) && $_SESSION['role'] === 'client';
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title; ?> - FreelanceHub</title>
    <meta name="description" content="<?= htmlspecialchars(substr(strip_tags($description ?? ''), 0, 155)); ?>">
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
    <!-- Header -->
    <?php include('../includes/header.php'); ?>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <!-- Breadcrumb Navigation -->
        <nav class="mb-6 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400" aria-label="Breadcrumb">
            <a href="../index.php" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors flex items-center gap-1">
                <i class="ri-home-4-line"></i> Home
            </a>
            <span>/</span>
            <a href="gig.php" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">Marketplace</a>
            <span>/</span>
            <a href="gig.php?category=<?= (int)($gig['category_id'] ?? 0); ?>" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors"><?= $category; ?></a>
            <span>/</span>
            <span class="text-slate-800 dark:text-slate-200 truncate max-w-xs sm:max-w-md"><?= $title; ?></span>
        </nav>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <i class="ri-checkbox-circle-fill text-xl text-emerald-500"></i>
                    <div class="text-sm font-medium"><?= htmlspecialchars($_SESSION['success']); ?></div>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 p-1"><i class="ri-close-line text-lg"></i></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <i class="ri-error-warning-fill text-xl text-rose-500"></i>
                    <div class="text-sm font-medium"><?= htmlspecialchars($_SESSION['error']); ?></div>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-300 p-1"><i class="ri-close-line text-lg"></i></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Main Gig Layout Grid (2 Columns) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-16">
            <!-- Left Column: Media & Service Scope (8 Cols) -->
            <div class="lg:col-span-8 space-y-8" data-aos="fade-up">
                <!-- Gig Media Card -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="relative aspect-[16/9] w-full bg-slate-950 overflow-hidden">
                        <img src="<?= htmlspecialchars($image); ?>" alt="<?= $title; ?>" class="w-full h-full object-cover opacity-95" onerror="this.src='../assets/img/placeholder-gig.svg';">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>

                        <div class="absolute bottom-6 left-6 right-6 text-white">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-md mb-2 border border-white/30 text-white">
                                <?= $category; ?>
                            </span>
                            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight leading-tight drop-shadow-sm">
                                <?= $title; ?>
                            </h1>
                        </div>
                    </div>

                    <!-- Seller Snapshot Bar -->
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-800/40">
                        <div class="flex items-center gap-3.5">
                            <div class="relative w-12 h-12 rounded-full overflow-hidden border-2 border-white dark:border-slate-700 shadow-xs flex-shrink-0">
                                <img src="<?= htmlspecialchars($profilePic); ?>" alt="<?= htmlspecialchars($displayName); ?>" class="w-full h-full object-cover" onerror="this.src='../assets/img/placeholder-avatar.svg';">
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-sm leading-tight"><?= htmlspecialchars($displayName); ?></h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">@<?= $username; ?> &bull; Verified Specialist</p>
                                <div class="flex items-center gap-1 text-amber-500 text-xs font-bold mt-0.5">
                                    <i class="ri-star-fill"></i>
                                    <span><?= number_format($rating, 1); ?></span>
                                    <span class="text-slate-400 font-normal">(<?= $reviewsCount; ?> reviews)</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                            <div class="flex items-center gap-1.5"><i class="ri-calendar-line text-slate-400"></i> Posted <?= $createdAt; ?></div>
                            <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold"><i class="ri-shield-check-fill text-emerald-500"></i> Escrow Guaranteed</div>
                        </div>
                    </div>

                    <!-- Service Description Body -->
                    <div class="p-6 sm:p-8">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                            <i class="ri-file-text-line text-purple-600 dark:text-purple-400 text-lg"></i>
                            <span>Detailed Deliverables & Description</span>
                        </h2>
                        <div class="text-slate-700 dark:text-slate-300 leading-relaxed text-sm whitespace-pre-line bg-slate-50/70 dark:bg-slate-800/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                            <?= $description; ?>
                        </div>
                    </div>
                </div>

                <!-- Client Reviews Section -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="ri-chat-voice-line text-purple-600 dark:text-purple-400"></i>
                            <span>Client Reviews (<?= count($reviews); ?>)</span>
                        </h3>
                        <div class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400 text-sm font-bold bg-amber-50 dark:bg-amber-950/40 border border-amber-200/60 dark:border-amber-800/40 px-3 py-1 rounded-full">
                            <i class="ri-star-fill text-amber-400"></i>
                            <span><?= number_format($rating, 1); ?> / 5.0 Rating</span>
                        </div>
                    </div>

                    <?php if (!empty($reviews)): ?>
                        <div class="space-y-4">
                            <?php foreach ($reviews as $rev): ?>
                                <?php
                                $revName = trim(($rev['first_name'] ?? '') . ' ' . ($rev['last_name'] ?? '')) ?: $rev['username'];
                                $revPic = resolveAvatarUrl($rev['profile_picture'] ?? null, '../', $revName);
                                ?>
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 shadow-2xs">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 flex-shrink-0">
                                                <img src="<?= htmlspecialchars($revPic); ?>" alt="<?= htmlspecialchars($revName); ?>" loading="lazy" decoding="async" class="w-full h-full object-cover" onerror="this.src='../assets/img/placeholder-avatar.svg';">
                                            </div>
                                            <div>
                                                <h5 class="text-xs font-bold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($revName); ?></h5>
                                                <span class="text-[10px] text-slate-400"><?= date('M d, Y', strtotime($rev['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center text-amber-400 text-xs">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="<?= $i <= (int)$rev['rating'] ? 'ri-star-fill' : 'ri-star-line text-slate-300 dark:text-slate-600'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed pl-10">
                                        <?= nl2br(htmlspecialchars($rev['comment'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200/60 dark:border-slate-700/60 p-4">
                            <i class="ri-chat-smile-2-line text-3xl text-slate-300 dark:text-slate-600 mb-1 block"></i>
                            <p class="text-xs text-slate-500 dark:text-slate-400">No client reviews submitted yet for this service.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Sticky Pricing & Action Sidebar (4 Cols) -->
            <div class="lg:col-span-4" data-aos="fade-up" data-aos-delay="100">
                <div class="sticky top-28 bg-white dark:bg-slate-900 p-6 sm:p-7 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl shadow-purple-950/5 dark:shadow-black/50 space-y-6">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-950/60 px-3 py-1 rounded-full border border-purple-200/60 dark:border-purple-800/60">Standard Package</span>
                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1"><i class="ri-shield-check-fill"></i> Guaranteed</span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-black text-slate-900 dark:text-white">$<?= $price; ?></span>
                            <span class="text-xs text-slate-400 font-medium">USD fixed price</span>
                        </div>
                    </div>

                    <!-- Package Features Checklist -->
                    <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800 text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                        <div class="flex items-center gap-2.5">
                            <i class="ri-time-line text-purple-600 dark:text-purple-400 text-base"></i>
                            <span><strong><?= $deliveryTime; ?> Days</strong> Delivery Timeline</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i class="ri-refresh-line text-indigo-600 dark:text-indigo-400 text-base"></i>
                            <span>Milestone Revisions Included</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i class="ri-shield-check-line text-emerald-600 dark:text-emerald-400 text-base"></i>
                            <span>Escrow Protected Payment</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i class="ri-message-3-line text-cyan-600 dark:text-cyan-400 text-base"></i>
                            <span>Direct In-App Messaging</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <?php if ($isClient): ?>
                            <!-- Trigger Order Confirmation Modal -->
                            <button type="button" onclick="openOrderModal()" class="w-full py-3.5 px-6 bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-2xl font-bold text-sm shadow-md shadow-purple-500/20 hover:scale-[1.02] active:scale-[0.98] transition flex items-center justify-center gap-2 cursor-pointer">
                                <i class="ri-shopping-bag-3-line text-base"></i>
                                <span>Order Service Now ($<?= $price; ?>)</span>
                            </button>

                            <a href="../client/hire_freelancer.php?freelancer_id=<?= $gig['freelancer_id']; ?>" class="w-full py-3 px-6 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-2xl font-semibold text-xs border border-slate-200 dark:border-slate-700 transition flex items-center justify-center gap-1.5">
                                <i class="ri-file-edit-line"></i>
                                <span>Propose Custom Project Contract</span>
                            </a>
                        <?php elseif (!$isLoggedIn): ?>
                            <a href="../auth/login.php" class="w-full py-3.5 px-6 bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-2xl font-bold text-sm shadow-md shadow-purple-500/20 hover:scale-[1.02] active:scale-[0.98] transition flex items-center justify-center gap-2">
                                <i class="ri-login-box-line text-base"></i>
                                <span>Sign In to Order</span>
                            </a>
                            <p class="text-[11px] text-center text-slate-400">Client account required to book gigs</p>
                        <?php else: ?>
                            <div class="p-4 bg-purple-50 dark:bg-purple-950/40 rounded-2xl border border-purple-100 dark:border-purple-800 text-center">
                                <i class="ri-information-line text-purple-600 dark:text-purple-400 text-lg mb-1 block"></i>
                                <p class="text-xs text-purple-800 dark:text-purple-300 font-bold">Freelancer Account Active</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Switch or sign into a client account to order marketplace services.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Similar Gigs in Marketplace -->
        <?php if (!empty($similarGigs)): ?>
            <div class="pt-8 border-t border-slate-200/80 dark:border-slate-800 mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">Similar Services You Might Like</h3>
                    <a href="gig.php?category=<?= (int)($gig['category_id'] ?? 0); ?>" class="text-xs font-semibold text-purple-600 dark:text-purple-400 hover:underline">
                        View more in <?= $category; ?> &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($similarGigs as $sg): ?>
                        <?php
                        $sgImg = resolveGigImage($sg['image'] ?? null, '../');
                        $sgName = trim(($sg['first_name'] ?? '') . ' ' . ($sg['last_name'] ?? '')) ?: $sg['username'];
                        ?>
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 overflow-hidden shadow-sm hover:shadow-xl hover:border-purple-400/50 dark:hover:border-purple-500/40 transition-all flex flex-col group transform hover:-translate-y-1">
                            <div class="relative aspect-[16/9] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                                <img src="<?= htmlspecialchars($sgImg); ?>" alt="<?= htmlspecialchars($sg['title']); ?>" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" onerror="this.src='../assets/img/placeholder-gig.svg';">
                                <span class="absolute top-2.5 left-2.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/95 dark:bg-slate-900/90 text-slate-800 dark:text-slate-200 shadow-2xs">
                                    <?= htmlspecialchars($sg['category_name'] ?? 'Service'); ?>
                                </span>
                            </div>
                            <div class="p-5 flex flex-col flex-grow">
                                <div class="text-[11px] text-slate-400 font-medium mb-1">By <?= htmlspecialchars($sgName); ?></div>
                                <a href="gig_detail.php?id=<?= $sg['id']; ?>" class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors line-clamp-2 mb-3">
                                    <?= htmlspecialchars($sg['title']); ?>
                                </a>
                                <div class="mt-auto pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                    <span class="font-black text-slate-900 dark:text-white">$<?= number_format((float)$sg['price'], 2); ?></span>
                                    <a href="gig_detail.php?id=<?= $sg['id']; ?>" class="font-bold text-purple-600 dark:text-purple-400 hover:underline">View &rarr;</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Interactive Order Confirmation Modal -->
    <div id="orderModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm hidden animate-fadeIn p-4">
        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 dark:border-slate-800 transform transition-all">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl">
                        <i class="ri-shopping-cart-line"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-base">Review & Confirm Order</h4>
                        <p class="text-xs text-slate-400">Milestone escrow guarantee</p>
                    </div>
                </div>
                <button type="button" onclick="closeOrderModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1 cursor-pointer">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <!-- Order Summary Details -->
            <div class="space-y-3 mb-6 bg-slate-50 dark:bg-slate-800/60 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/60 text-xs sm:text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-slate-400">Service:</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200 text-right max-w-xs truncate"><?= $title; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-slate-400">Freelancer:</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($displayName); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 dark:text-slate-400">Estimated Delivery:</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200"><?= $deliveryTime; ?> business days</span>
                </div>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex justify-between text-base font-black text-slate-900 dark:text-white">
                    <span>Total Amount:</span>
                    <span class="text-emerald-600 dark:text-emerald-400">$<?= $price; ?></span>
                </div>
            </div>

            <p class="text-[11px] text-slate-400 mb-6 leading-relaxed">
                By placing this order, funds will be reserved in escrow. The freelancer will receive payment only once you review and accept the final deliverables.
            </p>

            <form method="POST" action="hire_gig.php" class="flex items-center justify-end gap-3">
                <input type="hidden" name="gig_id" value="<?= $gigId; ?>">
                <input type="hidden" name="freelancer_id" value="<?= $gig['freelancer_id']; ?>">
                <button type="button" onclick="closeOrderModal()" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-bold shadow-md shadow-purple-500/20 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="ri-check-line"></i> Confirm & Place Order
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true });

        function openOrderModal() {
            document.getElementById('orderModal').classList.remove('hidden');
        }

        function closeOrderModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }
    </script>
</body>

</html>