<?php
/**
 * includes/pethome_guardas_save-handler.php
 * Procesa el POST del formulario “Agregar Guarda”,
 * crea el pedido en WooCommerce y luego el CPT reserva_guarda
 * Plugin Name: PetHomeHoney Plugin
 * Plugin URI:  https://pethomehoney.com.ar
 * Description: Plugin para gestionar reservas de guarda con WooCommerce y CPT.
 * Version:     1.0 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrolla   www.streaminginternacional.com 
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1) Hooks para manejar el POST
add_action( 'admin_post_pethome_guardas_save',        'pethome_guardas_save_handler' );
add_action( 'admin_post_nopriv_pethome_guardas_save', 'pethome_guardas_save_handler' ); // Considera si los no-privilegiados deben poder acceder a esto.

function pethome_guardas_save_handler() {
    error_log('Pethome Save Handler: Inicio del procesamiento.');

    // 2) Seguridad
    if ( ! isset( $_POST['pethome_guarda_nonce'] ) || ! wp_verify_nonce( $_POST['pethome_guarda_nonce'], 'pethome_guarda_save_details' ) ) {
        error_log('Pethome Save Handler: Fallo de seguridad (Nonce inválido o faltante).');
        wp_die( 'Permisos insuficientes o formulario expirado.' );
    }
    if ( ! current_user_can( 'manage_options' ) ) { // Asegúrate de que este es el capability correcto.
        error_log('Pethome Save Handler: Permisos de usuario insuficientes.');
        wp_die( 'Permisos insuficientes.' );
    }
    error_log('Pethome Save Handler: Verificación de seguridad exitosa.');

    // 3) Leer y validar fechas/horas/servicio
    $fechas_raw   = sanitize_text_field( $_POST['pethome_reserva_fechas']         ?? '' );
    $hora_ingreso = sanitize_text_field( $_POST['pethome_reserva_hora_ingreso'] ?? '10:00' );
    $hora_egreso  = sanitize_text_field( $_POST['pethome_reserva_hora_egreso']  ?? '18:00' );
    $servicio_sel = sanitize_text_field( $_POST['pethome_reserva_servicio']       ?? '' );

    error_log("Pethome Save Handler: Datos recibidos - Fechas: {$fechas_raw}, Ingreso: {$hora_ingreso}, Egreso: {$hora_egreso}, Servicio: {$servicio_sel}");

    $fechas_arr = array_filter( array_map( 'trim', explode( ',', $fechas_raw ) ) );
    if ( empty( $fechas_arr ) || empty($hora_ingreso) || empty($hora_egreso) || empty($servicio_sel) ) {
        error_log('Pethome Save Handler: Datos de fechas, horas o servicio incompletos.');
        wp_die( 'Selecciona fechas, horas y servicio.' );
    }
    $dias = count( $fechas_arr );
    error_log("Pethome Save Handler: Validación de datos básicos exitosa. Días calculados: {$dias}");

    // 4) Precio diario y nombre de línea
    $precio_dia = 0;
    $line_name = __('Servicio Desconocido', 'pethomehoney-plugin');
    $product_obj = null;

    if ( str_starts_with( $servicio_sel, 'booking_product:' ) ) {
        $prod_id     = intval( substr( $servicio_sel, strlen('booking_product:') ) );
        $product_obj = wc_get_product( $prod_id );
        if ($product_obj) {
            $block_cost  = (float) $product_obj->get_meta( '_wc_booking_block_cost', true );
            $base_cost   = (float) $product_obj->get_meta( '_wc_booking_cost', true );
            $precio_dia  = $block_cost > 0 ? $block_cost : ( $base_cost > 0 ? $base_cost : 0 );
            $line_name   = $product_obj->get_name();
        } else {
             $line_name = __('Booking ID Inválido #', 'pethomehoney-plugin') . $prod_id;
        }
        error_log("Pethome Save Handler: Servicio Booking seleccionado. ID: {$prod_id}, Precio Día: {$precio_dia}");
    } elseif ( str_starts_with( $servicio_sel, 'custom_service:' ) ) {
        $idx         = intval( substr( $servicio_sel, strlen('custom_service:') ) );
        $rows        = get_option( 'pethome_precios_base', [] );
        $row         = $rows[ $idx ] ?? [];
        $precio_dia  = floatval( $row['precio'] ?? 0 );
        $line_name   = $row['servicio'] ?? __('Servicio Personalizado', 'pethomehoney-plugin');
        error_log("Pethome Save Handler: Servicio Custom seleccionado. Index: {$idx}, Precio Día: {$precio_dia}");
    } else {
        error_log("Pethome Save Handler: Servicio seleccionado no reconocido: {$servicio_sel}");
    }

    $subtotal = round( $precio_dia * $dias, 2 );
    error_log("Pethome Save Handler: Subtotal calculado: {$subtotal}");

    // 5) Recargos / descuentos
    $monto_total_cargos_y_recargos = round( floatval( $_POST['pethome_reserva_cargos'] ?? 0 ), 2 );
    $total_pedido = round( $subtotal + $monto_total_cargos_y_recargos, 2 );
    error_log("Pethome Save Handler: Monto Total de Recargos/Ajustes desde frontend: {$monto_total_cargos_y_recargos}. Total del pedido plugin: {$total_pedido}");

    // 6) Crear pedido en WooCommerce
    $order = wc_create_order();
    if ( is_wp_error( $order ) ) {
        error_log('Pethome Save Handler: Error al crear pedido de WooCommerce: ' . $order->get_error_message());
        wp_die( __('Error al crear el pedido en WooCommerce.', 'pethomehoney-plugin') );
    }
    $order_id = $order->get_id();
    error_log("Pethome Save Handler: Pedido WooCommerce creado con ID: {$order_id}");

    // Añadir producto o tarifa base
    if ( $product_obj instanceof WC_Product ) {
        $order->add_product( $product_obj, $dias, ['subtotal' => $subtotal, 'total' => $subtotal] );
        error_log("Pethome Save Handler: Producto de Booking añadido. Nombre: {$line_name}, Cantidad: {$dias}, Subtotal item: {$subtotal}");
    } else {
        $order->add_fee( (object) [
            'name'    => $line_name,
            'amount'  => $subtotal,
            'total'   => $subtotal,
            'taxable' => false,
        ]);
        error_log("Pethome Save Handler: Fee de servicio ({$line_name}) añadida: {$subtotal}");
    }

    // Añadir fee de recargos/ajustes
    if ( $monto_total_cargos_y_recargos != 0 ) {
        $order->add_fee( (object) [
            'name'    => __('Recargos y Ajustes de Reserva', 'pethomehoney-plugin'),
            'amount'  => $monto_total_cargos_y_recargos,
            'total'   => $monto_total_cargos_y_recargos,
            'taxable' => false,
        ]);
        error_log("Pethome Save Handler: Fee de recargos/ajustes añadida: {$monto_total_cargos_y_recargos}");
    }
    
    // Información del cliente para el pedido
    $cliente_email = sanitize_email( $_POST['pethome_cliente_email'] ?? '' );
    if ( $cliente_email ) {
        $user = get_user_by( 'email', $cliente_email );
        if ( $user ) {
            $order->set_customer_id( $user->ID );
        }
        $order->set_billing_email( $cliente_email );
        $order->set_billing_first_name( sanitize_text_field( $_POST['pethome_cliente_nombre'] ?? '' ) );
        $order->set_billing_last_name( sanitize_text_field( $_POST['pethome_cliente_apellido'] ?? '' ) );
        $order->set_billing_phone( sanitize_text_field( $_POST['pethome_cliente_telefono'] ?? '' ) );
        // Considera añadir dirección de facturación si es relevante para el pedido
    }

    $order->calculate_totals(true); // Permitir que WC calcule primero
    $wc_calculated_total = $order->get_total();
    error_log("Pethome Save Handler: Total WC post-calculate_totals(): {$wc_calculated_total}");

    // Si el total de WC difiere del calculado por el plugin, forzar el total del plugin
    if ( round(floatval($wc_calculated_total), 2) !== round(floatval($total_pedido), 2) ) {
        error_log("Pethome Save Handler: Total WC ({$wc_calculated_total}) difiere de esperado ({$total_pedido}). Forzando total a {$total_pedido}.");
        $order->set_total( $total_pedido );
    }

    $order->update_status( 'pending', __('Reserva generada desde formulario de admin.', 'pethomehoney-plugin') );
    $order->save();
    error_log("Pethome Save Handler: Pedido WooCommerce finalizado. ID: {$order_id}, Estado: {$order->get_status()}, Total Final: {$order->get_total()}");

    // 7) Crear el CPT reserva_guarda
    $post_title = __('Reserva para Pedido #', 'pethomehoney-plugin') . $order_id;
    $res_id = wp_insert_post([
        'post_type'   => 'reserva_guarda',
        'post_title'  => $post_title,
        'post_status' => 'publish',
    ]);

    if ( is_wp_error( $res_id ) || $res_id === 0 ) {
        $error_message = is_wp_error( $res_id ) ? $res_id->get_error_message() : 'wp_insert_post devolvió 0';
        error_log('Pethome Save Handler: Error al crear CPT reserva_guarda: ' . $error_message);
        wp_die( __('Error al crear la reserva en WordPress.', 'pethomehoney-plugin') );
    } else {
        error_log("Pethome Save Handler: CPT reserva_guarda creado con ID: {$res_id}");

        update_post_meta( $res_id, 'order_id', $order_id );
        update_post_meta( $res_id, 'producto_reserva_name', $line_name );
        update_post_meta( $res_id, 'pethome_reserva_fechas',       $fechas_raw );
        update_post_meta( $res_id, 'pethome_reserva_fecha_ingreso',  reset( $fechas_arr ) );
        update_post_meta( $res_id, 'pethome_reserva_fecha_salida',   end( $fechas_arr ) );
        update_post_meta( $res_id, 'pethome_reserva_hora_ingreso',   $hora_ingreso );
        update_post_meta( $res_id, 'pethome_reserva_hora_egreso',    $hora_egreso );
        // Guardar cantidad de días con la meta_key correcta
        update_post_meta( $res_id, 'pethome_reserva_cantidad_dias',  $dias ); // <--- CLAVE CORRECTA Y ÚNICA PARA DÍAS
        update_post_meta( $res_id, 'pethome_reserva_precio_diario',  $precio_dia );
        update_post_meta( $res_id, 'pethome_reserva_subtotal',       $subtotal );
        // Guardar el servicio seleccionado (puede ser 'booking_product:ID' o 'custom_service:idx')
        update_post_meta( $res_id, 'pethome_reserva_servicio', $servicio_sel );

        error_log("Pethome Save Handler: Metadatos de fechas/horas/precios guardados. Cantidad de Dias: {$dias}");

        // Cliente
        $client_fields_map = [
            'pethome_cliente_nombre' => 'pethome_cliente_nombre', 'pethome_cliente_apellido' => 'pethome_cliente_apellido',
            'pethome_cliente_dni' => 'pethome_cliente_dni', 'pethome_cliente_telefono' => 'pethome_cliente_telefono',
            'pethome_cliente_email' => 'pethome_cliente_email', 'pethome_cliente_barrio' => 'pethome_cliente_barrio',
            'pethome_cliente_alias_bancario' => 'pethome_cliente_alias_bancario',
            'pethome_cliente_calle' => 'pethome_cliente_calle', 'pethome_cliente_numero' => 'pethome_cliente_numero'
        ];
        foreach ($client_fields_map as $meta_key => $post_key) {
            $value = isset($_POST[$post_key]) ? sanitize_text_field($_POST[$post_key]) : '';
            if ($meta_key === 'pethome_cliente_email') {
                $value = sanitize_email($value);
            }
            update_post_meta($res_id, $meta_key, $value);
        }
        $domicilio = trim( sanitize_text_field( $_POST['pethome_cliente_calle'] ?? '' ) . ' ' . sanitize_text_field( $_POST['pethome_cliente_numero'] ?? '' ) );
        update_post_meta( $res_id, 'pethome_cliente_domicilio', $domicilio );
        error_log("Pethome Save Handler: Metadatos de cliente guardados.");

        // Mascota y otros
        $fields_to_save_text = [
            'pethome_mascota_nombre', 'pethome_mascota_tipo', 'pethome_mascota_raza',
            'pethome_mascota_peso', 'pethome_mascota_tamano', 'pethome_mascota_sexo',
            'pethome_mascota_castrada', 'pethome_mascota_enfermedades', 'pethome_mascota_medicamentos',
            'pethome_mascota_alergias', 'pethome_mascota_sociable_perros', 
            'pethome_mascota_sociable_ninios', 'pethome_mascota_agresivo_otros_animales',
            'pethome_mascota_vacunas_completas', 'pethome_mascota_desparasitado', 'pethome_mascota_antipulgas',
            'pethome_mascota_veterinario_nombre', 'pethome_mascota_veterinario_telefono',
            'pethome_mascota_chip', 'pethome_mascota_collar_identificacion', 'pethome_mascota_con_correa',
            'pethome_mascota_con_pechera', 'pethome_mascota_cobertura_salud',
            'pethome_reserva_cuidador_asignado'
        ];
        foreach ($fields_to_save_text as $key) {
            update_post_meta($res_id, $key, sanitize_text_field($_POST[$key] ?? ''));
        }
        $fields_to_save_int = ['pethome_mascota_edad' => 0, 'pethome_mascota_edad_meses' => 0, 'pethome_mascota_imagen_id' => 0];
        foreach ($fields_to_save_int as $key => $default) {
            update_post_meta($res_id, $key, intval($_POST[$key] ?? $default));
        }
        $fields_to_save_textarea = [
            'pethome_mascota_observaciones_sociabilidad', 'pethome_mascota_observaciones_sanidad',
            'pethome_mascota_observaciones_seguridad', 'pethome_reserva_observaciones'
        ];
        foreach ($fields_to_save_textarea as $key) {
            update_post_meta($res_id, $key, sanitize_textarea_field($_POST[$key] ?? ''));
        }
        error_log("Pethome Save Handler: Metadatos de mascota, sociabilidad, sanidad, seguridad y observaciones de reserva guardados.");

        // Campos financieros calculados para el CPT
        update_post_meta( $res_id, 'pethome_reserva_cargos', $monto_total_cargos_y_recargos );
        // Los campos entrega y saldo_final se envían desde el JS a través de campos hidden.
        // Asegúrate que los names de esos campos hidden sean 'pethome_reserva_entrega' y 'pethome_reserva_saldo_final'.
        $entrega_from_form = round(floatval($_POST['pethome_reserva_entrega'] ?? ($total_pedido * 0.10)), 2);
        $saldo_from_form = round(floatval($_POST['pethome_reserva_saldo_final'] ?? ($total_pedido * 0.90)), 2);

        update_post_meta( $res_id, 'pethome_reserva_entrega', $entrega_from_form );
        update_post_meta( $res_id, 'pethome_reserva_saldo_final', $saldo_from_form );
        update_post_meta( $res_id, 'pethome_reserva_precio_total', $total_pedido );
        error_log("Pethome Save Handler: Metadatos financieros (cargos, entrega, saldo, total) del CPT guardados.");
    }

    // 8) Redirigir a la página de edición de la reserva recién creada
    wp_safe_redirect( get_edit_post_link( $res_id, 'raw' ) );
    exit;
}

// La función pethome_guardas_save_details no se usa si el hook principal es pethome_guardas_save_handler.
// Si la necesitas, asegúrate de que tenga un propósito distinto y esté correctamente enganchada.
if ( ! function_exists( 'pethome_guardas_save_details' ) ) {
    function pethome_guardas_save_details() {
        // Contenido de esta función si es necesaria para otra acción.
        // Actualmente, pethome_guardas_save_handler maneja el guardado principal.
    }
}
?>