<?php
/**
 * Footer Bootstrap
 
 */
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
    <!-- Custom JS -->
    <?php $jsVersion = @filemtime(__DIR__ . '/../assets/js/main.js') ?: time(); ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo $jsVersion; ?>"></script>
</body>
</html>
