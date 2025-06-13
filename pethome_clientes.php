<?php
/**
 * Panel de gestión de Clientes con buscador y paginación.
 * Plugin Name: PetHomeHoney Plugin
 * Plugin URI:  https://pethomehoney.com.ar
 * Description: Plugin para gestionar reservas de guarda con WooCommerce y CPT.
 * Version:     1.0 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrolla   www.streaminginternacional.com 
 */

function pethome_clientes_panel() {
    // Título principal
    echo '<h1 style="color:#5e4365; text-align:center;"><i class="fa-thin fa-users"></i> Gestión de Clientes</h1>';

    // Procesar la eliminación de la marca de cliente
    if (isset($_GET['action']) && $_GET['action'] === 'remove_client' && isset($_GET['user_id'])) {
        if (isset($_REQUEST['pethome_remove_client_nonce']) && wp_verify_nonce($_REQUEST['pethome_remove_client_nonce'], 'pethome_remove_client_nonce_action')) {
            $user_id = intval($_GET['user_id']);
            if (current_user_can('edit_user', $user_id)) {
                if (delete_user_meta($user_id, '_pethome_is_cliente')) {
                    echo '<div class="notice notice-success is-dismissible"><p><i class="fa-thin fa-check"></i> Se quitó la marca de cliente al usuario.</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Error: No se pudo quitar la marca de cliente.</p></div>';
                }
            }
        } else {
             echo '<div class="notice notice-error is-dismissible"><p>Error de seguridad.</p></div>';
        }
    }

    // Estilos del panel
    echo '<style>
    .section-block {
        background: #f9f9f9;
        border: 2px solid #ccc;
        border-radius: 16px;
        padding: 20px;
        margin-top: 30px;
    }
    .section-block h2 {
        background: #5e4365;
        color: #ffffff;
        text-align: center;
        padding: 15px;
        margin: -20px -20px 20px -20px;
        border-radius: 14px 14px 0 0;
    }
    .section-block h2 i { margin-right: 10px; }
    .pethome-search-form {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    .pethome-search-form input[type="search"] {
        flex-grow: 1;
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
    }
    table.clientes-listado {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 20px;
        border: 1px solid #ccc;
        border-radius: 20px;
        overflow: hidden;
    }
    table.clientes-listado th, table.clientes-listado td {
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
        text-align: left;
        vertical-align: middle;
    }
    table.clientes-listado th {
        background-color: #5e4365;
        color: #ffffff;
        text-align: center;
        font-weight: bold;
    }
    table.clientes-listado tr:hover { background-color: #f3eef4; }
    table.clientes-listado th:first-child { border-top-left-radius: 12px; }
    table.clientes-listado th:last-child  { border-top-right-radius: 12px; }
    table.clientes-listado tr:last-child td:first-child { border-bottom-left-radius: 12px; }
    table.clientes-listado tr:last-child td:last-child  { border-bottom-right-radius: 12px; }
    .whatsapp-link { margin-left: 8px; vertical-align: middle; }
    .whatsapp-link .fa-whatsapp { font-size: 24px; color: #25D366; line-height: 1; }
    
    .action-icon {
        text-decoration: none;
        display: inline-block;
        margin: 0 8px;
        font-size: 18px;
        transition: opacity 0.2s ease-in-out;
    }
    .action-icon:hover {
        opacity: 0.7;
    }
    .edit-icon i {
        color: #0073aa;
    }
    .remove-icon i {
        color: #d63638;
    }

    .tablenav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding: 0 5px;
    }
    .tablenav .displaying-num { font-style: italic; color: #666; }
    .pagination-links .page-numbers {
        text-decoration: none; padding: 6px 12px; border: 1px solid #ccc; margin: 0 2px;
        border-radius: 4px; background: #fff; color: #5e4365;
    }
    .pagination-links .page-numbers.current,
    .pagination-links .page-numbers:hover {
        background: #5e4365; color: #fff; border-color: #5e4365;
    }
    </style>';

    // Variables de búsqueda y paginación
    $search_term = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $number_per_page = 20;
    $offset = ($paged - 1) * $number_per_page;

    // Argumentos base de la consulta
    $args = [
        'meta_key'   => '_pethome_is_cliente',
        'meta_value' => '1',
        'orderby'    => 'display_name',
        'order'      => 'ASC'
    ];

    // Modificar argumentos si hay un término de búsqueda
    if (!empty($search_term)) {
        $args['search'] = '*' . esc_attr($search_term) . '*';
        $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        $args['meta_query'] = [
            'relation' => 'OR',
            ['key' => 'first_name', 'value' => $search_term, 'compare' => 'LIKE'],
            ['key' => 'last_name', 'value' => $search_term, 'compare' => 'LIKE'],
            ['key' => 'pethome_cliente_dni', 'value' => $search_term, 'compare' => 'LIKE'],
            ['key' => 'pethome_cliente_telefono', 'value' => $search_term, 'compare' => 'LIKE']
        ];
    }
    
    // Consulta para obtener el total de clientes (para la paginación)
    $total_query = new WP_User_Query($args);
    $total_clientes = $total_query->get_total();

    // Consulta para obtener los clientes de la página actual
    $args['number'] = $number_per_page;
    $args['offset'] = $offset;
    $clientes = get_users($args);
    
    // Bloque principal
    echo '<div class="section-block">';
    echo '<h2><i class="fa-thin fa-list-check"></i> Listado de Clientes</h2>';
    
    // Formulario de búsqueda
    echo '<form method="get" class="pethome-search-form">';
    echo '<input type="hidden" name="page" value="pethome_clientes">';
    echo '<input type="search" name="s" value="' . esc_attr($search_term) . '" placeholder="Buscar por nombre, DNI, email, teléfono...">';
    echo '<input type="submit" class="button button-primary" value="Buscar Cliente">';
    echo '</form>';
    
    if (!empty($clientes)) {
        echo '<table class="clientes-listado">
                <thead>
                    <tr>
                        <th>Nombre y Apellido</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Domicilio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($clientes as $cliente) {
            $user_id = $cliente->ID;
            $edit_link = admin_url('admin.php?page=pethome_cliente_editar&user_id=' . $user_id);
            $remove_nonce = wp_create_nonce('pethome_remove_client_nonce_action');
            $remove_link = admin_url('admin.php?page=pethome_clientes&action=remove_client&user_id=' . $user_id . '&pethome_remove_client_nonce=' . $remove_nonce);

            $nombre_completo = trim($cliente->first_name . ' ' . $cliente->last_name);
            if (empty($nombre_completo)) {
                $nombre_completo = $cliente->display_name;
            }

            $telefono = get_user_meta($user_id, 'pethome_cliente_telefono', true);
            $calle = get_user_meta($user_id, 'pethome_cliente_calle', true);
            $numero = get_user_meta($user_id, 'pethome_cliente_numero', true);
            $domicilio = trim($calle . ' ' . $numero);

            echo '<tr>';
            echo '<td><strong>' . esc_html($nombre_completo) . '</strong></td>';
            echo '<td>' . esc_html($cliente->user_email) . '</td>';
            echo '<td>';
            echo esc_html($telefono);
            if (!empty($telefono)) {
                $telefono_wa = preg_replace('/\D/', '', $telefono);
                if (strlen($telefono_wa) > 8) {
                    echo "<a href='https://wa.me/549{$telefono_wa}' target='_blank' class='whatsapp-link'><i class=\"fa-brands fa-whatsapp\"></i></a>";
                }
            }
            echo '</td>';
            echo '<td>' . esc_html($domicilio) . '</td>';
            echo '<td style="text-align: center;">
                    <a href="' . esc_url($edit_link) . '" class="action-icon edit-icon" title="Editar Cliente"><i class="fa-thin fa-pencil"></i></a>
                    <a href="' . esc_url($remove_link) . '" class="action-icon remove-icon" title="Quitar Marca de Cliente" onclick="return confirm(\'¿Estás seguro de que querés quitarle la marca de cliente a este usuario?\');"><i class="fa-thin fa-user-minus"></i></a>
                  </td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

    } else {
        if (!empty($search_term)) {
            echo '<div class="notice notice-warning is-dismissible"><p><i class="fa-thin fa-circle-info"></i> No se encontraron clientes que coincidan con tu búsqueda.</p></div>';
        } else {
            echo '<div class="notice notice-info is-dismissible"><p><i class="fa-thin fa-circle-info"></i> No hay clientes registrados aún. Podés importarlos desde el menú "Importar".</p></div>';
        }
    }

    // Navegación de paginación
    echo '<div class="tablenav bottom">';
    echo '<div class="displaying-num">' . $total_clientes . ' cliente(s)</div>';
    $total_pages = ceil($total_clientes / $number_per_page);
    if ($total_pages > 1) {
        $pagination_args = [
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'total' => $total_pages,
            'current' => $paged,
            'show_all' => false,
            'end_size' => 1,
            'mid_size' => 2,
            'prev_next' => true,
            'prev_text' => __('&laquo; Anterior'),
            'next_text' => __('Siguiente &raquo;'),
        ];
        echo '<div class="pagination-links">' . paginate_links($pagination_args) . '</div>';
    }
    echo '</div>'; // fin .tablenav

    echo '</div>'; // fin .section-block
}
?>