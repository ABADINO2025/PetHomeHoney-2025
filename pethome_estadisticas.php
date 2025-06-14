<?php
/**
 * pethome_estadisticas.php - Panel de Estadísticas
 * Version:     1.0 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrolla   www.streaminginternacional.com 
 */

if (!defined('ABSPATH')) exit;

function pethome_estadisticas_panel() {
    global $wpdb;

    echo '<div class="wrap pethome-stats-wrap">';
    echo '<h1 style="color:#5e4365; text-align:center;"><i class="fa-thin fa-chart-mixed" style="margin-right:15px;"></i>Estadísticas</h1>';
    echo '<p style="font-size:16px; text-align:center;">Aquí se muestran los datos estadísticos de las reservas e ingresos mensuales.</p>';

    $posts_table = $wpdb->prefix . "posts";
    $postmeta_table = $wpdb->prefix . "postmeta";

    // --- 1. RESERVAS MENSUALES (Fuente: WooCommerce Orders) ---
    $reservas_query = $wpdb->prepare("
        SELECT DATE_FORMAT(post_date, %s) as mes, COUNT(*) as total
        FROM $posts_table
        WHERE post_type = %s AND post_status IN ('wc-completed', 'wc-processing')
        GROUP BY mes
        ORDER BY STR_TO_DATE(CONCAT('01/', mes), %s)
    ", '%m/%Y', 'shop_order', '%d/%m/%Y');
    $reservas_result = $wpdb->get_results($reservas_query);
    
    $reservas_meses = [];
    $reservas_totales = [];
    if (is_array($reservas_result)) {
        foreach ($reservas_result as $r) {
            $reservas_meses[] = $r->mes;
            $reservas_totales[] = (int)$r->total;
        }
    }

    // --- 2. INGRESOS MENSUALES (Fuente: WooCommerce Orders) ---
    $ingresos_query = $wpdb->prepare("
        SELECT DATE_FORMAT(p.post_date, %s) as mes, SUM(pm.meta_value) as total
        FROM $posts_table p
        JOIN $postmeta_table pm ON p.ID = pm.post_id
        WHERE p.post_type = %s 
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND pm.meta_key = %s
        GROUP BY mes
        ORDER BY STR_TO_DATE(CONCAT('01/', mes), %s)
    ", '%m/%Y', 'shop_order', '_order_total', '%d/%m/%Y');
    $ingresos_result = $wpdb->get_results($ingresos_query);
    
    $ingresos_meses = [];
    $ingresos_totales = [];
    if (is_array($ingresos_result)) {
        foreach ($ingresos_result as $r) {
            $ingresos_meses[] = $r->mes;
            $ingresos_totales[] = (float)$r->total;
        }
    }

    // --- 3. GUARDAS POR CUIDADOR (Fuente: PHH 'reserva_guarda') ---
    $cuidadores_query = $wpdb->prepare("
        SELECT pm.meta_value AS cuidador, COUNT(*) AS total
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = %s
          AND p.post_type = %s
          AND p.post_status = 'publish'
          AND pm.meta_value != ''
        GROUP BY pm.meta_value
    ", 'pethome_reserva_cuidador_asignado', 'reserva_guarda');
    $cuidadores_result = $wpdb->get_results($cuidadores_query);

    $cuidadores_nombres = []; 
    $cuidadores_cantidades = [];
    if (is_array($cuidadores_result)) {
        foreach ($cuidadores_result as $g) {
            $cuidadores_nombres[] = $g->cuidador ?: 'Sin Asignar';
            $cuidadores_cantidades[] = (int)$g->total;
        }
    }
    ?>

    <div class="section-block">
        <h2><i class="fa-thin fa-calendar-day" style="margin-right:10px;"></i>Reservas por Mes (WooCommerce)</h2>
        <canvas id="reservas_por_mes_chart"></canvas>
    </div>

    <div class="section-block">
        <h2><i class="fa-thin fa-money-bill-trend-up" style="margin-right:10px;"></i>Ingresos por Mes (WooCommerce)</h2>
        <canvas id="ingresos_por_mes_chart"></canvas>
    </div>

    <div class="section-block">
        <h2><i class="fa-thin fa-people" style="margin-right:10px;"></i>Guardas por Cuidador (PetHomeHoney)</h2>
        <canvas id="guardas_por_cuidador_chart"></canvas>
    </div>

    </div> <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const phhBorderColor = 'rgba(94, 67, 101, 1)';
        const phhDonutColors = [
            'rgba(94, 67, 101, 0.9)', 'rgba(156, 126, 169, 0.9)',
            'rgba(211, 196, 218, 0.9)', 'rgba(94, 67, 101, 0.6)',
            'rgba(156, 126, 169, 0.6)', 'rgba(211, 196, 218, 0.6)'
        ];

        const generateGradientColors = (values) => {
            const hue = 285, saturation = 40, startLum = 90, endLum = 45;
            if (!values || values.length === 0) return [`hsl(${hue}, ${saturation}%, ${(startLum + endLum) / 2}%)`];
            if (values.length === 1) return [`hsl(${hue}, ${saturation}%, ${endLum}%)`];
            
            const min = Math.min(...values);
            const max = Math.max(...values);
            const range = max - min;

            return values.map(v => {
                const factor = (range > 0) ? ((v - min) / range) : 0;
                const luminosity = startLum - (factor * (startLum - endLum));
                return `hsl(${hue}, ${saturation}%, ${luminosity}%)`;
            });
        };

        // Gráfico 1: Reservas por Mes
        const ctx1 = document.getElementById('reservas_por_mes_chart');
        if (ctx1) {
            const reservasData = <?php echo json_encode($reservas_totales); ?>;
            new Chart(ctx1.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($reservas_meses); ?>,
                    datasets: [{
                        label: 'N° de Pedidos',
                        data: reservasData,
                        backgroundColor: generateGradientColors(reservasData),
                        borderColor: phhBorderColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }

        // Gráfico 2: Ingresos por Mes
        const ctx2 = document.getElementById('ingresos_por_mes_chart');
        if (ctx2) {
            new Chart(ctx2.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($ingresos_meses); ?>,
                    datasets: [{
                        label: 'Ingresos ($)',
                        data: <?php echo json_encode($ingresos_totales); ?>,
                        backgroundColor: 'rgba(94, 67, 101, 0.2)',
                        borderColor: phhBorderColor,
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        // Gráfico 3: Guardas por Cuidador
        const ctx3 = document.getElementById('guardas_por_cuidador_chart');
        if (ctx3) {
            const cuidadoresData = <?php echo json_encode($cuidadores_cantidades); ?>;
            new Chart(ctx3.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($cuidadores_nombres); ?>,
                    datasets: [{
                        label: 'Cantidad de Guardas',
                        data: cuidadoresData,
                        backgroundColor: generateGradientColors(cuidadoresData),
                        borderColor: phhBorderColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false } 
                    },
                    scales: { 
                        y: { 
                            beginAtZero: true, 
                            ticks: { precision: 0 } 
                        } 
                    }
                }
            });
        }
    });
    </script>

    <style>
    .section-block {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 25px;
        margin-top: 30px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    }
    .section-block h2 {
        margin-top: 0;
        margin-bottom: 25px;
        color: #5e4365;
        text-align: center;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }
    #reservas_por_mes_chart, #ingresos_por_mes_chart, #guardas_por_cuidador_chart {
        max-height: 400px !important;
    }
    </style>
<?php
}
?>