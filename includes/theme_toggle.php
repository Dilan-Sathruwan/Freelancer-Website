<style>
/* General Styles */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Light Theme */
body {
    background-color: #ffffff;
    color: #333333;
}

/* Dark Theme */
body.dark-theme {
    background-color: #121212;
    color: #e0e0e0;
}

/* Typography */
h1, h2, h3, h4, h5, h6, p {
    transition: color 0.3s ease;
}

body.dark-theme h1, body.dark-theme h2, body.dark-theme h3, 
body.dark-theme h4, body.dark-theme h5, body.dark-theme h6, 
body.dark-theme p {
    color: #ffffff;
}

/* Buttons */
button {
    background-color: #007bff;
    color: #ffffff;
    border: none;
    border-radius: 5px;
    padding: 10px 20px;
    cursor: pointer;
    transition: background-color 0.3s ease, color 0.3s ease;
}

button:hover {
    background-color: #0056b3;
}

body.dark-theme button {
    background-color: #444444;
    color: #ffffff;
}

body.dark-theme button:hover {
    background-color: #555555;
}

/* Links */
a {
    color: #007bff;
    text-decoration: none;
    transition: color 0.3s ease;
}

a:hover {
    text-decoration: underline;
}

body.dark-theme a {
    color: #80bfff;
}

body.dark-theme a:hover {
    color: #add8e6;
}

/* Cards */
.card {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

body.dark-theme .card {
    background-color: #222222;
    border-color: #444444;
    color: #e0e0e0;
}

body.dark-theme .card:hover {
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
}

/* Inputs */
input, textarea, select {
    background-color: #ffffff;
    color: #333333;
    border: 1px solid #ced4da;
    padding: 10px;
    border-radius: 4px;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: #007bff;
}

body.dark-theme input, body.dark-theme textarea, body.dark-theme select {
    background-color: #1e1e1e;
    color: #e0e0e0;
    border-color: #444444;
}

body.dark-theme input:focus, body.dark-theme textarea:focus, body.dark-theme select:focus {
    border-color: #80bfff;
}

/* Navbar */
.navbar {
    background-color: #ffffff;
    border-bottom: 1px solid #dee2e6;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

.navbar a {
    color: #333333;
}

body.dark-theme .navbar {
    background-color: #1e1e1e;
    border-bottom-color: #444444;
}

body.dark-theme .navbar a {
    color: #e0e0e0;
}

/* Footer */
.footer {
    background-color: #f8f9fa;
    color: #333333;
    padding: 20px;
    text-align: center;
    transition: background-color 0.3s ease, color 0.3s ease;
}

body.dark-theme .footer {
    background-color: #1e1e1e;
    color: #e0e0e0;
}

/* Tables */
table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

table th, table td {
    border: 1px solid #dee2e6;
    padding: 10px;
    text-align: left;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

table tr:nth-child(even) {
    background-color: #f2f2f2;
}

body.dark-theme table th {
    background-color: #222222;
    color: #e0e0e0;
}

body.dark-theme table tr:nth-child(even) {
    background-color: #2a2a2a;
}

body.dark-theme table tr:nth-child(odd) {
    background-color: #1e1e1e;
}

body.dark-theme table th, body.dark-theme table td {
    border-color: #444444;
}

/* Custom Scrollbar for Dark Theme */
body.dark-theme ::-webkit-scrollbar {
    width: 8px;
}

body.dark-theme ::-webkit-scrollbar-track {
    background: #121212;
}

body.dark-theme ::-webkit-scrollbar-thumb {
    background: #444444;
    border-radius: 4px;
}

body.dark-theme ::-webkit-scrollbar-thumb:hover {
    background: #555555;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .card {
        padding: 15px;
    }

    button {
        padding: 8px 16px;
    }
}


/* Theme Toggle Button Styles */
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
        width: 60px;
        height: 60px;
        font-size: 24px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease-in-out;
        outline: none;
    }

    .theme-toggle button:focus {
        outline: 2px solid #ffd700;
        outline-offset: 4px;
    }

    .theme-toggle button:hover {
        background-color: #444;
        transform: scale(1.2);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    }

    .theme-toggle button:active {
        transform: scale(0.95);
    }

    /* Icon Styling */
    .theme-toggle button i {
        transition: color 0.3s ease;
    }

    .theme-toggle button:hover i {
        color: #ffd700;
    }

    /* Dark Mode Toggle Enhancements */
    body.dark-theme .theme-toggle button {
        background-color: #444;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.6);
    }

    body.dark-theme .theme-toggle button:hover {
        background-color: #555;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .theme-toggle button {
            width: 50px;
            height: 50px;
            font-size: 20px;
        }
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

        // Apply stored theme preference
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



    