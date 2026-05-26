<?php
/**
 * Footer Bootstrap
 
 */
?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-inner container">
            <div class="footer-col">
                <h5>Sistema de Tickets de Ayuda</h5>
                <p class="small text-muted">Gestión eficiente de solicitudes de soporte para su finca y estaciones.</p>
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

            <div class="w-100" style="border-top:1px solid rgba(0,0,0,0.06); margin-top:1.5rem"></div>
            <div class="w-100 mt-3 text-center">
                <p class="small mb-0 text-muted">&copy; 2026 Sistema de Tickets. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <?php $jsVersion = @filemtime(__DIR__ . '/../assets/js/main.js') ?: time(); ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo $jsVersion; ?>"></script>
</body>
</html>
