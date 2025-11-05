<?php
session_start();
include_once "./config/db.con.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreelanceHub - Connect with Top Talent Worldwide</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- AOS Animation -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">

    <!-- Review Slider Styles -->
    <link rel="stylesheet" href="./assets/css/slider_style.css">

    <!-- Tailwind Custom Configuration -->
    <script>
        tailwind.config = {
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
                        'slide-down': 'slideDown 0.5s ease-out',
                        'scale-up': 'scaleUp 0.3s ease-out',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': {
                                transform: 'translateY(0)'
                            },
                            '50%': {
                                transform: 'translateY(-20px)'
                            },
                        },
                        slideUp: {
                            '0%': {
                                transform: 'translateY(100px)',
                                opacity: '0'
                            },
                            '100%': {
                                transform: 'translateY(0)',
                                opacity: '1'
                            },
                        },
                        slideDown: {
                            '0%': {
                                transform: 'translateY(-100px)',
                                opacity: '0'
                            },
                            '100%': {
                                transform: 'translateY(0)',
                                opacity: '1'
                            },
                        },
                        scaleUp: {
                            '0%': {
                                transform: 'scale(0.8)',
                                opacity: '0'
                            },
                            '100%': {
                                transform: 'scale(1)',
                                opacity: '1'
                            },
                        },
                        glow: {
                            '0%': {
                                boxShadow: '0 0 20px rgba(99, 102, 241, 0.5)'
                            },
                            '100%': {
                                boxShadow: '0 0 40px rgba(139, 92, 246, 0.8)'
                            },
                        },
                    },
                }
            }
        }
    </script>
    <!-- Custom Styles -->
    <link rel="stylesheet" href="./assets/css/index_style.css">
</head>

<body class="bg-gray-50 overflow-x-hidden">

    <?php include_once "./includes/index_header.php"; ?>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-20 bg-gradient-to-br from-purple-50 via-white to-blue-50">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
            <div class="absolute top-1/3 right-1/4 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse" style="animation-delay: 2s;"></div>
            <div class="absolute bottom-1/4 left-1/3 w-96 h-96 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse" style="animation-delay: 4s;"></div>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-20">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="text-center lg:text-left" data-aos="fade-right">
                    <div class="inline-flex items-center space-x-2 bg-purple-100 text-purple-700 px-4 py-2 rounded-full mb-6">
                        <span class="w-2 h-2 bg-purple-600 rounded-full animate-pulse"></span>
                        <span class="text-sm font-semibold">🎉 #1 Freelance Platform of 2024</span>
                    </div>

                    <h1 class="hero-title text-5xl sm:text-6xl lg:text-7xl font-extrabold text-gray-900 mb-6 leading-tight">
                        Discover Top
                        <span class="gradient-text block mt-2">Freelance Talent</span>
                    </h1>

                    <p class="text-xl text-gray-600 mb-8 leading-relaxed max-w-2xl">
                        Connect with skilled professionals worldwide. Transform your ideas into reality with expert freelancers ready to bring your vision to life.
                    </p>


                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mb-8">
                        <a href="./public/gig.php" class="group px-8 py-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-full font-semibold text-lg shadow-lg hover:shadow-2xl transform hover:scale-105 transition duration-300 flex items-center justify-center gap-2">
                            <span>Explore Gigs</span>
                            <i class="ri-arrow-right-line text-xl group-hover:translate-x-1 transition-transform"></i>
                        </a>
                        <a href="./auth/signup.php" class="px-8 py-4 bg-white border-2 border-purple-600 text-purple-600 rounded-full font-semibold text-lg hover:bg-purple-50 transition duration-300 flex items-center justify-center gap-2">
                            <i class="ri-user-add-line text-xl"></i>
                            <span>Join Now Free</span>
                        </a>
                    </div>

                    <!-- Trust Indicators -->
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-6 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <i class="ri-checkbox-circle-fill text-green-500 text-xl"></i>
                            <span>No Credit Card Required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="ri-shield-check-fill text-blue-500 text-xl"></i>
                            <span>100% Secure</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="ri-star-fill text-yellow-400 text-xl"></i>
                            <span>4.9/5 Rating</span>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Illustration -->
                <div class="relative" data-aos="fade-left">
                    <div class="relative z-10">
                        <!-- Main Card -->
                        <div class="bg-white rounded-3xl shadow-2xl p-8 floating">
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-blue-500 rounded-2xl flex items-center justify-center">
                                    <i class="ri-code-s-slash-line text-3xl text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg">Development</h3>
                                    <p class="text-gray-500">2,500+ Services</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                            <i class="ri-user-line text-purple-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">John Doe</p>
                                            <p class="text-sm text-gray-500">Web Developer</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="ri-star-fill text-yellow-400"></i>
                                        <span class="font-bold text-gray-900">4.9</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="ri-user-line text-blue-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">Sarah Smith</p>
                                            <p class="text-sm text-gray-500">UI/UX Designer</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="ri-star-fill text-yellow-400"></i>
                                        <span class="font-bold text-gray-900">5.0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Stats Cards -->
                        <div class="absolute -top-8 -right-8 bg-white rounded-2xl shadow-xl p-6 floating" style="animation-delay: 1s;">
                            <div class="text-center">
                                <div class="text-3xl font-bold gradient-text mb-1">50K+</div>
                                <div class="text-sm text-gray-600">Happy Clients</div>
                            </div>
                        </div>

                        <div class="absolute -bottom-8 -left-8 bg-white rounded-2xl shadow-xl p-6 floating" style="animation-delay: 2s;">
                            <div class="text-center">
                                <div class="text-3xl font-bold gradient-text mb-1">95%</div>
                                <div class="text-sm text-gray-600">Success Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mt-20" data-aos="fade-up">
                <div class="bg-white rounded-2xl p-6 shadow-lg text-center card-hover">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="ri-user-line text-2xl text-purple-600"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-2">50K+</div>
                    <div class="text-gray-600">Active Freelancers</div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-lg text-center card-hover">
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="ri-briefcase-line text-2xl text-blue-600"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-2">30K+</div>
                    <div class="text-gray-600">Projects Done</div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-lg text-center card-hover">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="ri-star-line text-2xl text-green-600"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-2">4.9/5</div>
                    <div class="text-gray-600">Average Rating</div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-lg text-center card-hover">
                    <div class="w-14 h-14 bg-pink-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="ri-global-line text-2xl text-pink-600"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-2">150+</div>
                    <div class="text-gray-600">Countries</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 lg:py-32 bg-white" id="features">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <div class="inline-flex items-center space-x-2 bg-purple-100 text-purple-700 px-4 py-2 rounded-full mb-4">
                    <i class="ri-star-line"></i>
                    <span class="text-sm font-semibold">Features</span>
                </div>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                    Everything You Need to
                    <span class="gradient-text block mt-2">Succeed Online</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Powerful features to help you find the perfect freelancer or showcase your skills to the world
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="group bg-gradient-to-br from-purple-50 to-white rounded-3xl p-8 shadow-lg card-hover border border-purple-100" data-aos="fade-up">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-300">
                        <i class="ri-search-line text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Smart Matching</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Our AI-powered algorithm matches you with the perfect freelancer based on your project requirements and budget.
                    </p>
                    <a href="#" class="inline-flex items-center gap-2 text-purple-600 font-semibold group-hover:gap-3 transition-all">
                        Learn More <i class="ri-arrow-right-line"></i>
                    </a>
                </div>

                <!-- Feature 2 -->
                <div class="group bg-gradient-to-br from-blue-50 to-white rounded-3xl p-8 shadow-lg card-hover border border-blue-100" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-300">
                        <i class="ri-shield-check-line text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Secure Payments</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Escrow protection ensures your money is safe. Release payment only when you're 100% satisfied with the work.
                    </p>
                    <a href="#" class="inline-flex items-center gap-2 text-blue-600 font-semibold group-hover:gap-3 transition-all">
                        Learn More <i class="ri-arrow-right-line"></i>
                    </a>
                </div>

                <!-- Feature 3 -->
                <div class="group bg-gradient-to-br from-green-50 to-white rounded-3xl p-8 shadow-lg card-hover border border-green-100" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-700 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-300">
                        <i class="ri-time-line text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Fast Delivery</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Get your projects completed on time, every time. Track progress and communicate in real-time.
                    </p>
                    <a href="#" class="inline-flex items-center gap-2 text-green-600 font-semibold group-hover:gap-3 transition-all">
                        Learn More <i class="ri-arrow-right-line"></i>
                    </a>
                </div>

                <!-- Feature 4 -->
                <div class="group bg-gradient-to-br from-yellow-50 to-white rounded-3xl p-8 shadow-lg card-hover border border-yellow-100" data-aos="fade-up" data-aos-delay="300">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-yellow-700 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-300">
                        <i class="ri-customer-service-2-line text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">24/7 Support</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Our dedicated support team is always here to help you. Get instant assistance whenever you need it.
                    </p>
                    <a href="#" class="inline-flex items-center gap-2 text-yellow-600 font-semibold group-hover:gap-3 transition-all">
                        Learn More <i class="ri-arrow-right-line"></i>
                    </a>
                </div>

                <!-- Feature 5 -->
                <div class="group bg-gradient-to-br from-pink-50 to-white rounded-3xl p-8 shadow-lg card-hover border border-pink-100" data-aos="fade-up" data-aos-delay="400">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-pink-700 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-300">
                        <i class="ri-medal-line text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Quality Guaranteed</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        All freelancers are vetted and verified. Work with confidence knowing you're getting top quality.
                    </p>
                    <a href="#" class="inline-flex items-center gap-2 text-pink-600 font-semibold group-hover:gap-3 transition-all">
                        Learn More <i class="ri-arrow-right-line"></i>
                    </a>
                </div>

                <!-- Feature 6 -->
                <div class="group bg-gradient-to-br from-indigo-50 to-white rounded-3xl p-8 shadow-lg card-hover border border-indigo-100" data-aos="fade-up" data-aos-delay="500">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-300">
                        <i class="ri-global-line text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Global Reach</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Connect with talented professionals from over 150 countries. Work with the best, anywhere.
                    </p>
                    <a href="#" class="inline-flex items-center gap-2 text-indigo-600 font-semibold group-hover:gap-3 transition-all">
                        Learn More <i class="ri-arrow-right-line"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-20 lg:py-32 bg-gradient-to-br from-gray-50 to-purple-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <div class="inline-flex items-center space-x-2 bg-purple-100 text-purple-700 px-4 py-2 rounded-full mb-4">
                    <i class="ri-grid-line"></i>
                    <span class="text-sm font-semibold">Categories</span>
                </div>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                    Explore Popular
                    <span class="gradient-text block mt-2">Service Categories</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Find the perfect service for your needs from our wide range of categories
                </p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 lg:gap-6">
                <!-- Category 1 -->
                <div class="group bg-white rounded-2xl p-6 shadow-lg card-hover cursor-pointer text-center" data-aos="zoom-in">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition duration-300">
                        <i class="ri-code-s-slash-line text-3xl text-white"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Development</h4>
                    <p class="text-sm text-gray-500">2,500+ Gigs</p>
                </div>

                <!-- Category 2 -->
                <div class="group bg-white rounded-2xl p-6 shadow-lg card-hover cursor-pointer text-center" data-aos="zoom-in" data-aos-delay="100">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition duration-300">
                        <i class="ri-palette-line text-3xl text-white"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Design</h4>
                    <p class="text-sm text-gray-500">1,800+ Gigs</p>
                </div>

                <!-- Category 3 -->
                <div class="group bg-white rounded-2xl p-6 shadow-lg card-hover cursor-pointer text-center" data-aos="zoom-in" data-aos-delay="200">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition duration-300">
                        <i class="ri-article-line text-3xl text-white"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Writing</h4>
                    <p class="text-sm text-gray-500">1,200+ Gigs</p>
                </div>

                <!-- Category 4 -->
                <div class="group bg-white rounded-2xl p-6 shadow-lg card-hover cursor-pointer text-center" data-aos="zoom-in" data-aos-delay="300">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition duration-300">
                        <i class="ri-line-chart-line text-3xl text-white"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Marketing</h4>
                    <p class="text-sm text-gray-500">980+ Gigs</p>
                </div>

                <!-- Category 5 -->
                <div class="group bg-white rounded-2xl p-6 shadow-lg card-hover cursor-pointer text-center" data-aos="zoom-in" data-aos-delay="400">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-400 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition duration-300">
                        <i class="ri-video-line text-3xl text-white"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Video</h4>
                    <p class="text-sm text-gray-500">750+ Gigs</p>
                </div>

                <!-- Category 6 -->
                <div class="group bg-white rounded-2xl p-6 shadow-lg card-hover cursor-pointer text-center" data-aos="zoom-in" data-aos-delay="500">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition duration-300">
                        <i class="ri-music-line text-3xl text-white"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Audio</h4>
                    <p class="text-sm text-gray-500">620+ Gigs</p>
                </div>
            </div>

            <div class="text-center mt-12" data-aos="fade-up">
                <a href="./public/gig.php" class="inline-flex items-center gap-3 px-10 py-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-full font-bold text-lg shadow-xl hover:shadow-2xl transform hover:scale-105 transition duration-300">
                    <span>View All Categories</span>
                    <i class="ri-arrow-right-line text-xl"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-20 lg:py-32 bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <div class="inline-flex items-center space-x-2 bg-purple-100 text-purple-700 px-4 py-2 rounded-full mb-4">
                    <i class="ri-lightbulb-line"></i>
                    <span class="text-sm font-semibold">How It Works</span>
                </div>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                    Get Started in
                    <span class="gradient-text block mt-2">4 Simple Steps</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Join thousands of satisfied users and start your freelancing journey today
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Step 1 -->
                <div class="relative" data-aos="fade-up">
                    <div class="bg-gradient-to-br from-purple-50 to-white rounded-3xl p-8 shadow-xl text-center card-hover border-2 border-purple-100">
                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl flex items-center justify-center text-white text-xl font-bold shadow-lg">
                            1
                        </div>
                        <div class="w-20 h-20 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mt-4 mb-6">
                            <i class="ri-user-add-line text-4xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Create Account</h3>
                        <p class="text-gray-600">Sign up for free in seconds and build your professional profile</p>
                    </div>
                    <div class="hidden lg:block absolute top-1/2 -right-4 transform -translate-y-1/2 z-10">
                        <i class="ri-arrow-right-line text-4xl text-purple-300"></i>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="relative" data-aos="fade-up" data-aos-delay="100">
                    <div class="bg-gradient-to-br from-blue-50 to-white rounded-3xl p-8 shadow-xl text-center card-hover border-2 border-blue-100">
                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl flex items-center justify-center text-white text-xl font-bold shadow-lg">
                            2
                        </div>
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mt-4 mb-6">
                            <i class="ri-search-line text-4xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Browse & Select</h3>
                        <p class="text-gray-600">Find the perfect freelancer or post your amazing gig</p>
                    </div>
                    <div class="hidden lg:block absolute top-1/2 -right-4 transform -translate-y-1/2 z-10">
                        <i class="ri-arrow-right-line text-4xl text-blue-300"></i>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="relative" data-aos="fade-up" data-aos-delay="200">
                    <div class="bg-gradient-to-br from-green-50 to-white rounded-3xl p-8 shadow-xl text-center card-hover border-2 border-green-100">
                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-gradient-to-br from-green-500 to-green-700 rounded-xl flex items-center justify-center text-white text-xl font-bold shadow-lg">
                            3
                        </div>
                        <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center mx-auto mt-4 mb-6">
                            <i class="ri-chat-smile-3-line text-4xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Collaborate</h3>
                        <p class="text-gray-600">Work together seamlessly with built-in tools and chat</p>
                    </div>
                    <div class="hidden lg:block absolute top-1/2 -right-4 transform -translate-y-1/2 z-10">
                        <i class="ri-arrow-right-line text-4xl text-green-300"></i>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="relative" data-aos="fade-up" data-aos-delay="300">
                    <div class="bg-gradient-to-br from-pink-50 to-white rounded-3xl p-8 shadow-xl text-center card-hover border-2 border-pink-100">
                        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-gradient-to-br from-pink-500 to-pink-700 rounded-xl flex items-center justify-center text-white text-xl font-bold shadow-lg">
                            4
                        </div>
                        <div class="w-20 h-20 bg-gradient-to-br from-pink-400 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mt-4 mb-6">
                            <i class="ri-checkbox-circle-line text-4xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Get Results</h3>
                        <p class="text-gray-600">Receive high-quality work and release secure payment</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-12 sm:py-16 lg:py-24 xl:py-32 bg-gradient-to-br from-purple-50 via-blue-50 to-indigo-50 relative overflow-hidden" id="testimonials">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <!-- Section Header -->
            <div class="text-center mb-10 sm:mb-12 lg:mb-16" data-aos="fade-up">
                <!-- Badge -->
                <div class="inline-flex items-center space-x-2 bg-white/80 backdrop-blur-sm text-purple-700 px-4 sm:px-6 py-2.5 rounded-full mb-4 sm:mb-6 shadow-lg border border-purple-100 hover:shadow-xl transition-all duration-300">
                    <i class="ri-chat-quote-line text-lg sm:text-xl"></i>
                    <span class="text-xs sm:text-sm font-semibold tracking-wide">CLIENT TESTIMONIALS</span>
                    <div class="w-2 h-2 bg-purple-500 rounded-full animate-pulse"></div>
                </div>

                <!-- Title -->
                <h2 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-4 sm:mb-6 leading-tight px-4">
                    What Our Clients
                    <span class="gradient-text block mt-1 sm:mt-2 bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-600 bg-clip-text text-transparent">
                        Have to Say
                    </span>
                </h2>

                <!-- Subtitle -->
                <p class="text-base sm:text-lg lg:text-xl text-gray-600 max-w-3xl mx-auto px-4 leading-relaxed">
                    Real feedback from real clients who found success with our platform
                </p>

                <!-- Stats Row -->
                <div class="flex flex-wrap justify-center gap-4 sm:gap-6 lg:gap-8 mt-8 sm:mt-10" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-center gap-2 bg-white/60 backdrop-blur-sm px-4 sm:px-6 py-3 rounded-2xl shadow-md hover:shadow-lg transition-all duration-300">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center">
                            <i class="ri-star-fill text-white text-lg sm:text-xl"></i>
                        </div>
                        <div class="text-left">
                            <div class="text-xl sm:text-2xl font-bold text-gray-900">4.9/5</div>
                            <div class="text-xs sm:text-sm text-gray-600">Average Rating</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 bg-white/60 backdrop-blur-sm px-4 sm:px-6 py-3 rounded-2xl shadow-md hover:shadow-lg transition-all duration-300">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-500 to-blue-500 rounded-xl flex items-center justify-center">
                            <i class="ri-user-heart-line text-white text-lg sm:text-xl"></i>
                        </div>
                        <div class="text-left">
                            <div class="text-xl sm:text-2xl font-bold text-gray-900">10K+</div>
                            <div class="text-xs sm:text-sm text-gray-600">Happy Clients</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 bg-white/60 backdrop-blur-sm px-4 sm:px-6 py-3 rounded-2xl shadow-md hover:shadow-lg transition-all duration-300">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                            <i class="ri-verified-badge-line text-white text-lg sm:text-xl"></i>
                        </div>
                        <div class="text-left">
                            <div class="text-xl sm:text-2xl font-bold text-gray-900">98%</div>
                            <div class="text-xs sm:text-sm text-gray-600">Satisfaction</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review Slider -->
            <div class="review-slider-container relative" data-aos="fade-up" data-aos-delay="200">

                <div class="review-slider">
                    <?php
                    // Enhanced query to get more user information and handle potential NULL values
                    $sql = "SELECT r.comment, r.rating, u.username, u.first_name, u.last_name, r.created_at 
                        FROM reviews r 
                        JOIN users u ON r.client_id = u.id 
                        WHERE r.status = 'active' AND r.comment IS NOT NULL AND r.comment != '' 
                        ORDER BY r.created_at DESC 
                        LIMIT 12";
                    try {
                        $stmt = $conn->prepare($sql);
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                            $reviewIndex = 0;
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $comment = $row["comment"];
                                $rating = $row["rating"];
                                $userName = (!empty($row["first_name"]) && !empty($row["last_name"]))
                                    ? $row["first_name"] . " " . $row["last_name"]
                                    : $row["username"];
                                $createdAt = new DateTime($row["created_at"]);
                                $timeAgo = $createdAt->format('F Y');
                                $validRating = max(1, min(5, (int)$rating));
                                $reviewIndex++;
                    ?>
                                <div class="review-slide">
                                    <div class="review-card group">
                                        <!-- Verified Badge -->
                                        <?php if ($validRating >= 4): ?>
                                            <div class="absolute top-4 right-4 sm:top-6 sm:right-6 z-10">
                                                <div class="flex items-center gap-1.5 bg-gradient-to-r from-green-500 to-emerald-500 text-white px-3 py-1.5 rounded-full text-xs font-semibold shadow-lg">
                                                    <i class="ri-checkbox-circle-fill"></i>
                                                    <span>Verified</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Decorative Elements -->
                                        <div class="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 bg-gradient-to-br from-purple-400 to-blue-400 rounded-bl-full opacity-10 group-hover:opacity-20 transition-opacity duration-300"></div>
                                        <div class="absolute bottom-0 left-0 w-20 sm:w-24 h-20 sm:h-24 bg-gradient-to-tr from-indigo-400 to-purple-400 rounded-tr-full opacity-10 group-hover:opacity-20 transition-opacity duration-300"></div>

                                        <!-- Quote Icon -->
                                        <div class="quote-icon">
                                            <i class="ri-double-quotes-l text-2xl sm:text-3xl text-white"></i>
                                        </div>

                                        <!-- Rating with Animation -->
                                        <div class="flex items-center justify-between gap-2 mb-4 sm:mb-6 mt-6 sm:mt-8">
                                            <div class="flex items-center gap-1">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $validRating) {
                                                        echo '<i class="ri-star-fill text-yellow-400 text-base sm:text-xl transition-transform duration-300 hover:scale-125"></i>';
                                                    } else {
                                                        echo '<i class="ri-star-line text-gray-300 text-base sm:text-xl"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <div class="flex items-center gap-1 bg-yellow-50 px-2.5 sm:px-3 py-1 rounded-full">
                                                <span class="text-xs sm:text-sm font-bold text-yellow-700"><?php echo $validRating; ?>.0</span>
                                            </div>
                                        </div>

                                        <!-- Comment with Read More -->
                                        <div class="comment-wrapper mb-4 sm:mb-6">
                                            <p class="text-gray-700 leading-relaxed text-sm sm:text-base lg:text-lg review-comment">
                                                "<?php echo htmlspecialchars($comment, ENT_QUOTES, 'UTF-8'); ?>"
                                            </p>
                                        </div>

                                        <!-- User Info -->
                                        <div class="user-info-section">
                                            <div class="flex items-center gap-3 sm:gap-4">
                                                <!-- Avatar with Status -->
                                                <div class="relative flex-shrink-0">
                                                    <div class="avatar-circle">
                                                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                                    </div>
                                                    <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 sm:w-4 sm:h-4 bg-green-500 border-2 sm:border-3 border-white rounded-full"></div>
                                                </div>

                                                <!-- User Details -->
                                                <div class="flex-grow min-w-0">
                                                    <h5 class="font-bold text-gray-900 text-sm sm:text-base lg:text-lg truncate">
                                                        <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                                                    </h5>
                                                    <p class="text-xs sm:text-sm text-gray-500 flex items-center gap-1.5 mt-0.5">
                                                        <i class="ri-time-line text-gray-400 text-xs sm:text-sm"></i>
                                                        <span><?php echo $timeAgo; ?></span>
                                                    </p>
                                                </div>

                                                <!-- Like Button -->
                                                <button class="like-btn" aria-label="Helpful review">
                                                    <i class="ri-thumb-up-line"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Hover Shine Effect -->
                                        <div class="shine-effect"></div>
                                    </div>
                                </div>
                    <?php
                            }
                        } else {
                            echo renderFallbackReviews();
                        }
                    } catch (PDOException $e) {
                        error_log("Review fetch error: " . $e->getMessage());
                        echo renderFallbackReviews();
                    }

                    function renderFallbackReviews()
                    {
                        $fallbackReviews = [
                            [
                                "name" => "Alex Johnson",
                                "comment" => "This platform has completely transformed how I work with freelancers. The quality of talent is exceptional and the process is seamless. I've completed over 20 projects here!",
                                "rating" => 5,
                                "date" => "January 2024"
                            ],
                            [
                                "name" => "Maria Garcia",
                                "comment" => "I've been using this platform for over a year now and it's been a game-changer for my business. The support team is responsive and the freelancers are top-notch.",
                                "rating" => 5,
                                "date" => "March 2024"
                            ],
                            [
                                "name" => "David Wilson",
                                "comment" => "The best freelancing platform I've used. Found exactly what I needed for my project in just a few days. The escrow system gives me peace of mind.",
                                "rating" => 5,
                                "date" => "May 2024"
                            ],
                            [
                                "name" => "Sarah Chen",
                                "comment" => "Outstanding experience! The platform is user-friendly and the quality of work delivered exceeded my expectations. Will definitely use again.",
                                "rating" => 4,
                                "date" => "April 2024"
                            ],
                            [
                                "name" => "James Miller",
                                "comment" => "Great platform with excellent customer service. Found talented professionals quickly and the project management tools are very helpful.",
                                "rating" => 5,
                                "date" => "February 2024"
                            ],
                            [
                                "name" => "Emma Brown",
                                "comment" => "Highly recommended! The vetting process ensures quality freelancers, and the payment system is secure and straightforward.",
                                "rating" => 4,
                                "date" => "June 2024"
                            ]
                        ];

                        $output = "";
                        foreach ($fallbackReviews as $index => $review) {
                            $output .= '
                        <div class="review-slide">
                            <div class="review-card group">';

                            if ($review["rating"] >= 4) {
                                $output .= '
                                <div class="absolute top-4 right-4 sm:top-6 sm:right-6 z-10">
                                    <div class="flex items-center gap-1.5 bg-gradient-to-r from-green-500 to-emerald-500 text-white px-3 py-1.5 rounded-full text-xs font-semibold shadow-lg">
                                        <i class="ri-checkbox-circle-fill"></i>
                                        <span>Verified</span>
                                    </div>
                                </div>';
                            }

                            $output .= '
                                <div class="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 bg-gradient-to-br from-purple-400 to-blue-400 rounded-bl-full opacity-10 group-hover:opacity-20 transition-opacity duration-300"></div>
                                <div class="absolute bottom-0 left-0 w-20 sm:w-24 h-20 sm:h-24 bg-gradient-to-tr from-indigo-400 to-purple-400 rounded-tr-full opacity-10 group-hover:opacity-20 transition-opacity duration-300"></div>

                                <div class="quote-icon">
                                    <i class="ri-double-quotes-l text-2xl sm:text-3xl text-white"></i>
                                </div>

                                <div class="flex items-center justify-between gap-2 mb-4 sm:mb-6 mt-6 sm:mt-8">
                                    <div class="flex items-center gap-1">';

                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $review["rating"]) {
                                    $output .= '<i class="ri-star-fill text-yellow-400 text-base sm:text-xl transition-transform duration-300 hover:scale-125"></i>';
                                } else {
                                    $output .= '<i class="ri-star-line text-gray-300 text-base sm:text-xl"></i>';
                                }
                            }

                            $output .= '
                                    </div>
                                    <div class="flex items-center gap-1 bg-yellow-50 px-2.5 sm:px-3 py-1 rounded-full">
                                        <span class="text-xs sm:text-sm font-bold text-yellow-700">' . $review["rating"] . '.0</span>
                                    </div>
                                </div>

                                <div class="comment-wrapper mb-4 sm:mb-6">
                                    <p class="text-gray-700 leading-relaxed text-sm sm:text-base lg:text-lg review-comment">
                                        "' . htmlspecialchars($review["comment"], ENT_QUOTES, 'UTF-8') . '"
                                    </p>
                                </div>

                                <div class="user-info-section">
                                    <div class="flex items-center gap-3 sm:gap-4">
                                        <div class="relative flex-shrink-0">
                                            <div class="avatar-circle">
                                                ' . strtoupper(substr($review["name"], 0, 1)) . '
                                            </div>
                                            <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 sm:w-4 sm:h-4 bg-green-500 border-2 sm:border-3 border-white rounded-full"></div>
                                        </div>

                                        <div class="flex-grow min-w-0">
                                            <h5 class="font-bold text-gray-900 text-sm sm:text-base lg:text-lg truncate">
                                                ' . htmlspecialchars($review["name"], ENT_QUOTES, 'UTF-8') . '
                                            </h5>
                                            <p class="text-xs sm:text-sm text-gray-500 flex items-center gap-1.5 mt-0.5">
                                                <i class="ri-time-line text-gray-400 text-xs sm:text-sm"></i>
                                                <span>' . $review["date"] . '</span>
                                            </p>
                                        </div>

                                        <button class="like-btn" aria-label="Helpful review">
                                            <i class="ri-thumb-up-line"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="shine-effect"></div>
                            </div>
                        </div>';
                        }
                        return $output;
                    }
                    ?>
                </div>

                <!-- Enhanced Slider Controls -->
                <div class="slider-controls-wrapper">

                    <!-- Mobile Touch Indicator -->
                    <div class="mobile-swipe-indicator md:hidden">
                        <div class="swipe-hint">
                            <i class="ri-arrow-left-s-line"></i>
                            <span>Swipe</span>
                            <i class="ri-arrow-right-s-line"></i>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Slider Dots -->
                <!-- <div class="slider-dots-container">
                    <div class="slider-dots"></div>
                </div> -->

                <!-- Auto-play Control -->
                <button class="autoplay-toggle" aria-label="Toggle autoplay">
                    <i class="ri-play-fill play-icon"></i>
                    <i class="ri-pause-fill pause-icon hidden"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 lg:py-32 bg-gradient-to-br from-purple-600 via-blue-600 to-purple-800 relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full filter blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full filter blur-3xl"></div>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                <div class="inline-flex items-center space-x-2 bg-white/20 backdrop-blur-lg text-white px-4 py-2 rounded-full mb-6" data-aos="fade-up">
                    <i class="ri-rocket-line"></i>
                    <span class="text-sm font-semibold">Join Our Community</span>
                </div>

                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6" data-aos="fade-up" data-aos-delay="100">
                    Ready to Transform Your
                    <span class="block mt-2">Freelance Career?</span>
                </h2>

                <p class="text-xl text-white/90 mb-12 leading-relaxed max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="200">
                    Join 50,000+ professionals already growing their careers on FreelanceHub. Start your journey today - it's completely free!
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-16" data-aos="fade-up" data-aos-delay="300">
                    <a href="./auth/signup.php" class="group px-10 py-5 bg-white text-purple-600 rounded-full font-bold text-lg shadow-2xl hover:shadow-3xl transform hover:scale-105 transition duration-300 flex items-center gap-3">
                        <i class="ri-user-add-line text-2xl"></i>
                        <span>Start Free Today</span>
                        <i class="ri-arrow-right-line text-xl group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="./public/gig.php" class="px-10 py-5 bg-transparent border-2 border-white text-white rounded-full font-bold text-lg hover:bg-white hover:text-purple-600 transition duration-300 flex items-center gap-3">
                        <i class="ri-search-line text-2xl"></i>
                        <span>Explore Gigs</span>
                    </a>
                </div>

                <!-- Trust Badges -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="400">
                    <div class="flex flex-col items-center gap-3 text-white">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-lg rounded-2xl flex items-center justify-center">
                            <i class="ri-shield-check-line text-3xl"></i>
                        </div>
                        <div>
                            <div class="font-bold text-lg">100% Secure</div>
                            <div class="text-sm text-white/80">Bank-level security</div>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-3 text-white">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-lg rounded-2xl flex items-center justify-center">
                            <i class="ri-time-line text-3xl"></i>
                        </div>
                        <div>
                            <div class="font-bold text-lg">24/7 Support</div>
                            <div class="text-sm text-white/80">Always here to help</div>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-3 text-white">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-lg rounded-2xl flex items-center justify-center">
                            <i class="ri-award-line text-3xl"></i>
                        </div>
                        <div>
                            <div class="font-bold text-lg">Top Quality</div>
                            <div class="text-sm text-white/80">Vetted professionals</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                <!-- Brand -->
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-blue-600 rounded-xl flex items-center justify-center">
                            <i class="ri-lightbulb-flash-line text-2xl text-white"></i>
                        </div>
                        <span class="text-2xl font-bold text-white">FreelanceHub</span>
                    </div>
                    <p class="text-gray-400 mb-6 leading-relaxed">
                        Connect with top talent worldwide and build your dream career in the freelance economy.
                    </p>
                    <div class="flex items-center gap-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-purple-600 rounded-lg flex items-center justify-center transition duration-300">
                            <i class="ri-facebook-fill text-xl"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-purple-600 rounded-lg flex items-center justify-center transition duration-300">
                            <i class="ri-twitter-fill text-xl"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-purple-600 rounded-lg flex items-center justify-center transition duration-300">
                            <i class="ri-instagram-fill text-xl"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-purple-600 rounded-lg flex items-center justify-center transition duration-300">
                            <i class="ri-linkedin-fill text-xl"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-white font-bold text-lg mb-6">Quick Links</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">About Us</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">How It Works</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Careers</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Blog</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Contact</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h3 class="text-white font-bold text-lg mb-6">Categories</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Development</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Design</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Writing</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Marketing</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Video & Audio</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h3 class="text-white font-bold text-lg mb-6">Support</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Help Center</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Trust & Safety</a></li>
                        <li><a href="#" class="hover:text-purple-400 transition duration-300">Sitemap</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="pt-8 border-t border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-400 text-center md:text-left">
                    © 2024 FreelanceHub. All rights reserved.
                </p>
                <div class="flex items-center gap-6">
                    <a href="#" class="text-gray-400 hover:text-purple-400 transition duration-300">Terms</a>
                    <a href="#" class="text-gray-400 hover:text-purple-400 transition duration-300">Privacy</a>
                    <a href="#" class="text-gray-400 hover:text-purple-400 transition duration-300">Cookies</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollTop" class="fixed bottom-8 right-8 w-14 h-14 bg-gradient-to-br from-purple-600 to-blue-600 text-white rounded-full shadow-2xl opacity-0 invisible transition-all duration-300 hover:scale-110 flex items-center justify-center z-50">
        <i class="ri-arrow-up-line text-2xl"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="./assets/js/index.js"></script>
    <script src="./assets/js/slider.js"></script>

</body>

</html>