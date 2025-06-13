<?php
/**
 * File: pethome_todas_las_reservas.php
 * Muestra un panel unificado, con búsqueda y paginación, de todas las reservas, ordenado por fecha de creación y con edición en línea.
 * Panel de listado de reservas (modificado para ser un CPT 'reserva_guarda' o WC_Booking)
 * Panel de gestión de Clientes con buscador y paginación.
 * Plugin Name: PetHomeHoney Plugin
 * Plugin URI:  https://pethomehoney.com.ar
 * Description: Plugin para gestionar reservas de guarda con WooCommerce y CPT.
 * Version:     1.0 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrolla   www.streaminginternacional.com 
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Función principal que renderiza el panel de "Todas las Reservas".
 */
function pethome_todas_las_reservas_panel() {

    // --- INICIO: LÓGICA DE BÚSQUEDA, PAGINACIÓN Y OPCIONES ---
    $user_id = get_current_user_id();
    $meta_key_per_page = 'reservas_por_pagina';
    $per_page_options = [25, 50, 100];

    if ( isset($_POST['pethome_per_page_nonce']) && wp_verify_nonce($_POST['pethome_per_page_nonce'], 'pethome_per_page_action') && isset($_POST['reservas_per_page']) ) {
        $new_per_page = intval($_POST['reservas_per_page']);
        if ($new_per_page === -1 || in_array($new_per_page, $per_page_options)) {
            update_user_meta($user_id, $meta_key_per_page, $new_per_page);
        }
    }

    $items_per_page = get_user_meta($user_id, $meta_key_per_page, true);
    if ( !in_array($items_per_page, $per_page_options) && $items_per_page != -1 ) {
        $items_per_page = 25; // Valor por defecto
    }

    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $search_term = isset($_GET['phh_search']) ? sanitize_text_field(wp_unslash($_GET['phh_search'])) : '';
    
    if ($items_per_page == -1) {
        $items_per_page_for_query = 9999;
        $current_page = 1;
    } else {
        $items_per_page_for_query = $items_per_page;
    }
    
    // --- Lógica PHP para obtener y ordenar TODAS las reservas ---
    $todas_las_reservas = [];

    // 1. OBTENER RESERVAS DE WOOCOMMERCE BOOKINGS
    if (class_exists('WC_Booking')) {
        $wc_bookings_posts = get_posts(['post_type' => 'wc_booking', 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC']);
        foreach ($wc_bookings_posts as $booking_post) {
            $booking = new WC_Booking($booking_post->ID);
            if ($booking) {
                $order = $booking->get_order() ?: false;
                
                $cuidador_alias = get_post_meta($booking->get_id(), 'pethome_reserva_cuidador_asignado', true);
                if (empty($cuidador_alias)) {
                    $resource_id = $booking->get_resource_id();
                    if ($resource_id > 0) {
                        $resource = $booking->get_resource();
                        if ($resource && method_exists($resource, 'get_type') && $resource->get_type() === 'user') {
                             $cuidador_user_id = $resource->get_id();
                             $cuidador_alias = get_user_meta($cuidador_user_id, 'nickname', true) ?: $resource->get_name();
                        } else if ($resource) {
                            $cuidador_alias = $resource->get_name();
                        }
                    }
                }

                $todas_las_reservas[] = [
                    'id'            => $booking->get_id(),
                    'type'          => 'wc_booking',
                    'creation_date' => $booking_post->post_date,
                    'start_date'    => $booking->get_start(),
                    'end_date'      => $booking->get_end(),
                    'status'        => $booking->get_status(),
                    'cliente'       => $order ? $order->get_formatted_billing_full_name() : 'N/A',
                    'servicio'      => $booking->get_product() ? $booking->get_product()->get_name() : 'Servicio Eliminado',
                    'cuidador'      => $cuidador_alias ?: 'N/A',
                    'edit_link'     => get_edit_post_link($booking->get_id()),
                ];
            }
        }
    }

    // 2. OBTENER RESERVAS MANUALES/IMPORTADAS
    $manual_reservas_posts = get_posts(['post_type' => 'reserva_guarda', 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC']);
    foreach ($manual_reservas_posts as $reserva_post) {
        $fechas = get_post_meta($reserva_post->ID, 'pethome_reserva_fechas', true);
        $start_timestamp = 0; $end_timestamp = 0;
        if (!empty($fechas)) {
            $date_parts = explode(' al ', $fechas);
            if (count($date_parts) === 2) {
                $start_timestamp = strtotime($date_parts[0]); $end_timestamp = strtotime($date_parts[1]);
            }
        }
        $todas_las_reservas[] = [
            'id'            => $reserva_post->ID,
            'type'          => 'reserva_guarda',
            'creation_date' => $reserva_post->post_date,
            'start_date'    => $start_timestamp,
            'end_date'      => $end_timestamp,
            'status'        => get_post_meta($reserva_post->ID, 'pethome_reserva_prioridad', true) ?: 'normal',
            'cliente'       => get_post_meta($reserva_post->ID, 'pethome_cliente_nombre', true) . ' ' . get_post_meta($reserva_post->ID, 'pethome_cliente_apellido', true),
            'servicio'      => get_post_meta($reserva_post->ID, 'pethome_mascota_nombre', true),
            'cuidador'      => get_post_meta($reserva_post->ID, 'pethome_reserva_cuidador_asignado', true) ?: 'N/A',
            'edit_link'     => get_edit_post_link($reserva_post->ID),
        ];
    }

    // 3. ORDENAR POR FECHA DE CREACIÓN
    if (!empty($todas_las_reservas)) {
        usort($todas_las_reservas, function($a, $b) { 
            return strtotime($b['creation_date']) <=> strtotime($a['creation_date']); 
        });
    }
    
    // 4. LÓGICA DE FILTRADO POR BÚSQUEDA
    $reservas_filtradas = [];
    if (!empty($search_term)) {
        foreach ($todas_las_reservas as $reserva) {
            $id_str = ($reserva['type'] === 'wc_booking') ? 'Woo' . $reserva['id'] : 'PHH' . $reserva['id'];
            if (
                stripos($id_str, $search_term) !== false ||
                stripos($reserva['cliente'], $search_term) !== false ||
                stripos($reserva['servicio'], $search_term) !== false ||
                stripos($reserva['cuidador'], $search_term) !== false ||
                $reserva['id'] == $search_term
            ) {
                $reservas_filtradas[] = $reserva;
            }
        }
    } else {
        $reservas_filtradas = $todas_las_reservas;
    }

    // 5. LÓGICA DE PAGINACIÓN
    $total_items = count($reservas_filtradas);
    $items_para_mostrar = array_slice($reservas_filtradas, ($current_page - 1) * $items_per_page_for_query, $items_per_page_for_query);
    ?>
    <style>
        .wrap.pethome-admin-wrap .wp-list-table {
            width: 100% !important; border: none; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 15px; overflow: hidden; background-color: #fff;
            border-spacing: 0; border-collapse: separate;
        }
        .wrap.pethome-admin-wrap .wp-list-table thead tr th {
            background-color: #5e4365; color: #ffffff; font-weight: bold;
            border-bottom: 2px solid #4a324f; text-align: center;
            padding-top: 12px; padding-bottom: 12px;
        }
        .wrap.pethome-admin-wrap .wp-list-table tbody tr td {
            border-bottom: 1px solid #eee; vertical-align: middle; text-align: center;
            padding-top: 6px; padding-bottom: 6px; line-height: 1.3;
        }
        .wrap.pethome-admin-wrap .wp-list-table tbody tr:last-child td { border-bottom: none; }
        .wrap.pethome-admin-wrap .wp-list-table th, .wrap.pethome-admin-wrap .wp-list-table td { border-left: 1px solid #eee; }
        .wrap.pethome-admin-wrap .wp-list-table th:first-child, .wrap.pethome-admin-wrap .wp-list-table td:first-child { border-left: none; }
        .wrap.pethome-admin-wrap .wp-list-table thead tr:first-child th:first-child { border-top-left-radius: 15px; }
        .wrap.pethome-admin-wrap .wp-list-table thead tr:first-child th:last-child { border-top-right-radius: 15px; }
        .wrap.pethome-admin-wrap .wp-list-table tbody tr:last-child td:first-child { border-bottom-left-radius: 15px; }
        .wrap.pethome-admin-wrap .wp-list-table tbody tr:last-child td:last-child { border-bottom-right-radius: 15px; }
        .wp-list-table td.column-acciones {
            padding-top: 5px !important; padding-bottom: 5px !important;
        }
        .wrap.pethome-admin-wrap .wp-list-table td.column-acciones .button.button-secondary {
            display: inline-flex !important; align-items: center; justify-content: center;
            height: auto !important; min-height: unset !important; line-height: 1 !important;
            font-size: 11px !important; padding: 4px 8px !important; white-space: nowrap;
        }
        .pethome-table-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; margin-top: 0; }
        .pethome-table-controls .search-box { margin: 0; }
        .pethome-pagination-nav { margin-top: 20px; text-align: center; }
        .pethome-pagination-nav .page-numbers { display: inline-block; padding: 8px 14px; margin: 0 2px; border: 1px solid #ccc; border-radius: 4px; text-decoration: none; color: #5e4365; background: #fff; }
        .pethome-pagination-nav .page-numbers.current, .pethome-pagination-nav .page-numbers:hover { background: #5e4365; color: #fff; border-color: #5e4365; }
        .editable-cuidador { cursor: pointer; position: relative; }
        .editable-cuidador:hover { background-color: #f0f8ff !important; }
        .editable-cuidador .cuidador-input { width: 90%; padding: 2px; box-sizing: border-box; }
        .saving-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(0,0,0,.3); border-radius: 50%; border-top-color: #000; animation: spin 1s ease-in-out infinite; margin-left: 5px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>

    <div class="wrap pethome-admin-wrap">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0;">Todas las Reservas</h1>
            <span style="font-style: italic; color: #555;">(Una vista centralizada de todas las reservas)</span>
        </div>
        
        <div class="pethome-table-controls">
            <div class="pethome-pagination-nav">
                <form method="POST" action="">
                    <?php wp_nonce_field('pethome_per_page_action', 'pethome_per_page_nonce'); ?>
                    <span>Mostrar</span>
                    <select name="reservas_per_page" onchange="this.form.submit()">
                        <?php
                        $current_per_page_val = get_user_meta($user_id, 'reservas_por_pagina', true) ?: 25;
                        foreach ($per_page_options as $option) {
                            echo '<option value="' . esc_attr($option) . '" ' . selected($current_per_page_val, $option, false) . '>' . esc_html($option) . '</option>';
                        }
                        echo '<option value="-1" ' . selected($current_per_page_val, -1, false) . '>Todos</option>';
                        ?>
                    </select>
                    <span>resultados por página.</span>
                </form>
            </div>
            
            <form method="GET">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
                <p class="search-box">
                    <label class="screen-reader-text" for="pethome-search-input">Buscar Reserva:</label>
                    <input type="search" id="pethome-search-input" name="phh_search" value="<?php echo esc_attr($search_term); ?>">
                    <input type="submit" id="search-submit" class="button" value="Buscar Reserva">
                </p>
            </form>
        </div>

        <table class="wp-list-table widefat fixed">
            <thead>
                <tr>
                    <th scope="col" style="width: 150px;">Pedido</th>
                    <th scope="col" style="width: 200px;">Fecha de Reserva</th>
                    <th scope="col" style="width: 180px;">Cliente</th>
                    <th scope="col">Servicio</th>
                    <th scope="col" style="width: 140px;">Cuidador</th>
                    <th scope="col" style="width: 120px;">Estado</th>
                    <th scope="col" class="column-acciones" style="width: 100px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items_para_mostrar)): ?>
                    <tr>
                        <td colspan="7">
                            <?php
                            if (!empty($search_term)) {
                                echo 'No se encontraron reservas que coincidan con su búsqueda.';
                            } else {
                                echo 'No se encontraron reservas.';
                            }
                            ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items_para_mostrar as $reserva): ?>
                        <?php
                            if ($reserva['type'] === 'wc_booking') { $pedido_display_id = 'Woo - Pedido #' . $reserva['id']; } 
                            else { $pedido_display_id = 'PHH - Pedido #' . $reserva['id']; }
                        ?>
                        <tr>
                            <td><a href="<?php echo esc_url($reserva['edit_link']); ?>"><strong><?php echo esc_html($pedido_display_id); ?></strong></a></td>
                            <td><?php echo esc_html(date_i18n('d/m/Y', $reserva['start_date'])) . ' - ' . esc_html(date_i18n('d/m/Y', $reserva['end_date'])); ?></td>
                            <td><?php echo esc_html($reserva['cliente']); ?></td>
                            <td><?php echo esc_html($reserva['servicio']); ?></td>
                            <td class="editable-cuidador" data-id="<?php echo esc_attr($reserva['id']); ?>">
                                <span class="cuidador-text"><?php echo esc_html($reserva['cuidador']); ?></span>
                            </td>
                            <td><?php echo function_exists('pethome_get_status_badge') ? pethome_get_status_badge($reserva['status']) : esc_html($reserva['status']); ?></td>
                            <td class="column-acciones"><a href="<?php echo esc_url($reserva['edit_link']); ?>" class="button button-secondary">Ver/Editar</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pethome-pagination-nav">
            <?php
            $total_pages_for_links = ($items_per_page_for_query == 9999) ? 1 : ceil($total_items / $items_per_page_for_query);
            $pagination_base_url = add_query_arg('paged', '%#%');
            if (!empty($search_term)) {
                $pagination_base_url = add_query_arg('phh_search', urlencode($search_term), $pagination_base_url);
            }

            echo paginate_links([
                'base' => $pagination_base_url, 'format' => '?paged=%#%',
                'current' => $current_page, 'total' => $total_pages_for_links,
                'prev_text' => '« Anterior', 'next_text' => 'Siguiente »',
            ]);
            ?>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        const updateNonce = '<?php echo wp_create_nonce("pethome_actualizar_cuidador_nonce"); ?>';

        // Usamos delegación de eventos para que funcione con la paginación
        $('.wp-list-table tbody').on('click', '.editable-cuidador', function() {
            var $td = $(this);
            if ($td.find('input').length) return;

            var currentText = $td.find('.cuidador-text').text().trim();
            var reservaId = $td.data('id');
            
            $td.find('.cuidador-text').hide();
            var $input = $('<input type="text" class="cuidador-input">');
            $input.val(currentText === 'N/A' ? '' : currentText);
            $td.append($input);
            $input.focus();

            function guardarCambios() {
                var nuevoValor = $input.val().trim();
                
                // Si el valor no ha cambiado, no hacemos nada y solo revertimos la UI
                if (nuevoValor === currentText || (nuevoValor === '' && currentText === 'N/A') ) {
                     $input.remove();
                     $td.find('.cuidador-text').show();
                     return;
                }
                
                $td.append('<span class="saving-spinner"></span>');
                $input.prop('disabled', true);

                $.ajax({
                    url: ajaxurl, type: 'POST',
                    data: {
                        action: 'pethome_actualizar_cuidador',
                        security: updateNonce,
                        reserva_id: reservaId,
                        nuevo_cuidador: nuevoValor
                    },
                    success: function(response) {
                        if (response.success) {
                            $td.find('.cuidador-text').text(response.data.nuevo_nombre || 'N/A').show();
                        } else {
                            $td.find('.cuidador-text').show();
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        $td.find('.cuidador-text').show();
                        alert('Error de conexión al guardar.');
                    },
                    complete: function() {
                        $input.remove();
                        $td.find('.saving-spinner').remove();
                    }
                });
            }

            $input.on('keydown', function(e) {
                if (e.which === 13) { e.preventDefault(); guardarCambios(); } 
                else if (e.which === 27) { $input.remove(); $td.find('.cuidador-text').show(); }
            });

            $input.on('blur', function() {
                guardarCambios();
            });
        });
    });
    </script>
    <?php
}