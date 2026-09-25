<?php
session_start();
include '../config/db.con.php'; // Include database connection
include_once '../includes/image_helper.php'; // Standard image & avatar resolver

// Ensure only freelancers access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: ../auth/login.php");
    exit;
}

$freelancer_id = (int)$_SESSION['user_id'];

// Handle Accept Job Request
if (isset($_POST['accept_request'])) {
    $job_request_id = validateInteger($_POST['job_request_id']);
    if ($job_request_id) {
        try {
            $stmt = $conn->prepare("UPDATE job_requests SET status = 'accepted' WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$job_request_id, $freelancer_id]);
            $_SESSION['success'] = "Job request accepted successfully! You can now start working on the deliverables.";
            header("Location: manage_job_requests.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error accepting job request: " . $e->getMessage());
            $_SESSION['error'] = "Error accepting job request.";
        }
    }
}

// Handle Complete Job Request
if (isset($_POST['complete_request'])) {
    $job_request_id = validateInteger($_POST['job_request_id']);
    if ($job_request_id) {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("UPDATE job_requests SET status = 'completed', completion_date = NOW() WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$job_request_id, $freelancer_id]);

            // Find price to credit wallet
            $stmtPrice = $conn->prepare("
                SELECT g.price 
                FROM job_requests jr
                JOIN gigs g ON jr.gig_id = g.id
                WHERE jr.id = ? AND jr.freelancer_id = ?
            ");
            $stmtPrice->execute([$job_request_id, $freelancer_id]);
            $price = $stmtPrice->fetchColumn();

            if ($price && (float)$price > 0) {
                // Ensure wallet exists
                $stmtCheckW = $conn->prepare("SELECT id FROM wallets WHERE user_id = ?");
                $stmtCheckW->execute([$freelancer_id]);
                if (!$stmtCheckW->fetch()) {
                    $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)")->execute([$freelancer_id]);
                }

                $stmtWallet = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
                $stmtWallet->execute([(float)$price, $freelancer_id]);
            }

            $conn->commit();
            $_SESSION['success'] = "Job marked as completed! $" . number_format((float)$price, 2) . " has been credited to your wallet balance.";
            header("Location: manage_job_requests.php");
            exit;
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Error completing job request: " . $e->getMessage());
            $_SESSION['error'] = "Error completing job request.";
        }
    }
}

// Handle Cancel / Reject Job Request
if (isset($_POST['reject_request'])) {
    $job_request_id = validateInteger($_POST['job_request_id']);
    if ($job_request_id) {
        try {
            $stmt = $conn->prepare("UPDATE job_requests SET status = 'cancelled' WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$job_request_id, $freelancer_id]);
            $_SESSION['success'] = "Job request has been cancelled.";
            header("Location: manage_job_requests.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error rejecting job request: " . $e->getMessage());
            $_SESSION['error'] = "Error rejecting job request.";
        }
    }
}

// Handle Custom Projects Actions
if (isset($_POST['project_action'])) {
    $projectId = validateInteger($_POST['project_id']);
    $action = $_POST['action_type'] ?? '';

    if ($projectId && in_array($action, ['accepted', 'completed', 'cancelled'])) {
        try {
            $conn->beginTransaction();

            $stmtProj = $conn->prepare("UPDATE projects SET status = ? WHERE id = ? AND freelancer_id = ?");
            $stmtProj->execute([$action, $projectId, $freelancer_id]);

            if ($action === 'completed') {
                $stmtBudget = $conn->prepare("SELECT budget FROM projects WHERE id = ? AND freelancer_id = ?");
                $stmtBudget->execute([$projectId, $freelancer_id]);
                $budget = $stmtBudget->fetchColumn();
                if ($budget && (float)$budget > 0) {
                    // Ensure wallet exists
                    $stmtCheckW = $conn->prepare("SELECT id FROM wallets WHERE user_id = ?");
                    $stmtCheckW->execute([$freelancer_id]);
                    if (!$stmtCheckW->fetch()) {
                        $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)")->execute([$freelancer_id]);
                    }

                    $stmtWallet = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
                    $stmtWallet->execute([(float)$budget, $freelancer_id]);
                }
            }

            $conn->commit();
            $_SESSION['success'] = "Project status updated to " . ucfirst($action) . " successfully!";
            header("Location: manage_job_requests.php");
            exit;
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Error updating project: " . $e->getMessage());
            $_SESSION['error'] = "Failed to update project status.";
        }
    }
}

// Fetch Job Requests for the Freelancer
$job_requests = [];
try {
    $stmt = $conn->prepare("
        SELECT jr.id, jr.status, jr.request_date, jr.completion_date, 
               g.title AS gig_title, g.price AS gig_price, g.image AS gig_image,
               u.first_name AS client_first_name, u.last_name AS client_last_name, u.email AS client_email, u.profile_picture AS client_avatar
        FROM job_requests jr
        LEFT JOIN gigs g ON jr.gig_id = g.id
        JOIN users u ON jr.client_id = u.id
        WHERE jr.freelancer_id = ?
        ORDER BY jr.request_date DESC
    ");
    $stmt->execute([$freelancer_id]);
    $job_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching job requests: " . $e->getMessage());
}

// Fetch Direct Custom Project Proposals for the Freelancer
$custom_projects = [];
try {
    $stmtP = $conn->prepare("
        SELECT p.*, u.first_name AS client_first_name, u.last_name AS client_last_name, u.email AS client_email, u.profile_picture AS client_avatar
        FROM projects p
        JOIN users u ON p.client_id = u.id
        WHERE p.freelancer_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmtP->execute([$freelancer_id]);
    $custom_projects = $stmtP->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching projects: " . $e->getMessage());
}

// Calculate Summary Statistics
$totalOrders = count($job_requests) + count($custom_projects);
$pendingOrders = 0;
$inProgressOrders = 0;
$completedOrders = 0;

foreach ($job_requests as $jr) {
    if ($jr['status'] === 'pending') $pendingOrders++;
    elseif ($jr['status'] === 'accepted') $inProgressOrders++;
    elseif ($jr['status'] === 'completed') $completedOrders++;
}

foreach ($custom_projects as $cp) {
    if ($cp['status'] === 'pending') $pendingOrders++;
    elseif (in_array($cp['status'], ['accepted', 'in_progress'])) $inProgressOrders++;
    elseif ($cp['status'] === 'completed') $completedOrders++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Client Requests & Orders - FreelanceHub</title>
    <meta name="description" content="Review, accept, and manage incoming client project requests and milestone deliverables.">
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

<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans min-h-screen flex flex-col pt-20 transition-colors duration-300 relative selection:bg-purple-500 selection:text-white">
    <!-- Ambient Background Glow -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-500/10 dark:bg-purple-600/15 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 -left-40 w-96 h-96 bg-indigo-500/10 dark:bg-indigo-600/15 rounded-full blur-3xl"></div>
    </div>

    <!-- Header -->
    <?php include('../includes/header.php'); ?>

    <main class="relative z-10 flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <!-- Flash Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200 flex items-center justify-between shadow-sm animate-fadeIn">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center flex-shrink-0">
                        <i class="ri-checkbox-circle-fill text-emerald-600 dark:text-emerald-400 text-lg"></i>
                    </div>
                    <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['success']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 p-1"><i class="ri-close-line text-lg"></i></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200 flex items-center justify-between shadow-sm animate-fadeIn">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-900/60 flex items-center justify-center flex-shrink-0">
                        <i class="ri-error-warning-fill text-rose-600 dark:text-rose-400 text-lg"></i>
                    </div>
                    <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['error']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 p-1"><i class="ri-close-line text-lg"></i></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8" data-aos="fade-up">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400 mb-1">
                    <i class="ri-briefcase-4-line"></i> Client Order Management
                </div>
                <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Manage Job Requests & Proposals</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Review incoming orders, deliver client projects, and claim your wallet payouts.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="dashboard.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl text-slate-700 dark:text-slate-200 text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm">
                    <i class="ri-dashboard-line text-purple-600 dark:text-purple-400"></i> Workspace Dashboard
                </a>
            </div>
        </div>

        <!-- Metric Stat Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8" data-aos="fade-up" data-aos-delay="50">
            <!-- Total Inquiries -->
            <div class="bg-white dark:bg-slate-900/90 rounded-3xl p-5 border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl flex items-center gap-4 transition hover:shadow-md">
                <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-900/40 flex items-center justify-center text-2xl flex-shrink-0">
                    <i class="ri-inbox-archive-line"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white"><?= $totalOrders; ?></div>
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Client Inquiries</div>
                </div>
            </div>

            <!-- Pending Actions -->
            <div class="bg-white dark:bg-slate-900/90 rounded-3xl p-5 border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl flex items-center gap-4 transition hover:shadow-md">
                <div class="w-12 h-12 rounded-2xl <?= $pendingOrders > 0 ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60 ring-2 ring-amber-200 dark:ring-amber-900/40' : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500'; ?> flex items-center justify-center text-2xl flex-shrink-0">
                    <i class="ri-time-line"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-bold text-slate-900 dark:text-white"><?= $pendingOrders; ?></span>
                        <?php if ($pendingOrders > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">Action Needed</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Pending Acceptance</div>
                </div>
            </div>

            <!-- Active / In Progress -->
            <div class="bg-white dark:bg-slate-900/90 rounded-3xl p-5 border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl flex items-center gap-4 transition hover:shadow-md">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40 flex items-center justify-center text-2xl flex-shrink-0">
                    <i class="ri-loader-4-line"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white"><?= $inProgressOrders; ?></div>
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">In Progress / Ongoing</div>
                </div>
            </div>

            <!-- Completed & Paid -->
            <div class="bg-white dark:bg-slate-900/90 rounded-3xl p-5 border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl flex items-center gap-4 transition hover:shadow-md">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/40 flex items-center justify-center text-2xl flex-shrink-0">
                    <i class="ri-checkbox-circle-line"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white"><?= $completedOrders; ?></div>
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Delivered & Completed</div>
                </div>
            </div>
        </div>

        <!-- Main Content Area with Tab Switcher -->
        <div class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl overflow-hidden mb-12" data-aos="fade-up" data-aos-delay="100">
            <!-- Navigation Tabs -->
            <div class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 px-6 pt-4 flex flex-wrap gap-2 sm:gap-6">
                <button onclick="switchTab('gig-requests')" id="tab-btn-gig-requests" class="tab-btn pb-4 px-2 text-sm font-semibold border-b-2 border-purple-600 dark:border-purple-400 text-purple-600 dark:text-purple-400 transition-all flex items-center gap-2">
                    <i class="ri-shopping-bag-3-line"></i>
                    <span>Marketplace Gig Orders</span>
                    <span id="tab-badge-gig-requests" class="ml-1 px-2 py-0.5 text-xs rounded-full bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 font-bold"><?= count($job_requests); ?></span>
                </button>
                <button onclick="switchTab('custom-projects')" id="tab-btn-custom-projects" class="tab-btn pb-4 px-2 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-all flex items-center gap-2">
                    <i class="ri-file-list-3-line"></i>
                    <span>Direct Project Proposals</span>
                    <span id="tab-badge-custom-projects" class="ml-1 px-2 py-0.5 text-xs rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold"><?= count($custom_projects); ?></span>
                </button>
            </div>

            <!-- TAB 1: Marketplace Gig Orders -->
            <div id="tab-content-gig-requests" class="tab-pane p-6 block">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Gig Orders & Job Requests</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Clients who booked your published gigs directly through the marketplace.</p>
                    </div>
                </div>

                <?php if (!empty($job_requests)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50">
                                    <th class="py-3.5 px-4 rounded-l-xl">Service / Gig</th>
                                    <th class="py-3.5 px-4">Client</th>
                                    <th class="py-3.5 px-4">Earnings</th>
                                    <th class="py-3.5 px-4">Requested On</th>
                                    <th class="py-3.5 px-4">Status</th>
                                    <th class="py-3.5 px-4 text-right rounded-r-xl">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm">
                                <?php foreach ($job_requests as $request): ?>
                                    <?php
                                    $clientName = trim(($request['client_first_name'] ?? '') . ' ' . ($request['client_last_name'] ?? '')) ?: 'Client';
                                    $gigImg = resolveGigImage($request['gig_image'] ?? null, '../');
                                    $clientAvatar = resolveAvatarUrl($request['client_avatar'] ?? null, '../', $clientName);

                                    $status = strtolower($request['status'] ?? 'pending');
                                    $badge = match ($status) {
                                        'accepted' => ['bg' => 'bg-indigo-50 dark:bg-indigo-950/60', 'text' => 'text-indigo-700 dark:text-indigo-300', 'border' => 'border-indigo-200 dark:border-indigo-800/60', 'label' => 'In Progress', 'icon' => 'ri-play-circle-line'],
                                        'completed' => ['bg' => 'bg-emerald-50 dark:bg-emerald-950/60', 'text' => 'text-emerald-700 dark:text-emerald-300', 'border' => 'border-emerald-200 dark:border-emerald-800/60', 'label' => 'Completed', 'icon' => 'ri-checkbox-circle-line'],
                                        'rejected', 'cancelled' => ['bg' => 'bg-rose-50 dark:bg-rose-950/60', 'text' => 'text-rose-700 dark:text-rose-300', 'border' => 'border-rose-200 dark:border-rose-800/60', 'label' => 'Cancelled', 'icon' => 'ri-close-circle-line'],
                                        default => ['bg' => 'bg-amber-50 dark:bg-amber-950/60', 'text' => 'text-amber-700 dark:text-amber-300', 'border' => 'border-amber-200 dark:border-amber-800/60', 'label' => 'Pending Review', 'icon' => 'ri-time-line'],
                                    };
                                    ?>
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                        <!-- Service Title -->
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-3">
                                                <img src="<?= htmlspecialchars($gigImg); ?>" alt="Gig" class="w-11 h-11 rounded-2xl object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0" onerror="this.src='../assets/img/placeholder-gig.svg'">
                                                <div>
                                                    <div class="font-semibold text-slate-900 dark:text-white line-clamp-1"><?= htmlspecialchars($request['gig_title'] ?? 'Direct Gig Service'); ?></div>
                                                    <div class="text-xs text-slate-400 dark:text-slate-500">Order #<?= str_pad($request['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Client Info -->
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-2.5">
                                                <img src="<?= htmlspecialchars($clientAvatar); ?>" alt="<?= htmlspecialchars($clientName); ?>" class="w-8 h-8 rounded-full object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0" onerror="this.src='../assets/img/placeholder-avatar.svg'">
                                                <div>
                                                    <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs"><?= htmlspecialchars($clientName); ?></div>
                                                    <a href="mailto:<?= htmlspecialchars($request['client_email']); ?>" class="text-[11px] text-slate-400 dark:text-slate-500 hover:text-purple-600 dark:hover:text-purple-400 transition-colors flex items-center gap-1">
                                                        <i class="ri-mail-line"></i> <?= htmlspecialchars($request['client_email']); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Price -->
                                        <td class="py-4 px-4 font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                            $<?= number_format((float)($request['gig_price'] ?? 0), 2); ?>
                                        </td>

                                        <!-- Date -->
                                        <td class="py-4 px-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                            <div class="font-medium text-slate-700 dark:text-slate-300"><?= !empty($request['request_date']) ? date('M d, Y', strtotime($request['request_date'])) : 'N/A'; ?></div>
                                            <?php if (!empty($request['completion_date'])): ?>
                                                <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">Done <?= date('M d', strtotime($request['completion_date'])); ?></div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border <?= $badge['bg'] . ' ' . $badge['text'] . ' ' . $badge['border']; ?>">
                                                <i class="<?= $badge['icon']; ?>"></i>
                                                <?= $badge['label']; ?>
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-4 px-4 text-right whitespace-nowrap">
                                            <form action="" method="POST" class="inline-flex items-center gap-2 justify-end">
                                                <input type="hidden" name="job_request_id" value="<?= $request['id']; ?>">

                                                <?php if ($status === 'pending'): ?>
                                                    <button type="submit" name="accept_request" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-semibold shadow-sm shadow-purple-500/20 transition-all flex items-center gap-1">
                                                        <i class="ri-check-line"></i> Accept
                                                    </button>
                                                    <button type="submit" name="reject_request" onclick="return confirm('Decline this job request from <?= htmlspecialchars(addslashes($clientName)); ?>?');" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 text-xs font-semibold transition-all">
                                                        Decline
                                                    </button>

                                                <?php elseif ($status === 'accepted'): ?>
                                                    <button type="submit" name="complete_request" onclick="return confirm('Confirm delivery of this job? This will mark the order as completed and credit $<?= number_format((float)($request['gig_price'] ?? 0), 2); ?> to your wallet balance.');" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-semibold shadow-sm shadow-emerald-500/20 transition-all flex items-center gap-1">
                                                        <i class="ri-checkbox-circle-line"></i> Deliver & Complete
                                                    </button>
                                                    <button type="submit" name="reject_request" onclick="return confirm('Are you sure you want to cancel this ongoing job?');" class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 text-xs font-semibold transition-all">
                                                        Cancel
                                                    </button>

                                                <?php elseif ($status === 'completed'): ?>
                                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-1 rounded-xl border border-emerald-100 dark:border-emerald-800/60">
                                                        <i class="ri-wallet-3-line"></i> Credited
                                                    </span>

                                                <?php else: ?>
                                                    <span class="text-xs text-slate-400 dark:text-slate-500 font-medium">Closed</span>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 px-4">
                        <div class="w-16 h-16 rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-500 flex items-center justify-center text-3xl mx-auto mb-3 shadow-inner">
                            <i class="ri-inbox-line"></i>
                        </div>
                        <h4 class="text-base font-bold text-slate-900 dark:text-white mb-1">No gig orders yet</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-4">When clients book your published marketplace gigs, their orders and inquiries will be displayed here.</p>
                        <a href="dashboard.php#publish-gig" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-xs font-semibold hover:from-purple-500 hover:to-indigo-500 shadow-sm transition-all">
                            <i class="ri-add-line"></i> Create New Gig
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 2: Direct Custom Project Proposals -->
            <div id="tab-content-custom-projects" class="tab-pane p-6 hidden">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Custom Project Proposals</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Tailored custom contracts and proposals submitted directly to your profile by clients.</p>
                    </div>
                </div>

                <?php if (!empty($custom_projects)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50">
                                    <th class="py-3.5 px-4 rounded-l-xl">Project Title & Brief</th>
                                    <th class="py-3.5 px-4">Client</th>
                                    <th class="py-3.5 px-4">Budget</th>
                                    <th class="py-3.5 px-4">Target Deadline</th>
                                    <th class="py-3.5 px-4">Status</th>
                                    <th class="py-3.5 px-4 text-right rounded-r-xl">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm">
                                <?php foreach ($custom_projects as $proj): ?>
                                    <?php
                                    $clientName = trim(($proj['client_first_name'] ?? '') . ' ' . ($proj['client_last_name'] ?? '')) ?: 'Client';
                                    $clientAvatar = resolveAvatarUrl($proj['client_avatar'] ?? null, '../', $clientName);
                                    $status = strtolower($proj['status'] ?? 'pending');
                                    $badge = match ($status) {
                                        'accepted', 'in_progress' => ['bg' => 'bg-indigo-50 dark:bg-indigo-950/60', 'text' => 'text-indigo-700 dark:text-indigo-300', 'border' => 'border-indigo-200 dark:border-indigo-800/60', 'label' => 'In Progress', 'icon' => 'ri-play-circle-line'],
                                        'completed' => ['bg' => 'bg-emerald-50 dark:bg-emerald-950/60', 'text' => 'text-emerald-700 dark:text-emerald-300', 'border' => 'border-emerald-200 dark:border-emerald-800/60', 'label' => 'Completed', 'icon' => 'ri-checkbox-circle-line'],
                                        'cancelled' => ['bg' => 'bg-rose-50 dark:bg-rose-950/60', 'text' => 'text-rose-700 dark:text-rose-300', 'border' => 'border-rose-200 dark:border-rose-800/60', 'label' => 'Cancelled', 'icon' => 'ri-close-circle-line'],
                                        default => ['bg' => 'bg-amber-50 dark:bg-amber-950/60', 'text' => 'text-amber-700 dark:text-amber-300', 'border' => 'border-amber-200 dark:border-amber-800/60', 'label' => 'Proposal Pending', 'icon' => 'ri-time-line'],
                                    };
                                    ?>
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                        <!-- Project Title & Scope -->
                                        <td class="py-4 px-4 max-w-xs">
                                            <div class="font-semibold text-slate-900 dark:text-white line-clamp-1"><?= htmlspecialchars($proj['title']); ?></div>
                                            <div class="text-xs text-slate-400 dark:text-slate-500 line-clamp-2 mt-0.5"><?= htmlspecialchars($proj['description'] ?? ''); ?></div>
                                        </td>

                                        <!-- Client Info -->
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-2.5">
                                                <img src="<?= htmlspecialchars($clientAvatar); ?>" alt="<?= htmlspecialchars($clientName); ?>" class="w-8 h-8 rounded-full object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0" onerror="this.src='../assets/img/placeholder-avatar.svg'">
                                                <div>
                                                    <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs"><?= htmlspecialchars($clientName); ?></div>
                                                    <a href="mailto:<?= htmlspecialchars($proj['client_email']); ?>" class="text-[11px] text-slate-400 dark:text-slate-500 hover:text-purple-600 dark:hover:text-purple-400 transition-colors flex items-center gap-1">
                                                        <i class="ri-mail-line"></i> <?= htmlspecialchars($proj['client_email']); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Budget -->
                                        <td class="py-4 px-4 font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                            $<?= number_format((float)$proj['budget'], 2); ?>
                                        </td>

                                        <!-- Deadline -->
                                        <td class="py-4 px-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                            <div class="font-medium text-slate-700 dark:text-slate-300"><?= !empty($proj['deadline']) ? date('M d, Y', strtotime($proj['deadline'])) : 'Open'; ?></div>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500">Created <?= date('M d', strtotime($proj['created_at'])); ?></div>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border <?= $badge['bg'] . ' ' . $badge['text'] . ' ' . $badge['border']; ?>">
                                                <i class="<?= $badge['icon']; ?>"></i>
                                                <?= $badge['label']; ?>
                                            </span>
                                        </td>

                                        <!-- Action Buttons -->
                                        <td class="py-4 px-4 text-right whitespace-nowrap">
                                            <form action="" method="POST" class="inline-flex items-center gap-2 justify-end">
                                                <input type="hidden" name="project_id" value="<?= $proj['id']; ?>">

                                                <?php if ($status === 'pending'): ?>
                                                    <input type="hidden" name="action_type" value="accepted">
                                                    <button type="submit" name="project_action" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-semibold shadow-sm shadow-purple-500/20 transition-all flex items-center gap-1">
                                                        <i class="ri-check-line"></i> Accept Proposal
                                                    </button>
                                                    <button type="button" onclick="cancelProject(<?= $proj['id']; ?>)" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 text-xs font-semibold transition-all">
                                                        Decline
                                                    </button>

                                                <?php elseif (in_array($status, ['accepted', 'in_progress'])): ?>
                                                    <input type="hidden" name="action_type" value="completed">
                                                    <button type="submit" name="project_action" onclick="return confirm('Complete this project? Wallet balance will be credited with $<?= number_format((float)$proj['budget'], 2); ?>.');" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-semibold shadow-sm shadow-emerald-500/20 transition-all flex items-center gap-1">
                                                        <i class="ri-checkbox-circle-line"></i> Deliver & Complete
                                                    </button>
                                                    <button type="button" onclick="cancelProject(<?= $proj['id']; ?>)" class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 text-xs font-semibold transition-all">
                                                        Cancel
                                                    </button>

                                                <?php elseif ($status === 'completed'): ?>
                                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-1 rounded-xl border border-emerald-100 dark:border-emerald-800/60">
                                                        <i class="ri-wallet-3-line"></i> Paid Out
                                                    </span>

                                                <?php else: ?>
                                                    <span class="text-xs text-slate-400 dark:text-slate-500 font-medium">Closed</span>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 px-4">
                        <div class="w-16 h-16 rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-500 flex items-center justify-center text-3xl mx-auto mb-3 shadow-inner">
                            <i class="ri-folder-open-line"></i>
                        </div>
                        <h4 class="text-base font-bold text-slate-900 dark:text-white mb-1">No custom project proposals</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">When clients send direct project invitations to your profile, you will see them here with full budget and timeline details.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Hidden Form for Project Cancellation -->
        <form id="cancelProjectForm" action="" method="POST" class="hidden">
            <input type="hidden" name="project_id" id="cancelProjectId" value="">
            <input type="hidden" name="action_type" value="cancelled">
            <input type="hidden" name="project_action" value="1">
        </form>
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

        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-pane').forEach(el => {
                el.classList.add('hidden');
                el.classList.remove('block');
            });

            // Reset tab button states
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-purple-600', 'dark:border-purple-400', 'text-purple-600', 'dark:text-purple-400');
                btn.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400');
            });

            // Reset tab badge states
            const badgeGig = document.getElementById('tab-badge-gig-requests');
            const badgeCustom = document.getElementById('tab-badge-custom-projects');
            if (badgeGig) {
                badgeGig.className = 'ml-1 px-2 py-0.5 text-xs rounded-full font-bold ' + 
                    (tabId === 'gig-requests' ? 'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400');
            }
            if (badgeCustom) {
                badgeCustom.className = 'ml-1 px-2 py-0.5 text-xs rounded-full font-bold ' + 
                    (tabId === 'custom-projects' ? 'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400');
            }

            // Activate chosen tab
            const targetPane = document.getElementById('tab-content-' + tabId);
            const targetBtn = document.getElementById('tab-btn-' + tabId);

            if (targetPane) {
                targetPane.classList.remove('hidden');
                targetPane.classList.add('block');
            }
            if (targetBtn) {
                targetBtn.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
                targetBtn.classList.add('border-purple-600', 'dark:border-purple-400', 'text-purple-600', 'dark:text-purple-400');
            }
        }

        function cancelProject(projectId) {
            if (confirm('Are you sure you want to decline or cancel this project proposal?')) {
                document.getElementById('cancelProjectId').value = projectId;
                document.getElementById('cancelProjectForm').submit();
            }
        }
    </script>
</body>

</html>