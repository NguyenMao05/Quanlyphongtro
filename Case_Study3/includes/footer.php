    </main>

    <footer class="site-footer-v2">
        <div class="container gtpt-container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h6><i class="fa-solid fa-building"></i> GTPT</h6>
                    <p class="small mb-0">Nền tảng kết nối người thuê và chủ trọ minh bạch, nhanh chóng.</p>
                </div>
                <div class="col-md-4">
                    <h6>Khám phá</h6>
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-1"><a href="/Case_Study3/index.php">Trang chủ</a></li>
                        <li class="mb-1"><a href="/Case_Study3/motel/search.php">Tìm kiếm nâng cao</a></li>
                        <li><a href="/Case_Study3/motel/add_motel.php">Đăng tin cho thuê</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6>Liên hệ</h6>
                    <p class="small mb-1"><i class="fa-solid fa-phone"></i> 0384262881</p>
                    <p class="small mb-0"><i class="fa-solid fa-envelope"></i> symaonguyen2005@gmail.com</p>
                </div>
            </div>
            <div class="site-footer-bottom">
                &copy; <?php echo date('Y'); ?> GTPT
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/Case_Study3/assets/js/main.js"></script>
    <script>
    document.getElementById('mobileMenuBtn')?.addEventListener('click', function () {
        document.getElementById('siteNav')?.classList.toggle('is-open');
    });
    </script>
</body>
</html>
