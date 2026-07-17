<?php
// 1. Inicialización del Entorno y Seguridad
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Control de acceso nativo de tu sistema
requireAdmin(); 

$current_admin_id = getUserId();

// 2. Procesamiento y Sanitización de Filtros (Entradas)
$anio_actual = (int)date('Y');
$mes_actual = (int)date('n');

// Forzamos el casteo a entero (int) para evitar inyecciones por URL
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : $anio_actual;
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : $mes_actual;

// Rango dinámico para el selector (desde el año actual descendiendo hasta 2024)
$años_disponibles = range($anio_actual, 2024);

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// 3. Consulta de Datos a la API/Funciones Internas
$tickets = getResolvedTicketsByMonth($selected_year, $selected_month, $current_admin_id);

// Contadores rápidos para los bloques analíticos superiores
$total_tickets = count($tickets);
$resueltos = 0;
$cerrados = 0;

foreach ($tickets as $t) {
    if ($t['estado'] === 'Resuelto') {
        $resueltos++;
    }
    if ($t['estado'] === 'Cerrado') {
        $cerrados++;
    }
}

// 4. Inclusión de la Interfaz Visual Común
include '../includes/header.php';
?>

<div class="container my-4 historial-page">
    <div class="historial-hero card shadow-sm mb-4">
        <div class="card-body historial-hero-body px-4 py-3 px-md-5">
            <div class="historial-hero-line"></div>
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="historial-hero-dot"></span>
                    <h1 class="historial-hero-title mb-0">Historial personal</h1>
                </div>
            
            </div>
        </div>
    </div>

    <div class="historial-toolbar card shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="month" class="form-label historial-label">Filtrar por mes</label>
                    <select name="month" id="month" class="form-select historial-select">
                        <?php foreach ($meses as $num => $nombre): ?>
                            <option value="<?= $num ?>" <?= $selected_month === $num ? 'selected' : '' ?>>
                                <?= $nombre ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="year" class="form-label historial-label">Filtrar por año</label>
                    <select name="year" id="year" class="form-select historial-select">
                        <?php foreach ($años_disponibles as $año): ?>
                            <option value="<?= $año ?>" <?= $selected_year === $año ? 'selected' : '' ?>>
                                <?= $año ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-success historial-filter-btn">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4 g-3 historial-stats">
        <div class="col-md-4">
            <div class="card historial-stat-card shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="historial-stat-label">Total finalizados</div>
                    <div class="historial-stat-value"><?= $total_tickets ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card historial-stat-card shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="historial-stat-label historial-stat-label--success">Resueltos</div>
                    <div class="historial-stat-value historial-stat-value--success"><?= $resueltos ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card historial-stat-card shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="historial-stat-label">Cerrados</div>
                    <div class="historial-stat-value historial-stat-value--muted"><?= $cerrados ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card historial-table-card shadow-sm border-0">
        <div class="card-header historial-table-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Resultados de <?= $meses[$selected_month] ?> del <?= $selected_year ?></h5>
                <small class="text-muted">Solo tickets asignados a tu usuario.</small>
            </div>
            <span class="badge historial-count-badge"><?= $total_tickets ?> registros</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($tickets)): ?>
                <div class="p-5 text-center historial-empty">
                    <p class="mb-0 text-muted">No tienes tickets resueltos o cerrados para este periodo.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-clean historial-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 80px;">ID</th>
                                <th>Asunto</th>
                                <th>Usuario / Cliente</th>
                                <th>Ubicación y Área</th>
                                <th>Atendido por</th>
                                <th>Fecha resolución</th>
                                <th class="text-center pe-4" style="width: 120px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-secondary">#<?= $ticket['id'] ?></td>
                                    <td>
                                        <div class="historial-ticket-title"><?= htmlspecialchars($ticket['asunto'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <span class="re-state-chip <?= $ticket['estado'] === 'Resuelto' ? 're-state-chip--resuelto' : 're-state-chip--cerrado' ?> mt-2">
                                            <?= $ticket['estado'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="historial-muted-text"><?= htmlspecialchars($ticket['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td>
                                        <small class="d-block historial-location">
                                            <?= htmlspecialchars($ticket['ubicacion'], ENT_QUOTES, 'UTF-8') ?>
                                        </small>
                                        <?php if (!empty($ticket['area'])): ?>
                                            <small class="historial-area">
                                                <?= htmlspecialchars($ticket['area'], ENT_QUOTES, 'UTF-8') ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($ticket['asignado_nombre'])): ?>
                                            <span class="historial-muted-text"><?= htmlspecialchars($ticket['asignado_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php else: ?>
                                            <em class="text-muted">Sin asignar</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="historial-date">
                                            <?= date('d/m/Y H:i', strtotime($ticket['fecha_resolucion'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-center pe-4">
                                        <a href="ver_ticket.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-outline-primary historial-detail-btn">
                                            Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
include '../includes/footer.php'; 
?>