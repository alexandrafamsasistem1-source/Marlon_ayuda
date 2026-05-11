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

// Obtener estadísticas
$stats_estado = countTicketsByStatus();
$stats_ubicacion = countTicketsByLocation();
$total_tickets = countTotalTickets();
$tickets = getAllTickets(500, 0);

// Calcular promedios
$promedio_resueltos = 0;
$promedio_en_proceso = 0;
$promedio_cerrados = 0;

foreach ($stats_estado as $stat) {
    if ($stat['estado'] === 'Resuelto') $promedio_resueltos = $stat['cantidad'];
    if ($stat['estado'] === 'En proceso') $promedio_en_proceso = $stat['cantidad'];
    if ($stat['estado'] === 'Cerrado') $promedio_cerrados = $stat['cantidad'];
}

// Tasa de resolución
$tasa_resolucion = $total_tickets > 0 ? round(($promedio_resueltos / $total_tickets) * 100, 1) : 0;
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<h2 class="mb-4">
    <i class="fas fa-chart-bar"></i> Reportes y Estadísticas
</h2>

<!-- KPIs Principales -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6 class="card-title text-uppercase">Total Tickets</h6>
                <h2><?php echo $total_tickets; ?></h2>
                <small>Todos los tickets del sistema</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6 class="card-title text-uppercase">Resueltos</h6>
                <h2><?php echo $promedio_resueltos; ?></h2>
                <small><?php echo $tasa_resolucion; ?>% de efectividad</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6 class="card-title text-uppercase">En Proceso</h6>
                <h2><?php echo $promedio_en_proceso; ?></h2>
                <small>Pendientes de resolución</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6 class="card-title text-uppercase">Nuevos</h6>
                <h2><?php echo isset($stats_estado[0]) && $stats_estado[0]['estado'] === 'Nuevo' ? $stats_estado[0]['cantidad'] : 0; ?></h2>
                <small>Sin procesar</small>
            </div>
        </div>
    </div>
</div>

<!-- Gráficas y Estadísticas -->
<div class="row">
    <!-- Estado de Tickets -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie"></i> Tickets por Estado
                </h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 300px;">
                    <canvas id="chartEstado"></canvas>
                </div>
                <table class="table table-sm mt-3">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Cantidad</th>
                            <th>Porcentaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats_estado as $stat): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $badgeClass = '';
                                    switch($stat['estado']) {
                                        case 'Nuevo':
                                            $badgeClass = 'badge bg-warning text-dark';
                                            break;
                                        case 'En proceso':
                                            $badgeClass = 'badge bg-info';
                                            break;
                                        case 'Resuelto':
                                            $badgeClass = 'badge bg-success';
                                            break;
                                        case 'Cerrado':
                                            $badgeClass = 'badge bg-secondary';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $badgeClass; ?>"><?php echo $stat['estado']; ?></span>
                                </td>
                                <td><strong><?php echo $stat['cantidad']; ?></strong></td>
                                <td><?php echo round(($stat['cantidad'] / $total_tickets) * 100, 1); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ubicación de Tickets -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar"></i> Tickets por Ubicación
                </h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 300px;">
                    <canvas id="chartUbicacion"></canvas>
                </div>
                <table class="table table-sm mt-3">
                    <thead>
                        <tr>
                            <th>Ubicación</th>
                            <th>Cantidad</th>
                            <th>Porcentaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats_ubicacion as $stat): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-map-marker-alt"></i>
                                    <strong><?php echo sanitize($stat['ubicacion']); ?></strong>
                                </td>
                                <td><strong><?php echo $stat['cantidad']; ?></strong></td>
                                <td><?php echo round(($stat['cantidad'] / $total_tickets) * 100, 1); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Tickets Recientes -->
<div class="card shadow">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">
            <i class="fas fa-history"></i> Tickets Recientes
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Ubicación</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $tickets_recientes = array_slice($tickets, 0, 10);
                    foreach ($tickets_recientes as $ticket): 
                    ?>
                        <tr>
                            <td><code>#<?php echo $ticket['id']; ?></code></td>
                            <td>
                                <small><?php echo sanitize($ticket['usuario_nombre']); ?></small>
                            </td>
                            <td><?php echo sanitize(substr($ticket['asunto'], 0, 30)); ?></td>
                            <td>
                                <?php 
                                $estadoClass = '';
                                switch($ticket['estado']) {
                                    case 'Nuevo':
                                        $estadoClass = 'badge bg-warning text-dark';
                                        break;
                                    case 'En proceso':
                                        $estadoClass = 'badge bg-info';
                                        break;
                                    case 'Resuelto':
                                        $estadoClass = 'badge bg-success';
                                        break;
                                    case 'Cerrado':
                                        $estadoClass = 'badge bg-secondary';
                                        break;
                                }
                                ?>
                                <span class="<?php echo $estadoClass; ?>"><?php echo $ticket['estado']; ?></span>
                            </td>
                            <td><small><?php echo sanitize($ticket['ubicacion']); ?></small></td>
                            <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js para gráficas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    // Configurar datos para gráfica de estado
    const estadoLabels = [<?php echo implode(',', array_map(function($s) { return '"' . $s['estado'] . '"'; }, $stats_estado)); ?>];
    const estadoData = [<?php echo implode(',', array_map(function($s) { return $s['cantidad']; }, $stats_estado)); ?>];
    
    // Configurar datos para gráfica de ubicación
    const ubicacionLabels = [<?php echo implode(',', array_map(function($s) { return '"' . $s['ubicacion'] . '"'; }, $stats_ubicacion)); ?>];
    const ubicacionData = [<?php echo implode(',', array_map(function($s) { return $s['cantidad']; }, $stats_ubicacion)); ?>];

    // Gráfica de Estado (Pie)
    const ctxEstado = document.getElementById('chartEstado').getContext('2d');
    new Chart(ctxEstado, {
        type: 'pie',
        data: {
            labels: estadoLabels,
            datasets: [{
                label: 'Tickets por Estado',
                data: estadoData,
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#6c757d'],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Gráfica de Ubicación (Bar)
    const ctxUbicacion = document.getElementById('chartUbicacion').getContext('2d');
    new Chart(ctxUbicacion, {
        type: 'bar',
        data: {
            labels: ubicacionLabels,
            datasets: [{
                label: 'Tickets por Ubicación',
                data: ubicacionData,
                backgroundColor: ['#007bff', '#ff6b6b'],
                borderColor: ['#0056b3', '#ff0000'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
