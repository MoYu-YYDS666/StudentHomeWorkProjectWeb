<?php
/**
 * ============================================================
 * 初高中作业大赏 - 公共底部
 * 文件：includes/footer.php
 * 说明：输出页脚、Bootstrap JS、站点脚本并关闭标签。
 * ============================================================
 */
?>
</main>
<footer class="bg-light border-top py-4 mt-4">
    <div class="container text-center text-muted">
        <p class="mb-1">初高中作业大赏 · 记录与分享初高中时光</p>
        <p class="mb-0 small">© <?= date('Y') ?> 本站图片仅供学习交流使用</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(base_url('assets/js/main.js')) ?>"></script>
</body>
</html>