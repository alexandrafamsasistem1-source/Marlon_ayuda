<?php
/**
 * Footer Bootstrap
 * Se incluye al final de cada página
 */
?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Sistema de Tickets de Ayuda</h5>
                    <p class="text-muted">Gestión eficiente de solicitudes de soporte</p>
                </div>
                <div class="col-md-6">
                    <h5>Ubicaciones</h5>
                    <p class="text-muted">
                        <i class="fas fa-map-marker-alt"></i> Finca El Jardín<br>
                        <i class="fas fa-map-marker-alt"></i> San Ignacio
                    </p>
                </div>
            </div>
            <hr class="bg-secondary">
            <p class="text-muted mb-0">&copy; 2026 Sistema de Tickets. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <?php $jsVersion = @filemtime(__DIR__ . '/../assets/js/main.js') ?: time(); ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo $jsVersion; ?>"></script>
</body>
</html>
