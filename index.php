<?php
session_start();
include_once "./config/db.con.php";

// Fetch active categories with gig counts for dynamic display
$categories = [];
try {
    $stmtCat = $conn->query("
        SELECT c.id, c.name, COUNT(g.id) as gig_count
        FROM categories c
        LEFT JOIN gigs g ON c.id = g.category_id AND g.status = 'active'
        WHERE c.status = 'active'
        GROUP BY c.id, c.name
        ORDER BY gig_count DESC, c.name ASC
        LIMIT 6
    ");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories for index: " . $e->getMessage());
}

if (empty($categories)) {
    // Dynamic fallbacks
    $categories = [
        ['id' => 1, 'name' => 'Development', 'gig_count' => 24],
        ['id' => 2, 'name' => 'Design', 'gig_count' => 18],
        ['id' => 3, 'name' => 'Writing', 'gig_count' => 12],
        ['id' => 4, 'name' => 'Marketing', 'gig_count' => 9],
        ['id' => 5, 'name' => 'Video & Animation', 'gig_count' => 7],
        ['id' => 6, 'name' => 'Music & Audio', 'gig_count' => 6],
    ];
}

// Fetch featured & trending gigs from database
$featuredGigs = [];
try {
    $stmtGigs = $conn->query("
        SELECT g.id, g.title, g.description, g.price, g.delivery_time, g.image, g.avg_rating, g.reviews_count,
               u.id AS seller_id, u.username, u.first_name, u.last_name, u.profile_picture,
               c.name as category_name
        FROM gigs g
        JOIN users u ON g.freelancer_id = u.id
        LEFT JOIN categories c ON g.category_id = c.id
        WHERE g.status = 'active'
        ORDER BY g.avg_rating DESC, g.reviews_count DESC, g.created_at DESC
        LIMIT 6
    ");
    $featuredGigs = $stmtGigs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching featured gigs for index: " . $e->getMessage());
}

// Fallback featured gigs if database is offline or empty
if (empty($featuredGigs)) {
    $featuredGigs = [
        [
            'id' => 1,
            'title' => 'Professional Full-Stack Web App in Next.js & Tailwind CSS',
            'category_name' => 'Development',
            'price' => 150.00,
            'delivery_time' => 3,
            'avg_rating' => 5.0,
            'reviews_count' => 24,
            'username' => 'alexdev',
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
            'profile_picture' => 'assets/img/login.jpg',
            'image' => 'assets/img/hero-image.png'
        ],
        [
            'id' => 2,
            'title' => 'Premium Brand Identity & Modern Logo Design Package',
            'category_name' => 'Design',
            'price' => 85.00,
            'delivery_time' => 2,
            'avg_rating' => 4.9,
            'reviews_count' => 42,
            'username' => 'elenadesign',
            'first_name' => 'Elena',
            'last_name' => 'Rostova',
            'profile_picture' => 'assets/img/login.jpg',
            'image' => 'assets/img/hero-image.png'
        ],
        [
            'id' => 3,
            'title' => 'High-Converting SEO Copywriting & Content Strategy',
            'category_name' => 'Writing',
            'price' => 60.00,
            'delivery_time' => 1,
            'avg_rating' => 4.8,
            'reviews_count' => 19,
            'username' => 'marcuspen',
            'first_name' => 'Marcus',
            'last_name' => 'Vance',
            'profile_picture' => 'assets/img/login.jpg',
            'image' => 'assets/img/hero-image.png'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreelanceHub - Global Marketplace for Top Talent & Services</title>
    <meta name="description" content="Connect with vetted specialists across development, design, marketing, and content. Protect your milestones with escrow security on FreelanceHub.">
    <meta name="keywords" content="freelance, freelance marketplace, hire developers, graphic designers, copywriters, escrow protection">
    <meta name="theme-color" content="#6366f1">

    <!-- Favicon -->
    <link rel="shortcut icon" href="./assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/svg+xml" href="./assets/img/favicon.svg">

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
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-14px)' },
                        },
                        glow: {
                            '0%': { boxShadow: '0 0 20px rgba(99, 102, 241, 0.4)' },
                            '100%': { boxShadow: '0 0 35px rgba(139, 92, 246, 0.7)' },
                        },
                    },
                }
            }
        }
    </script>
</head>

<body class="bg-slate-50 text-slate-800 font-sans overflow-x-hidden min-h-screen flex flex-col">

    <!-- Navigation Header -->
    <?php include_once "./includes/index_header.php"; ?>

    <!-- Main Hero Section Component -->
    <?php include_once "./components/hero_section.php"; ?>

    <!-- Live Statistics Counter Component -->
    <?php include_once "./components/stats_section.php"; ?>

    <!-- Popular Service Categories Component -->
    <?php include_once "./components/categories_section.php"; ?>

    <!-- Featured & Trending Marketplace Gigs Component -->
    <?php include_once "./components/featured_gigs_section.php"; ?>

    <!-- Dual-Sided Value & How-It-Works Component -->
    <?php include_once "./components/features_section.php"; ?>

    <!-- Verified Client Reviews & Testimonials Slider Component -->
    <?php include_once "./components/testimonials_section.php"; ?>

    <!-- Global High-Conversion Call-to-Action Banner Component -->
    <?php include_once "./components/cta_section.php"; ?>

    <!-- Unified Global Footer -->
    <?php include_once "./includes/footer.php"; ?>

    <!-- Scroll to Top Button -->
    <button id="scrollTop" class="fixed bottom-6 right-6 w-12 h-12 bg-gradient-to-br from-purple-600 to-indigo-600 text-white rounded-2xl shadow-xl opacity-0 invisible transition-all duration-300 hover:scale-110 flex items-center justify-center z-50">
        <i class="ri-arrow-up-line text-xl"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="./assets/js/index.js"></script>
    <script src="./assets/js/slider.js"></script>
</body>

</html>