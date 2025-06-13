<?php
/**
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

function pethome_reservas() {
    // Manejar eliminación de reserva
    if ( isset( $_GET['eliminar_reserva'] ) ) {
        $post_id = intval( $_GET['eliminar_reserva'] );
        if ( $post_id > 0 ) {
            // Se asume que este 'eliminar_reserva' es para el CPT 'reserva_guarda'
            // Ya que el CPT 'reserva_guarda' guarda el 'order_id' de WC
            $order_id = get_post_meta( $post_id, 'order_id', true ); // Meta key del order_id de WC
            if ( $order_id ) {
                $order = wc_get_order( $order_id );
                if ( $order ) {
                    $order->delete( true ); // true para eliminación permanente del pedido WC
                    error_log( "Pedido de WooCommerce #{$order_id} eliminado junto con la reserva CPT #{$post_id}." );
                } else {
                    error_log( "Advertencia: No se encontró el pedido de WooCommerce #{$order_id} para eliminar (Reserva CPT #{$post_id})." );
                }
            } else {
                error_log( "Advertencia: Reserva CPT #{$post_id} no tiene order_id asociado. Eliminando solo CPT." );
            }

            wp_delete_post( $post_id, true ); // true para eliminación permanente del CPT
            echo '<div class="notice notice-success is-dismissible"><p><strong><i class="fa-thin fa-check"></i> Reserva y su pedido asociado (si existía) eliminados correctamente.</strong></p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p><strong><i class="fa-thin fa-circle-xmark"></i> Error: ID de reserva no válido para eliminar.</strong></p></div>';
        }
    }

    // --- Parámetros de Paginación y Ordenamiento ---
    $paged = max( 1, intval( $_GET['paged'] ?? 1 ) );
    $posts_per_page = 25; // 25 IDs por página

    $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'date';
    $order   = isset( $_GET['order'] )   ? strtoupper( sanitize_text_field( $_GET['order'] ) ) : 'DESC';

    // --- Parámetros de Búsqueda ---
    $search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

    $args = array(
        'post_type'      => 'reserva_guarda', // Usamos tu CPT 'reserva_guarda'
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
        'post_status'    => 'publish',
        'orderby'        => $orderby,
        'order'          => $order,
        'meta_query'     => array( 'relation' => 'OR' ), // Para el buscador
    );

    // Configurar 'orderby' para meta_value si es necesario
    if ( $orderby === 'cliente_nombre' ) {
        $args['orderby'] = 'meta_value';
        $args['meta_key'] = 'pethome_cliente_nombre';
    } elseif ( $orderby === 'mascota_nombre' ) { // Nuevo orderby para mascota
        $args['orderby'] = 'meta_value';
        $args['meta_key'] = 'pethome_mascota_nombre';
    } elseif ( $orderby === 'fecha_inicio' ) {
        $args['orderby'] = 'meta_value';
        $args['meta_key'] = 'pethome_reserva_fecha_ingreso';
    } elseif ( $orderby === 'fecha_salida' ) {
        $args['orderby'] = 'meta_value';
        $args['meta_key'] = 'pethome_reserva_fecha_salida';
    } elseif ( $orderby === 'total_reserva' ) {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = 'pethome_reserva_precio_total';
    } elseif ( $orderby === 'entrega_reserva' ) {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = 'pethome_reserva_entrega';
    } elseif ( $orderby === 'saldo_reserva' ) {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = 'pethome_reserva_saldo_final';
    }

    // Añadir condiciones de búsqueda
    if ( ! empty( $search_query ) ) {
        // La búsqueda 's' por defecto busca en post_title y post_content.
        // Aquí agregamos búsqueda por meta_values relevantes.
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'     => 'pethome_cliente_nombre',
                'value'   => $search_query,
                'compare' => 'LIKE',
            ),
            array(
                'key'     => 'pethome_cliente_apellido',
                'value'   => $search_query,
                'compare' => 'LIKE',
            ),
            array(
                'key'     => 'pethome_cliente_email',
                'value'   => $search_query,
                'compare' => 'LIKE',
            ),
            array(
                'key'     => 'pethome_cliente_dni',
                'value'   => $search_query,
                'compare' => 'LIKE',
            ),
            array(
                'key'     => 'pethome_mascota_nombre',
                'value'   => $search_query,
                'compare' => 'LIKE',
            ),
            array(
                'key'     => 'order_id', // Para buscar por el ID de pedido de WooCommerce asociado
                'value'   => $search_query,
                'compare' => 'LIKE',
            ),
            // Asegurarse de que 's' también busque en post_title para el ID de reserva
            array(
                'key'     => 'post_title', // Esto es más para títulos que contengan el ID de reserva
                'value'   => $search_query,
                'compare' => 'LIKE',
                'type'    => 'CHAR', // Puede ser necesario para búsquedas de texto
            ),
        );
    }

    $reservas_query = new WP_Query( $args );

    ?>
    <div class="wrap" style="margin: 30px;">
        <h1 style="text-align:center; color:#5e4365; font-size:32px; margin-bottom:20px;"><i class="fa-thin fa-square-list" style="margin-right:10px;"></i>Listado de Guardas</h1>

        <div style="text-align:center; margin-bottom: 20px;">
            <a href="admin.php?page=pethome_guardas_agregar" class="page-title-action" style="font-size:16px; font-weight:bold; background-color:#5e4365; color:white; padding:8px 16px; border-radius:5px; text-decoration:none;"><i class="fa-thin fa-plus" style="margin-right:8px;"></i>Agregar Guarda</a>
        </div>

        <style>
            .acciones i {
                font-size: 1.3em;
                vertical-align: middle;
            }
            .fa-pen-field { color: #0073aa; }
            .fa-trash-can { color: #a00; }
            .fa-whatsapp { color: #25D366; }
            
            .wp-list-table th i {
                color: #5e4365;
            }
            .acciones a:hover .fa-pen-field { color: #0099cc; }
            .acciones a:hover .fa-trash-can { color: #d00; }
            a:hover .fa-whatsapp { color: #1DA851; }

            table.wp-list-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                margin-top: 20px;
                background-color: #fff;
                border: 1px solid #ccc;
                border-radius: 20px;
                overflow: hidden;
                font-family: Arial, sans-serif;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                table-layout: fixed; /* Importante para que los anchos funcionen */
            }
            table.wp-list-table thead th {
                background-color: #f0f0f1 !important;
                color: #5e4365;
                font-weight: bold;
                padding: 10px 10px;
                border-bottom: 2px solid #ddd;
                border-right: 1px solid #eee;
                white-space: nowrap;
                text-align: center;
                vertical-align: middle;
            }
            table.wp-list-table tbody td {
                padding: 10px 5px;
                color: #444;
                background-color: #fff !important;
                transition: background-color 0.2s, color 0.2s;
                border-bottom: 1px solid #eee;
                border-right: 1px solid #eee;
                white-space: nowrap;
                font-size: 14px;
                text-align: center;
                vertical-align: middle;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            table.wp-list-table tbody tr:hover td {
                background-color: #e9e9e9 !important;
                color: #333 !important;
            }
            table.wp-list-table td a {
                color: #5e4365;
                font-weight: bold;
                text-decoration: none;
            }
            table.wp-list-table tbody tr:hover td a {
                color: #5e4365 !important;
            }
            table.wp-list-table thead th:first-child { border-top-left-radius: 12px; }
            table.wp-list-table thead th:last-child { border-top-right-radius: 12px; }
            table.wp-list-table tbody tr:last-child td:first-child { border-bottom-left-radius: 12px; }
            table.wp-list-table tbody tr:last-child td:last-child  { border-bottom-right-radius: 12px; }

            /* --- INICIO: Anchos de columna ajustados --- */
            table.wp-list-table th:nth-child(1), table.wp-list-table td:nth-child(1) { width: 8%; }   /* ID */
            table.wp-list-table th:nth-child(2), table.wp-list-table td:nth-child(2) { width: 20%; }  /* Cliente */
            table.wp-list-table th:nth-child(3), table.wp-list-table td:nth-child(3) { width: 15%; }   /* Mascota */
            table.wp-list-table th:nth-child(4), table.wp-list-table td:nth-child(4) { width: 10%; }  /* Fecha Ingreso */
            table.wp-list-table th:nth-child(5), table.wp-list-table td:nth-child(5) { width: 10%; }  /* Fecha Salida */
            table.wp-list-table th:nth-child(6), table.wp-list-table td:nth-child(6) { width: 4%; }   /* WhatsApp */
            table.wp-list-table th:nth-child(7), table.wp-list-table td:nth-child(7) { width: 6%; }   /* Total */
            table.wp-list-table th:nth-child(8), table.wp-list-table td:nth-child(8) { width: 6%; }   /* Entrega */
            table.wp-list-table th:nth-child(9), table.wp-list-table td:nth-child(9) { width: 6%; }   /* Saldo */
            table.wp-list-table th:nth-child(10), table.wp-list-table td:nth-child(10) { width: 3%; }  /* Editar */
            table.wp-list-table th:nth-child(11), table.wp-list-table td:nth-child(11) { width: 3%; }  /* Eliminar */
            /* --- FIN: Anchos de columna --- */

            .pethome-search-form {
                background: #f0f0f1;
                padding: 15px 20px;
                border-radius: 8px;
                border: 1px solid #ddd;
                margin-bottom: 20px;
                display: flex;
                gap: 10px;
                align-items: center;
                justify-content: center;
            }
            .pethome-search-form input[type="search"] {
                flex-grow: 1;
                max-width: 400px;
                padding: 8px 12px;
                border: 1px solid #ccc;
                border-radius: 5px;
                font-size: 1em;
            }
            .pethome-search-form input[type="submit"] {
                background-color: #5e4365;
                color: white;
                padding: 8px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 1em;
                font-weight: bold;
            }
            .pethome-search-form input[type="submit"]:hover {
                background-color: #7a5d8d;
            }

            .tablenav .tablenav-pages {
                float: none;
                text-align: center;
                margin: 20px 0;
            }
            .tablenav .tablenav-pages a,
            .tablenav .tablenav-pages span.current {
                border: 1px solid #ccc;
                padding: 8px 12px;
                margin: 0 4px;
                background: #fff;
                color: #5e4365;
                text-decoration: none;
                border-radius: 4px;
                transition: background-color 0.2s, color 0.2s;
            }
            .tablenav .tablenav-pages a:hover {
                background-color: #5e4365;
                color: #fff;
            }
            .tablenav .tablenav-pages span.current {
                background-color: #5e4365;
                color: #fff;
                font-weight: bold;
            }
            .tablenav .tablenav-pages .pagination-links {
                display: inline-block;
            }
            table.wp-list-table td:nth-child(2) {
                text-align: left;
            }
            table.wp-list-table td:nth-child(3) {
                text-align: left;
            }
        </style>

        <form method="get" class="pethome-search-form">
            <input type="hidden" name="page" value="pethome_reservas">
            <input type="search" name="s" placeholder="Buscar reservas..." value="<?php echo esc_attr( $search_query ); ?>">
            <input type="submit" value="Buscar">
        </form>

        <table class="wp-list-table widefat fixed striped table-view-list posts">
            <thead>
                <tr>
                    <?php
                    function get_sort_link( $orderby_name, $column_label, $current_orderby, $current_order, $url_base ) {
                        $new_order  = ( $current_orderby === $orderby_name && $current_order === 'asc' ) ? 'desc' : 'asc';
                        $order_icon = ( $current_orderby === $orderby_name ) ? ( ( $current_order === 'asc' ) ? '↑' : '↓' ) : '';
                        $link_url   = add_query_arg( array( 'orderby' => $orderby_name, 'order' => $new_order ), $url_base );
                        if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
                            $link_url = add_query_arg( 's', sanitize_text_field( $_GET['s'] ), $link_url );
                        }
                        return '<th><a href="' . esc_url( $link_url ) . '" style="text-decoration:none; color:inherit;">' . $column_label . ' ' . $order_icon . '</a></th>';
                    }

                    $url_base_pagination = admin_url( 'admin.php?page=pethome_reservas' );
                    if ( ! empty( $search_query ) ) {
                        $url_base_pagination = add_query_arg( 's', $search_query, $url_base_pagination );
                    }

                    echo get_sort_link( 'ID', 'ID', $orderby, $order, $url_base_pagination );
                    echo get_sort_link( 'cliente_nombre', 'Cliente', $orderby, $order, $url_base_pagination );
                    echo get_sort_link( 'mascota_nombre', '<i class="fa-thin fa-dog-leashed"></i>', $orderby, $order, $url_base_pagination );
                    echo get_sort_link( 'fecha_inicio', 'Fecha Ingreso', $orderby, $order, $url_base_pagination );
                    echo get_sort_link( 'fecha_salida', 'Fecha Salida', $orderby, $order, $url_base_pagination );
                    ?>
                    <th><i class="fa-brands fa-whatsapp"></i></th>
                    <?php
                    echo get_sort_link( 'total_reserva', 'Total', $orderby, $order, $url_base_pagination );
                    echo get_sort_link( 'entrega_reserva', 'Entrega', $orderby, $order, $url_base_pagination );
                    echo get_sort_link( 'saldo_reserva', 'Saldo', $orderby, $order, $url_base_pagination );
                    ?>
                    <th class="acciones"><i class="fa-thin fa-pen-field"></i></th>
                    <th class="acciones"><i class="fa-thin fa-trash-list"></i></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( $reservas_query->have_posts() ) {
                    while ( $reservas_query->have_posts() ) {
                        $reservas_query->the_post();
                        $reserva_id = get_the_ID();

                        $cliente_nombre      = get_post_meta( $reserva_id, 'pethome_cliente_nombre', true );
                        $cliente_apellido    = get_post_meta( $reserva_id, 'pethome_cliente_apellido', true );
                        $cliente_telefono    = get_post_meta( $reserva_id, 'pethome_cliente_telefono', true );
                        $mascota_nombre      = get_post_meta( $reserva_id, 'pethome_mascota_nombre', true );
                        $fecha_ingreso       = get_post_meta( $reserva_id, 'pethome_reserva_fecha_ingreso', true );
                        $hora_ingreso        = get_post_meta( $reserva_id, 'pethome_reserva_hora_ingreso', true );
                        $fecha_salida        = get_post_meta( $reserva_id, 'pethome_reserva_fecha_salida', true );
                        $hora_egreso         = get_post_meta( $reserva_id, 'pethome_reserva_hora_egreso', true );
                        $precio_total        = get_post_meta( $reserva_id, 'pethome_reserva_precio_total', true );
                        $entrega             = get_post_meta( $reserva_id, 'pethome_reserva_entrega', true );
                        $saldo_final         = get_post_meta( $reserva_id, 'pethome_reserva_saldo_final', true );

                        $whatsapp_link = '';
                        if ( ! empty( $cliente_telefono ) ) {
                            $phone_number_clean = preg_replace( '/[^0-9]/', '', $cliente_telefono );
                            if ( substr( $phone_number_clean, 0, 1 ) === '0' ) {
                                $phone_number_clean = substr( $phone_number_clean, 1 );
                            }
                            if ( str_starts_with( $phone_number_clean, '54' ) && substr( $phone_number_clean, 2, 1 ) !== '9' ) {
                                $phone_number_clean = substr_replace( $phone_number_clean, '9', 2, 0 );
                            }
                            $whatsapp_link = 'https://wa.me/' . $phone_number_clean;
                        }

                        echo "<tr>";
                        echo "<td><a href='" . esc_url( admin_url( 'post.php?post=' . $reserva_id . '&action=edit' ) ) . "'>#" . esc_html( $reserva_id ) . "</a></td>";
                        echo "<td>" . esc_html( $cliente_nombre . ' ' . $cliente_apellido ) . "</td>";
                        echo "<td>" . esc_html( $mascota_nombre ) . "</td>";
                        echo "<td>" . esc_html( $fecha_ingreso . ' ' . $hora_ingreso ) . "</td>";
                        echo "<td>" . esc_html( $fecha_salida . ' ' . $hora_egreso ) . "</td>";
                        echo "<td>";
                        if ( ! empty( $whatsapp_link ) ) {
                            echo "<a href='" . esc_url( $whatsapp_link ) . "' target='_blank' title='Enviar WhatsApp a " . esc_attr( $cliente_nombre ) . "'><i class=\"fa-brands fa-whatsapp\"></i></a>";
                        } else {
                            echo "-";
                        }
                        echo "</td>";
                        echo "<td>" . wc_price( $precio_total ) . "</td>";
                        echo "<td>" . wc_price( $entrega ) . "</td>";
                        echo "<td>" . wc_price( $saldo_final ) . "</td>";
                        echo "<td class='acciones'><a href='" . esc_url( admin_url( 'post.php?post=' . $reserva_id . '&action=edit' ) ) . "' title='Editar Reserva'><i class=\"fa-thin fa-pen-field\"></i></a></td>";
                        echo "<td class='acciones'><a href='" . esc_url( admin_url( 'admin.php?page=pethome_reservas&eliminar_reserva=' . $reserva_id ) ) . "' onclick=\"return confirm('¿Estás seguro de eliminar esta reserva y su pedido asociado?')\" title='Eliminar Reserva'><i class=\"fa-thin fa-trash-can\"></i></a></td>";
                        echo "</tr>";
                    }
                    wp_reset_postdata();
                } else {
                    echo '<tr><td colspan="11">No hay reservas que coincidan con la búsqueda.</td></tr>';
                }
                ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links( array(
                    'base'      => add_query_arg( array( 'paged' => '%#%', 'orderby' => $orderby, 'order' => $order, 's' => $search_query ), $url_base_pagination ),
                    'format'    => '',
                    'total'     => $reservas_query->max_num_pages,
                    'current'   => $paged,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'type'      => 'plain',
                ) );
                ?>
            </div>
        </div>
    </div>
<?php } ?>