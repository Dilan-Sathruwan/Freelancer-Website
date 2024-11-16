<style>
/* Dark Mode Styles */
body.dark-theme {
    background-color: #121212;
    color: #e0e0e0;
}

body.dark-theme .navbar {
    background-color: #333;
}

body.dark-theme .nav-link {
    color: #e0e0e0;
}

body.dark-theme .theme-toggle button {
    background-color: #333;
    color: #ffd700;
}

body.dark-theme .theme-toggle button i {
    color: #ffd700;
}

/* Sticky Button Styling */
.theme-toggle {
    position: fixed;
    bottom: 20px;  /* Position the button at the bottom */
    right: 20px;   /* Position the button on the right */
    z-index: 1000; /* Ensure the button stays above other content */
}

.theme-toggle button {
    background-color: #222; /* Button background color */
    border: none;
    border-radius: 50%;
    padding: 15px;
    font-size: 20px;
    cursor: pointer;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    transition: background-color 0.3s ease;
}

.theme-toggle button:hover {
    background-color: #333; /* Button color when hovered */
}

.theme-toggle button i {
    color: #fff; /* Icon color */
}

/* Hover effect for the theme button */
.theme-toggle button:hover i {
    color: #ffd700; /* Icon color change on hover */
}

</style>

<div class="theme-toggle">
    <button id="theme-toggle-btn" class="btn">
        <i class="fas fa-moon"></i> <!-- Default dark mode icon -->
    </button>
</div>

<script>
    // Check the stored theme preference in localStorage when the page loads
    window.addEventListener('DOMContentLoaded', (event) => {
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-theme');
            document.querySelector('#theme-toggle-btn i').classList.replace('fa-moon', 'fa-sun');
        } else {
            document.body.classList.remove('dark-theme');
            document.querySelector('#theme-toggle-btn i').classList.replace('fa-sun', 'fa-moon');
        }
    });

    // Theme toggle button script
    document.getElementById('theme-toggle-btn').addEventListener('click', function() {
        document.body.classList.toggle('dark-theme'); // Toggle dark mode class
        let icon = document.querySelector('#theme-toggle-btn i');
        
        if (document.body.classList.contains('dark-theme')) {
            icon.classList.replace('fa-moon', 'fa-sun'); // Change icon to sun when dark mode is active
            localStorage.setItem('theme', 'dark'); // Save dark mode preference
        } else {
            icon.classList.replace('fa-sun', 'fa-moon'); // Change back to moon for light mode
            localStorage.setItem('theme', 'light'); // Save light mode preference
        }
    });
</script>
