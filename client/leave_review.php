<?php
// Include necessary files for connection and session
include('../config/db.con.php');
include_once('../includes/image_helper.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure logged in as client
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    $_SESSION['error'] = "Please log in as a client to leave a review.";
    header('Location: ../auth/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$freelancerId = isset($_GET['freelancer_id']) ? (int)$_GET['freelancer_id'] : 0;
$projectId = isset($_GET['project_id']) && is_numeric($_GET['project_id']) ? (int)$_GET['project_id'] : null;
$gigId = isset($_GET['gig_id']) && is_numeric($_GET['gig_id']) ? (int)$_GET['gig_id'] : null;

if ($freelancerId <= 0) {
    $_SESSION['error'] = "Invalid freelancer specified for review.";
    header('Location: dashboard.php');
    exit;
}

// Fetch freelancer details
$sql = "
    SELECT u.id AS user_id, u.username, u.first_name, u.last_name, u.profile_picture,
           COALESCE(f.skills, fp.title, 'Freelancer') AS skills
    FROM users u
    LEFT JOIN freelancers f ON (f.user_id = u.id OR f.id = :freelancer_id)
    LEFT JOIN freelancer_profiles fp ON fp.user_id = u.id
    WHERE u.id = :freelancer_id OR f.id = :freelancer_id
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->execute(['freelancer_id' => $freelancerId]);
$freelancer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$freelancer) {
    $_SESSION['error'] = "Freelancer not found.";
    header('Location: dashboard.php');
    exit;
}

$displayName = trim(($freelancer['first_name'] ?? '') . ' ' . ($freelancer['last_name'] ?? '')) ?: ($freelancer['username'] ?? 'Freelancer');
$profilePic = resolveAvatarUrl($freelancer['profile_picture'] ?? null, '../', $displayName);

// Check if review already exists
$review = null;
if ($projectId) {
    $checkReviewSql = "SELECT * FROM reviews WHERE client_id = :client_id AND freelancer_id = :freelancer_id AND project_id = :project_id LIMIT 1";
    $checkStmt = $conn->prepare($checkReviewSql);
    $checkStmt->execute([
        'client_id' => $userId,
        'freelancer_id' => $freelancer['user_id'],
        'project_id' => $projectId,
    ]);
    $review = $checkStmt->fetch(PDO::FETCH_ASSOC);
} elseif ($gigId) {
    $checkReviewSql = "SELECT * FROM reviews WHERE client_id = :client_id AND freelancer_id = :freelancer_id AND gig_id = :gig_id LIMIT 1";
    $checkStmt = $conn->prepare($checkReviewSql);
    $checkStmt->execute([
        'client_id' => $userId,
        'freelancer_id' => $freelancer['user_id'],
        'gig_id' => $gigId,
    ]);
    $review = $checkStmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review - FreelanceHub</title>
    <meta name="description" content="Rate and review your completed project with the freelancer on FreelanceHub.">
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
        <div class="absolute -top-32 left-1/4 w-[32rem] h-[32rem] bg-amber-500/15 dark:bg-amber-600/15 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 right-10 w-[28rem] h-[28rem] bg-purple-500/15 dark:bg-purple-600/15 rounded-full blur-3xl"></div>
    </div>

    <!-- Header -->
    <?php include('../includes/header.php'); ?>

    <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full relative z-10">
        <!-- Back Navigation -->
        <div class="mb-6">
            <a href="dashboard.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition">
                <i class="ri-arrow-left-line text-base"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>

        <!-- Page Header -->
        <div class="text-center max-w-xl mx-auto mb-8" data-aos="fade-up">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60 mb-2">
                <i class="ri-star-line"></i> Client Feedback
            </span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                <?= $review ? 'Update Your Review' : 'Rate Your Experience'; ?>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Your feedback acknowledges great work and helps fellow clients make informed decisions.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Freelancer Info Card -->
            <div class="md:col-span-1" data-aos="fade-up" data-aos-delay="50">
                <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm text-center">
                    <img src="<?= htmlspecialchars($profilePic); ?>" alt="<?= htmlspecialchars($displayName); ?>"
                         class="w-20 h-20 rounded-full object-cover mx-auto mb-3 border-2 border-purple-200 dark:border-purple-800 shadow-sm"
                         onerror="this.src='../assets/img/placeholder-avatar.svg';">
                    <h3 class="font-bold text-slate-900 dark:text-white text-base"><?= htmlspecialchars($displayName); ?></h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mb-3">@<?= htmlspecialchars($freelancer['username']); ?></p>

                    <span class="inline-block px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-full text-xs font-medium border border-slate-200/60 dark:border-slate-700">
                        <?= htmlspecialchars($freelancer['skills']); ?>
                    </span>
                </div>
            </div>

            <!-- Review Form -->
            <div class="md:col-span-2" data-aos="fade-up" data-aos-delay="100">
                <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl p-6 sm:p-8 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm">
                    <?php if ($review): ?>
                        <div class="mb-6 p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200 text-xs flex items-center gap-2">
                            <i class="ri-information-line text-base text-blue-600 dark:text-blue-400"></i>
                            <span>You already reviewed this project. Submitting below will update your rating and feedback.</span>
                        </div>
                    <?php endif; ?>

                    <form action="leave_review_action.php" method="POST" class="space-y-6">
                        <!-- Star Rating Selector -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Overall Rating *
                            </label>
                            <div class="flex items-center gap-2" id="star-picker">
                                <?php
                                    $currentRating = $review ? (int)$review['rating'] : 5;
                                    for ($i = 1; $i <= 5; $i++):
                                ?>
                                    <button type="button" data-rating="<?= $i; ?>" class="star-btn text-3xl transition transform hover:scale-110 focus:outline-none cursor-pointer <?= $i <= $currentRating ? 'text-amber-400' : 'text-slate-300 dark:text-slate-700'; ?>">
                                        <i class="ri-star-fill"></i>
                                    </button>
                                <?php endfor; ?>
                                <span id="rating-label" class="text-sm font-bold text-slate-700 dark:text-slate-300 ml-2"><?= $currentRating; ?> of 5 Stars</span>
                            </div>
                            <input type="hidden" id="rating" name="rating" value="<?= $currentRating; ?>">
                        </div>

                        <!-- Feedback Commentary -->
                        <div>
                            <label for="comment" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Detailed Review & Comments *
                            </label>
                            <textarea id="comment" name="comment" rows="5" required
                                      placeholder="Describe the quality of delivery, responsiveness, communication, and overall experience..."
                                      class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm leading-relaxed transition"><?= htmlspecialchars($review['comment'] ?? ''); ?></textarea>
                        </div>

                        <input type="hidden" name="clientId" value="<?= $userId; ?>">
                        <input type="hidden" name="freelancerId" value="<?= $freelancer['user_id']; ?>">
                        <input type="hidden" name="projectId" value="<?= $projectId ?: ''; ?>">
                        <input type="hidden" name="gigId" value="<?= $gigId ?: ''; ?>">

                        <!-- Submit Button -->
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-2xl font-bold text-sm shadow-xl shadow-purple-500/20 hover:shadow-purple-500/30 transition transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2 cursor-pointer">
                                <i class="ri-check-line text-base"></i>
                                <span><?= $review ? 'Update Review' : 'Publish Review'; ?></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>

    <!-- Interactive Star Rating Script with Dark Mode Support -->
    <script>
        const starButtons = document.querySelectorAll('#star-picker .star-btn');
        const ratingInput = document.getElementById('rating');
        const ratingLabel = document.getElementById('rating-label');

        function updateStars(val) {
            ratingInput.value = val;
            ratingLabel.textContent = val + ' of 5 Stars';
            starButtons.forEach(btn => {
                const r = parseInt(btn.getAttribute('data-rating'));
                if (r <= val) {
                    btn.classList.remove('text-slate-300', 'dark:text-slate-700');
                    btn.classList.add('text-amber-400');
                } else {
                    btn.classList.remove('text-amber-400');
                    btn.classList.add('text-slate-300', 'dark:text-slate-700');
                }
            });
        }

        starButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const val = parseInt(btn.getAttribute('data-rating'));
                updateStars(val);
            });
        });
    </script>
</body>

</html>
