<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include('../config/db.con.php');
include_once('../includes/image_helper.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$success = '';
$error = '';

// Fetch user data
try {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User account not found.";
        header('Location: ../auth/login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Profile fetch error: " . $e->getMessage());
    $error = "An error occurred while fetching profile data.";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $email = validateEmailInput($_POST['email'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "First name, last name, and email are required fields.";
    } else {
        try {
            $profile_picture = $user['profile_picture'];

            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
                $uploadDir = '../uploads/proPic/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($extension, $allowed)) {
                    $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['profile_picture']['name']));
                    $targetFile = $uploadDir . $safeName;

                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                        $profile_picture = 'uploads/proPic/' . $safeName;
                    }
                } else {
                    $error = "Invalid file type. Only JPG, PNG, and WebP images are permitted.";
                }
            }

            if (empty($error)) {
                // Update user table
                $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, bio = ?, profile_picture = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->execute([$first_name, $last_name, $email, $bio, $profile_picture, $user_id]);

                // Sync freelancer tables if user has freelancer role
                if ($user['role'] === 'freelancer') {
                    $stmtFp = $conn->prepare("UPDATE freelancer_profiles SET bio = ? WHERE user_id = ?");
                    $stmtFp->execute([$bio, $user_id]);

                    $stmtFree = $conn->prepare("UPDATE freelancers SET bio = ?, profile_picture = ? WHERE user_id = ?");
                    $stmtFree->execute([$bio, $profile_picture, $user_id]);
                }

                // Update session values
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $email;

                $success = "Your profile has been updated successfully!";

                // Refresh user record
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = "An error occurred while updating profile: " . $e->getMessage();
        }
    }
}

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['username'] ?? 'User');
// Normalize avatar display path with fallback initials
$pic = resolveAvatarUrl($user['profile_picture'] ?? null, '../', $fullName);

$userRole = strtolower($user['role'] ?? 'client');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings & Profile - FreelanceHub</title>
    <meta name="description" content="Manage your FreelanceHub account details, contact info, bio, and profile picture.">
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
        <?php if ($success): ?>
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200 flex items-center justify-between shadow-sm animate-fadeIn">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/60 flex items-center justify-center flex-shrink-0">
                        <i class="ri-checkbox-circle-fill text-emerald-600 dark:text-emerald-400 text-lg"></i>
                    </div>
                    <span class="text-sm font-medium"><?= htmlspecialchars($success); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 p-1"><i class="ri-close-line text-lg"></i></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200 flex items-center justify-between shadow-sm animate-fadeIn">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-900/60 flex items-center justify-center flex-shrink-0">
                        <i class="ri-error-warning-fill text-rose-600 dark:text-rose-400 text-lg"></i>
                    </div>
                    <span class="text-sm font-medium"><?= htmlspecialchars($error); ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 p-1"><i class="ri-close-line text-lg"></i></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8" data-aos="fade-up">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400 mb-1">
                    <i class="ri-user-settings-line"></i> Profile Preferences
                </div>
                <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Account Profile & Settings</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Manage your public credentials, avatar photo, and contact information.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= $userRole === 'client' ? '../client/dashboard.php' : ($userRole === 'freelancer' ? '../freelancer/dashboard.php' : '../admin/dashboard.php'); ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl text-slate-700 dark:text-slate-200 text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm">
                    <i class="ri-dashboard-line text-purple-600 dark:text-purple-400"></i> Go to Dashboard
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Column: Profile Summary Snapshot -->
            <div class="lg:col-span-4" data-aos="fade-up" data-aos-delay="50">
                <div class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm backdrop-blur-xl sticky top-28 text-center">
                    <!-- Avatar Preview & Status Indicator -->
                    <div class="relative w-28 h-28 mx-auto mb-4">
                        <img id="avatar-display" src="<?= htmlspecialchars($pic); ?>" alt="Avatar" class="w-28 h-28 rounded-2xl object-cover ring-4 ring-purple-100 dark:ring-purple-900/40 shadow-md border border-slate-100 dark:border-slate-800 mx-auto" onerror="this.src='../assets/img/placeholder-avatar.svg';">
                        <div class="absolute -bottom-1.5 -right-1.5 w-7 h-7 rounded-full bg-emerald-500 ring-4 ring-white dark:ring-slate-900 flex items-center justify-center text-white text-xs shadow-sm" title="Active Online">
                            <i class="ri-check-line"></i>
                        </div>
                    </div>

                    <!-- Name and Username -->
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($fullName); ?></h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 font-medium mt-0.5">@<?= htmlspecialchars($user['username']); ?></p>

                    <!-- Role Badge -->
                    <div class="mt-3">
                        <?php if ($userRole === 'freelancer'): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60">
                                <i class="ri-code-box-line"></i> Freelance Specialist
                            </span>
                        <?php elseif ($userRole === 'client'): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60">
                                <i class="ri-user-star-line"></i> Hiring Client
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60">
                                <i class="ri-shield-star-line"></i> Administrator
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="my-5 border-t border-slate-100 dark:border-slate-800"></div>

                    <!-- User Metadata -->
                    <div class="text-left space-y-3 text-xs text-slate-600 dark:text-slate-300">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 dark:text-slate-500 flex items-center gap-1.5"><i class="ri-mail-line text-sm"></i> Email</span>
                            <span class="font-medium text-slate-800 dark:text-slate-200 truncate max-w-[160px]" title="<?= htmlspecialchars($user['email']); ?>"><?= htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 dark:text-slate-500 flex items-center gap-1.5"><i class="ri-calendar-line text-sm"></i> Member Since</span>
                            <span class="font-medium text-slate-800 dark:text-slate-200"><?= date('F Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 dark:text-slate-500 flex items-center gap-1.5"><i class="ri-shield-check-line text-sm"></i> Status</span>
                            <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Verified</span>
                        </div>
                    </div>

                    <?php if (!empty($user['bio'])): ?>
                        <div class="mt-5 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-left text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">About Me</div>
                            <?= nl2br(htmlspecialchars($user['bio'])); ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-2">
                        <a href="<?= $userRole === 'client' ? '../client/dashboard.php' : '../freelancer/dashboard.php'; ?>" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-semibold shadow-md shadow-purple-500/20 transition-all flex items-center justify-center gap-2">
                            <i class="ri-dashboard-3-line"></i> Access My Portal
                        </a>
                        <a href="gig.php" class="w-full py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-semibold transition-all flex items-center justify-center gap-2">
                            <i class="ri-compass-3-line"></i> Explore Marketplace
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column: Profile Edit Form -->
            <div class="lg:col-span-8" data-aos="fade-up" data-aos-delay="100">
                <div class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 sm:p-8 shadow-sm backdrop-blur-xl">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="w-10 h-10 rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl flex-shrink-0 border border-purple-100 dark:border-purple-900/40">
                            <i class="ri-edit-circle-line"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Personal Information & Profile</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Update your public contact info, bio, and portrait photo.</p>
                        </div>
                    </div>

                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <!-- Names Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">First Name <span class="text-rose-500">*</span></label>
                                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? ''); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-white text-sm transition-all">
                            </div>
                            <div>
                                <label for="last_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Last Name <span class="text-rose-500">*</span></label>
                                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? ''); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-white text-sm transition-all">
                            </div>
                        </div>

                        <!-- Email Address -->
                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Email Address <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                                    <i class="ri-mail-line"></i>
                                </span>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" required class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-white text-sm transition-all">
                            </div>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">This email will be used for order notifications and account recovery.</p>
                        </div>

                        <!-- Bio / Professional Summary -->
                        <div>
                            <label for="bio" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Bio / Professional Summary</label>
                            <textarea id="bio" name="bio" rows="4" placeholder="Tell clients and collaborators about your expertise, experience, and the services you deliver..." class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100/60 dark:hover:bg-slate-800 focus:bg-white dark:focus:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 text-slate-900 dark:text-white text-sm transition-all resize-y placeholder-slate-400 dark:placeholder-slate-500"><?= htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">A compelling bio significantly improves client trust and project inquiries.</p>
                        </div>

                        <!-- Avatar Upload Box with Live Preview -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Profile Avatar Photo</label>
                            <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-purple-500/50 rounded-2xl p-6 transition-all bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800/80 text-center cursor-pointer relative" onclick="document.getElementById('profile_picture').click()">
                                <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-900/40 flex items-center justify-center text-2xl mx-auto mb-2">
                                    <i class="ri-upload-cloud-2-line"></i>
                                </div>
                                <div class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Click to browse or drag and drop a new picture</div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500">Supports JPG, PNG, or WebP (Max 5MB). Existing photo will be replaced.</div>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewImage(this)">
                            </div>
                            <div id="file-chosen-name" class="text-xs font-medium text-purple-600 dark:text-purple-400 mt-2 hidden flex items-center gap-1.5">
                                <i class="ri-image-line"></i>
                                <span id="file-name-text"></span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <a href="profile.php" class="px-5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm font-semibold transition-all">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white text-sm font-semibold shadow-md shadow-purple-500/20 hover:shadow-lg transition-all flex items-center gap-2">
                                <i class="ri-save-line"></i> Save Profile Changes
                            </button>
                        </div>
                    </form>
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

        // Instant Avatar Preview
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                reader.onload = function(e) {
                    const avatarImg = document.getElementById('avatar-display');
                    if (avatarImg) {
                        avatarImg.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);

                const chosenDisplay = document.getElementById('file-chosen-name');
                const chosenText = document.getElementById('file-name-text');
                if (chosenDisplay && chosenText) {
                    chosenText.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                    chosenDisplay.classList.remove('hidden');
                }
            }
        }
    </script>
</body>

</html>