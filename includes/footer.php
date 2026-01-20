<!-- includes/footer.php -->
</div> <!-- End Container -->

<footer class="text-center py-4 mt-5 border-top">
    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
    <small class="text-muted">Designed with <i class="fa-solid fa-heart text-danger"></i> for you.</small>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script>
    // Simple Dark Mode Toggle
    const toggleBtn = document.getElementById('theme-toggle');
    const body = document.body;
    const navbar = document.querySelector('.navbar');

    // Check Local Storage
    if (localStorage.getItem('theme') === 'dark') {
        body.classList.add('dark-mode');
        navbar.classList.add('navbar-dark', 'bg-dark');
        navbar.classList.remove('navbar-light', 'bg-light');
        toggleBtn.innerHTML = '<i class="fa-solid fa-sun"></i>';
    }

    toggleBtn.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        if (body.classList.contains('dark-mode')) {
            localStorage.setItem('theme', 'dark');
            document.cookie = "theme=dark; path=/";
            navbar.classList.add('navbar-dark', 'bg-dark');
            navbar.classList.remove('navbar-light', 'bg-light');
            toggleBtn.innerHTML = '<i class="fa-solid fa-sun"></i>';
        } else {
            localStorage.setItem('theme', 'light');
            document.cookie = "theme=light; path=/";
            navbar.classList.remove('navbar-dark', 'bg-dark');
            navbar.classList.add('navbar-light', 'bg-light');
            toggleBtn.innerHTML = '<i class="fa-solid fa-moon"></i>';
        }
    });
</script>
</body>
</html>
