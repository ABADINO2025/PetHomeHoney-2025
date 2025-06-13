<?php
/**
 * includes/pethome_guardas_save-handler.php
 * Procesa el POST del formulario “Agregar Guarda”,
 * crea el pedido en WC y un CPT reserva_guarda con todos los meta.
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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1) Hooks para manejar el POST
add_action( 'admin_post_pethome_guardas_save',        'pethome_guardas_save_handler' );
add_action( 'admin_post_nopriv_pethome_guardas_save', 'pethome_guardas_save_handler' );

function pethome_guardas_save_handler() {
    // 2) Seguridad
    check_admin_referer( 'pethome_guardas_form', 'pethome_guardas_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Permisos insuficientes.' );
    }

    // 3) Leer y sanitizar fechas/horas
    $fechas_raw   = sanitize_text_field( $_POST['fechas_seleccionadas'] ?? '' );
    $hora_ingreso = sanitize_text_field( $_POST['hora_ingreso']       ?? '' );
    $hora_egreso  = sanitize_text_field( $_POST['hora_egreso']        ?? '' );
    $sel          = sanitize_text_field( $_POST['producto_reserva']   ?? '' );

    $fechas_arr = array_filter( array_map( 'trim', explode( ',', $fechas_raw ) ) );
    if ( empty( $fechas_arr ) || ! $hora_ingreso || ! $hora_egreso || ! $sel ) {
        wp_die( 'Selecciona fechas, horas y servicio.' );
    }
    $dias = count( $fechas_arr );

    // 4) Determinar precio diario y nombre de línea
    if ( str_starts_with( $sel, 'bk_' ) ) {
        $prod_id       = intval( substr( $sel, 3 ) );
        $product_obj   = wc_get_product( $prod_id );
        $block_cost    = (float) get_post_meta( $prod_id, '_wc_booking_block_cost', true );
        $base_cost     = (float) get_post_meta( $prod_id, '_wc_booking_cost',       true );
        $precio_dia    = $block_cost > 0 ? $block_cost : ( $base_cost > 0 ? $base_cost : 0 );
        $line_name     = $product_obj ? $product_obj->get_name() : 'Booking #' . $prod_id;
    } else {
        $idx        = intval( str_replace( 'svc_', '', $sel ) );
        $rows       = get_option( 'pethome_precios_base', [] );
        $row        = $rows[ $idx ] ?? [];
        $precio_dia = floatval( $row['precio'] ?? 0 );
        $line_name  = $row['servicio'] ?? 'Servicio';
    }
    $subtotal = round( $precio_dia * $dias, 2 );

    // 5) Recargos/descuentos
    $map = [
        'tamanio'              => [ 'chico'=>0,'mediano'=>25,'grande'=>50 ],
        'sociable_ninios'      => [ 'si'=>0,'no'=>20 ],
        'sociable_mascotas'    => [ 'si'=>0,'no'=>20 ],
        'vacunacion'           => [ 'vacunado'=>0,'sin_vacuna'=>5 ],
        'castracion'           => [ 'castrado'=>0,'no_castrado'=>2 ],
        'pechera'              => [ 'con'=>0,'sin'=>20 ],
        'seguro'               => [ 'con_cobertura'=>-10,'sin_cobertura'=>0 ],
    ];
    $total_recargos = 0;
    foreach( $map as $campo=>$tabla ) {
        $v = sanitize_text_field( $_POST[ $campo ] ?? '' );
        if ( isset( $tabla[ $v ] ) && $tabla[ $v ] ) {
            $monto = round( $subtotal * $tabla[ $v ] / 100, 2 );
            $total_recargos += $monto;
        }
    }
    $total_pedido = round( $subtotal + $total_recargos, 2 );

    // 6) Crear pedido WooCommerce
    $order = wc_create_order();
    // ítem base
    if ( isset( $product_obj ) && $product_obj instanceof WC_Product ) {
        $order->add_product( $product_obj, $dias );
    } else {
        $order->add_fee( [
            'name'    => $line_name,
            'total'   => $subtotal,
            'taxable' => false,
        ] );
    }
    // recargos
    if ( $total_recargos ) {
        $order->add_fee( [
            'name'    => 'Recargos / Descuentos',
            'total'   => $total_recargos,
            'taxable' => false,
        ] );
    }
    $order->set_total( $total_pedido );
    $order->calculate_totals();
    $order->update_status( 'pending', 'Reserva generada desde formulario.' );
    $order->save();

    // 7) Crear CPT reserva_guarda
    $res_id = wp_insert_post([
        'post_type'   => 'reserva_guarda',
        'post_title'  => 'Reserva #' . $order->get_id(),
        'post_status' => 'publish',
    ]);

    if ( $res_id && ! is_wp_error( $res_id ) ) {
        // ── Fechas y horas ──
        update_post_meta( $res_id, 'fecha_inicio',  reset( $fechas_arr ) );
        update_post_meta( $res_id, 'fecha_fin',     end(   $fechas_arr ) );
        update_post_meta( $res_id, 'hora_ingreso',  $hora_ingreso );
        update_post_meta( $res_id, 'hora_egreso',   $hora_egreso );
        update_post_meta( $res_id, 'dias',          $dias );
        update_post_meta( $res_id, 'precio_diario', $precio_dia );
        update_post_meta( $res_id, 'total_base',    $subtotal );

// ── Datos Cliente ──
$nombre       = sanitize_text_field( $_POST['cliente_nombre']   ?? '' );
$apellido     = sanitize_text_field( $_POST['cliente_apellido'] ?? '' );
$telefono     = sanitize_text_field( $_POST['cliente_telefono'] ?? '' );

// aquí componemos la dirección:
$calle    = sanitize_text_field( $_POST['cliente_calle']   ?? '' );
$numero   = sanitize_text_field( $_POST['cliente_numero']  ?? '' );
$domicilio = trim( $calle . ' ' . $numero );

$barrio       = sanitize_text_field( $_POST['cliente_barrio']   ?? '' );
$mail_cliente = sanitize_email(     $_POST['cliente_email']    ?? '' );
$dni          = sanitize_text_field( $_POST['cliente_dni']      ?? '' );
$alias_banco  = sanitize_text_field( $_POST['cliente_alias_bancario'] ?? '' );

        // ── Mascota ──
        update_post_meta( $res_id, 'nombre_mascota',   sanitize_text_field( $_POST['nombre_mascota']     ?? '' ) );
        update_post_meta( $res_id, 'sexo',             sanitize_text_field( $_POST['sexo']               ?? '' ) );
        update_post_meta( $res_id, 'raza',             sanitize_text_field( $_POST['raza']               ?? '' ) );
        update_post_meta( $res_id, 'edad_anios',       intval(            $_POST['edad_anios']           ?? 0  ) );
        update_post_meta( $res_id, 'edad_meses',       intval(            $_POST['edad_meses']           ?? 0  ) );
        update_post_meta( $res_id, 'tamanio',          sanitize_text_field( $_POST['tamanio']            ?? '' ) );
        update_post_meta( $res_id, 'sociable_ninios',  sanitize_text_field( $_POST['sociable_ninios']    ?? '' ) );
        update_post_meta( $res_id, 'sociable_mascotas',sanitize_text_field( $_POST['sociable_mascotas']  ?? '' ) );
        update_post_meta( $res_id, 'heces',            sanitize_text_field( $_POST['heces']              ?? '' ) );
        update_post_meta( $res_id, 'pechera',          sanitize_text_field( $_POST['pechera']            ?? '' ) );
        update_post_meta( $res_id, 'seguro',           sanitize_text_field( $_POST['seguro']             ?? '' ) );
    }

    // 8) Redirigir al listado
    wp_safe_redirect( admin_url( 'edit.php?post_type=reserva_guarda' ) );
    exit;
}
