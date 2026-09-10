</main>


<footer class="footer footer-simple">
    <div class="footer-container">
        <div class="footer-brand-center">
            <a href="/" class="footer-logo">
                <img src="/assets/img/logo.svg" alt="ThriftVibe" class="brand-logo">
                <span class="brand-text">ThriftVibe</span>
            </a>
            <p class="footer-tagline">Platform membeli barang thrift yang terpercaya</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> ThriftVibe Market. All rights reserved.</p>
    </div>
</footer>


<div class="modal" id="loginModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Login Diperlukan</h3>
            <button class="modal-close" onclick="closeModal('loginModal')">×</button>
        </div>
        <div class="modal-body">
            <p>Silakan login terlebih dahulu untuk melanjutkan.</p>
        </div>
        <div class="modal-footer">
            <a href="/auth/login.php" class="btn btn-primary">Login Sekarang</a>
            <a href="/auth/register.php" class="btn btn-outline">Daftar Baru</a>
        </div>
    </div>
</div>

<script src="/assets/js/main.js"></script>
</body>

</html>
