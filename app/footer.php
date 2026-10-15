</main>
<script src="assets/vendor/bootstrap.bundle.min.js"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $src): ?>
<script src="<?= e($src) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
