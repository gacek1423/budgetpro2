</main>
        </div>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        <?php if (isset($_SESSION['flash_message'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showToast(
                    "<?php echo addslashes($_SESSION['flash_message']['text']); ?>", 
                    "<?php echo $_SESSION['flash_message']['type']; ?>"
                );
            });
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>