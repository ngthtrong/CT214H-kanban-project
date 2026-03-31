        </div>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <strong><?= APP_NAME ?></strong>
                    <p class="text-muted mb-0">Hệ thống quản lý dự án nhóm</p>
                </div>
                
                <div class="footer-info">
                    <p class="mb-0">
                        &copy; <?= date('Y') ?> CT214H - Web Programming | 
                        <a href="https://cit.ctu.edu.vn" target="_blank" rel="noopener">
                            Đại học Cần Thơ
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="<?= asset('js/main.js') ?>"></script>
    
    <?php if (isset($additionalJs)): ?>
        <?php foreach ($additionalJs as $js): ?>
        <script src="<?= asset($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inlineJs)): ?>
    <script>
        <?= $inlineJs ?>
    </script>
    <?php endif; ?>
</body>
</html>
