<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Attendance Management System</title>
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Main stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet"> <!-- Font Awesome icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- Bootstrap -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> <!-- jQuery (optional for certain interactions) -->
    <style>
        /* Additional styling for header */
        body {
            font-family: 'Arial', sans-serif;
        }

        .navbar {
            background-color: #007bff;
            padding: 1rem;
        }

        .navbar a {
            color: white;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .navbar a:hover {
            color: #ffd700;
            text-decoration: none;
        }

        .logo img {
            width: 50px;
            height: auto;
        }

        /* Positioning logo to the left */
        .navbar .logo {
            flex-grow: 1;
        }

        /* Center the navigation links */
        .navbar .nav-links {
            display: flex;
            justify-content: center;
            flex-grow: 2;
        }

        .navbar .nav-links li {
            list-style: none;
            margin-right: 1rem;
        }

        /* Position login to the right */
        .navbar .nav-login {
            display: flex;
            justify-content: flex-end;
            flex-grow: 1;
        }

        .nav-links a {
            position: relative;
        }

        .nav-links a:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #ffd700;
            transform: scaleX(0);
            transform-origin: bottom right;
            transition: transform 0.25s ease-out;
        }

        .nav-links a:hover:after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .navbar .nav-links {
                flex-direction: column;
                align-items: center;
            }

            .navbar .nav-login {
                justify-content: center;
                margin-top: 1rem;
            }

            .theme-toggle {
                margin-top: 1rem;
            }

            .logo img {
                width: 40px;
            }

            .navbar a {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar / Header -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <!-- Logo Section -->
                <a href="index.php" class="logo">
                    <img src="assets/images/logo.png" alt="Logo">
                </a>

                <!-- Mobile Toggle Button -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar Links (Collapsed on mobile) -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ml-auto nav-links">
                        <li class="nav-item">
                            <a href="index.php" class="nav-link">Home</a>
                        </li>
                        <li class="nav-item">
                            <a href="register.php" class="nav-link">Register</a>
                        </li>
                        <li class="nav-item">
                            <a href="public/profile.php" class="nav-link">Profile</a>
                        </li>
                    </ul>

                    <!-- Login link aligned to the right -->
                    <div class="nav-login">
                        <a href="<?php echo $loginPage ? $loginPage : "../public/login.php"; ?>" class="nav-link">Login</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Bootstrap JS and Popper.js (for responsive navbar toggle) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

</body>

</html>