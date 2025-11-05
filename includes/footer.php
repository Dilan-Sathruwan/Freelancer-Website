<style>
/* Optimized footer styling */
.bg-footer {
    background-color: #2c2f33;
    padding: 50px 0 30px;
}

.footer-heading {
    letter-spacing: 2px;
    color: #fff;
    font-weight: bold;
    position: relative;
    padding-bottom: 12px;
}


.footer-heading::after {
    content: '';
    width: 50px;
    height: 2px;
    background-color: #1bbc9b;
    position: absolute;
    bottom: 0;
    left: 0;
}

.footer-link a {
    color: #acacac;
    line-height: 2;
    font-size: 14px;
    text-decoration: none;
    display: block;
    transition: color 0.3s ease, transform 0.3s ease;
}

.footer-link a:hover {
    color: #1bbc9b;
    transform: translateX(5px);
}

.contact-info {
    color: #acacac;
    font-size: 15px;
    line-height: 2;
}

.footer-social-icon {
    font-size: 20px;
    height: 40px;
    width: 40px;
    line-height: 40px;
    border-radius: 50%;
    text-align: center;
    display: inline-block;
    margin-right: 10px;
    transition: transform 0.3s ease, background-color 0.3s ease;
}

.footer-social-icon:hover {
    transform: scale(1.2);
}

.facebook:hover {
    background-color: #4e71a8;
    color: #fff;
}

.twitter:hover {
    background-color: #55acee;
    color: #fff;
}

.google:hover {
    background-color: #d6492f;
    color: #fff;
}

.apple:hover {
    background-color: #424041;
    color: #fff;
}

.footer-alt {
    color: #acacac;
    font-size: 14px;
    margin-top: 20px;
    text-align: center;
    border-top: 1px solid #3d4145;
    padding-top: 20px;
}

@media (max-width: 768px) {
    .footer-heading {
        text-align: center;
    }

    .footer-link,
    .contact-info {
        text-align: center;
    }

    .footer-social-icon {
        margin-right: 5px;
    }
}
</style>


<!-- footer.php -->
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

<!-- Include Font Awesome for icons -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>