<?php
/**
 * ============================================================================
 * Template Footer Publik
 * ============================================================================
 */
declare(strict_types=1);
?>
    </main>

    <!-- Footer Zen Minimalis -->
    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-copyright">
                <p>&copy; <?= date('Y'); ?> Heru Perdana Saputra</p>
                <a href="admin/dashboard.php" class="admin-discreet-link" title="Area Administrasi">Admin</a>
            </div>
            
            <ul class="footer-links">
                <li><a href="https://github.com/14heru" target="_blank" rel="noopener noreferrer" class="footer-link">GitHub</a></li>
                <li><a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="footer-link">LinkedIn</a></li>
                <li><a href="#main-content" class="footer-link">Kembali ke Atas &uarr;</a></li>
            </ul>
        </div>
    </footer>

    <!-- Interactive Vanilla JS (Filter & UI) -->
    <script src="<?= e(asset_url('public/js/main.js') . '?v=' . (string) filemtime(__DIR__ . '/../public/js/main.js')); ?>"></script>
</body>
</html>
