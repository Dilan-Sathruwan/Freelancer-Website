<?php
// Include necessary files for connection and session
include('../config/db.con.php');
include_once('../includes/image_helper.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in as client
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    $_SESSION['error'] = "Please log in as a client to hire freelancers.";
    header('Location: ../auth/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Get freelancer's ID (from query string)
$freelancerId = isset($_GET['freelancer_id']) ? (int)$_GET['freelancer_id'] : 0;

if ($freelancerId <= 0) {
    $_SESSION['error'] = "Invalid freelancer selected.";
    header('Location: ../public/gig.php');
    exit;
}

// Fetch freelancer details
$sql = "
    SELECT u.id AS user_id, u.username, u.first_name, u.last_name, u.email, u.profile_picture,
           COALESCE(f.skills, fp.title, 'Full Stack Freelancer') AS skills,
           COALESCE(f.hourly_rate, fp.hourly_rate, 25.00) AS hourly_rate,
           COALESCE(f.location, 'Remote') AS location,
           COALESCE(f.rating, 5.00) AS rating,
           COALESCE(f.bio, u.bio, 'Professional freelancer dedicated to delivering top quality results.') AS bio
    FROM users u
    LEFT JOIN freelancers f ON f.user_id = u.id
    LEFT JOIN freelancer_profiles fp ON fp.user_id = u.id
    WHERE u.id = ? OR f.id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->execute([$freelancerId, $freelancerId]);
$freelancer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$freelancer) {
    $_SESSION['error'] = "Freelancer profile could not be found.";
    header('Location: ../public/gig.php');
    exit;
}

$displayName = trim(($freelancer['first_name'] ?? '') . ' ' . ($freelancer['last_name'] ?? '')) ?: ($freelancer['username'] ?? 'Freelancer');
$profilePic = resolveAvatarUrl($freelancer['profile_picture'] ?? null, '../', $displayName);
$rating = (float)($freelancer['rating'] ?? 5.0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hire <?= htmlspecialchars($displayName); ?> - FreelanceHub</title>
    <meta name="description" content="Send a direct job proposal and hire <?= htmlspecialchars($displayName); ?> with escrow protection.">
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">


    <!-- Remix Icons -->
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

<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans min-h-screen flex flex-col pt-20 transition-colors duration-300 relative overflow-x-hidden">
    <!-- Ambient Background Blobs -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden opacity-30">
        <div class="absolute -top-32 left-1/4 w-[32rem] h-[32rem] bg-purple-500/20 dark:bg-purple-600/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 right-10 w-[28rem] h-[28rem] bg-indigo-500/15 dark:bg-indigo-600/15 rounded-full blur-3xl"></div>
    </div>

    <!-- Header -->
    <?php include('../includes/header.php'); ?>

    <main class="flex-grow max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full relative z-10">
        <!-- Back Navigation -->
        <div class="mb-6">
            <a href="dashboard.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition">
                <i class="ri-arrow-left-line text-base"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>

        <!-- Page Header -->
        <div class="mb-8" data-aos="fade-up">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 dark:bg-purple-950/70 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60 mb-2">
                <i class="ri-briefcase-line"></i> Direct Hire Proposal
            </span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                Propose a Custom Project to <?= htmlspecialchars($displayName); ?>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Define your project scope, budget, and timeline. The freelancer will review and accept your offer.
            </p>
        </div>

        <!-- Two Column Form Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Freelancer Snapshot -->
            <div class="lg:col-span-1" data-aos="fade-up" data-aos-delay="50">
                <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl p-6 sm:p-7 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm text-center sticky top-28">
                    <img src="<?= htmlspecialchars($profilePic); ?>" alt="<?= htmlspecialchars($displayName); ?>"
                         class="w-24 h-24 rounded-full object-cover mx-auto mb-4 border-4 border-purple-100 dark:border-purple-900/60 shadow-md"
                         onerror="this.src='../assets/img/placeholder-avatar.svg';">
                    
                    <h3 class="font-bold text-slate-900 dark:text-white text-lg"><?= htmlspecialchars($displayName); ?></h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mb-3">@<?= htmlspecialchars($freelancer['username']); ?></p>

                    <!-- Rating -->
                    <div class="flex items-center justify-center gap-1 text-amber-400 text-sm font-bold mb-4">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="<?= $i <= round($rating) ? 'ri-star-fill text-amber-400' : 'ri-star-line text-slate-200 dark:text-slate-700'; ?>"></i>
                        <?php endfor; ?>
                        <span class="text-slate-700 dark:text-slate-300 text-xs ml-1">(<?= number_format($rating, 1); ?>)</span>
                    </div>

                    <div class="space-y-3 text-left text-xs text-slate-600 dark:text-slate-400 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 dark:text-slate-500 font-medium">Hourly Rate:</span>
                            <span class="font-bold text-slate-900 dark:text-white text-sm">$<?= number_format((float)$freelancer['hourly_rate'], 2); ?>/hr</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 dark:text-slate-500 font-medium">Location:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($freelancer['location']); ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 dark:text-slate-500 font-medium block mb-1">Expertise:</span>
                            <span class="inline-block px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-medium border border-slate-200/60 dark:border-slate-700">
                                <?= htmlspecialchars($freelancer['skills']); ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($freelancer['bio'])): ?>
                        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 text-left">
                            <p class="text-[11px] uppercase tracking-wider text-slate-400 dark:text-slate-500 font-semibold mb-1">About</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-4">
                                <?= nl2br(htmlspecialchars($freelancer['bio'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Proposal Form -->
            <div class="lg:col-span-2" data-aos="fade-up" data-aos-delay="100">
                <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl p-6 sm:p-8 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="ri-file-edit-line text-purple-600 dark:text-purple-400"></i>
                        <span>Project Specifications</span>
                    </h3>

                    <form action="hire_freelancer_action.php" method="POST" class="space-y-6">
                        <!-- Project Title -->
                        <div>
                            <label for="projectTitle" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Project Title <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="projectTitle" name="projectTitle" required
                                   placeholder="e.g., Redesign Brand Identity and Landing Page"
                                   class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm font-medium transition">
                        </div>

                        <!-- Project Description -->
                        <div>
                            <label for="projectDescription" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Scope of Work & Deliverables <span class="text-rose-500">*</span>
                            </label>
                            <textarea id="projectDescription" name="projectDescription" rows="5" required
                                      placeholder="Describe your requirements, goals, preferred stack/tools, and key milestones in detail..."
                                      class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm font-medium transition"></textarea>
                        </div>

                        <!-- Budget & Deadline Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="budget" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                    Total Budget (USD $) <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 font-bold">$</span>
                                    <input type="number" step="0.01" min="5" id="budget" name="budget" required
                                           placeholder="150.00"
                                           class="w-full pl-8 pr-4 py-3 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm font-semibold transition">
                                </div>
                            </div>

                            <div>
                                <label for="deadline" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                    Target Completion Date <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" id="deadline" name="deadline" min="<?= date('Y-m-d'); ?>" required
                                       class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm font-medium transition cursor-pointer">
                            </div>
                        </div>

                        <input type="hidden" name="clientId" value="<?= $userId; ?>">
                        <input type="hidden" name="freelancerId" value="<?= $freelancer['user_id']; ?>">

                        <!-- Submit Button -->
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-2xl font-bold text-sm shadow-xl shadow-purple-500/20 hover:shadow-purple-500/30 transition transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2 cursor-pointer">
                                <i class="ri-send-plane-fill text-base"></i>
                                <span>Send Project Proposal</span>
                            </button>
                            <p class="text-center text-[11px] text-slate-400 dark:text-slate-500 mt-2.5">
                                Funds are not deducted until the freelancer accepts and work officially begins.
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>
</body>

</html>
