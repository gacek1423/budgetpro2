<?php
// budgetpro2/includes/footer.php
// Zawsze na końcu strony
?>
        </div>
    </div>
    
    <!-- Global scripts -->
    <script>
        // Dark mode toggle
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const html = document.documentElement;
                html.classList.toggle('dark');
                localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
            });
        }

        // Load saved theme
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>
<?php
// Czyszczenie flash messages
unset($_SESSION['flash_message']);
?>