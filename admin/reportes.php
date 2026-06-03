<?php
/**
 * Reportes - Estadísticas y gráficas
 */

session_start();
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

// Obtener tickets del mes
$pdo = getDB();
$stmt = $pdo->prepare(
    'SELECT t.id, t.asunto, t.estado, t.ubicacion, t.fecha_creacion, u.nombre as usuario_nombre, u.email as usuario_email
     FROM tickets t
     LEFT JOIN usuarios u ON t.usuario_id = u.id
     WHERE t.fecha_creacion BETWEEN ? AND ?
     ORDER BY t.fecha_creacion DESC'
);
$stmt->execute([$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
$tickets = $stmt->fetchAll();

// Exportar a CSV (compatible con Excel) si se solicita
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = 'reportes_' . $start->format('Y_m') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // BOM para Excel con UTF-8
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Usuario', 'Email', 'Asunto', 'Estado', 'Ubicación', 'Fecha Creación']);
    foreach ($tickets as $row) {
        fputcsv($out, [
            $row['id'],
            $row['usuario_nombre'],
            $row['usuario_email'],
            $row['asunto'],
            $row['estado'],
            $row['ubicacion'],
            $row['fecha_creacion']
        ]);
    }
    fclose($out);
    exit();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Informe mensual: <?php echo htmlspecialchars($start->format('F Y'), ENT_QUOTES, 'UTF-8'); ?></h4>
    <div>
        <form method="GET" class="d-inline-block me-2">
            <label for="month" class="visually-hidden">Mes</label>
            <input type="month" id="month" name="month" value="<?php echo $selectedMonth; ?>" class="form-control d-inline-block" style="width:160px; display:inline-block;">
            <button class="btn btn-primary ms-2" type="submit">Mostrar</button>
        </form>
        <a href="?month=<?php echo $selectedMonth; ?>&export=excel" class="btn btn-success">Exportar a Excel</a>
    </div>
</div>

<?php
// Resumen rápido por estado
$total_month = count($tickets);
$counts_by_state = [];
foreach ($tickets as $t) {
    $s = $t['estado'] ?? 'Sin estado';
    if (!isset($counts_by_state[$s])) $counts_by_state[$s] = 0;
    $counts_by_state[$s]++;
}
?>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Total tickets este mes</h6>
                <h3><?php echo $total_month; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Por estado</h6>
                <?php foreach ($counts_by_state as $state => $c): ?>
                    <span class="badge bg-secondary me-2"><?php echo sanitize($state); ?>: <?php echo $c; ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Detalle de tickets del mes</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Ubicación</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No se encontraron tickets en este mes.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><code>#<?php echo $ticket['id']; ?></code></td>
                                <td><?php echo sanitize($ticket['usuario_nombre']); ?></td>
                                <td><?php echo sanitize($ticket['usuario_email']); ?></td>
                                <td><?php echo sanitize(mb_substr($ticket['asunto'], 0, 80)); ?></td>
                                <td><span class="badge bg-info"><?php echo sanitize($ticket['estado']); ?></span></td>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
