<?php
/**
 * Footer Bootstrap
 
 */
?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
            <style>
                /* Footer compacto y responsivo */
                footer { padding: 0.6rem 0; background: transparent; }
                .footer-inner.container { display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-start; padding:0.5rem 0; }
                .footer-inner .footer-col { flex:1 1 240px; margin-bottom:0; padding-right:0.5rem; }
                .footer-inner h5 { margin-bottom:0.25rem; font-size:1rem; }
                .footer-inner p, .footer-inner ul, .footer-inner .small { margin-bottom:0.25rem; font-size:0.85rem; }
                .footer-inner .w-100 { border-top:1px solid rgba(0,0,0,0.06); margin-top:0.8rem; }
                footer .footer-inner .footer-col ul { padding-left:0; margin:0; }
                @media (max-width:768px) { .footer-inner.container { padding:0.5rem 0; } }
            </style>

            <div class="footer-inner container">
            <div class="footer-col">
                <h5>Sistema de Tickets de Ayuda</h5>
                <p class="small text-muted">Gestión  de solicitudes de soporte alexandra farms s.a.s..</p>
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

            <!-- Administración links removed; 'Reportes' moved to header -->

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
