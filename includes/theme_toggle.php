<style>
    /* General Styles */
    body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Dark Mode Styles */
    body.dark-theme {
        background-color: #121212;
        color: #e0e0e0;
    }

    body.dark-theme .navbar {
        background-color: #1f1f1f;
    }

    body.dark-theme .nav-link {
        color: #b3b3b3;
    }

    body.dark-theme .theme-toggle button {
        background-color: #444;
        color: #ffd700;
    }

    body.dark-theme .theme-toggle button i {
        color: #ffd700;
    }

    /* Sticky Button Styling */
    .theme-toggle {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }

    .theme-toggle button {
        background-color: #222;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        font-size: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .theme-toggle button:hover {
        background-color: #333;
        transform: scale(1.1);
        /* Add a scaling hover effect */
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    }

    .theme-toggle button:active {
        transform: scale(0.95);
        /* Slightly shrink on click */
    }

    /* Icon Styling */
    .theme-toggle button i {
        color: #fff;
        transition: color 0.3s ease;
    }

    .theme-toggle button:hover i {
        color: #ffd700;
        /* Icon color change on hover */
    }
</style>

<div class="theme-toggle">
    <button id="theme-toggle-btn" class="btn" aria-label="Toggle Theme">
        <i id="theme-icon" class="fas fa-moon"></i>
    </button>
</div>

<script>
    // Function to update the icon based on the current theme
    function updateIcon(isDark) {
        const icon = document.getElementById('theme-icon');
        if (isDark) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    }

    // On page load, apply stored theme preference
    document.addEventListener('DOMContentLoaded', () => {
        const theme = localStorage.getItem('theme');
        const body = document.body;

        if (theme === 'dark') {
            body.classList.add('dark-theme');
            updateIcon(true);
        } else {
            body.classList.remove('dark-theme');
            updateIcon(false);
        }
    });

    // Theme toggle functionality
    document.getElementById('theme-toggle-btn').addEventListener('click', () => {
        const body = document.body;
        const isDark = body.classList.toggle('dark-theme');

        // Update the icon and localStorage
        updateIcon(isDark);
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
</script>