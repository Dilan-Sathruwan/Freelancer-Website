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
<footer class="bg-footer">
    <div class="container">
        <div class="row">
            <!-- Information Column -->
            <div class="col-md-3">
                <h6 class="footer-heading text-uppercase">Information</h6>
                <ul class="list-unstyled footer-link mt-4">
                    <li><a href="#">Pages</a></li>
                    <li><a href="#">Our Team</a></li>
                    <li><a href="#">Features</a></li>
                    <li><a href="#">Pricing</a></li>
                </ul>
            </div>

            <!-- Resources Column -->
            <div class="col-md-3">
                <h6 class="footer-heading text-uppercase">Resources</h6>
                <ul class="list-unstyled footer-link mt-4">
                    <li><a href="#">Monitoring Grader</a></li>
                    <li><a href="#">Video Tutorials</a></li>
                    <li><a href="#">Terms & Services</a></li>
                    <li><a href="#">Zeeko API</a></li>
                </ul>
            </div>

            <!-- Help Column -->
            <div class="col-md-3">
                <h6 class="footer-heading text-uppercase">Help</h6>
                <ul class="list-unstyled footer-link mt-4">
                    <li><a href="#">Sign Up</a></li>
                    <li><a href="#">Login</a></li>
                    <li><a href="#">Terms of Services</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                </ul>
            </div>

            <!-- Contact Us Column -->
            <div class="col-md-3">
                <h6 class="footer-heading text-uppercase">Contact Us</h6>
                <p class="contact-info mt-4">Reach out for any inquiries or help!</p>
                <p class="contact-info">+01 123-456-7890</p>
                <div class="mt-4">
                    <a href="#" class="footer-social-icon facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="footer-social-icon twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="footer-social-icon google"><i class="fab fa-google"></i></a>
                    <a href="#" class="footer-social-icon apple"><i class="fab fa-apple"></i></a>
                </div>
            </div>
        </div>

        <!-- Footer Bottom Text -->
        <div class="footer-alt">
            <p>2024 Group project , All Rights Reserved.</p>
        </div>
    </div>
</footer>

<!-- Include Font Awesome for icons -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
<!-- Include Font Awesome for icons -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
