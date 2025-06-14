<?php
/**
 * pethome_clientes_fusionar.php - Herramienta para fusionar dos perfiles de clientes.
 * Version:     1.0 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrolla   www.streaminginternacional.com 
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Función para buscar usuarios para los selectores de la herramienta de fusión.
 */
function pethome_search_merge_users_callback() {
    check_ajax_referer('pethome_client_data_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permiso denegado.']);
    }

    $term = sanitize_text_field($_GET['term'] ?? '');
    $results = [];

    $user_query = new WP_User_Query([
        'search' => '*' . $term . '*',
        'search_columns' => ['ID', 'user_login', 'user_email', 'display_name'],
        'number' => 20,
        'meta_query' => [
             'relation' => 'OR',
             [
                'key' => '_pethome_is_cliente',
                'value' => '1',
                'compare' => '='
             ],
             [
                'key' => '_pethome_is_cuidador', // Asumiendo que también tenés cuidadores
                'value' => '1',
                'compare' => '='
             ]
        ]
    ]);

    $users = $user_query->get_results();

    if (!empty($users)) {
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->ID,
                'text' => esc_html($user->display_name . ' (' . $user->user_login . ' - ' . $user->user_email . ')')
            ];
        }
    }

    wp_send_json(['results' => $results]);
}

/**
 * Genera una previsualización de los datos que se van a fusionar.
 */
function pethome_preview_user_merge_callback() {
    check_ajax_referer('pethome_client_data_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Permiso denegado.']); }

    $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
    $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;

    if (!$source_id || !$target_id || $source_id === $target_id) {
        wp_send_json_error(['message' => 'Seleccioná dos usuarios diferentes.']);
    }
    
    // Contar pedidos de WooCommerce
    $orders = wc_get_orders(['customer' => $source_id, 'limit' => -1, 'return' => 'ids']);

    // Contar Reservas de Guarda (CPT)
    $reservas_guarda = new WP_Query([
        'post_type' => 'reserva_guarda',
        'author' => $source_id,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);

    $preview_data = [
        'orders_count' => count($orders),
        'reservas_count' => $reservas_guarda->post_count
    ];

    wp_send_json_success($preview_data);
}


/**
 * Ejecuta la fusión de los dos usuarios. ACCIÓN DESTRUCTIVA.
 */
function pethome_execute_user_merge_callback() {
    check_ajax_referer('pethome_client_data_nonce', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Permiso denegado.']); }

    $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
    $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;

    if (!$source_id || !$target_id || $source_id === $target_id) {
        wp_send_json_error(['message' => 'La fusión fue cancelada por seguridad. IDs inválidos.']);
    }

    global $wpdb;

    // 1. Reasignar Pedidos de WooCommerce
    $order_ids = wc_get_orders(['customer' => $source_id, 'limit' => -1, 'return' => 'ids']);
    if (!empty($order_ids)) {
        foreach ($order_ids as $order_id) {
            update_post_meta($order_id, '_customer_user', $target_id);
        }
    }
    
    // 2. Reasignar Reservas de Guarda (CPT) - Asumiendo que el autor es el cliente
    $wpdb->update(
        $wpdb->posts,
        ['post_author' => $target_id],
        ['post_author' => $source_id, 'post_type' => 'reserva_guarda']
    );

    // 3. Copiar metadatos del perfil (solo si no existen en el destino)
    $source_meta = get_user_meta($source_id);
    foreach ($source_meta as $key => $values) {
        // Ignorar claves internas de WP
        if (in_array($key, ['wp_capabilities', 'wp_user_level', 'show_admin_bar_front', 'session_tokens'])) continue;
        
        if (!metadata_exists('user', $target_id, $key)) {
            update_user_meta($target_id, $key, $values[0]);
        }
    }
    
    // 4. Eliminar el usuario de origen y reasignar todo su contenido restante
    if ( ! function_exists( 'wp_delete_user' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/user.php' );
    }
    wp_delete_user($source_id, $target_id);

    wp_send_json_success(['message' => '¡Fusión completada! El usuario de origen ha sido eliminado y todos sus datos han sido transferidos.']);
}


/**
 * Muestra el panel de la herramienta de Fusión de Clientes.
 */
function pethome_clientes_fusionar_panel() {
    ?>
    <style>
        .pethome-admin-wrap { margin: 20px 25px; }
        .pethome-admin-wrap h1, .pethome-admin-wrap h2 { color: #5e4365; }
        .form-container { background-color: #fff; padding: 25px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .merge-users-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start; }
        .user-column { padding: 20px; border: 1px solid #e0e0e0; border-radius: 6px; }
        .user-column h3 { margin-top: 0; }
        .user-column h3 i { margin-right: 8px; }
        .select2-container { width: 100% !important; }
        .merge-arrow { text-align: center; font-size: 40px; color: #5e4365; align-self: center;}
        #merge-preview-results { margin-top: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; display: none; }
        #merge-preview-results h3 { margin-top: 0; }
        #merge-preview-results ul { list-style: inside; margin: 0; padding: 0;}
        #merge-preview-results li { margin-bottom: 8px; }
        .notice-danger { border-left-color: #dc3545 !important; }
        .notice-danger h2 { color: #dc3545 !important; }
    </style>
    <div class="wrap pethome-admin-wrap">
        <h1><i class="fa-thin fa-people-arrows"></i> Herramienta de Fusión de Clientes</h1>

        <div class="notice notice-danger">
            <h2><i class="fa-thin fa-triangle-exclamation"></i> ATENCIÓN: HERRAMIENTA DESTRUCTIVA</h2>
            <p>Esta herramienta fusiona dos perfiles de usuario en uno solo. La acción es **PERMANENTE e IRREVERSIBLE**. El usuario de "Origen" será eliminado por completo.</p>
            <p><strong>Es indispensable que realices una copia de seguridad completa de la base de datos antes de proceder.</strong></p>
        </div>

        <div class="form-container">
            <div class="merge-users-grid">
                <div class="user-column">
                    <h3><i class="fa-thin fa-user-minus"></i> 1. Seleccionar Origen</h3>
                    <p>Este es el usuario duplicado que será **eliminado**.</p>
                    <select id="user-source" name="user_source" class="pethome-user-search"></select>
                </div>
                <div class="user-column">
                    <h3><i class="fa-thin fa-user-check"></i> 2. Seleccionar Destino</h3>
                    <p>Este es el usuario correcto que será **conservado**.</p>
                    <select id="user-target" name="user_target" class="pethome-user-search"></select>
                </div>
            </div>
            <hr>
            <button id="btn-preview-merge" class="button button-primary" disabled><i class="fa-thin fa-eye"></i> Previsualizar Fusión</button>
            <span class="spinner" style="float: none;"></span>

            <div id="merge-preview-results"></div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        // Inicializar Select2 para búsqueda de usuarios
        $('.pethome-user-search').select2({
            ajax: {
                url: ajax_object.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'pethome_search_merge_users',
                        term: params.term,
                        nonce: ajax_object.nonce
                    };
                },
                processResults: function(data) {
                    return { results: data.results };
                }
            },
            placeholder: 'Buscá por nombre, usuario o email',
            minimumInputLength: 2,
        });

        function checkSelections() {
            var sourceId = $('#user-source').val();
            var targetId = $('#user-target').val();
            if (sourceId && targetId && sourceId !== targetId) {
                $('#btn-preview-merge').prop('disabled', false);
            } else {
                $('#btn-preview-merge').prop('disabled', true);
            }
        }

        $('#user-source, #user-target').on('change', checkSelections);

        $('#btn-preview-merge').on('click', function() {
            var btn = $(this);
            var spinner = btn.next('.spinner');
            var resultsDiv = $('#merge-preview-results');
            
            btn.prop('disabled', true);
            spinner.addClass('is-active');
            resultsDiv.hide().html('');

            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'pethome_preview_user_merge',
                    nonce: ajax_object.nonce,
                    source_id: $('#user-source').val(),
                    target_id: $('#user-target').val()
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var html = '<h3><i class="fa-thin fa-circle-info"></i> Resumen de la Fusión</h3>';
                        html += '<p>Se transferirán los siguientes datos desde <strong>' + $('#user-source option:selected').text() + '</strong> hacia <strong>' + $('#user-target option:selected').text() + '</strong>:</p>';
                        html += '<ul>';
                        html += '<li><i class="fa-thin fa-receipt"></i> <strong>' + data.orders_count + '</strong> Pedidos de WooCommerce.</li>';
                        html += '<li><i class="fa-thin fa-calendar-check"></i> <strong>' + data.reservas_count + '</strong> Reservas de Guarda.</li>';
                        html += '<li><i class="fa-thin fa-address-card"></i> Todos los datos de perfil (teléfono, dirección, etc.) que no existan en el perfil de destino.</li>';
                        html += '<li><i class="fa-thin fa-comments"></i> Todos los comentarios y otro contenido de WordPress.</li>';
                        html += '</ul><hr>';
                        html += '<p>El usuario de origen será <strong>eliminado permanentemente</strong>.</p>';
                        html += '<button id="btn-execute-merge" class="button is-destructive"><i class="fa-thin fa-bolt"></i> Confirmar y Fusionar Usuarios</button>';
                        
                        resultsDiv.html(html).slideDown();
                    } else {
                        resultsDiv.html('<p style="color:red;"><strong>Error:</strong> ' + response.data.message + '</p>').slideDown();
                    }
                },
                error: function() {
                    resultsDiv.html('<p style="color:red;"><strong>Error:</strong> No se pudo conectar con el servidor.</p>').slideDown();
                },
                complete: function() {
                    spinner.removeClass('is-active');
                    btn.prop('disabled', false);
                }
            });
        });

        // Handler para el botón de ejecución final
        $(document).on('click', '#btn-execute-merge', function() {
             if (!confirm("ADVERTENCIA FINAL:\n\nEsta acción es PERMANENTE e IRREVERSIBLE.\n\n¿Estás absolutamente seguro de que querés fusionar estos dos usuarios?")) {
                return;
            }

            var confirmText = prompt("Para confirmar esta acción tan delicada, por favor escribí 'FUSIONAR' en mayúsculas:");
            if (confirmText !== 'FUSIONAR') {
                alert("La palabra no coincide. La fusión ha sido cancelada.");
                return;
            }

            var btn = $(this);
            var spinner = $('.spinner');

            btn.prop('disabled', true);
            spinner.addClass('is-active');

            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'pethome_execute_user_merge',
                    nonce: ajax_object.nonce,
                    source_id: $('#user-source').val(),
                    target_id: $('#user-target').val()
                },
                success: function(response) {
                    var message = '';
                    if (response.success) {
                        message = '<div class="notice notice-success"><p><strong>' + response.data.message + '</strong></p></div>';
                        $('#user-source, #user-target').val(null).trigger('change'); // Limpiar selectores
                    } else {
                        message = '<div class="notice notice-error"><p><strong>ERROR:</strong> ' + response.data.message + '</p></div>';
                    }
                    $('#merge-preview-results').html(message);
                },
                error: function() {
                    $('#merge-preview-results').html('<div class="notice notice-error"><p><strong>ERROR:</strong> No se pudo conectar con el servidor.</p></div>');
                },
                complete: function() {
                    spinner.removeClass('is-active');
                }
            });
        });
    });
    </script>
    <?php
}