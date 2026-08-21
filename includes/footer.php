<?php
/**
 * Footer Bootstrap
 
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
            <div class="footer-inner container">
            <div class="footer-col">
                <h5>Sistema de Tickets de Ayuda</h5>
                <p class="small text-muted">Gestión de solicitudes de soporte Alexandra Farms S.A.S.</p>
                <ul class="list-unstyled small mt-2">
                    <li><a href="#" class="text-decoration-none">Centro de ayuda</a></li>
                    <li><a href="#" class="text-decoration-none">Términos y privacidad</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h5>Ubicaciones</h5>
                <p class="small text-muted mb-0">
                    <i class="fas fa-map-marker-alt me-2"></i>Finca El Jardín<br>
                    <i class="fas fa-map-marker-alt me-2"></i>San Ignacio
                </p>
            </div>

            <div class="footer-col">
                <h5>Contacto</h5>
                <ul class="list-unstyled small mt-2">
                    <li class="text-muted">soporte@alexandrafarms.com</li>
                    <li class="text-muted">+57 320 000 0000</li>
                </ul>
            </div>

            <div class="w-100"></div>
            <div class="w-100 mt-2 text-center">
                <p class="small mb-0 text-muted">&copy; 2026 Sistema de Tickets. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JS -->
    <?php $jsVersion = @filemtime(__DIR__ . '/../assets/js/main.js') ?: time(); ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo $jsVersion; ?>"></script>
    <?php if (!empty($_SESSION['flash_notification']) && is_array($_SESSION['flash_notification'])): ?>
        <?php $flash = $_SESSION['flash_notification']; unset($_SESSION['flash_notification']); ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: <?php echo json_encode($flash['tipo'] ?? 'info'); ?>,
                    title: <?php echo json_encode($flash['titulo'] ?? ''); ?>,
                    text: <?php echo json_encode($flash['mensaje'] ?? ''); ?>,
                    showConfirmButton: false,
                    timer: 2200,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
            });
        </script>
    <?php endif; ?>
</body>
</html>
