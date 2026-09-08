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
    <footer class="site-footer bg-light border-top py-4 mt-auto">
        <div class="container">
            <!-- Filas de columnas centradas -->
            <div class="row g-4 text-center justify-content-center">
                
                <!-- Columna 1: Sistema -->
                <div class="col-12 col-md-5 col-lg-4">
                    <h5 class="fw-bold text-dark mb-2">Sistema de Tickets de Ayuda</h5>
                    <p class="small text-muted mb-2">Gestión de solicitudes de soporte Alexandra Farms S.A.S.</p>
                    <ul class="list-unstyled small mb-0">
                        <li><a href="#" class="text-decoration-none text-muted">Centro de ayuda</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">Términos y privacidad</a></li>
                    </ul>
                </div>

                <!-- Columna 2: Ubicaciones -->
                <div class="col-12 col-md-5 col-lg-4">
                    <h5 class="fw-bold text-dark mb-2">Ubicaciones</h5>
                    <p class="small text-muted mb-0">
                        <i class="fas fa-map-marker-alt text-success me-2"></i>Finca El Jardín<br>
                        <i class="fas fa-map-marker-alt text-success me-2"></i>San Ignacio
                    </p>
                </div>
            </div>

            <hr class="my-3 text-muted opacity-25">

            <!-- Copyright centrado -->
            <div class="row">
                <div class="col-12 text-center">
                    <p class="small mb-0 text-muted">&copy; <?php echo date('Y'); ?> Sistema de Tickets. Todos los derechos reservados.</p>
                </div>
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