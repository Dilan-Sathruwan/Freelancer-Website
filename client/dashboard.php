<?php
include_once "../config/db.con.php";
include_once "../includes/image_helper.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to ensure user is logged in as client
function check_logged_in()
{
    $userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
    $role = $_SESSION['role'] ?? null;
    if (!$userId || $role !== 'client') {
        header("Location: ../auth/login.php");
        exit;
    }
}

check_logged_in();

$userId = (int)($_SESSION['user_id'] ?? $_SESSION['id']); 
$userName = $_SESSION['username'] ?? 'Client'; 

// Fetch user data
try {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = "User account not found.";
        header("Location: ../index.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching client: " . $e->getMessage());
    $_SESSION['error'] = "Error loading account data.";
    header("Location: ../index.php");
    exit;
}

// Cancel Pending Gig Request handler
if (isset($_GET['cancelRequest'])) {
    $cancelId = validateInteger($_GET['cancelRequest']);
    if ($cancelId) {
        try {
            $stmt = $conn->prepare("UPDATE job_requests SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND client_id = ? AND status = 'pending'");
            $stmt->execute([$cancelId, $userId]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "Order request cancelled successfully.";
            } else {
                $_SESSION['error'] = "Unable to cancel request. Only pending orders can be cancelled.";
            }
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error cancelling request: " . $e->getMessage());
            $_SESSION['error'] = "Error cancelling request.";
        }
    }
}

// Cancel Pending Custom Project Proposal handler
if (isset($_GET['cancelProject'])) {
    $cancelProjId = validateInteger($_GET['cancelProject']);
    if ($cancelProjId) {
        try {
            $stmt = $conn->prepare("UPDATE projects SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND client_id = ? AND status = 'pending'");
            $stmt->execute([$cancelProjId, $userId]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "Project proposal cancelled successfully.";
            } else {
                $_SESSION['error'] = "Unable to cancel project. Only pending proposals can be cancelled.";
            }
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error cancelling project: " . $e->getMessage());
            $_SESSION['error'] = "Error cancelling project proposal.";
        }
    }
}

// Fetch client wallet balance
$walletBalance = 0.00;
$walletTotalSpent = 0.00;
try {
    $stmtWallet = $conn->prepare("SELECT balance, total_spent FROM wallets WHERE user_id = ?");
    $stmtWallet->execute([$userId]);
    $wallet = $stmtWallet->fetch(PDO::FETCH_ASSOC);
    if ($wallet) {
        $walletBalance = (float)$wallet['balance'];
        $walletTotalSpent = (float)($wallet['total_spent'] ?? 0);
    } else {
        $stmtCreateWallet = $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)");
        $stmtCreateWallet->execute([$userId]);
    }
} catch (PDOException $e) {
    error_log("Error fetching wallet: " . $e->getMessage());
}

// Fetch job requests & gig orders
$gigs = [];
try {
    $sql = "
        SELECT jr.id AS request_id, jr.status AS request_status, jr.request_date,
               jr.freelancer_id AS raw_freelancer_id,
               g.id AS gig_id, g.title, g.description, g.price, g.image,
               u.id AS freelancer_id, u.first_name, u.last_name, u.username AS freelancer_username, u.profile_picture AS freelancer_pic,
               (SELECT r.id FROM reviews r WHERE r.client_id = jr.client_id AND (r.order_id = jr.id OR r.gig_id = g.id) LIMIT 1) AS review_id
        FROM job_requests jr
        LEFT JOIN gigs g ON jr.gig_id = g.id
        LEFT JOIN users u ON jr.freelancer_id = u.id
        WHERE jr.client_id = ?
        ORDER BY jr.request_date DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    $gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching gigs: " . $e->getMessage());
}

// Fetch custom projects posted by client
$projects = [];
try {
    $sqlProjects = "
        SELECT p.*, u.first_name, u.last_name, u.username AS freelancer_username, u.profile_picture AS freelancer_pic,
               (SELECT r.id FROM reviews r WHERE r.client_id = p.client_id AND r.project_id = p.id LIMIT 1) AS review_id
        FROM projects p
        LEFT JOIN users u ON p.freelancer_id = u.id
        WHERE p.client_id = ?
        ORDER BY p.created_at DESC
    ";
    $stmtProjects = $conn->prepare($sqlProjects);
    $stmtProjects->execute([$userId]);
    $projects = $stmtProjects->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching projects: " . $e->getMessage());
}

// Compute metrics
$activeCount = 0;
$completedCount = 0;
$calculatedSpent = 0.00;

foreach ($gigs as $g) {
    $st = strtolower($g['request_status'] ?? '');
    if (in_array($st, ['pending', 'accepted', 'in_progress'])) {
        $activeCount++;
    } elseif ($st === 'completed') {
        $completedCount++;
        $calculatedSpent += (float)($g['price'] ?? 0);
    }
}

foreach ($projects as $p) {
    $pst = strtolower($p['status'] ?? '');
    if (in_array($pst, ['pending', 'accepted', 'in_progress'])) {
        $activeCount++;
    } elseif ($pst === 'completed') {
        $completedCount++;
        $calculatedSpent += (float)($p['budget'] ?? 0);
    }
}

$displaySpent = max($walletTotalSpent, $calculatedSpent);

// Normalize user avatar
$clientDisplayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $userName;
$clientPic = resolveAvatarUrl($user['profile_picture'] ?? null, '../', $clientDisplayName);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal & Dashboard - FreelanceHub</title>
    <meta name="description" content="Client Dashboard - Track active hires, orders, deliverables, and wallet escrow.">
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

    <!-- Top Navigation Header -->
    <?php include('../includes/header.php'); ?>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full relative z-10">
        <!-- Flash Message Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 flex items-center justify-between shadow-sm animate-fade-in" role="alert">
                <div class="flex items-center gap-3">
                    <i class="ri-checkbox-circle-fill text-xl text-emerald-500 dark:text-emerald-400"></i>
                    <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['success']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 text-lg cursor-pointer transition"><i class="ri-close-line"></i></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 flex items-center justify-between shadow-sm animate-fade-in" role="alert">
                <div class="flex items-center gap-3">
                    <i class="ri-error-warning-fill text-xl text-rose-500"></i>
                    <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['error']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-300 text-lg cursor-pointer transition"><i class="ri-close-line"></i></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Welcome Banner -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5 mb-8 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl p-6 sm:p-8 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm" data-aos="fade-up">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 dark:bg-purple-950/70 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60 inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                        Client Workspace
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">@<?= htmlspecialchars($user['username']); ?></span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                    Welcome back, <?= htmlspecialchars($user['first_name'] ?: $userName); ?>! 👋
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Monitor your ongoing hires, custom proposals, and escrow funds in real time.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="../public/gig.php" class="px-5 py-3 bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-2xl text-sm font-bold shadow-md shadow-purple-500/20 hover:shadow-lg transition-all flex items-center gap-2 transform active:scale-95">
                    <i class="ri-search-line"></i>
                    <span>Explore Marketplace</span>
                </a>
                <a href="../public/profile.php" class="px-4 py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-2xl text-sm font-semibold border border-slate-200 dark:border-slate-700 transition flex items-center gap-2" title="Profile Settings">
                    <i class="ri-user-settings-line"></i>
                    <span>Profile</span>
                </a>
            </div>
        </div>

        <!-- 4 Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8" data-aos="fade-up" data-aos-delay="50">
            <!-- Wallet Balance -->
            <div class="bg-white dark:bg-slate-900/90 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-800/80 transition backdrop-blur-xl group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Wallet Balance</p>
                        <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">$<?= number_format($walletBalance, 2); ?></h3>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Available Escrow</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="ri-wallet-3-line"></i>
                    </div>
                </div>
            </div>

            <!-- Active Hires -->
            <div class="bg-white dark:bg-slate-900/90 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-purple-300 dark:hover:border-purple-800/80 transition backdrop-blur-xl group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Active Hires</p>
                        <h3 class="text-2xl font-extrabold text-purple-600 dark:text-purple-400 mt-1"><?= $activeCount; ?></h3>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">In Progress & Pending</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="ri-time-line"></i>
                    </div>
                </div>
            </div>

            <!-- Completed Jobs -->
            <div class="bg-white dark:bg-slate-900/90 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-blue-300 dark:hover:border-blue-800/80 transition backdrop-blur-xl group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Completed</p>
                        <h3 class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-1"><?= $completedCount; ?></h3>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Successfully Delivered</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="ri-checkbox-circle-line"></i>
                    </div>
                </div>
            </div>

            <!-- Total Spent -->
            <div class="bg-white dark:bg-slate-900/90 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-amber-300 dark:hover:border-amber-800/80 transition backdrop-blur-xl group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Invested</p>
                        <h3 class="text-2xl font-extrabold text-amber-500 dark:text-amber-400 mt-1">$<?= number_format($displaySpent, 2); ?></h3>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Lifetime Platform Total</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="ri-money-dollar-circle-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Layout: 2 Columns (Sidebar + Tables) -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Left Sidebar: Profile & Safety -->
            <div class="lg:col-span-1 space-y-6" data-aos="fade-up" data-aos-delay="100">
                <!-- User Profile Card -->
                <div class="bg-white dark:bg-slate-900/90 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm text-center backdrop-blur-xl">
                    <img src="<?= htmlspecialchars($clientPic); ?>" alt="<?= htmlspecialchars($clientDisplayName); ?>"
                         class="w-20 h-20 rounded-full object-cover mx-auto mb-3 border-2 border-purple-500/20 shadow-md"
                         onerror="this.src='../assets/img/placeholder-avatar.svg';">
                    <h3 class="font-bold text-slate-900 dark:text-white text-base"><?= htmlspecialchars($clientDisplayName); ?></h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500">@<?= htmlspecialchars($user['username']); ?></p>

                    <div class="mt-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60 rounded-full text-xs font-semibold">
                            <i class="ri-user-star-line text-purple-600 dark:text-purple-400"></i>
                            <span>Verified Client</span>
                        </span>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 text-left text-xs text-slate-500 dark:text-slate-400 space-y-2.5">
                        <div class="flex items-center gap-2">
                            <i class="ri-mail-line text-slate-400 dark:text-slate-500"></i>
                            <span class="truncate"><?= htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="ri-calendar-line text-slate-400 dark:text-slate-500"></i>
                            <span>Member since <?= date('M Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>

                    <div class="mt-5">
                        <a href="../public/profile.php" class="block w-full py-2.5 px-4 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold border border-slate-200/80 dark:border-slate-700 transition text-center">
                            <i class="ri-edit-line mr-1"></i> Edit Profile & Settings
                        </a>
                    </div>
                </div>

                <!-- Trust Guarantee Card -->
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-950/40 dark:to-indigo-950/40 p-6 rounded-3xl border border-purple-100 dark:border-purple-900/50 backdrop-blur-xl">
                    <div class="w-10 h-10 rounded-xl bg-purple-600 dark:bg-purple-500 text-white flex items-center justify-center text-xl mb-3 shadow-md shadow-purple-500/20">
                        <i class="ri-shield-check-line"></i>
                    </div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm mb-1">Escrow Protection Guarantee</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Funds are safely reserved in platform escrow and only released to the freelancer when you are completely satisfied with the deliverables.
                    </p>
                </div>
            </div>

            <!-- Right Main Area: Tabbed Orders & Proposals -->
            <div class="lg:col-span-3 space-y-6" data-aos="fade-up" data-aos-delay="150">
                <div class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl overflow-hidden">
                    <!-- Tab Switcher Bar -->
                    <div class="flex border-b border-slate-100 dark:border-slate-800 p-2 sm:p-3 bg-slate-50/50 dark:bg-slate-800/40 gap-2">
                        <button id="tab-btn-gigs" onclick="switchTab('gigs')"
                                class="flex-1 py-3 px-4 rounded-2xl text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 bg-white dark:bg-slate-800 text-purple-600 dark:text-purple-400 shadow-sm border border-slate-200/80 dark:border-slate-700 cursor-pointer">
                            <i class="ri-shopping-bag-3-line text-base"></i>
                            <span>Marketplace Orders (<?= count($gigs); ?>)</span>
                        </button>
                        <button id="tab-btn-projects" onclick="switchTab('projects')"
                                class="flex-1 py-3 px-4 rounded-2xl text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 cursor-pointer">
                            <i class="ri-file-list-3-line text-base"></i>
                            <span>Custom Proposals (<?= count($projects); ?>)</span>
                        </button>
                    </div>

                    <!-- Tab 1: Hired Gigs Table -->
                    <div id="tab-content-gigs" class="p-0">
                        <?php if (!empty($gigs)): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs sm:text-sm">
                                    <thead class="bg-slate-50/80 dark:bg-slate-800/70 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800 uppercase text-[11px] tracking-wider">
                                        <tr>
                                            <th class="py-3.5 px-5">Service / Gig</th>
                                            <th class="py-3.5 px-4">Freelancer</th>
                                            <th class="py-3.5 px-4">Price</th>
                                            <th class="py-3.5 px-4">Status</th>
                                            <th class="py-3.5 px-5 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                        <?php foreach ($gigs as $g): ?>
                                            <?php
                                                $status = strtolower($g['request_status'] ?? 'pending');
                                                $statusBadge = match($status) {
                                                    'accepted', 'in_progress' => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60',
                                                    'completed' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60',
                                                    'rejected', 'cancelled' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60',
                                                    default => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60',
                                                };
                                                $fName = trim(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? '')) ?: ($g['freelancer_username'] ?? 'Freelancer');
                                                $fAvatar = resolveAvatarUrl($g['freelancer_pic'] ?? null, '../', $fName);
                                                $gigCover = resolveGigImage($g['image'] ?? null, '../');
                                            ?>
                                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                                <td class="py-4 px-5">
                                                    <div class="flex items-center gap-3">
                                                        <img src="<?= htmlspecialchars($gigCover); ?>" alt="Gig" class="w-11 h-11 rounded-xl object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0" onerror="this.src='../assets/img/placeholder-gig.svg'">
                                                        <div class="min-w-0">
                                                            <div class="font-bold text-slate-900 dark:text-white line-clamp-1"><?= htmlspecialchars($g['title'] ?? 'Custom Gig Order'); ?></div>
                                                            <span class="text-[11px] text-slate-400 dark:text-slate-500"><?= !empty($g['request_date']) ? date('M d, Y', strtotime($g['request_date'])) : 'Recent'; ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-4 whitespace-nowrap">
                                                    <div class="flex items-center gap-2.5">
                                                        <img src="<?= htmlspecialchars($fAvatar); ?>" alt="<?= htmlspecialchars($fName); ?>" class="w-7 h-7 rounded-full object-cover border border-slate-200 dark:border-slate-700" onerror="this.src='../assets/img/placeholder-avatar.svg'">
                                                        <span class="font-medium text-slate-700 dark:text-slate-300"><?= htmlspecialchars($fName); ?></span>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-4 whitespace-nowrap font-bold text-slate-900 dark:text-white">
                                                    $<?= number_format((float)($g['price'] ?? 0), 2); ?>
                                                </td>
                                                <td class="py-4 px-4 whitespace-nowrap">
                                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border <?= $statusBadge; ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $status)); ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-5 whitespace-nowrap text-right">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <?php if (!empty($g['gig_id'])): ?>
                                                            <a href="../public/gig_detail.php?id=<?= $g['gig_id']; ?>" target="_blank" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                                                                View
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if ($status === 'completed'): ?>
                                                            <a href="leave_review.php?freelancer_id=<?= $g['freelancer_id']; ?>&gig_id=<?= $g['gig_id']; ?>" class="px-3 py-1.5 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-300 rounded-xl text-xs font-bold border border-amber-200 dark:border-amber-800 transition flex items-center gap-1">
                                                                <i class="ri-star-line"></i>
                                                                <span><?= !empty($g['review_id']) ? 'Edit Review' : 'Review'; ?></span>
                                                            </a>
                                                        <?php elseif ($status === 'pending'): ?>
                                                            <a href="dashboard.php?cancelRequest=<?= $g['request_id']; ?>" onclick="return confirm('Are you sure you want to cancel this pending order request?');" class="px-3 py-1.5 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 rounded-xl text-xs font-semibold border border-rose-200/50 dark:border-rose-900/40 transition">
                                                                Cancel
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-16 px-4">
                                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">
                                    <i class="ri-inbox-line"></i>
                                </div>
                                <h4 class="font-bold text-slate-800 dark:text-white text-base mb-1">No marketplace orders yet</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-4">You haven't hired any services from the marketplace yet. Explore top talent and order your first service.</p>
                                <a href="../public/gig.php" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-purple-500/20">
                                    <i class="ri-search-line"></i>
                                    <span>Browse Marketplace</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 2: Custom Projects Table -->
                    <div id="tab-content-projects" class="p-0 hidden">
                        <?php if (!empty($projects)): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs sm:text-sm">
                                    <thead class="bg-slate-50/80 dark:bg-slate-800/70 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800 uppercase text-[11px] tracking-wider">
                                        <tr>
                                            <th class="py-3.5 px-5">Project Title</th>
                                            <th class="py-3.5 px-4">Freelancer</th>
                                            <th class="py-3.5 px-4">Budget</th>
                                            <th class="py-3.5 px-4">Deadline</th>
                                            <th class="py-3.5 px-4">Status</th>
                                            <th class="py-3.5 px-5 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                        <?php foreach ($projects as $proj): ?>
                                            <?php
                                                $status = strtolower($proj['status'] ?? 'pending');
                                                $statusBadge = match($status) {
                                                    'accepted', 'in_progress' => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60',
                                                    'completed' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60',
                                                    'cancelled', 'rejected' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60',
                                                    default => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60',
                                                };
                                                $fName = trim(($proj['first_name'] ?? '') . ' ' . ($proj['last_name'] ?? '')) ?: ($proj['freelancer_username'] ?? 'Freelancer');
                                                $fAvatar = resolveAvatarUrl($proj['freelancer_pic'] ?? null, '../', $fName);
                                            ?>
                                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                                <td class="py-4 px-5">
                                                    <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($proj['title']); ?></div>
                                                    <p class="text-xs text-slate-400 dark:text-slate-500 line-clamp-1 mt-0.5"><?= htmlspecialchars($proj['description']); ?></p>
                                                </td>
                                                <td class="py-4 px-4 whitespace-nowrap">
                                                    <div class="flex items-center gap-2.5">
                                                        <img src="<?= htmlspecialchars($fAvatar); ?>" alt="<?= htmlspecialchars($fName); ?>" class="w-7 h-7 rounded-full object-cover border border-slate-200 dark:border-slate-700" onerror="this.src='../assets/img/placeholder-avatar.svg'">
                                                        <span class="font-medium text-slate-700 dark:text-slate-300"><?= htmlspecialchars($fName); ?></span>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-4 whitespace-nowrap font-bold text-slate-900 dark:text-white">
                                                    $<?= number_format((float)$proj['budget'], 2); ?>
                                                </td>
                                                <td class="py-4 px-4 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">
                                                    <?= !empty($proj['deadline']) ? date('M d, Y', strtotime($proj['deadline'])) : 'Open'; ?>
                                                </td>
                                                <td class="py-4 px-4 whitespace-nowrap">
                                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border <?= $statusBadge; ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $status)); ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-5 whitespace-nowrap text-right">
                                                    <?php if ($status === 'completed'): ?>
                                                        <a href="leave_review.php?freelancer_id=<?= $proj['freelancer_id']; ?>&project_id=<?= $proj['id']; ?>" class="px-3 py-1.5 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-300 rounded-xl text-xs font-bold border border-amber-200 dark:border-amber-800 transition inline-flex items-center gap-1">
                                                            <i class="ri-star-line"></i>
                                                            <span><?= !empty($proj['review_id']) ? 'Edit Review' : 'Leave Review'; ?></span>
                                                        </a>
                                                    <?php elseif ($status === 'pending'): ?>
                                                        <a href="dashboard.php?cancelProject=<?= $proj['id']; ?>" onclick="return confirm('Are you sure you want to cancel this custom project proposal?');" class="px-3 py-1.5 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 rounded-xl text-xs font-semibold border border-rose-200/50 dark:border-rose-900/40 transition">
                                                            Cancel
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-xs text-slate-400 dark:text-slate-500 font-medium capitalize"><?= str_replace('_', ' ', $status); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-16 px-4">
                                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">
                                    <i class="ri-folder-open-line"></i>
                                </div>
                                <h4 class="font-bold text-slate-800 dark:text-white text-base mb-1">No custom project proposals</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">You have not proposed custom one-on-one projects directly to freelancers yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>

    <!-- AOS Animation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });

        function switchTab(tab) {
            const btnGigs = document.getElementById('tab-btn-gigs');
            const btnProjects = document.getElementById('tab-btn-projects');
            const contentGigs = document.getElementById('tab-content-gigs');
            const contentProjects = document.getElementById('tab-content-projects');

            const activeClass = 'flex-1 py-3 px-4 rounded-2xl text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 bg-white dark:bg-slate-800 text-purple-600 dark:text-purple-400 shadow-sm border border-slate-200/80 dark:border-slate-700 cursor-pointer';
            const inactiveClass = 'flex-1 py-3 px-4 rounded-2xl text-xs sm:text-sm font-semibold transition flex items-center justify-center gap-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 cursor-pointer';

            if (tab === 'gigs') {
                contentGigs.classList.remove('hidden');
                contentProjects.classList.add('hidden');

                btnGigs.className = activeClass;
                btnProjects.className = inactiveClass;
            } else {
                contentGigs.classList.add('hidden');
                contentProjects.classList.remove('hidden');

                btnProjects.className = activeClass;
                btnGigs.className = inactiveClass;
            }
        }
    </script>
</body>

</html>