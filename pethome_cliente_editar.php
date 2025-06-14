<?php
/**
 * Panel de gestión de Clientes con buscador y paginación.
 * Version:     1.0 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrolla   www.streaminginternacional.com 
 */
// Validar que se pasó un ID de usuario
if (!isset($_GET['user_id'])) {
    echo '<div class="notice notice-error"><p>Error: Falta el parámetro ID del cliente.</p></div>';
    return;
}

$user_id = intval($_GET['user_id']);
$user_data = get_userdata($user_id);

// Validar que el usuario exista
if (!$user_data) {
    echo '<div class="notice notice-error"><p>Error: No se encontró el cliente con ID ' . esc_html($user_id) . '.</p></div>';
    return;
}

// === PROCESAR FORMULARIOS Y ACCIONES ===

// Procesar la actualización del cliente (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_update_cliente'])) {
    if (!isset($_POST['pethome_cliente_nonce']) || !wp_verify_nonce($_POST['pethome_cliente_nonce'], 'pethome_update_cliente_action')) {
        echo '<div class="notice notice-error"><p>Error de seguridad. Intentalo de nuevo.</p></div>';
    } else {
        $update_user_args = [
            'ID'           => $user_id,
            'first_name'   => sanitize_text_field($_POST['cliente_nombre']),
            'last_name'    => sanitize_text_field($_POST['cliente_apellido']),
            'user_email'   => sanitize_email($_POST['cliente_email']),
        ];
        wp_update_user($update_user_args);

        $meta_fields = [
            'pethome_cliente_telefono'       => sanitize_text_field($_POST['cliente_telefono']),
            'pethome_cliente_dni'            => sanitize_text_field($_POST['cliente_dni']),
            'pethome_cliente_calle'          => sanitize_text_field($_POST['cliente_calle']),
            'pethome_cliente_numero'         => sanitize_text_field($_POST['cliente_numero']),
            'pethome_cliente_piso'           => sanitize_text_field($_POST['cliente_piso']),
            'pethome_cliente_barrio'         => sanitize_text_field($_POST['cliente_barrio']),
            'pethome_cliente_alias_bancario' => sanitize_text_field($_POST['cliente_alias_bancario']),
        ];
        foreach ($meta_fields as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }
        $user_data = get_userdata($user_id); // Recargar datos
        echo '<div class="notice notice-success is-dismissible"><p>✅ Cliente actualizado correctamente.</p></div>';
    }
}

// Procesar la adición de una reserva o pedido existente (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add_item'])) {
    if (!isset($_POST['pethome_add_item_nonce']) || !wp_verify_nonce($_POST['pethome_add_item_nonce'], 'pethome_add_item_action')) {
        echo '<div class="notice notice-error"><p>Error de seguridad. Intentalo de nuevo.</p></div>';
    } else {
        $item_id = isset($_POST['item_id_to_add']) ? intval($_POST['item_id_to_add']) : 0;
        $post_type = get_post_type($item_id);

        if ($item_id > 0 && in_array($post_type, ['reserva_guarda', 'wc_booking', 'shop_order'])) {
            update_post_meta($item_id, 'pethome_cliente_email', $user_data->user_email);
            update_post_meta($item_id, 'pethome_cliente_nombre', $user_data->first_name);
            update_post_meta($item_id, 'pethome_cliente_apellido', $user_data->last_name);
            update_post_meta($item_id, 'pethome_cliente_dni', get_user_meta($user_id, 'pethome_cliente_dni', true));
            update_post_meta($item_id, 'pethome_cliente_telefono', get_user_meta($user_id, 'pethome_cliente_telefono', true));
            echo '<div class="notice notice-success is-dismissible"><p>✅ Item #' . esc_html($item_id) . ' (' . esc_html($post_type) . ') asociado a este cliente.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>El ID ingresado no es válido o no corresponde a una reserva o pedido.</p></div>';
        }
    }
}

// Procesar la desvinculación de un item (GET)
if (isset($_GET['action']) && $_GET['action'] === 'unassign_item' && isset($_GET['item_id'])) {
    $item_id_to_unassign = intval($_GET['item_id']);
    $nonce = $_GET['_wpnonce'] ?? '';
    if (wp_verify_nonce($nonce, 'pethome_unassign_item_action_' . $item_id_to_unassign)) {
        delete_post_meta($item_id_to_unassign, 'pethome_cliente_email');
        delete_post_meta($item_id_to_unassign, 'pethome_cliente_nombre');
        delete_post_meta($item_id_to_unassign, 'pethome_cliente_apellido');
        delete_post_meta($item_id_to_unassign, 'pethome_cliente_dni');
        delete_post_meta($item_id_to_unassign, 'pethome_cliente_telefono');
        echo '<div class="notice notice-success is-dismissible"><p>✅ Item #' . esc_html($item_id_to_unassign) . ' desasociado correctamente.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Error de seguridad al intentar desasociar el item.</p></div>';
    }
}


// === OBTENER Y PRE-PROCESAR DATOS DEL HISTORIAL ===
$history_args = [
    'post_type'      => ['reserva_guarda', 'wc_booking', 'shop_order'],
    'posts_per_page' => -1,
    'meta_key'       => 'pethome_cliente_email',
    'meta_value'     => $user_data->user_email,
    'post_status'    => 'any',
    'orderby'        => 'ID',
    'order'          => 'DESC'
];
$history_query = new WP_Query($history_args);
$cuidadores_list = get_option('pethome_cuidadores', []);

// Pre-calcular total y preparar filas para la tabla
$monto_total_acumulado = 0;
$history_rows = [];
if ($history_query->have_posts()) {
    while ($history_query->have_posts()) {
        $history_query->the_post();
        $item_id = get_the_ID();
        $monto_total_item = 0;
        $item_type = get_post_type($item_id);

        if ($item_type === 'shop_order') {
            $order = wc_get_order($item_id);
            if ($order) { $monto_total_item = $order->get_total(); }
        } elseif ($item_type === 'wc_booking') {
            $booking = new WC_Booking($item_id);
            $monto_total_item = floatval($booking->get_cost());
        } else { // reserva_guarda
            $monto_total_item = floatval(get_post_meta($item_id, 'pethome_reserva_precio_total', true));
        }
        $monto_total_acumulado += $monto_total_item;
    }
    wp_reset_postdata();
}

?>

<style>
.section-block {
    background: #f9f9f9;
    border: 2px solid #ccc;
    border-radius: 16px;
    padding: 20px;
    margin-top: 30px;
}
.section-block h2 {
    margin-top: 0; margin-bottom: 20px; padding-bottom: 15px;
    border-bottom: 2px solid #e0e0e0; color: #5e4365;
}
.section-block h2 i { margin-right: 10px; }
.pethome-grid { display: grid; gap: 20px; }
.grid-3 { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
.pethome-grid label { display: block; font-weight: bold; margin-bottom: 5px; color: #5e4365; }
.pethome-grid input[type="text"],
.pethome-grid input[type="email"] { width: 100%; background: #f0f0f1; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }

.monto-display-wrapper { grid-column: 1 / -1; }
.monto-display {
    font-size: 24px;
    font-weight: bold;
    color: #ffffff;
    background-color: #5e4365;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid #4a3550;
    margin-top: 5px;
}

.guardar-cliente { background: #5e4365; color: white; font-weight: bold; border: none; border-radius: 6px; padding: 12px 35px; margin-top: 30px; cursor: pointer; font-size: 16px; }
.guardar-cliente:hover { background: #7a5d8d; }
.volver-atras { display: inline-block; margin-left: 15px; color: #5e4365; text-decoration: none; vertical-align: middle; }
.volver-atras:hover { text-decoration: underline; }

.add-item-form { display: flex; gap: 10px; align-items: center; padding: 15px; background-color: #e9e5ea; border-radius: 8px; margin-bottom: 20px; }
.add-item-form label { font-weight: bold; color: #5e4365; margin: 0; }
.add-item-form input[type="number"] { width: 160px; padding: 8px; }

.historial-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.historial-table th, .historial-table td { padding: 12px; border: 1px solid #ddd; text-align: left; vertical-align: middle; }
.historial-table th { background-color: #f2f2f2; color: #333; font-weight: bold; }
.historial-table tfoot td { font-weight: bold; background: #e9e5ea; color: #5e4365; }
.historial-table .monto-total, .historial-table .col-acciones { text-align: right; }
.col-acciones { width: 80px; text-align: center !important; }

.action-icon { text-decoration: none; display: inline-block; font-size: 18px; transition: opacity 0.2s ease-in-out; }
.action-icon:hover { opacity: 0.7; }
.remove-icon i { color: #d63638; }

.item-badge { font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 10px; color: #fff; margin-left: 5px; vertical-align: middle; text-transform: uppercase; }
.badge-phh { background-color: #5e4365; }
.badge-woo { background-color: #7f54b3; }
.badge-pedido { background-color: #28a745; }
</style>

<div class="wrap">
    <h1 style="color:#5e4365;">
        <i class="fa-thin fa-user-pen"></i> Editar Cliente: <?php echo esc_html($user_data->display_name); ?>
    </h1>

    <form method="post">
        <input type="hidden" name="cliente_user_id" value="<?php echo esc_attr($user_id); ?>">
        <?php wp_nonce_field('pethome_update_cliente_action', 'pethome_cliente_nonce'); ?>
        <div class="section-block">
            <h2><i class="fa-thin fa-address-card"></i> Datos Personales</h2>
            <div class="pethome-grid grid-3">
                <div><label for="cliente_nombre">Nombre</label><input type="text" id="cliente_nombre" name="cliente_nombre" value="<?php echo esc_attr($user_data->first_name); ?>"></div>
                <div><label for="cliente_apellido">Apellido</label><input type="text" id="cliente_apellido" name="cliente_apellido" value="<?php echo esc_attr($user_data->last_name); ?>"></div>
                <div><label for="cliente_email">Email</label><input type="email" id="cliente_email" name="cliente_email" value="<?php echo esc_attr($user_data->user_email); ?>" required></div>
                <div><label for="cliente_telefono">Teléfono</label><input type="text" id="cliente_telefono" name="cliente_telefono" value="<?php echo esc_attr(get_user_meta($user_id, 'pethome_cliente_telefono', true)); ?>"></div>
                <div><label for="cliente_dni">DNI</label><input type="text" id="cliente_dni" name="cliente_dni" value="<?php echo esc_attr(get_user_meta($user_id, 'pethome_cliente_dni', true)); ?>"></div>
                <div><label for="cliente_alias_bancario">Alias Bancario</label><input type="text" id="cliente_alias_bancario" name="cliente_alias_bancario" value="<?php echo esc_attr(get_user_meta($user_id, 'pethome_cliente_alias_bancario', true)); ?>"></div>
                
                <div class="monto-display-wrapper">
                    <label>Monto Total Histórico</label>
                    <div class="monto-display">
                        <?php echo function_exists('wc_price') ? wc_price($monto_total_acumulado) : '$' . number_format($monto_total_acumulado, 2); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="section-block">
            <h2><i class="fa-thin fa-map-location-dot"></i> Domicilio</h2>
            <div class="pethome-grid grid-3">
                <div><label for="cliente_calle">Calle</label><input type="text" id="cliente_calle" name="cliente_calle" value="<?php echo esc_attr(get_user_meta($user_id, 'pethome_cliente_calle', true)); ?>"></div>
                <div><label for="cliente_numero">Número</label><input type="text" id="cliente_numero" name="cliente_numero" value="<?php echo esc_attr(get_user_meta($user_id, 'pethome_cliente_numero', true)); ?>"></div>
                <div><label for="cliente_piso">Piso / Depto</label><input type="text" id="cliente_piso" name="cliente_piso" value="<?php echo esc_attr(get_user_meta($user_id, 'pethome_cliente_piso', true)); ?>"></div>
                <div><label for="cliente_barrio">Barrio</label><input type="text" id="cliente_barrio" name="cliente_barrio" value="<?php echo esc_attr(get_user_meta($user_id, 'pethome_cliente_barrio', true)); ?>"></div>
            </div>
        </div>
        <button type="submit" name="submit_update_cliente" class="guardar-cliente"><i class="fa-thin fa-floppy-disk"></i> Guardar Cambios</button>
        <a href="<?php echo admin_url('admin.php?page=pethome_clientes'); ?>" class="volver-atras"><i class="fa-thin fa-arrow-left"></i> Volver al listado</a>
    </form>
    
    <div class="section-block">
        <h2><i class="fa-thin fa-receipt"></i> Historial de Actividad</h2>
        <form method="post" class="add-item-form">
            <?php wp_nonce_field('pethome_add_item_action', 'pethome_add_item_nonce'); ?>
            <label for="item_id_to_add">Asociar Pedido/Reserva:</label>
            <input type="number" id="item_id_to_add" name="item_id_to_add" placeholder="N° de Pedido/Reserva" required>
            <button type="submit" name="submit_add_item" class="button button-secondary"><i class="fa-thin fa-plus"></i> Añadir</button>
        </form>

        <?php if ($history_query->have_posts()) : ?>
            <table class="historial-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Detalle</th>
                        <th>Fecha</th>
                        <th>Cuidador</th>
                        <th class="monto-total">Monto Total</th>
                        <th class="col-acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($history_query->have_posts()) : $history_query->the_post();
                        $item_id = get_the_ID();
                        $item_type = get_post_type($item_id);

                        $detalle_nombre = 'N/D';
                        $monto_total_item = 0;
                        $fecha_principal_ts = 0;
                        $nombre_cuidador = 'N/A';
                        $badge = '';

                        if ($item_type === 'shop_order') {
                            $order = wc_get_order($item_id);
                            if ($order) {
                                $items = [];
                                foreach ($order->get_items() as $item) { $items[] = $item->get_name(); }
                                $detalle_nombre = !empty($items) ? implode(', ', $items) : 'Pedido de Tienda';
                                $monto_total_item = $order->get_total();
                                $fecha_principal_ts = $order->get_date_created()->getTimestamp();
                            }
                            $badge = '<span class="item-badge badge-pedido">Pedido</span>';

                        } elseif ($item_type === 'wc_booking') {
                            $booking = new WC_Booking($item_id);
                            $producto = $booking->get_product();
                            $detalle_nombre = $producto ? $producto->get_name() : 'Servicio Eliminado';
                            $monto_total_item = floatval($booking->get_cost());
                            $fecha_principal_ts = $booking->get_start();
                            $cuidador_id = get_post_meta($item_id, 'pethome_reserva_cuidador_asignado', true);
                            if ($cuidador_id !== '' && isset($cuidadores_list[$cuidador_id])) {
                                $nombre_cuidador = esc_html($cuidadores_list[$cuidador_id]['alias']);
                            }
                            $badge = '<span class="item-badge badge-woo">Res. Woo</span>';

                        } else { // reserva_guarda
                            $detalle_nombre = get_post_meta($item_id, 'pethome_mascota_nombre', true);
                            $monto_total_item = floatval(get_post_meta($item_id, 'pethome_reserva_precio_total', true));
                            $fechas_str = get_post_meta($item_id, 'pethome_reserva_fechas', true);
                            $fechas_arr = explode(' a ', $fechas_str);
                            $fecha_principal_ts = !empty($fechas_arr[0]) ? strtotime($fechas_arr[0]) : 0;
                             $cuidador_id = get_post_meta($item_id, 'pethome_reserva_cuidador_asignado', true);
                            if ($cuidador_id !== '' && isset($cuidadores_list[$cuidador_id])) {
                                $nombre_cuidador = esc_html($cuidadores_list[$cuidador_id]['alias']);
                            }
                            $badge = '<span class="item-badge badge-phh">Res. PHH</span>';
                        }
                        
                        $fecha_principal = $fecha_principal_ts ? date('d/m/Y', $fecha_principal_ts) : 'N/D';
                        $unassign_nonce = wp_create_nonce('pethome_unassign_item_action_' . $item_id);
                        $unassign_link = admin_url('admin.php?page=pethome_cliente_editar&user_id=' . $user_id . '&action=unassign_item&item_id=' . $item_id . '&_wpnonce=' . $unassign_nonce);
                    ?>
                        <tr>
                            <td><a href="<?php echo get_edit_post_link($item_id); ?>" target="_blank">#<?php echo $item_id; ?></a> <?php echo $badge; ?></td>
                            <td><?php echo esc_html($detalle_nombre); ?></td>
                            <td><?php echo $fecha_principal; ?></td>
                            <td><?php echo esc_html($nombre_cuidador); ?></td>
                            <td class="monto-total"><?php echo function_exists('wc_price') ? wc_price($monto_total_item) : '$' . number_format($monto_total_item, 2); ?></td>
                            <td class="col-acciones">
                                <a href="<?php echo esc_url($unassign_link); ?>" class="action-icon remove-icon" title="Desasociar este item" onclick="return confirm('¿Estás seguro de que querés desasociar este item del cliente?');">
                                    <i class="fa-thin fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"><strong>Totales</strong></td>
                        <td class="monto-total"><strong><?php echo function_exists('wc_price') ? wc_price($monto_total_acumulado) : '$' . number_format($monto_total_acumulado, 2); ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        <?php else : ?>
            <p>Este cliente no tiene ninguna actividad registrada.</p>
        <?php endif;
        wp_reset_postdata(); ?>
    </div>
</div>