        </main>

        <footer class="bg-white border-top py-3 text-center text-muted small mt-auto no-print">
            <div class="container-fluid px-4">
                <span>&copy; <?= date('Y') ?> <strong>SPP-Pay Sekolah</strong> &bull; Sistem Informasi Pembayaran SPP Siswa</span>
            </div>
        </footer>
    </div>

    <!-- MODAL KONFIRMASI HAPUS GLOBAL -->
    <div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center p-4">
                    <div class="text-danger mb-3">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Hapus Data?</h5>
                    <p class="text-muted small mb-4">Data yang dihapus tidak dapat dipulihkan kembali. Anda yakin ingin melanjutkan?</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                        <a href="#" id="btnDoDelete" class="btn btn-danger px-3">Ya, Hapus</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle Sidebar Mobile
        const btnToggle = document.getElementById('btnToggleSidebar');
        const sidebar = document.getElementById('sidebar');
        if (btnToggle && sidebar) {
            btnToggle.addEventListener('click', function () {
                sidebar.classList.toggle('show');
            });
        }

        // Global Modal Confirm Delete Handler
        const modalDelete = document.getElementById('modalConfirmDelete');
        if (modalDelete) {
            modalDelete.addEventListener('show.bs.modal', function (event) {
                const triggerBtn = event.relatedTarget;
                const deleteUrl = triggerBtn.getAttribute('data-href');
                const btnConfirm = document.getElementById('btnDoDelete');
                if (btnConfirm) {
                    btnConfirm.setAttribute('href', deleteUrl);
                }
            });
        }

        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
