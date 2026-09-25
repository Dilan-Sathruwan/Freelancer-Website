<?php
// Include database connection and session management
include('../config/db.con.php');
include_once('../includes/image_helper.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if 'id' is provided in the query string
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $gigId = (int)$_GET['id'];

    try {
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
            
            $username = htmlspecialchars($gig['username']);
            $displayName = trim(($gig['first_name'] ?? '') . ' ' . ($gig['last_name'] ?? '')) ?: $username;
            
            $image = resolveGigImage($gig['image'] ?? null, '../');
            $profilePic = resolveAvatarUrl($gig['profile_picture'] ?? null, '../', $displayName);

            $createdAt = date('F j, Y', strtotime($gig['created_at']));
        } else {
            $_SESSION['error'] = "Gig not found or is currently inactive.";
            header("Location: dashboard.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching gig details: " . $e->getMessage());
        $_SESSION['error'] = "Error loading gig details.";
        header("Location: dashboard.php");
        exit;
    }
} else {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title; ?> - FreelanceHub</title>

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

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

<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans min-h-screen flex flex-col pt-20 transition-colors duration-300 relative selection:bg-purple-500 selection:text-white">
    <!-- Ambient Background Glow -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-500/10 dark:bg-purple-600/15 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 -left-40 w-96 h-96 bg-indigo-500/10 dark:bg-indigo-600/15 rounded-full blur-3xl"></div>
    </div>

    <!-- Header -->
    <?php include('../includes/header.php'); ?>

    <main class="relative z-10 flex-grow max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <!-- Back Navigation -->
        <div class="mb-6">
            <a href="dashboard.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition">
                <i class="ri-arrow-left-line text-base"></i>
                <span>Back to Client Dashboard</span>
            </a>
        </div>

        <div class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl overflow-hidden">
            <!-- Cover Banner -->
            <div class="relative h-72 sm:h-96 w-full bg-slate-900 overflow-hidden">
                <img src="<?= htmlspecialchars($image); ?>" alt="<?= $title; ?>"
                     class="w-full h-full object-cover opacity-90"
                     onerror="this.src='../assets/img/placeholder-gig.svg';">
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

                <div class="absolute bottom-6 left-6 right-6 text-white">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-white/20 backdrop-blur-md mb-2 border border-white/20">
                        <?= $category; ?>
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        <?= $title; ?>
                    </h1>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="p-6 sm:p-8 lg:p-10">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="flex items-center gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                            <img src="<?= htmlspecialchars($profilePic); ?>" alt="<?= htmlspecialchars($displayName); ?>"
                                 class="w-12 h-12 rounded-full object-cover border border-purple-500/20"
                                 onerror="this.src='../assets/img/placeholder-avatar.svg';">
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($displayName); ?></h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500">@<?= $username; ?> &bull; Posted <?= $createdAt; ?></p>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Service Description</h3>
                            <div class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-line bg-slate-50/50 dark:bg-slate-800/40 p-5 rounded-2xl border border-slate-100 dark:border-slate-800">
                                <?= $description; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Package Summary -->
                    <div class="lg:col-span-1">
                        <div class="bg-slate-50 dark:bg-slate-800/60 p-6 rounded-3xl border border-slate-200 dark:border-slate-700/80 space-y-5">
                            <div>
                                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Service Price</span>
                                <span class="text-3xl font-extrabold text-slate-900 dark:text-white mt-1 block">$<?= $price; ?></span>
                            </div>

                            <div class="text-xs text-slate-600 dark:text-slate-300 space-y-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-2">
                                    <i class="ri-time-line text-purple-600 dark:text-purple-400"></i>
                                    <span><?= $deliveryTime; ?> Days Delivery Time</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="ri-shield-check-line text-emerald-600 dark:text-emerald-400"></i>
                                    <span>Escrow Protection Included</span>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-200 dark:border-slate-700 space-y-2">
                                <a href="hire_freelancer.php?freelancer_id=<?= $gig['freelancer_user_id']; ?>"
                                   class="block w-full py-3 px-4 bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold text-center shadow-md shadow-purple-500/20 transition">
                                    <i class="ri-briefcase-line mr-1"></i> Hire for Custom Project
                                </a>
                                <a href="../public/gig_detail.php?id=<?= $gigId; ?>"
                                   class="block w-full py-2.5 px-4 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold text-center border border-slate-200 dark:border-slate-700 transition">
                                    View Public Listing
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>
</body>

</html>