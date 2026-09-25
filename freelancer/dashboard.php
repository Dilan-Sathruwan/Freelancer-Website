<?php
session_start();
include '../config/db.con.php'; // Include database connection
include_once '../includes/image_helper.php';

// Check if user is logged in and is a freelancer
$user_id = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';

if (!$user_id || $user_role !== 'freelancer') {
    header("Location: ../auth/login.php");
    exit;
}

// Fetch freelancer's account info
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'freelancer'");
    $stmt->execute([$user_id]);
    $freelancer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freelancer) {
        $_SESSION['error'] = "Freelancer account not found.";
        header("Location: ../index.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching freelancer: " . $e->getMessage());
    $_SESSION['error'] = "Error loading freelancer data.";
    header("Location: ../index.php");
    exit;
}

// Fetch professional info from freelancer_profiles or freelancers
$freelancerProfile = null;
try {
    $stmtFP = $conn->prepare("SELECT * FROM freelancer_profiles WHERE user_id = ?");
    $stmtFP->execute([$user_id]);
    $freelancerProfile = $stmtFP->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$freelancerDetails = null;
try {
    $stmtFD = $conn->prepare("SELECT * FROM freelancers WHERE user_id = ?");
    $stmtFD->execute([$user_id]);
    $freelancerDetails = $stmtFD->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Fetch or create wallet
$walletBalance = 0.00;
$walletPending = 0.00;
$walletTotalEarned = 0.00;
try {
    $stmtWallet = $conn->prepare("SELECT balance, pending_clearance, total_earned FROM wallets WHERE user_id = ?");
    $stmtWallet->execute([$user_id]);
    $wallet = $stmtWallet->fetch(PDO::FETCH_ASSOC);
    if ($wallet) {
        $walletBalance = (float)$wallet['balance'];
        $walletPending = (float)($wallet['pending_clearance'] ?? 0);
        $walletTotalEarned = (float)($wallet['total_earned'] ?? 0);
    } else {
        $stmtNewWallet = $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)");
        $stmtNewWallet->execute([$user_id]);
    }
} catch (PDOException $e) {
    error_log("Error fetching wallet: " . $e->getMessage());
}

// Fetch platform categories
$categories = [];
try {
    $stmtCats = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
    $categories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// Fetch platform reputation & reviews
$rating = 5.00;
$reviewsCount = 0;
try {
    $stmtRev = $conn->prepare("SELECT COUNT(*) as cnt, AVG(rating) as avg_rating FROM reviews WHERE freelancer_id = ? AND (status = 'active' OR status IS NULL)");
    $stmtRev->execute([$user_id]);
    $revData = $stmtRev->fetch(PDO::FETCH_ASSOC);
    if ($revData && $revData['cnt'] > 0) {
        $reviewsCount = (int)$revData['cnt'];
        $rating = round((float)$revData['avg_rating'], 1);
    } elseif (!empty($freelancerDetails['rating'])) {
        $rating = (float)$freelancerDetails['rating'];
    }
} catch (PDOException $e) {
    error_log("Error fetching profile rating: " . $e->getMessage());
}

// Profile update handler
if (isset($_POST['profileSubmit'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    try {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, updated_at = NOW() WHERE id = ? AND role = 'freelancer'");
        $stmt->execute([$firstName, $lastName, $phone, $user_id]);
        $_SESSION['success'] = "Personal profile details updated successfully!";
        header("Location: dashboard.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error updating profile: " . $e->getMessage());
        $_SESSION['error'] = "Error updating profile.";
    }
}

// Delete / Archive Gig handler
if (isset($_GET['deleteId'])) {
    $gigId = validateInteger($_GET['deleteId']);
    
    if ($gigId) {
        try {
            // First try direct deletion
            $stmt = $conn->prepare("DELETE FROM gigs WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$gigId, $user_id]);
            $_SESSION['success'] = "Gig removed successfully!";
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            // Soft delete fallback if foreign key constraint exists (orders/reviews)
            try {
                $stmt = $conn->prepare("UPDATE gigs SET status = 'deleted', deleted_at = NOW() WHERE id = ? AND freelancer_id = ?");
                $stmt->execute([$gigId, $user_id]);
                $_SESSION['success'] = "Gig archived successfully!";
                header("Location: dashboard.php");
                exit;
            } catch (PDOException $e2) {
                error_log("Error archiving gig: " . $e2->getMessage());
                $_SESSION['error'] = "Error deleting gig.";
            }
        }
    }
}

// Toggle Gig visibility status (active <-> paused)
if (isset($_GET['toggleStatus'])) {
    $gigId = validateInteger($_GET['toggleStatus']);
    if ($gigId) {
        try {
            $stmt = $conn->prepare("UPDATE gigs SET status = CASE WHEN status = 'active' THEN 'paused' ELSE 'active' END, updated_at = NOW() WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$gigId, $user_id]);
            $_SESSION['success'] = "Gig visibility status updated successfully!";
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error toggling gig: " . $e->getMessage());
            $_SESSION['error'] = "Failed to update gig status.";
        }
    }
}

// Update Gig handler (from Edit Modal)
if (isset($_POST['updateGig'])) {
    $gigId = validateInteger($_POST['gig_id'] ?? null);
    $title = sanitizeInput($_POST['edit_title'] ?? '');
    $description = sanitizeInput($_POST['edit_description'] ?? '');
    $category = validateInteger($_POST['edit_category'] ?? null);
    $price = floatval($_POST['edit_price'] ?? 0);
    $delivery_time = validateInteger($_POST['edit_delivery_time'] ?? null) ?: 3;
    $status = in_array($_POST['edit_status'] ?? '', ['active', 'paused']) ? $_POST['edit_status'] : 'active';

    if ($gigId && $title && $description && $category && $price > 0) {
        try {
            // Verify gig ownership
            $stmtCheck = $conn->prepare("SELECT image FROM gigs WHERE id = ? AND freelancer_id = ?");
            $stmtCheck->execute([$gigId, $user_id]);
            $existingGig = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existingGig) {
                $image = $existingGig['image'];

                // Handle replacement image upload
                if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
                    $fileType = mime_content_type($_FILES['edit_image']['tmp_name']);

                    if (in_array($fileType, $allowedTypes) && $_FILES['edit_image']['size'] <= 5 * 1024 * 1024) {
                        $target_dir = "../uploads/gigs/";
                        if (!is_dir($target_dir)) {
                            mkdir($target_dir, 0777, true);
                        }
                        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['edit_image']['name']));
                        $target_file = $target_dir . $filename;
                        if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $target_file)) {
                            $image = "uploads/gigs/" . $filename;
                        }
                    }
                }

                $stmtUpdate = $conn->prepare("
                    UPDATE gigs 
                    SET title = ?, description = ?, category_id = ?, price = ?, delivery_time = ?, status = ?, image = ?, updated_at = NOW() 
                    WHERE id = ? AND freelancer_id = ?
                ");
                $stmtUpdate->execute([$title, $description, $category, $price, $delivery_time, $status, $image, $gigId, $user_id]);
                $_SESSION['success'] = "Service listing updated successfully!";
                header("Location: dashboard.php");
                exit;
            } else {
                $_SESSION['error'] = "Gig not found or permission denied.";
            }
        } catch (PDOException $e) {
            error_log("Error updating gig: " . $e->getMessage());
            $_SESSION['error'] = "Failed to update gig: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please provide all required valid gig details.";
    }
}

// Add Gig handler
if (isset($_POST['addGig'])) {
    $title = sanitizeInput($_POST['new_title'] ?? '');
    $description = sanitizeInput($_POST['new_description'] ?? '');
    $category = validateInteger($_POST['new_category'] ?? null);
    $price = floatval($_POST['new_price'] ?? 0);
    $delivery_time = validateInteger($_POST['new_delivery_time'] ?? null) ?: 3;
    $image = null;
    
    if ($title && $description && $category && $price > 0) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))) . '-' . time();

        if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
            $fileType = mime_content_type($_FILES['new_image']['tmp_name']);

            if (in_array($fileType, $allowedTypes) && $_FILES['new_image']['size'] <= 5 * 1024 * 1024) {
                $target_dir = "../uploads/gigs/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['new_image']['name']));
                $target_file = $target_dir . $filename;
                if (move_uploaded_file($_FILES['new_image']['tmp_name'], $target_file)) {
                    $image = "uploads/gigs/" . $filename;
                }
            }
        }

        try {
            $stmt = $conn->prepare("
                INSERT INTO gigs (freelancer_id, title, slug, description, category_id, price, delivery_time, image, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
            ");
            $stmt->execute([$user_id, $title, $slug, $description, $category, $price, $delivery_time, $image]);
            $_SESSION['success'] = "New service published to marketplace successfully!";
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error adding gig: " . $e->getMessage());
            $_SESSION['error'] = "Error adding gig: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please fill in all required gig details with a valid price.";
    }
}

// Compute dashboard metrics
$gigsCount = 0;
$completedJobsCount = 0;
$pendingRequestsCount = 0;

try {
    $stmtG = $conn->prepare("SELECT COUNT(*) FROM gigs WHERE freelancer_id = ? AND (status != 'deleted' OR status IS NULL)");
    $stmtG->execute([$user_id]);
    $gigsCount = (int)$stmtG->fetchColumn();

    $stmtC = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM job_requests WHERE freelancer_id = ? AND status = 'completed') +
            (SELECT COUNT(*) FROM projects WHERE freelancer_id = ? AND status = 'completed')
    ");
    $stmtC->execute([$user_id, $user_id]);
    $completedJobsCount = (int)$stmtC->fetchColumn();

    $stmtP = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM job_requests WHERE freelancer_id = ? AND status = 'pending') +
            (SELECT COUNT(*) FROM projects WHERE freelancer_id = ? AND status = 'pending')
    ");
    $stmtP->execute([$user_id, $user_id]);
    $pendingRequestsCount = (int)$stmtP->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}

// Fetch unified recent incoming client orders & project requests
$recentRequests = [];
try {
    $stmtRecent = $conn->prepare("
        (SELECT 
            'gig_request' AS type,
            jr.id,
            jr.status,
            jr.request_date AS activity_date,
            COALESCE(g.title, 'Marketplace Gig Order') AS title,
            g.price AS amount,
            u.id AS client_id,
            u.first_name AS client_first_name,
            u.last_name AS client_last_name,
            u.username AS client_username,
            u.profile_picture AS client_avatar
        FROM job_requests jr
        LEFT JOIN gigs g ON jr.gig_id = g.id
        JOIN users u ON jr.client_id = u.id
        WHERE jr.freelancer_id = ?)
        
        UNION ALL
        
        (SELECT 
            'project' AS type,
            p.id,
            p.status,
            p.created_at AS activity_date,
            p.title AS title,
            p.budget AS amount,
            u.id AS client_id,
            u.first_name AS client_first_name,
            u.last_name AS client_last_name,
            u.username AS client_username,
            u.profile_picture AS client_avatar
        FROM projects p
        JOIN users u ON p.client_id = u.id
        WHERE p.freelancer_id = ?)
        
        ORDER BY activity_date DESC
        LIMIT 6
    ");
    $stmtRecent->execute([$user_id, $user_id]);
    $recentRequests = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent requests: " . $e->getMessage());
}

// Fetch recent client feedback / reviews
$recentReviews = [];
try {
    $stmtRevList = $conn->prepare("
        SELECT r.id, r.rating, r.comment, r.created_at,
               u.first_name AS client_first_name, u.last_name AS client_last_name, u.username AS client_username, u.profile_picture AS client_avatar,
               COALESCE(g.title, p.title, 'Completed Delivery') AS order_title
        FROM reviews r
        JOIN users u ON r.client_id = u.id
        LEFT JOIN gigs g ON r.gig_id = g.id
        LEFT JOIN projects p ON r.project_id = p.id
        WHERE r.freelancer_id = ? AND (r.status = 'active' OR r.status IS NULL)
        ORDER BY r.created_at DESC
        LIMIT 3
    ");
    $stmtRevList->execute([$user_id]);
    $recentReviews = $stmtRevList->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent reviews: " . $e->getMessage());
}

// Fetch freelancer's active gigs
$myGigs = [];
try {
    $stmtGigs = $conn->prepare("
        SELECT g.*, c.name AS category_name 
        FROM gigs g 
        LEFT JOIN categories c ON g.category_id = c.id 
        WHERE g.freelancer_id = ? AND (g.status != 'deleted' OR g.status IS NULL) 
        ORDER BY g.created_at DESC
    ");
    $stmtGigs->execute([$user_id]);
    $myGigs = $stmtGigs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading gigs: " . $e->getMessage());
}

$freelancerName = trim(($freelancer['first_name'] ?? '') . ' ' . ($freelancer['last_name'] ?? '')) ?: ($freelancer['username'] ?? 'Freelancer');
$freelancerPic = resolveAvatarUrl($freelancer['profile_picture'] ?? null, '../', $freelancerName);
$freelancerTitle = $freelancerProfile['title'] ?? ($freelancerDetails['skills'] ? 'Specialist in ' . explode(',', $freelancerDetails['skills'])[0] : 'Professional Freelancer');
$freelancerHourly = !empty($freelancerProfile['hourly_rate']) ? (float)$freelancerProfile['hourly_rate'] : (!empty($freelancerDetails['hourly_rate']) ? (float)$freelancerDetails['hourly_rate'] : 0);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelancer Workspace - FreelanceHub</title>
    <meta name="description" content="Freelancer Dashboard - Manage your active gigs, client requests, orders, and earnings.">
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
    <!-- Ambient Background Lighting -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden opacity-30">
        <div class="absolute -top-32 left-1/4 w-[36rem] h-[36rem] bg-purple-500/20 dark:bg-purple-600/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 right-10 w-[30rem] h-[30rem] bg-indigo-500/15 dark:bg-indigo-600/15 rounded-full blur-3xl"></div>
    </div>

    <!-- Header Navigation -->
    <?php include('../includes/header.php'); ?>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full relative z-10">
        <!-- Flash Feedback Alerts -->
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
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Freelancer Workspace
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">@<?= htmlspecialchars($freelancer['username']); ?></span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                    Welcome, <?= htmlspecialchars($freelancer['first_name'] ?: $freelancer['username']); ?>! 🚀
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Manage your marketplace services, respond to client orders, and monitor your earnings.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="manage_job_requests.php" class="px-4 py-3 bg-purple-50 dark:bg-purple-950/60 hover:bg-purple-100 dark:hover:bg-purple-900/60 text-purple-700 dark:text-purple-300 rounded-2xl text-sm font-bold border border-purple-200 dark:border-purple-800/60 transition flex items-center gap-2 relative">
                    <i class="ri-task-line text-lg"></i>
                    <span>Orders & Projects</span>
                    <?php if ($pendingRequestsCount > 0): ?>
                        <span class="px-2 py-0.5 bg-rose-500 text-white rounded-full text-xs font-extrabold animate-pulse"><?= $pendingRequestsCount; ?></span>
                    <?php endif; ?>
                </a>
                <button onclick="togglePublishGigSection()" class="px-5 py-3 bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-2xl text-sm font-bold shadow-md shadow-purple-500/20 hover:shadow-lg transition-all flex items-center gap-2 transform active:scale-95 cursor-pointer">
                    <i class="ri-add-circle-line text-lg"></i>
                    <span>Add New Service</span>
                </button>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8" data-aos="fade-up" data-aos-delay="50">
            <!-- Available Earnings -->
            <div class="bg-white dark:bg-slate-900/90 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-800/80 transition backdrop-blur-xl group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">Wallet Balance</p>
                        <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">$<?= number_format($walletBalance, 2); ?></h3>
                        <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                            <span>Available to withdraw</span>
                            <?php if ($walletTotalEarned > 0): ?>
                                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">• $<?= number_format($walletTotalEarned, 0); ?> earned</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/40 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="ri-wallet-3-line"></i>
                    </div>
                </div>
            </div>

            <!-- Published Gigs -->
            <div class="bg-white dark:bg-slate-900/90 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-purple-300 dark:hover:border-purple-800/80 transition backdrop-blur-xl group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">Active Services</p>
                        <h3 class="text-2xl font-extrabold text-purple-600 dark:text-purple-400 mt-1"><?= $gigsCount; ?></h3>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Marketplace Listings</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-900/40 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="ri-briefcase-line"></i>
                    </div>
                </div>
            </div>

            <!-- Completed Jobs -->
            <div class="bg-white dark:bg-slate-900/90 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-blue-300 dark:hover:border-blue-800/80 transition backdrop-blur-xl group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">Completed Orders</p>
                        <h3 class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-1"><?= $completedJobsCount; ?></h3>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Delivered Projects & Gigs</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="ri-checkbox-circle-line"></i>
                    </div>
                </div>
            </div>

            <!-- Freelancer Rating -->
            <div class="bg-white dark:bg-slate-900/90 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-amber-300 dark:hover:border-amber-800/80 transition backdrop-blur-xl group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">Client Rating</p>
                        <h3 class="text-2xl font-extrabold text-amber-500 dark:text-amber-400 mt-1 flex items-center gap-1.5">
                            <span><?= number_format($rating, 1); ?></span>
                            <i class="ri-star-fill text-lg"></i>
                        </h3>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500"><?= $reviewsCount; ?> Verified <?= $reviewsCount === 1 ? 'Review' : 'Reviews'; ?></span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-500 dark:text-amber-400 border border-amber-100 dark:border-amber-900/40 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="ri-star-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2 Column Workspace Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Profile & Account Sidebar -->
            <div class="lg:col-span-1 space-y-6" data-aos="fade-up" data-aos-delay="100">
                <!-- Freelancer Profile Card -->
                <div class="bg-white dark:bg-slate-900/90 p-6 sm:p-7 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl">
                    <div class="text-center">
                        <div class="relative inline-block mx-auto mb-3">
                            <img src="<?= htmlspecialchars($freelancerPic); ?>" alt="<?= htmlspecialchars($freelancerName); ?>"
                                 class="w-20 h-20 rounded-full object-cover border-2 border-purple-500/20 shadow-md"
                                 onerror="this.src='../assets/img/placeholder-avatar.svg';">
                            <span class="absolute bottom-0 right-0 w-5 h-5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full" title="Active Account"></span>
                        </div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-base"><?= htmlspecialchars($freelancerName); ?></h3>
                        <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mt-0.5"><?= htmlspecialchars($freelancerTitle); ?></p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">@<?= htmlspecialchars($freelancer['username']); ?></p>

                        <!-- Extra Freelancer Meta Badges -->
                        <div class="flex flex-wrap items-center justify-center gap-2 mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/80">
                            <?php if ($freelancerHourly > 0): ?>
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 text-[11px] font-bold border border-emerald-200/60 dark:border-emerald-800/50">
                                    $<?= number_format($freelancerHourly, 0); ?> / hr
                                </span>
                            <?php endif; ?>
                            <span class="px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 text-[11px] font-bold border border-amber-200/60 dark:border-amber-800/50 flex items-center gap-1">
                                <i class="ri-star-fill text-amber-500"></i>
                                <?= number_format($rating, 1); ?> (<?= $reviewsCount; ?>)
                            </span>
                            <?php if (!empty($freelancerDetails['location'])): ?>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[11px] font-semibold border border-slate-200 dark:border-slate-700 flex items-center gap-1">
                                    <i class="ri-map-pin-line text-xs"></i>
                                    <?= htmlspecialchars($freelancerDetails['location']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Skills pills -->
                    <?php if (!empty($freelancerDetails['skills'])): 
                        $skillList = array_map('trim', explode(',', $freelancerDetails['skills']));
                    ?>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80">
                            <p class="text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider mb-2">Core Skills</p>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach (array_slice($skillList, 0, 5) as $skill): ?>
                                    <span class="px-2.5 py-1 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 text-[11px] font-semibold border border-purple-200/60 dark:border-purple-800/50">
                                        <?= htmlspecialchars($skill); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Quick Personal Details Update Form -->
                    <form action="" method="POST" class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800/80 space-y-4 text-left">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Account Details</span>
                            <span class="text-[11px] text-slate-400 font-mono">ID: #<?= $user_id; ?></span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">First Name</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($freelancer['first_name'] ?? ''); ?>" required
                                   class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs transition focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Last Name</label>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($freelancer['last_name'] ?? ''); ?>" required
                                   class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs transition focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($freelancer['phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000"
                                   class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs transition focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Registered Email</label>
                            <input type="email" value="<?= htmlspecialchars($freelancer['email'] ?? ''); ?>" disabled
                                   class="w-full px-3.5 py-2.5 bg-slate-100 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 rounded-xl border border-slate-200 dark:border-slate-800 text-xs cursor-not-allowed">
                        </div>

                        <div class="space-y-2 pt-2">
                            <button type="submit" name="profileSubmit" class="w-full py-2.5 px-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-sm shadow-purple-500/20 flex items-center justify-center gap-1.5 cursor-pointer">
                                <i class="ri-save-line text-sm"></i>
                                <span>Save Changes</span>
                            </button>
                            <a href="../public/profile.php" class="block w-full py-2.5 px-4 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold text-center border border-slate-200 dark:border-slate-700 transition">
                                <i class="ri-image-edit-line mr-1"></i> Edit Avatar, Bio & Skills
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Services & Orders -->
            <div class="lg:col-span-2 space-y-8" data-aos="fade-up" data-aos-delay="150">
                <!-- Publish Gig Card (Expandable / Collapsible) -->
                <div id="publish-gig" class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl overflow-hidden transition-all">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between cursor-pointer select-none" onclick="togglePublishGigSection()">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg">
                                <i class="ri-add-circle-line"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Publish a New Marketplace Service (Gig)</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Offer your expertise to global clients searching for talent.</p>
                            </div>
                        </div>
                        <button type="button" id="publish-gig-toggle-btn" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl transition">
                            <i id="publish-gig-chevron" class="ri-arrow-up-s-line"></i>
                        </button>
                    </div>

                    <div id="publish-gig-body" class="p-6 sm:p-8">
                        <form action="" method="POST" enctype="multipart/form-data" class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Gig Title *</label>
                                <input type="text" name="new_title" required
                                       placeholder="e.g. I will design a high converting modern landing page in Figma"
                                       class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm transition focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 placeholder-slate-400 dark:placeholder-slate-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Service Description *</label>
                                <textarea name="new_description" rows="4" required
                                          placeholder="Detail your deliverables, expertise, workflow, and what is included in this offer..."
                                          class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm leading-relaxed transition focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 placeholder-slate-400 dark:placeholder-slate-500"></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Category *</label>
                                    <select name="new_category" required
                                            class="w-full px-3.5 py-3 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-medium transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                                        <option value="" disabled selected class="text-slate-400">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id']; ?>" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white"><?= htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Price ($ USD) *</label>
                                    <input type="number" step="0.01" min="5" name="new_price" required placeholder="50.00"
                                           class="w-full px-3.5 py-3 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 placeholder-slate-400 dark:placeholder-slate-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Delivery Time (Days) *</label>
                                    <input type="number" min="1" max="90" name="new_delivery_time" value="3" required
                                           class="w-full px-3.5 py-3 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Cover Image (Optional)</label>
                                <input type="file" name="new_image" accept="image/*" onchange="previewImage(this, 'add-gig-preview', 'add-gig-img')"
                                       class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-purple-50 dark:file:bg-purple-950/60 file:text-purple-700 dark:file:text-purple-300 hover:file:bg-purple-100 dark:hover:file:bg-purple-900/60 cursor-pointer">
                                
                                <div id="add-gig-preview" class="hidden mt-3 p-2 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 inline-block relative">
                                    <img id="add-gig-img" src="" alt="Cover Preview" class="h-28 w-44 rounded-xl object-cover">
                                    <button type="button" onclick="clearImagePreview('new_image', 'add-gig-preview', 'add-gig-img')" class="absolute top-3 right-3 w-6 h-6 rounded-full bg-slate-900/80 hover:bg-rose-600 text-white text-xs flex items-center justify-center transition">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="pt-2 flex items-center justify-end">
                                <button type="submit" name="addGig" class="px-7 py-3 bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-2xl text-xs font-bold shadow-md shadow-purple-500/20 hover:shadow-lg transition transform active:scale-95 flex items-center gap-2 cursor-pointer">
                                    <i class="ri-upload-cloud-line text-base"></i>
                                    <span>Publish to Marketplace</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Published Gigs Table -->
                <div class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg">
                                <i class="ri-list-check"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-base">Your Active Services</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Manage, edit, pause, and preview your active listings.</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-full border border-slate-200 dark:border-slate-700"><?= $gigsCount; ?> total</span>
                    </div>

                    <?php if (!empty($myGigs)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs sm:text-sm">
                                <thead class="bg-slate-50/80 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800 uppercase text-[11px] tracking-wider">
                                    <tr>
                                        <th class="py-3.5 px-5">Service Title</th>
                                        <th class="py-3.5 px-4">Price</th>
                                        <th class="py-3.5 px-4">Delivery</th>
                                        <th class="py-3.5 px-4">Status</th>
                                        <th class="py-3.5 px-5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                    <?php foreach ($myGigs as $gig): 
                                        $gigCover = resolveGigImage($gig['image'] ?? null, '../');
                                        $gigStatus = strtolower($gig['status'] ?? 'active');
                                        $gigJson = htmlspecialchars(json_encode([
                                            'id' => $gig['id'],
                                            'title' => $gig['title'],
                                            'category_id' => $gig['category_id'],
                                            'price' => (float)$gig['price'],
                                            'delivery_time' => (int)$gig['delivery_time'],
                                            'status' => $gigStatus,
                                            'description' => $gig['description'] ?? '',
                                            'image' => $gigCover
                                        ]), ENT_QUOTES, 'UTF-8');
                                    ?>
                                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                            <td class="py-4 px-5">
                                                <div class="flex items-center gap-3">
                                                    <img src="<?= htmlspecialchars($gigCover); ?>" alt="Gig" class="w-12 h-12 rounded-xl object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0" onerror="this.src='../assets/img/placeholder-gig.svg'">
                                                    <div class="min-w-0">
                                                        <div class="font-bold text-slate-900 dark:text-white line-clamp-1 text-xs sm:text-sm"><?= htmlspecialchars($gig['title']); ?></div>
                                                        <div class="flex flex-wrap items-center gap-2 mt-1">
                                                            <?php if (!empty($gig['category_name'])): ?>
                                                                <span class="text-[10px] font-semibold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/60 px-2 py-0.5 rounded-md border border-purple-200/50 dark:border-purple-900/40"><?= htmlspecialchars($gig['category_name']); ?></span>
                                                            <?php endif; ?>
                                                            <span class="text-[11px] text-slate-400 dark:text-slate-500"><?= date('M d, Y', strtotime($gig['created_at'])); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                                $<?= number_format((float)$gig['price'], 2); ?>
                                            </td>
                                            <td class="py-4 px-4 text-xs text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                                <?= (int)$gig['delivery_time']; ?> days
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap">
                                                <?php if ($gigStatus === 'active'): ?>
                                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60 inline-flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                                    </span>
                                                <?php elseif ($gigStatus === 'paused'): ?>
                                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60 inline-flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Paused
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 inline-flex items-center gap-1">
                                                        <?= ucfirst($gigStatus); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-5 whitespace-nowrap text-right">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <!-- Toggle Pause / Resume -->
                                                    <a href="dashboard.php?toggleStatus=<?= $gig['id']; ?>" class="p-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition" title="<?= $gigStatus === 'active' ? 'Pause Listing' : 'Resume Listing'; ?>">
                                                        <i class="<?= $gigStatus === 'active' ? 'ri-pause-circle-line text-amber-500' : 'ri-play-circle-line text-emerald-500'; ?> text-base"></i>
                                                    </a>
                                                    <!-- Edit Modal Trigger -->
                                                    <button type="button" onclick="openEditModal(<?= $gigJson; ?>)" class="p-1.5 bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-semibold transition border border-indigo-200/50 dark:border-indigo-900/40 cursor-pointer" title="Edit Service Details">
                                                        <i class="ri-edit-line text-base"></i>
                                                    </button>
                                                    <!-- Public View Link -->
                                                    <a href="../public/gig_detail.php?id=<?= $gig['id']; ?>" target="_blank" class="p-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition" title="Preview Public Listing">
                                                        <i class="ri-eye-line text-base"></i>
                                                    </a>
                                                    <!-- Delete -->
                                                    <a href="dashboard.php?deleteId=<?= $gig['id']; ?>" onclick="return confirm('Are you sure you want to delete this service listing?');" class="p-1.5 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 rounded-lg text-xs font-semibold transition border border-rose-200/50 dark:border-rose-900/40" title="Delete Listing">
                                                        <i class="ri-delete-bin-line text-base"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 px-4">
                            <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-500 mx-auto flex items-center justify-center text-2xl mb-3">
                                <i class="ri-inbox-line"></i>
                            </div>
                            <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm">No services published yet</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">Publish your first service using the form above to start getting booked by clients worldwide.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Client Orders & Project Proposals Widget -->
                <div class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg">
                                <i class="ri-inbox-archive-line"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-base">Recent Client Orders & Work Requests</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Incoming bookings from marketplace gigs and direct custom proposals.</p>
                            </div>
                        </div>
                        <a href="manage_job_requests.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-900/60 text-xs font-bold transition border border-purple-200/60 dark:border-purple-800/60">
                            <span>Manage All Orders</span>
                            <i class="ri-arrow-right-line"></i>
                        </a>
                    </div>

                    <?php if (!empty($recentRequests)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs sm:text-sm">
                                <thead class="bg-slate-50/80 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800 uppercase text-[11px] tracking-wider">
                                    <tr>
                                        <th class="py-3.5 px-5">Client</th>
                                        <th class="py-3.5 px-4">Work / Service</th>
                                        <th class="py-3.5 px-4">Amount</th>
                                        <th class="py-3.5 px-4">Date</th>
                                        <th class="py-3.5 px-4">Status</th>
                                        <th class="py-3.5 px-5 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                    <?php foreach ($recentRequests as $req): 
                                        $cName = trim(($req['client_first_name'] ?? '') . ' ' . ($req['client_last_name'] ?? '')) ?: ($req['client_username'] ?? 'Client');
                                        $cAvatar = resolveAvatarUrl($req['client_avatar'] ?? null, '../', $cName);
                                        $rStatus = strtolower($req['status'] ?? 'pending');
                                        $isGig = ($req['type'] === 'gig_request');
                                        $rBadge = match ($rStatus) {
                                            'accepted', 'in_progress' => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60',
                                            'completed' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60',
                                            'cancelled', 'rejected' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60',
                                            default => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60',
                                        };
                                    ?>
                                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                            <td class="py-3.5 px-5">
                                                <div class="flex items-center gap-2.5">
                                                    <img src="<?= htmlspecialchars($cAvatar); ?>" alt="Client" class="w-8 h-8 rounded-full object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0" onerror="this.src='../assets/img/placeholder-avatar.svg'">
                                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs"><?= htmlspecialchars($cName); ?></span>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <div class="flex items-center gap-1.5 mb-0.5">
                                                    <?php if ($isGig): ?>
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200/50 dark:border-purple-800/40">Gig Order</span>
                                                    <?php else: ?>
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/50 dark:border-blue-800/40">Direct Project</span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="font-medium text-slate-800 dark:text-slate-200 line-clamp-1 text-xs"><?= htmlspecialchars($req['title'] ?? 'Platform Request'); ?></span>
                                            </td>
                                            <td class="py-3.5 px-4 font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap text-xs">
                                                $<?= number_format((float)($req['amount'] ?? 0), 2); ?>
                                            </td>
                                            <td class="py-3.5 px-4 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                                <?= !empty($req['activity_date']) ? date('M d, Y', strtotime($req['activity_date'])) : 'Recent'; ?>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border <?= $rBadge; ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $rStatus)); ?>
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-5 whitespace-nowrap text-right">
                                                <a href="manage_job_requests.php" class="px-3 py-1 bg-purple-50 dark:bg-purple-950/60 hover:bg-purple-100 dark:hover:bg-purple-900/60 text-purple-700 dark:text-purple-300 rounded-lg text-xs font-semibold transition border border-purple-200/50 dark:border-purple-800/40">
                                                    Manage
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 px-4">
                            <i class="ri-inbox-line text-2xl text-slate-300 dark:text-slate-600 mb-1 block"></i>
                            <p class="text-xs text-slate-500 dark:text-slate-400">No client orders received yet. Active gig listings will attract client requests here.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Client Reviews & Feedback Widget -->
                <div class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-xl overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-500 flex items-center justify-center text-lg">
                                <i class="ri-chat-smile-2-line"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-base">Client Reviews & Testimonials</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Feedback submitted by clients on completed deliveries.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/60 text-xs font-bold">
                            <i class="ri-star-fill text-amber-500"></i>
                            <span><?= number_format($rating, 1); ?> Overall</span>
                        </div>
                    </div>

                    <?php if (!empty($recentReviews)): ?>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <?php foreach ($recentReviews as $rev): 
                                $clientName = trim(($rev['client_first_name'] ?? '') . ' ' . ($rev['client_last_name'] ?? '')) ?: ($rev['client_username'] ?? 'Client');
                                $clientAvatar = resolveAvatarUrl($rev['client_avatar'] ?? null, '../', $clientName);
                                $stars = (int)$rev['rating'];
                            ?>
                                <div class="p-5 hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <div class="flex items-center justify-between gap-4 mb-2">
                                        <div class="flex items-center gap-3">
                                            <img src="<?= htmlspecialchars($clientAvatar); ?>" alt="Client" class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-slate-700" onerror="this.src='../assets/img/placeholder-avatar.svg'">
                                            <div>
                                                <h4 class="text-xs font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($clientName); ?></h4>
                                                <p class="text-[11px] text-slate-400 dark:text-slate-500"><?= htmlspecialchars($rev['order_title']); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="flex items-center gap-0.5 text-amber-400 text-xs">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?= $i <= $stars ? 'ri-star-fill' : 'ri-star-line text-slate-300 dark:text-slate-600'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500"><?= date('M d, Y', strtotime($rev['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed italic pl-12">
                                        "<?= htmlspecialchars($rev['comment']); ?>"
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 px-4">
                            <i class="ri-chat-heart-line text-2xl text-slate-300 dark:text-slate-600 mb-1 block"></i>
                            <p class="text-xs text-slate-500 dark:text-slate-400">No client reviews received yet. High-quality deliverables will earn you 5-star ratings here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Gig Modal -->
    <div id="editGigModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden transition-opacity duration-300" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-2xl w-full p-6 sm:p-8 max-h-[92vh] overflow-y-auto transform transition-transform">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/80 mb-5">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg">
                        <i class="ri-edit-line"></i>
                    </div>
                    <div>
                        <h3 id="edit-modal-title" class="text-base font-bold text-slate-900 dark:text-white">Edit Service (Gig) Details</h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500">Update pricing, delivery time, description, or cover media.</p>
                    </div>
                </div>
                <button type="button" onclick="closeEditModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center text-lg cursor-pointer transition">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" id="edit_gig_id" name="gig_id" value="">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Gig Title *</label>
                    <input type="text" id="edit_title" name="edit_title" required
                           class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs transition focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Category *</label>
                        <select id="edit_category" name="edit_category" required
                                class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-medium cursor-pointer focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Price ($ USD) *</label>
                        <input type="number" step="0.01" min="5" id="edit_price" name="edit_price" required
                               class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Delivery Time (Days) *</label>
                        <input type="number" min="1" max="90" id="edit_delivery_time" name="edit_delivery_time" required
                               class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status</label>
                    <select id="edit_status" name="edit_status"
                            class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs font-medium cursor-pointer focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                        <option value="active">Active (Visible in Marketplace)</option>
                        <option value="paused">Paused (Temporarily Hidden)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Description *</label>
                    <textarea id="edit_description" name="edit_description" rows="4" required
                              class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs leading-relaxed focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Cover Image</label>
                    <div class="flex items-center gap-3">
                        <img id="edit_current_img" src="../assets/img/placeholder-gig.svg" alt="Current Cover" class="w-16 h-14 rounded-xl object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0">
                        <div class="flex-grow">
                            <input type="file" name="edit_image" accept="image/*" onchange="previewImage(this, 'edit-gig-preview', 'edit-gig-img')"
                                   class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-purple-50 dark:file:bg-purple-950/60 file:text-purple-700 dark:file:text-purple-300 hover:file:bg-purple-100 cursor-pointer">
                            <span class="text-[11px] text-slate-400 block mt-0.5">Leave empty to keep existing image.</span>
                        </div>
                    </div>
                    <div id="edit-gig-preview" class="hidden mt-2 p-1.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 inline-block relative">
                        <img id="edit-gig-img" src="" alt="New Cover Preview" class="h-20 w-32 rounded-lg object-cover">
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-800/80">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" name="updateGig" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white shadow-md shadow-purple-500/20 transition cursor-pointer flex items-center gap-1.5">
                        <i class="ri-check-line"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>

    <!-- AOS Animation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });

        // Toggle Publish Gig Form Card
        function togglePublishGigSection() {
            const body = document.getElementById('publish-gig-body');
            const chevron = document.getElementById('publish-gig-chevron');
            if (body.classList.contains('hidden')) {
                body.classList.remove('hidden');
                if (chevron) {
                    chevron.className = 'ri-arrow-up-s-line';
                }
                document.getElementById('publish-gig').scrollIntoView({ behavior: 'smooth' });
            } else {
                body.classList.add('hidden');
                if (chevron) {
                    chevron.className = 'ri-arrow-down-s-line';
                }
            }
        }

        // Live Image Previewer
        function previewImage(input, containerId, imgId) {
            const container = document.getElementById(containerId);
            const img = document.getElementById(imgId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (img) img.src = e.target.result;
                    if (container) container.classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function clearImagePreview(inputName, containerId, imgId) {
            const input = document.querySelector(`input[name="${inputName}"]`);
            if (input) input.value = '';
            const container = document.getElementById(containerId);
            if (container) container.classList.add('hidden');
            const img = document.getElementById(imgId);
            if (img) img.src = '';
        }

        // Edit Gig Modal Controls
        function openEditModal(gig) {
            document.getElementById('edit_gig_id').value = gig.id || '';
            document.getElementById('edit_title').value = gig.title || '';
            document.getElementById('edit_category').value = gig.category_id || '';
            document.getElementById('edit_price').value = gig.price || '';
            document.getElementById('edit_delivery_time').value = gig.delivery_time || 3;
            document.getElementById('edit_status').value = gig.status || 'active';
            document.getElementById('edit_description').value = gig.description || '';
            
            const currentImg = document.getElementById('edit_current_img');
            if (currentImg && gig.image) {
                currentImg.src = gig.image;
            }

            const previewContainer = document.getElementById('edit-gig-preview');
            if (previewContainer) previewContainer.classList.add('hidden');

            const modal = document.getElementById('editGigModal');
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeEditModal() {
            const modal = document.getElementById('editGigModal');
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>

</html>