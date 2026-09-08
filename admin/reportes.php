<?php
/**
 * Reportes - Estadísticas y gráficas
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar que sea admin
if (!isLoggedIn()) {
    include __DIR__ . '/../auth/login.php';
    exit();
}
if (!isAdmin()) {
    include __DIR__ . '/../usuario/dashboard.php';
    exit();
}

$pageTitle = 'Reportes';

// Selector de mes (formato YYYY-MM)
$selectedMonth = $_GET['month'] ?? date('Y-m');
// Validar formato
if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}

// Calcular rango de fechas para el mes seleccionado
$start = new DateTime($selectedMonth . '-01 00:00:00');
$end = clone $start;
$end->modify('last day of this month')->setTime(23,59,59);

// Obtener tickets del mes (incluyendo tipo_problema)
$pdo = getDB();
$stmt = $pdo->prepare(
    'SELECT t.id, t.asunto, t.estado, t.ubicacion, t.tipo_problema, t.fecha_creacion, u.nombre as usuario_nombre, u.email as usuario_email
     FROM tickets t
     LEFT JOIN usuarios u ON t.usuario_id = u.id
     WHERE t.fecha_creacion BETWEEN ? AND ?
     ORDER BY t.fecha_creacion DESC'
);
$stmt->execute([$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
$tickets = $stmt->fetchAll();

// Resumen por categoría (Software / Hardware / Sin clasificar) y sus estados
$total_month = count($tickets);

$software_states = [];
$hardware_states = [];
$unclassified_states = [];

foreach ($tickets as $t) {
    $s = $t['estado'] ?? 'Sin estado';
    $tipo = strtolower(trim($t['tipo_problema'] ?? ''));

    if (strpos($tipo, 'software') !== false) {
        $software_states[$s] = ($software_states[$s] ?? 0) + 1;
    } elseif (strpos($tipo, 'hardware') !== false) {
        $hardware_states[$s] = ($hardware_states[$s] ?? 0) + 1;
    } else {
        $unclassified_states[$s] = ($unclassified_states[$s] ?? 0) + 1;
    }
}

$stateClassMap = [
    'Nuevo' => 're-state-chip--nuevo',
    'En proceso' => 're-state-chip--proceso',
    'Resuelto' => 're-state-chip--resuelto',
    'Cerrado' => 're-state-chip--cerrado',
    'Sin estado' => 're-state-chip--default'
];

$monthNames = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
$monthLabel = $monthNames[(int)$start->format('n')] . ' ' . $start->format('Y');

// Exportar a CSV (compatible con Excel) si se solicita
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = 'reportes_' . $start->format('Y_m') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // BOM para Excel con UTF-8
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Usuario', 'Email', 'Asunto', 'Estado', 'Tipo Problema', 'Ubicación', 'Fecha Creación']);
    foreach ($tickets as $row) {
        fputcsv($out, [
            $row['id'],
            $row['usuario_nombre'],
            $row['usuario_email'],
            $row['asunto'],
            $row['estado'],
            $row['tipo_problema'] ?? 'Sin clasificar',
            $row['ubicacion'],
            $row['fecha_creacion']
        ]);
    }
    fclose($out);
    exit();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="reportes-page">
    <div class="reportes-toolbar card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="mb-1">Informe mensual</h4>
                <p class="reportes-subtitle mb-0"><?php echo sanitize($monthLabel); ?></p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label for="month" class="visually-hidden">Mes</label>
                    <input type="month" id="month" name="month" value="<?php echo $selectedMonth; ?>" class="form-control reportes-month-input">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-filter me-1"></i> Mostrar
                    </button>
                </form>
                <a href="?month=<?php echo $selectedMonth; ?>&export=excel" class="btn btn-outline-success">
                    <i class="fas fa-file-excel me-1"></i> Exportar
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card reportes-stat-card shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                    <div class="reportes-stat-label">Total de tickets</div>
                    <div class="reportes-stat-value my-1"><?php echo $total_month; ?></div>
                    <small class="text-muted">Registrados en el mes seleccionado</small>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card reportes-stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="reportes-stat-label mb-3">Distribución por estado</div>
                    
                    <?php if (empty($software_states) && empty($hardware_states) && empty($unclassified_states)): ?>
                        <small class="text-muted">Sin tickets registrados para mostrar.</small>
                    <?php else: ?>
                        <!-- Desglose Software -->
                        <div class="mb-2">
                            <div class="fw-bold mb-1" style="color: #006547; font-size: 0.9rem;">
                                <i class="fas fa-laptop-code me-1"></i> Software
                            </div>
                            <?php if (empty($software_states)): ?>
                                <small class="text-muted d-block ms-2">Sin tickets registrados</small>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($software_states as $state => $c): ?>
                                        <?php $chipClass = $stateClassMap[$state] ?? 're-state-chip--default'; ?>
                                        <span class="re-state-chip <?php echo $chipClass; ?>">
                                            <?php echo sanitize($state); ?>: <?php echo (int)$c; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr class="my-2" style="border-top: 1px dashed #e0e0e0;">

                        <!-- Desglose Hardware -->
                        <div class="mb-2">
                            <div class="fw-bold mb-1" style="color: #C57D88; font-size: 0.9rem;">
                                <i class="fas fa-desktop me-1"></i> Hardware
                            </div>
                            <?php if (empty($hardware_states)): ?>
                                <small class="text-muted d-block ms-2">Sin tickets registrados</small>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($hardware_states as $state => $c): ?>
                                        <?php $chipClass = $stateClassMap[$state] ?? 're-state-chip--default'; ?>
                                        <span class="re-state-chip <?php echo $chipClass; ?>">
                                            <?php echo sanitize($state); ?>: <?php echo (int)$c; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Desglose Sin clasificar -->
                        <?php if (!empty($unclassified_states)): ?>
                            <hr class="my-2" style="border-top: 1px dashed #e0e0e0;">
                            <div>
                                <div class="fw-bold mb-1 text-secondary" style="font-size: 0.9rem;">
                                    <i class="fas fa-question-circle me-1"></i> Sin clasificar
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($unclassified_states as $state => $c): ?>
                                        <?php $chipClass = $stateClassMap[$state] ?? 're-state-chip--default'; ?>
                                        <span class="re-state-chip <?php echo $chipClass; ?>">
                                            <?php echo sanitize($state); ?>: <?php echo (int)$c; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm reportes-table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list-ul me-2"></i>Detalle de tickets del mes
            </h5>
            <span class="badge bg-light text-dark"><?php echo $total_month; ?> registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover reportes-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Asunto</th>
                            <th>Estado</th>
                            <th>Tipo</th>
                            <th>Ubicación</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No se encontraron tickets en este mes.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <?php $statusClass = $stateClassMap[$ticket['estado'] ?? ''] ?? 're-state-chip--default'; ?>
                                <tr>
                                    <td><a class="reportes-ticket-link" href="<?php echo BASE_URL; ?>/admin/ver_ticket.php?id=<?php echo (int)$ticket['id']; ?>">#<?php echo (int)$ticket['id']; ?></a></td>
                                    <td><?php echo sanitize($ticket['usuario_nombre']); ?></td>
                                    <td><?php echo sanitize($ticket['usuario_email']); ?></td>
                                    <td><?php echo sanitize(mb_substr($ticket['asunto'], 0, 80)); ?></td>
                                    <td><span class="re-state-chip <?php echo $statusClass; ?>"><?php echo sanitize($ticket['estado']); ?></span></td>
                                    <td>
                                        <?php if (!empty($ticket['tipo_problema'])): ?>
                                            <span class="badge bg-info text-dark" style="font-size: 0.75rem;">
                                                <?php echo sanitize($ticket['tipo_problema']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin clasificar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo sanitize($ticket['ubicacion']); ?></td>
                                    <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>