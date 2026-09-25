    </main>
    
    <script>
        (function() {
            // Safe sidebar toggle functionality
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle') || document.getElementById('mobileMenuToggle');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (!sidebar) return;
                if (window.innerWidth < 1024 && 
                    !sidebar.contains(event.target) && 
                    (!sidebarToggle || !sidebarToggle.contains(event.target)) && 
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });
        })();
    </script>
</body>
</html>