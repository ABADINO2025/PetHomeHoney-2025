<?php
/**
 * pethome_guardas_agregar.php Formulario completo “Agregar Guarda”
 * Version:     1.6 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrolla   www.streaminginternacional.com 
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if (isset($_GET['status']) && $_GET['status'] === 'saved') {
    echo '<div id="message" class="updated notice is-dismissible"><p>' . __('Reserva guardada correctamente. Podés seguir editando o crear una nueva.', 'pethomehoney-plugin') . '</p></div>';
}

// --- Lógica PHP para obtener datos ---
global $wpdb;
$bookings = wc_get_products( [ 'type' => 'booking', 'limit' => -1 ] );

// SE ELIMINÓ LA FUNCIÓN DUPLICADA DE AQUÍ PARA EVITAR CONFLICTOS

$servicios_creados = get_option( 'pethome_precios_base', [] );
$tipos_mascota = get_option( 'pethome_tipos_mascotas', [] );
$razas            = get_option( 'pethome_razas', [] );

// --- Cargar tipos de cliente ---
$client_types_table = $wpdb->prefix . 'phh_client_types';
$client_types = $wpdb->get_results("SELECT * FROM $client_types_table ORDER BY name ASC", ARRAY_A);

// --- Cargar datos de costos avanzados, relaciones y el predefinido ---
$costos_configs = get_option( 'pethomehoney_costos_guardas_configs', [] );
$relaciones_costos = get_option( 'pethome_relaciones_costos', [] );
$default_cost_config_id = get_option('pethome_default_cost_config_id', null);
$cost_data_for_js = [];
$all_costs_for_js = [];

if (is_array($costos_configs)) {
    foreach ($costos_configs as $costo_config) {
        if (isset($costo_config['id']) && isset($costo_config['data'])) {
            $all_costs_for_js[$costo_config['id']] = $costo_config['data'];
        }
    }
}

if (is_array($relaciones_costos)) {
    foreach ($relaciones_costos as $relacion) {
        if (isset($relacion['servicio_id']) && isset($relacion['costo_id'])) {
            if (isset($all_costs_for_js[$relacion['costo_id']])) {
                $cost_data_for_js[$relacion['servicio_id']] = $all_costs_for_js[$relacion['costo_id']];
            }
        }
    }
}


$razas_por_tipo = [];
foreach ( $razas as $raza_data ) {
    if ( isset( $raza_data['tipo_mascota'] ) && isset( $raza_data['raza'] ) ) {
        $tipo = sanitize_title( $raza_data['tipo_mascota'] );
        if ( ! isset( $razas_por_tipo[$tipo] ) ) {
            $razas_por_tipo[$tipo] = [];
        }
        $razas_por_tipo[$tipo][] = sanitize_text_field( $raza_data['raza'] );
    }
}

$post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;

$default_values = [
    'guarda_nombre' => '', 'guarda_ubicacion' => '', 'guarda_latitud' => '', 'guarda_longitud' => '',
    'guarda_tarifa_base' => '0', 'guarda_descripcion' => '', 'guarda_imagen_id' => '',
    'reserva_observaciones' => '', 'reserva_cuidador_asignado' => '', 'reserva_costo_guarda_id' => '', 'reserva_tipo_cliente_id' => '',
    'reserva_cargos' => '0', 'reserva_entrega' => '0', 'reserva_saldo_final' => '0', 'reserva_precio_total' => '0', 'reserva_subtotal' => '0',
    'reserva_fechas' => '', 'hora_ingreso_reserva' => '10:00', 'hora_egreso_reserva' => '18:00',
    'booking_product_or_service' => '', 'reserva_servicio' => '', 'reserva_prioridad' => 'normal',
    'mascota_nombre' => '', 'mascota_tipo' => '', 'mascota_raza' => '', 'mascota_tamano' => 'chico', 'mascota_edad' => '', 'mascota_edad_meses' => '',
    'mascota_imagen_id' => '', 'mascota_enfermedades' => '', 'mascota_medicamentos' => '', 'mascota_alergias' => '',
    'mascota_cobertura_salud' => 'sin_cobertura', 'mascota_sociable_perros' => 'si', 'mascota_sociable_ninios' => 'si',
    'mascota_vacunas_completas' => 'vacunado', 'mascota_castrada' => 'castrado',
    'mascota_sexo' => '',
    'mascota_desparasitado' => 'si', 'mascota_antipulgas' => 'si', 
    'mascota_veterinario_nombre' => '', 'mascota_veterinario_telefono' => '',
    'mascota_observaciones_sanidad' => '', 'mascota_chip' => 'no', 'mascota_collar_identificacion' => 'si', 
    'mascota_con_correa' => 'si', 'mascota_con_pechera' => 'con', 'mascota_observaciones_seguridad' => '', 
    'cliente_nombre' => '', 'cliente_apellido' => '', 'cliente_dni' => '', 'cliente_alias_bancario' => '',
    'cliente_calle' => '', 'cliente_numero' => '', 'cliente_barrio' => '', 'cliente_email' => '',
    'cliente_telefono' => '',
    'reserva_cantidad_dias' => '0',
];
$guarda_data = [];

if ( $post_id ) {
    $meta_keys_to_load = [
        'pethome_guarda_nombre', 'pethome_guarda_ubicacion',
        'pethome_reserva_fechas', 'pethome_reserva_cantidad_dias', 'pethome_reserva_hora_ingreso', 'pethome_reserva_hora_egreso',
        'pethome_reserva_servicio', 'pethome_reserva_subtotal', 'pethome_reserva_precio_total', 'pethome_reserva_entrega', 'pethome_reserva_saldo_final', 'pethome_reserva_cargos',
        'pethome_reserva_prioridad', 'pethome_reserva_costo_guarda_id', 'pethome_reserva_tipo_cliente_id',
        'pethome_cliente_nombre', 'pethome_cliente_apellido', 'pethome_cliente_dni', 'pethome_cliente_alias_bancario',
        'pethome_cliente_calle', 'pethome_cliente_numero', 'pethome_cliente_barrio', 'pethome_cliente_email', 'pethome_cliente_telefono',
        'pethome_mascota_nombre', 'pethome_mascota_tipo', 'pethome_mascota_raza', 'pethome_mascota_tamano', 'pethome_mascota_edad', 'pethome_mascota_edad_meses', 'pethome_mascota_sexo', 'pethome_mascota_castrada',
        'pethome_mascota_enfermedades', 'pethome_mascota_medicamentos', 'pethome_mascota_alergias', 'pethome_mascota_cobertura_salud',
        'pethome_mascota_vacunas_completas', 'pethome_mascota_desparasitado', 'pethome_mascota_antipulgas',
        'pethome_mascota_veterinario_nombre', 'pethome_mascota_veterinario_telefono', 'pethome_mascota_observaciones_sanidad',
        'pethome_mascota_chip', 'pethome_mascota_collar_identificacion', 'pethome_mascota_con_correa', 'pethome_mascota_con_pechera',
        'pethome_mascota_observaciones_seguridad', 'pethome_mascota_sociable_perros', 'pethome_mascota_sociable_ninios', 
        'pethome_reserva_observaciones', 'pethome_reserva_cuidador_asignado',
    ];
    foreach ( $meta_keys_to_load as $key ) {
        $cleaned_key = str_replace('pethome_', '', $key);
        $meta_value = get_post_meta( $post_id, $key, true );
        if ($meta_value !== '' || array_key_exists($cleaned_key, $default_values) ) {
             $guarda_data[$cleaned_key] = $meta_value;
        }
    }
    $guarda_data = array_merge($default_values, $guarda_data);

    $numeric_fields = ['guarda_tarifa_base', 'reserva_cargos', 'reserva_entrega', 'reserva_saldo_final', 'reserva_cantidad_dias', 'reserva_subtotal', 'reserva_precio_total'];
    foreach($numeric_fields as $field_key) {
        if (strpos($field_key, 'dias') !== false) {
            $guarda_data[$field_key] = (int) ($guarda_data[$field_key]);
        } else {
            $guarda_data[$field_key] = (float) ($guarda_data[$field_key]);
        }
    }
    $guarda_data['booking_product_or_service'] = $guarda_data['reserva_servicio'] ?? '';
} else {
    $guarda_data = $default_values;
    $guarda_data['reserva_costo_guarda_id'] = $default_cost_config_id;
}

$telefono_completo_guardado = $guarda_data['cliente_telefono'] ?? '';
$telefono_area_val = '';
$telefono_numero_val = '';

if (!empty($telefono_completo_guardado) && is_numeric($telefono_completo_guardado)) {
    if (strlen($telefono_completo_guardado) >= 9 && strpos($telefono_completo_guardado, '11') === 0) {
        $telefono_area_val = substr($telefono_completo_guardado, 0, 4);
        $telefono_numero_val = substr($telefono_completo_guardado, 4);
    } elseif (strlen($telefono_completo_guardado) > 6) {
        $largo_area = strlen($telefono_completo_guardado) - 6;
        $telefono_area_val = substr($telefono_completo_guardado, 0, $largo_area);
        $telefono_numero_val = substr($telefono_completo_guardado, $largo_area);
    } else {
        $telefono_numero_val = $telefono_completo_guardado;
    }
}
?>
<style>
/* Estilos generales y de botones */
.pethome-admin-wrap { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.50); padding: 25px; background-color: #f0f0f0; border-radius: 8px; margin-top: 20px; margin-bottom: 20px; }
.pethome-admin-wrap h1 { margin-bottom: 25px; color: #5e4365; }
.pethome-section { box-shadow: 0 2px 8px rgba(0, 0, 0, 0.50); padding: 20px; background-color: #ffffff; border-radius: 10px; margin-bottom: 25px; }
.pethome-section:last-of-type { margin-bottom: 0; }
.pethome-section-header-with-buttons { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 10px; }
.pethome-section-header-with-buttons h2 { color: #5e4365; margin:0 !important; padding:0; font-size: 1.4em; line-height: normal; }
.pethome-status-buttons .button { margin-left: 8px; padding: 0px 12px; line-height: 26px; height: 28px; font-weight: 600; vertical-align: middle; border-width: 1px; border-style: solid; transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out, border-color 0.15s ease-in-out; cursor: pointer; box-shadow: none !important; }
.pethome-status-buttons .button:focus { outline: 1px dotted #555; }
.pethome-button-urgente:not(.active-status) { background-color: transparent !important; color: #DC3545 !important; border-color: #DC3545 !important; }
.pethome-button-normal:not(.active-status) { background-color: transparent !important; color: #28A745 !important; border-color: #28A745 !important; }
.pethome-button-pendiente:not(.active-status) { background-color: transparent !important; color: #007BFF !important; border-color: #007BFF !important; }
.pethome-button-urgente:hover:not(.active-status), .pethome-button-urgente:focus:not(.active-status) { background-color: rgba(220, 53, 69, 0.08) !important; }
.pethome-button-normal:hover:not(.active-status), .pethome-button-normal:focus:not(.active-status) { background-color: rgba(40, 167, 69, 0.08) !important; }
.pethome-button-pendiente:hover:not(.active-status), .pethome-button-pendiente:focus:not(.active-status) { background-color: rgba(0, 123, 255, 0.08) !important; }
.pethome-button-urgente.active-status { background-color: #DC3545 !important; border-color: #DC3545 !important; color: white !important; }
.pethome-button-normal.active-status { background-color: #28A745 !important; border-color: #28A745 !important; color: white !important; }
.pethome-button-pendiente.active-status { background-color: #007BFF !important; border-color: #007BFF !important; color: white !important; }
.cuidador-costo-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 20px;
    align-items: flex-end;
}
.costo-calculado-field .display-value {
    font-size: 1.5em;
    font-weight: bold;
    color: #5e4365;
    padding: 5px 10px;
    background-color: #f0f0f0;
    border-radius: 4px;
    min-width: 150px;
    text-align: center;
}
</style>
<div class="wrap pethome-admin-wrap">
    <h1><?php echo $post_id ? __('Editar Reserva', 'pethomehoney-plugin') : __('Agregar Nueva Reserva', 'pethomehoney-plugin'); ?></h1>

    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" id="pethome-guarda-form">
        <?php wp_nonce_field('pethome_guarda_save_details', 'pethome_guarda_nonce'); ?>
        <input type="hidden" name="action" value="pethome_guardas_save">
        <?php if ($post_id): ?>
            <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">
        <?php endif; ?>

        <div class="pethome-section">
            <div class="pethome-section-header-with-buttons">
                <h2><?php _e('Reserva', 'pethomehoney-plugin'); ?></h2>
                <div class="pethome-status-buttons">
                    <button type="button" id="btn_urgente" class="button pethome-button-urgente" data-status="urgente"><?php _e('URGENTE', 'pethomehoney-plugin'); ?></button>
                    <button type="button" id="btn_normal" class="button pethome-button-normal" data-status="normal"><?php _e('NORMAL', 'pethomehoney-plugin'); ?></button>
                    <button type="button" id="btn_pendiente" class="button pethome-button-pendiente" data-status="pendiente"><?php _e('PENDIENTE', 'pethomehoney-plugin'); ?></button>
                    <input type="hidden" name="pethome_reserva_prioridad" id="pethome_reserva_prioridad_status" value="<?php echo esc_attr($guarda_data['reserva_prioridad']); ?>"> 
                </div>
            </div>
            
            <div class="pethome-main-booking-grid">
                 <div class="pethome-calendar-column" style="grid-area: calendar;">
                    <label for="pethome_flatpickr_inline_calendar_container"><?php _e('Seleccionar Fechas', 'pethomehoney-plugin'); ?></label>
                    <input type="hidden" id="calendario_fechas" name="pethome_reserva_fechas" value="<?php echo esc_attr( $guarda_data['reserva_fechas'] ); ?>">
                    <div id="pethome_flatpickr_inline_calendar_container"></div>
                </div>
                <div class="grid-item-input" style="grid-area: hora-ingreso;">
                    <label for="hora_ingreso_reserva"><?php _e('Hora de Ingreso', 'pethomehoney-plugin'); ?></label>
                    <input type="time" id="hora_ingreso_reserva" name="pethome_reserva_hora_ingreso" value="<?php echo esc_attr($guarda_data['hora_ingreso_reserva']); ?>" style="background-color: #e9ecef;">
                </div>
                <div class="grid-item-input" style="grid-area: hora-egreso;">
                    <label for="hora_egreso_reserva"><?php _e('Hora de Egreso', 'pethomehoney-plugin'); ?></label>
                    <input type="time" id="hora_egreso_reserva" name="pethome_reserva_hora_egreso" value="<?php echo esc_attr($guarda_data['hora_egreso_reserva']); ?>" style="background-color: #e9ecef;">
                </div>
                <div class="grid-item-display days-field" style="grid-area: dias;">
                    <label><?php _e('Días', 'pethomehoney-plugin'); ?></label>
                    <div class="display-value-container">
                        <p id="cantidad_dias_reserva" style="font-weight: bold; font-size: 1.1em;"><?php echo esc_html($guarda_data['reserva_cantidad_dias']); ?></p>
                    </div>
                </div>
                <input type="hidden" id="pethome_reserva_cantidad_dias_hidden" name="pethome_reserva_cantidad_dias" value="<?php echo esc_attr($guarda_data['reserva_cantidad_dias']); ?>">
                <div class="grid-item-select service-product-field" style="grid-area: servicio-producto;">
                    <label for="booking_product_or_service"><?php _e('Seleccionar Servicio/Producto', 'pethomehoney-plugin'); ?></label>
                    <select id="booking_product_or_service" name="pethome_reserva_servicio" style="background-color: #e9ecef;">
                        <option value="" data-cost="0"><?php _e('Seleccionar...', 'pethomehoney-plugin'); ?></option>
                        <optgroup label="<?php _e('Productos de Booking', 'pethomehoney-plugin'); ?>">
                            <?php foreach ( $bookings as $product ) :
                                $product_cost = pethome_get_booking_daily_cost( $product->get_id() ); ?>
                                <option value="product_id:<?php echo esc_attr( $product->get_id() ); ?>"
                                    data-cost="<?php echo esc_attr( $product_cost ); ?>"
                                    <?php selected( $guarda_data['booking_product_or_service'], 'product_id:' . $product->get_id() ); ?>>
                                    <?php echo esc_html( $product->get_name() ); ?> (<?php echo wc_price( $product_cost ); ?>/día)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php _e('Servicios Creados', 'pethomehoney-plugin'); ?>">
                        <?php foreach ( $servicios_creados as $idx => $servicio ) :
                                $servicio_nombre = isset($servicio['servicio']) ? $servicio['servicio'] : __('Servicio sin nombre', 'pethomehoney-plugin');
                                $servicio_precio_base = isset($servicio['precio']) ? (float) $servicio['precio'] : 0.0;
                                ?>
                                <option value="custom_service:<?php echo esc_attr( $idx ); ?>"
                                    data-cost="<?php echo esc_attr( $servicio_precio_base ); ?>"
                                    <?php selected( $guarda_data['booking_product_or_service'], 'custom_service:' . $idx ); ?>>
                                    <?php echo esc_html( $servicio_nombre ); ?> (<?php echo wc_price( $servicio_precio_base ); ?>/día)
                                </option>
                        <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="grid-item-display" style="grid-area: costo-diario;">
                    <label><?php _e('Costo Diario', 'pethomehoney-plugin'); ?></label>
                    <div class="display-value-container">
                        <p id="costo_diario_reserva" style="font-weight: bold; font-size: 1.1em;">0</p>
                    </div>
                </div>
                <div class="grid-item-display" style="grid-area: sub-total;">
                    <label><?php _e('Sub Total Base', 'pethomehoney-plugin'); ?></label>
                    <div class="display-value-container">
                        <p id="sub_total_reserva_display" style="font-weight: bold; font-size: 1.1em;"></p>
                    </div>
                </div>
                <input type="hidden" id="pethome_reserva_subtotal_hidden" name="pethome_reserva_subtotal" value="<?php echo esc_attr($guarda_data['reserva_subtotal']); ?>">
                <div class="grid-item-display" style="grid-area: cargos;">
                    <label><?php _e('Cargos/Ajustes', 'pethomehoney-plugin'); ?></label>
                    <div class="display-value-container">
                        <p id="reserva_cargos_display" style="font-weight: bold; font-size: 1.1em;"><?php echo wc_price($guarda_data['reserva_cargos']); ?></p>
                    </div>
                    <input type="hidden" id="pethome_reserva_cargos" name="pethome_reserva_cargos" value="<?php echo esc_attr($guarda_data['reserva_cargos']); ?>">
                </div>
                <div class="grid-item-display" style="grid-area: entrega;">
                    <label><?php _e('Entrega (10%)', 'pethomehoney-plugin'); ?></label>
                    <div class="display-value-container">
                        <p id="reserva_entrega_display" style="font-weight: bold; font-size: 1.1em;"></p>
                    </div>
                </div>
                <input type="hidden" id="pethome_reserva_entrega_hidden" name="pethome_reserva_entrega" value="<?php echo esc_attr($guarda_data['reserva_entrega']); ?>">
                <div class="grid-item-display" style="grid-area: precio-total;">
                    <label><?php _e('Precio Total', 'pethomehoney-plugin'); ?></label>
                    <div class="display-value-container">
                        <p id="precio_total_reserva_display" style="font-weight: bold; font-size: 1.3em; color: #4CAF50;">0</p>
                    </div>
                </div>
                <input type="hidden" id="pethome_reserva_precio_total_hidden" name="pethome_reserva_precio_total" value="<?php echo esc_attr($guarda_data['reserva_precio_total']); ?>">
                <div class="grid-item-display" style="grid-area: saldo-final;">
                    <label><?php _e('Saldo (90%)', 'pethomehoney-plugin'); ?></label>
                    <div class="display-value-container">
                        <p id="reserva_saldo_final_display" style="font-weight: bold; font-size: 1.3em; color: #DC3545;"></p>
                    </div>
                </div>
                <input type="hidden" id="pethome_reserva_saldo_final_hidden" name="pethome_reserva_saldo_final" value="<?php echo esc_attr($guarda_data['reserva_saldo_final']); ?>">
                <div class="grid-item-input grid-item-fechas-seleccionadas" style="grid-area: fechas-display;">
                    <label for="fechas_seleccionadas_texto"><?php _e('Fechas Seleccionadas', 'pethomehoney-plugin'); ?></label>
                    <input type="text" id="fechas_seleccionadas_texto" readonly placeholder="<?php esc_attr_e('Fechas de la reserva', 'pethomehoney-plugin'); ?>" value="<?php echo esc_attr($guarda_data['reserva_fechas']); ?>" style="background-color: #e9ecef;">
                </div>
            </div>
        </div>

        <div class="pethome-section">
            <h2><?php _e('Datos del Cliente', 'pethomehoney-plugin'); ?></h2>
            <div class="pethome-details-grid">
                <div class="item-nombre"><label for="pethome_cliente_nombre"><?php _e('Nombre', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_cliente_nombre" name="pethome_cliente_nombre" value="<?php echo esc_attr($guarda_data['cliente_nombre']); ?>" required style="background-color: #e9ecef;"></div>
                <div class="item-apellido"><label for="pethome_cliente_apellido"><?php _e('Apellido', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_cliente_apellido" name="pethome_cliente_apellido" value="<?php echo esc_attr($guarda_data['cliente_apellido']); ?>" required style="background-color: #e9ecef;"></div>
                <div class="item-dni"><label for="pethome_cliente_dni"><?php _e('DNI', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_cliente_dni" name="pethome_cliente_dni" value="<?php echo esc_attr($guarda_data['cliente_dni']); ?>" required style="background-color: #e9ecef;"></div>
                <div class="item-alias"><label for="pethome_cliente_alias_bancario"><?php _e('Alias Bancario/CBU', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_cliente_alias_bancario" name="pethome_cliente_alias_bancario" value="<?php echo esc_attr($guarda_data['cliente_alias_bancario']); ?>" style="background-color: #e9ecef;"></div>
                <div class="item-calle"><label for="pethome_cliente_calle"><?php _e('Calle', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_cliente_calle" name="pethome_cliente_calle" value="<?php echo esc_attr($guarda_data['cliente_calle']); ?>" style="background-color: #e9ecef;"></div>
                <div class="item-numero"><label for="pethome_cliente_numero"><?php _e('Número', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_cliente_numero" name="pethome_cliente_numero" value="<?php echo esc_attr($guarda_data['cliente_numero']); ?>" style="background-color: #e9ecef;"></div>
                <div class="item-barrio"><label for="pethome_cliente_barrio"><?php _e('Barrio', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_cliente_barrio" name="pethome_cliente_barrio" value="<?php echo esc_attr($guarda_data['cliente_barrio']); ?>" style="background-color: #e9ecef;"></div>
                <div class="item-email email-field"><label for="pethome_cliente_email"><?php _e('Email', 'pethomehoney-plugin'); ?></label><input type="email" id="pethome_cliente_email" name="pethome_cliente_email" value="<?php echo esc_attr($guarda_data['cliente_email']); ?>" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}" placeholder="<?php esc_attr_e('usuario@dominio.com', 'pethomehoney-plugin'); ?>" oninvalid="this.setCustomValidity('<?php _e('Introduce un email válido, ej usuario@dominio.com', 'pethomehoney-plugin'); ?>')" oninput="this.setCustomValidity('')" style="background-color: #e9ecef;"></div>
                <div class="item-telefono">
                    <label for="pethome_cliente_telefono_area"><?php _e('Teléfono', 'pethomehoney-plugin'); ?> <small>(Ej: +54 9 XXXX XXXXXX)</small></label>
                    <div class="telefono-input-linea">
                        <span class="telefono-prefijo fijo"> +54 9</span>
                        <input type="text" id="pethome_cliente_telefono_area" value="<?php echo esc_attr($telefono_area_val); ?>" placeholder="<?php esc_attr_e('Cód. Área', 'pethomehoney-plugin'); ?>" maxlength="4" title="<?php esc_attr_e('Hasta 4 dígitos del código de área sin el cero inicial', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef; width: 100px; margin-right: 5px;">
                        <input type="text" id="pethome_cliente_telefono_numero" value="<?php echo esc_attr($telefono_numero_val); ?>" placeholder="<?php esc_attr_e('Número', 'pethomehoney-plugin'); ?>" maxlength="8" title="<?php esc_attr_e('Hasta 8 dígitos del número local sin el 15', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef; flex-grow:1;">
                    </div>
                    <input type="hidden" id="pethome_cliente_telefono_completo" name="pethome_cliente_telefono" value="<?php echo esc_attr($guarda_data['cliente_telefono']); ?>">
                </div>
                <div class="item-tipo-cliente">
                    <label for="pethome_reserva_tipo_cliente_id"><?php _e('Tipo de Cliente', 'pethomehoney-plugin'); ?></label>
                    <select id="pethome_reserva_tipo_cliente_id" name="pethome_reserva_tipo_cliente_id" style="background-color: #e9ecef; width:100%;">
                        <option value="" data-modifier="0"><?php _e('Sin tipo específico', 'pethomehoney-plugin'); ?></option>
                        <?php
                        if (!empty($client_types)) {
                            foreach ($client_types as $type) {
                                $modifier_val = floatval($type['discount']);
                                $modifier_label = ($modifier_val > 0 ? '+' : '') . number_format($modifier_val, 2, ',', '.') . '%';
                                echo '<option value="' . esc_attr($type['id']) . '" ' . selected($guarda_data['reserva_tipo_cliente_id'], $type['id'], false) . ' data-modifier="' . esc_attr($modifier_val) . '">' . esc_html($type['name']) . ' (' . esc_html($modifier_label) . ')</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="pethome-section">
             <h2><?php _e('Datos de la Mascota', 'pethomehoney-plugin'); ?></h2>
             <div class="pethome-details-grid">
                 <div><label for="pethome_mascota_nombre"><?php _e('Nombre de la Mascota', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_mascota_nombre" name="pethome_mascota_nombre" value="<?php echo esc_attr($guarda_data['mascota_nombre']); ?>" placeholder="<?php esc_attr_e('Ej: Rufo', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef;"></div>
                 <div>
                    <label for="pethome_mascota_tipo"><?php _e('Tipo de Mascota', 'pethomehoney-plugin'); ?></label>
                    <select id="pethome_mascota_tipo" name="pethome_mascota_tipo" style="background-color: #e9ecef;">
                        <option value=""><?php _e('Seleccionar tipo', 'pethomehoney-plugin'); ?></option>
                        <?php 
                        foreach ( $tipos_mascota as $tipo_info ) : 
                            if ( ! isset( $tipo_info['tipo'] ) || empty( $tipo_info['tipo'] ) ) continue;
                            $tipo_slug = sanitize_title( $tipo_info['tipo'] );
                        ?>
                            <option value="<?php echo esc_attr( $tipo_slug ); ?>" <?php selected( $guarda_data['mascota_tipo'], $tipo_slug ); ?>>
                                <?php echo esc_html( $tipo_info['tipo'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                 </div>
                 <div>
                    <label for="pethome_mascota_raza"><?php _e('Raza', 'pethomehoney-plugin'); ?></label>
                    <select id="pethome_mascota_raza" name="pethome_mascota_raza" style="background-color: #e9ecef;">
                        <option value=""><?php _e('Seleccionar raza', 'pethomehoney-plugin'); ?></option>
                        <?php 
                        $tipo_actual_para_raza = !empty($guarda_data['mascota_tipo']) ? sanitize_title($guarda_data['mascota_tipo']) : '';
                        if ( !empty($tipo_actual_para_raza) && isset( $razas_por_tipo[$tipo_actual_para_raza] ) ) {
                            foreach ( $razas_por_tipo[$tipo_actual_para_raza] as $raza_item ) {
                                echo '<option value="' . esc_attr( $raza_item ) . '" ' . selected( $guarda_data['mascota_raza'], $raza_item, false ) . '>' . esc_html( $raza_item ) . '</option>';
                            }
                        } elseif (!empty($guarda_data['mascota_raza'])) {
                            echo '<option value="' . esc_attr( $guarda_data['mascota_raza'] ) . '" selected>' . esc_html( $guarda_data['mascota_raza'] ) . '</option>';
                        }
                        ?>
                    </select>
                 </div>
                 <div><label for="pethome_mascota_edad"><?php _e('Edad (años)', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_mascota_edad" name="pethome_mascota_edad" value="<?php echo esc_attr($guarda_data['mascota_edad']); ?>" placeholder="<?php esc_attr_e('Ej: 3', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef;"></div>
                 <div><label for="pethome_mascota_edad_meses"><?php _e('Edad (meses)', 'pethomehoney-plugin'); ?></label><input type="number" id="pethome_mascota_edad_meses" name="pethome_mascota_edad_meses" value="<?php echo esc_attr($guarda_data['mascota_edad_meses']); ?>" placeholder="<?php esc_attr_e('Ej: 6', 'pethomehoney-plugin'); ?>" min="0" max="11" step="1" style="background-color: #e9ecef;"></div>
                 <div><label for="tamanio_mascota"><?php _e('Tamaño', 'pethomehoney-plugin'); ?></label><select id="tamanio_mascota" name="pethome_mascota_tamano" data-p-select style="background-color: #e9ecef;"><option value="chico" data-p="0" <?php selected($guarda_data['mascota_tamano'], 'chico'); ?>><?php _e('Chico', 'pethomehoney-plugin'); ?></option><option value="mediano" data-p="25" <?php selected($guarda_data['mascota_tamano'], 'mediano'); ?>><?php _e('Mediano', 'pethomehoney-plugin'); ?></option><option value="grande" data-p="50" <?php selected($guarda_data['mascota_tamano'], 'grande'); ?>><?php _e('Grande', 'pethomehoney-plugin'); ?></option></select></div>
                 <div><label for="pethome_mascota_sexo"><?php _e('Sexo', 'pethomehoney-plugin'); ?></label><select id="pethome_mascota_sexo" name="pethome_mascota_sexo" style="background-color: #e9ecef;"><option value=""><?php _e('Seleccionar', 'pethomehoney-plugin'); ?></option><option value="macho" <?php selected($guarda_data['mascota_sexo'], 'macho'); ?>><?php _e('Macho', 'pethomehoney-plugin'); ?></option><option value="hembra" <?php selected($guarda_data['mascota_sexo'], 'hembra'); ?>><?php _e('Hembra', 'pethomehoney-plugin'); ?></option></select></div>
                 <div><label for="castracion"><?php _e('Castración', 'pethomehoney-plugin'); ?></label><select id="castracion" name="pethome_mascota_castrada" data-p-select style="background-color: #e9ecef;"><option value="castrado" data-p="0" <?php selected($guarda_data['mascota_castrada'], 'castrado'); ?>><?php _e('Castrado', 'pethomehoney-plugin'); ?></option><option value="no_castrado" data-p="2" <?php selected($guarda_data['mascota_castrada'], 'no_castrado'); ?>><?php _e('No Castrado (+2 %)', 'pethomehoney-plugin'); ?></option></select></div>
                 <div class="item-imagen"><label for="pethome_mascota_imagen_id_input"><?php _e('Imagen de la Mascota', 'pethomehoney-plugin'); ?></label><input type="hidden" id="pethome_mascota_imagen_id_input" name="pethome_mascota_imagen_id" value="<?php echo esc_attr($guarda_data['mascota_imagen_id']); ?>"><button type="button" class="button media-button" data-target="pethome_mascota_imagen_id_input" data-preview="pethome_mascota_imagen_preview"><?php _e('Subir/Seleccionar Imagen', 'pethomehoney-plugin'); ?></button><div id="pethome_mascota_imagen_preview" class="preview-container"><?php $mascota_imagen_url = ''; if ( ! empty($guarda_data['mascota_imagen_id']) && is_numeric($guarda_data['mascota_imagen_id']) ) { $mascota_imagen_url = wp_get_attachment_image_url( (int) $guarda_data['mascota_imagen_id'], 'thumbnail' ); } if ( $mascota_imagen_url ) : ?><img src="<?php echo esc_url( $mascota_imagen_url ); ?>" alt="<?php esc_attr_e('Vista previa de mascota', 'pethomehoney-plugin'); ?>"><?php endif; ?></div></div>
                 <div><label for="sociable_mascotas"><?php _e('¿Sociable con mascotas?', 'pethomehoney-plugin'); ?></label><select id="sociable_mascotas" name="pethome_mascota_sociable_perros" data-p-select style="background-color: #e9ecef;"><option value="si" data-p="0" <?php selected($guarda_data['mascota_sociable_perros'], 'si'); ?>><?php _e('Es Sociable', 'pethomehoney-plugin'); ?></option><option value="no" data-p="20" <?php selected($guarda_data['mascota_sociable_perros'], 'no'); ?>><?php _e('No es Sociable (+20 %)', 'pethomehoney-plugin'); ?></option></select></div>
                 <div><label for="sociable_ninios"><?php _e('¿Sociable con niños?', 'pethomehoney-plugin'); ?></label><select id="sociable_ninios" name="pethome_mascota_sociable_ninios" data-p-select style="background-color: #e9ecef;"><option value="si" data-p="0" <?php selected($guarda_data['mascota_sociable_ninios'], 'si'); ?>><?php _e('Es Sociable', 'pethomehoney-plugin'); ?></option><option value="no" data-p="20" <?php selected($guarda_data['mascota_sociable_ninios'], 'no'); ?>><?php _e('No es Sociable (+20 %)', 'pethomehoney-plugin'); ?></option></select></div>
                 <div style="grid-column: 1 / -1;"><label for="reserva_observaciones_mascota"><?php _e('Observaciones de la Mascota (para la reserva)', 'pethomehoney-plugin'); ?></label><textarea id="reserva_observaciones_mascota" name="pethome_reserva_observaciones" rows="3" placeholder="<?php esc_attr_e('Notas adicionales sobre la mascota, comportamiento, hábitos, miedos, etc.', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef;"><?php echo esc_textarea($guarda_data['reserva_observaciones']); ?></textarea></div>
             </div>
        </div>

        <div class="pethome-section">
            <h2><?php _e('Sanidad', 'pethomehoney-plugin'); ?></h2>
            <div class="pethome-details-grid">
                <div><label for="pethome_mascota_enfermedades"><?php _e('Enfermedades/Condiciones Médicas', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_mascota_enfermedades" name="pethome_mascota_enfermedades" value="<?php echo esc_attr($guarda_data['mascota_enfermedades']); ?>" placeholder="<?php esc_attr_e('Separar por comas', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef;"></div>
                <div><label for="pethome_mascota_medicamentos"><?php _e('Medicamentos (cuáles y cada cuánto)', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_mascota_medicamentos" name="pethome_mascota_medicamentos" value="<?php echo esc_attr($guarda_data['mascota_medicamentos']); ?>" placeholder="<?php esc_attr_e('Ej: Insulina (1 vez al día)', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef;"></div>
                <div><label for="pethome_mascota_alergias"><?php _e('Alergias (alimentos, medicamentos, etc.)', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_mascota_alergias" name="pethome_mascota_alergias" value="<?php echo esc_attr($guarda_data['mascota_alergias']); ?>" placeholder="<?php esc_attr_e('Separar por comas', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef;"></div>
                <div><label for="vacunacion"><?php _e('Vacunación', 'pethomehoney-plugin'); ?></label><select id="vacunacion" name="pethome_mascota_vacunas_completas" data-p-select style="background-color: #e9ecef;"><option value="vacunado" data-p="0" <?php selected($guarda_data['mascota_vacunas_completas'], 'vacunado'); ?>><?php _e('Vacunado', 'pethomehoney-plugin'); ?></option><option value="sin_vacuna" data-p="5" <?php selected($guarda_data['mascota_vacunas_completas'], 'sin_vacuna'); ?>><?php _e('Sin Vacunar (+5 %)', 'pethomehoney-plugin'); ?></option></select></div>
                <div><label for="pethome_mascota_desparasitado"><?php _e('Desparasitado', 'pethomehoney-plugin'); ?></label><select id="pethome_mascota_desparasitado" name="pethome_mascota_desparasitado" style="background-color: #e9ecef;"><option value=""><?php _e('Seleccionar', 'pethomehoney-plugin'); ?></option><option value="si" <?php selected($guarda_data['mascota_desparasitado'], 'si'); ?>><?php _e('Sí', 'pethomehoney-plugin'); ?></option> <option value="no" <?php selected($guarda_data['mascota_desparasitado'], 'no'); ?>><?php _e('No', 'pethomehoney-plugin'); ?></option></select></div>
                <div><label for="pethome_mascota_antipulgas"><?php _e('Antipulgas/Garrapatas', 'pethomehoney-plugin'); ?></label><select id="pethome_mascota_antipulgas" name="pethome_mascota_antipulgas" style="background-color: #e9ecef;"><option value=""><?php _e('Seleccionar', 'pethomehoney-plugin'); ?></option><option value="si" <?php selected($guarda_data['mascota_antipulgas'], 'si'); ?>><?php _e('Sí', 'pethomehoney-plugin'); ?></option> <option value="no" <?php selected($guarda_data['mascota_antipulgas'], 'no'); ?>><?php _e('No', 'pethomehoney-plugin'); ?></option></select></div>
                <div><label for="pethome_mascota_veterinario_nombre"><?php _e('Veterinario (Nombre)', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_mascota_veterinario_nombre" name="pethome_mascota_veterinario_nombre" value="<?php echo esc_attr($guarda_data['mascota_veterinario_nombre']); ?>" placeholder="<?php esc_attr_e('Nombre del veterinario', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef;"></div>
                <div><label for="pethome_mascota_veterinario_telefono"><?php _e('Veterinario (Teléfono)', 'pethomehoney-plugin'); ?></label><input type="text" id="pethome_mascota_veterinario_telefono" name="pethome_mascota_veterinario_telefono" value="<?php echo esc_attr($guarda_data['mascota_veterinario_telefono']); ?>" placeholder="<?php esc_attr_e('Teléfono del veterinario', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef;"></div>
                <div style="grid-column: 1 / -1;"><label for="pethome_mascota_observaciones_sanidad"><?php _e('Observaciones de Sanidad', 'pethomehoney-plugin'); ?></label><textarea id="pethome_mascota_observaciones_sanidad" name="pethome_mascota_observaciones_sanidad" rows="3" placeholder="<?php esc_attr_e('Historial médico, restricciones dietéticas, etc.', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef;"><?php echo esc_textarea($guarda_data['mascota_observaciones_sanidad']); ?></textarea></div>
            </div>
        </div>

        <div class="pethome-section">
            <h2><?php _e('Seguridad', 'pethomehoney-plugin'); ?></h2>
            <div class="pethome-details-grid">
                <div><label for="pethome_mascota_chip"><?php _e('¿Tiene Chip de Identificación?', 'pethomehoney-plugin'); ?></label><select id="pethome_mascota_chip" name="pethome_mascota_chip" style="background-color: #e9ecef;"><option value=""><?php _e('Seleccionar', 'pethomehoney-plugin'); ?></option><option value="si" <?php selected($guarda_data['mascota_chip'], 'si'); ?>><?php _e('Sí', 'pethomehoney-plugin'); ?></option> <option value="no" <?php selected($guarda_data['mascota_chip'], 'no'); ?>><?php _e('No', 'pethomehoney-plugin'); ?></option></select></div>
                <div><label for="pethome_mascota_collar_identificacion"><?php _e('¿Usa Collar con Identificación?', 'pethomehoney-plugin'); ?></label><select id="pethome_mascota_collar_identificacion" name="pethome_mascota_collar_identificacion" style="background-color: #e9ecef;"><option value=""><?php _e('Seleccionar', 'pethomehoney-plugin'); ?></option><option value="si" <?php selected($guarda_data['mascota_collar_identificacion'], 'si'); ?>><?php _e('Sí', 'pethomehoney-plugin'); ?></option> <option value="no" <?php selected($guarda_data['mascota_collar_identificacion'], 'no'); ?>><?php _e('No', 'pethomehoney-plugin'); ?></option></select></div>
                <div><label for="pethome_mascota_con_correa"><?php _e('¿Con correa?', 'pethomehoney-plugin'); ?></label><select id="pethome_mascota_con_correa" name="pethome_mascota_con_correa" style="background-color: #e9ecef;"><option value=""><?php _e('Seleccionar', 'pethomehoney-plugin'); ?></option><option value="si" <?php selected($guarda_data['mascota_con_correa'], 'si'); ?>><?php _e('Sí', 'pethomehoney-plugin'); ?></option><option value="no" <?php selected($guarda_data['mascota_con_correa'], 'no'); ?>><?php _e('No', 'pethomehoney-plugin'); ?></option></select></div>
                <div><label for="pechera"><?php _e('Pechera', 'pethomehoney-plugin'); ?></label><select id="pechera" name="pethome_mascota_con_pechera" data-p-select style="background-color: #e9ecef;"><option value="con" data-p="0" <?php selected($guarda_data['mascota_con_pechera'], 'con'); ?>><?php _e('Con Pechera', 'pethomehoney-plugin'); ?></option><option value="sin" data-p="20" <?php selected($guarda_data['mascota_con_pechera'], 'sin'); ?>><?php _e('Sin Pechera (+20 %)', 'pethomehoney-plugin'); ?></option></select></div>
                <div><label for="seguro"><?php _e('Seguro', 'pethomehoney-plugin'); ?></label><select id="seguro" name="pethome_mascota_cobertura_salud" data-p-select style="background-color: #e9ecef;"><option value="con_cobertura" data-p="-10" <?php selected($guarda_data['mascota_cobertura_salud'], 'con_cobertura'); ?>><?php _e('Tengo cobertura de salud (-10 %)', 'pethomehoney-plugin'); ?></option><option value="sin_cobertura" data-p="0" <?php selected($guarda_data['mascota_cobertura_salud'], 'sin_cobertura'); ?>><?php _e('No tengo cobertura', 'pethomehoney-plugin'); ?></option></select></div>
                <div style="grid-column: 1 / -1;"><label for="pethome_mascota_observaciones_seguridad"><?php _e('Observaciones de Seguridad', 'pethomehoney-plugin'); ?></label><textarea id="pethome_mascota_observaciones_seguridad" name="pethome_mascota_observaciones_seguridad" rows="3" placeholder="<?php esc_attr_e('Comportamiento en la calle, manejo de correa, etc.', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef;"><?php echo esc_textarea($guarda_data['mascota_observaciones_seguridad']); ?></textarea></div>
            </div>
        </div>

        <div class="pethome-section">
            <h2><?php _e('Asignación y Costos Finales', 'pethomehoney-plugin'); ?></h2>
            <div class="cuidador-costo-wrapper">
                <div class="cuidador-field">
                    <label for="reserva_cuidador_asignado"><?php _e('Cuidador Asignado', 'pethomehoney-plugin'); ?></label>
                    <input type="text" id="reserva_cuidador_asignado" name="pethome_reserva_cuidador_asignado" value="<?php echo esc_attr($guarda_data['reserva_cuidador_asignado']); ?>" placeholder="<?php esc_attr_e('Nombre del cuidador', 'pethomehoney-plugin'); ?>" style="background-color: #e9ecef;">
                </div>
                <div class="costo-guarda-field">
                    <label for="pethome_costo_guarda_select"><?php _e('Costo Guarda', 'pethomehoney-plugin'); ?></label>
                    <select id="pethome_costo_guarda_select" name="pethome_reserva_costo_guarda_id" style="background-color: #e9ecef; width:100%; padding: 8px; border-radius: 4px; border: 1px solid #7e8993;">
                        <option value=""><?php _e('Automático por Servicio', 'pethomehoney-plugin'); ?></option>
                        <?php
                        $selected_cost_id = $guarda_data['reserva_costo_guarda_id'];
                        if (is_array($costos_configs)) {
                            foreach ($costos_configs as $config) {
                                if (isset($config['id']) && isset($config['name'])) {
                                    echo '<option value="' . esc_attr($config['id']) . '" ' . selected($selected_cost_id, $config['id'], false) . '>' . esc_html($config['name']) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="costo-calculado-field">
                    <label><?php _e('Costo Base Calculado', 'pethomehoney-plugin'); ?></label>
                    <p id="costo_total_calculado_display" class="display-value">$ 0,00</p>
                </div>
            </div>
        </div>

        <button type="submit" name="guardar_guarda" class="button button-primary button-large" style="margin-top: 20px;"><?php echo $post_id ? __('Actualizar Reserva', 'pethomehoney-plugin') : __('Crear Reserva', 'pethomehoney-plugin'); ?></button>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    const pethomeCostData = <?php echo json_encode($cost_data_for_js); ?>;
    const pethomeAllCostConfigs = <?php echo json_encode($all_costs_for_js); ?>;
    
    const calendarioFechasInput = document.getElementById('calendario_fechas');
    const servicioProductoSelect = document.getElementById('booking_product_or_service');
    const cantidadDiasDisplay = document.getElementById('cantidad_dias_reserva');
    const cantidadDiasHiddenInput = document.getElementById('pethome_reserva_cantidad_dias_hidden');
    const costoDiarioDisplay = document.getElementById('costo_diario_reserva');
    const subTotalBaseDisplay = document.getElementById('sub_total_reserva_display');
    const reservaCargosDisplay = document.getElementById('reserva_cargos_display');
    const reservaEntregaDisplay = document.getElementById('reserva_entrega_display');
    const precioTotalDisplay = document.getElementById('precio_total_reserva_display');
    const reservaSaldoFinalDisplay = document.getElementById('reserva_saldo_final_display');
    const fechasSeleccionadasTexto = document.getElementById('fechas_seleccionadas_texto');
    const tamanioMascotaSelect = document.getElementById('tamanio_mascota');
    const costoTotalCalculadoDisplay = document.getElementById('costo_total_calculado_display');
    const subTotalHiddenInput = document.getElementById('pethome_reserva_subtotal_hidden');
    const cargosHiddenInput = document.getElementById('pethome_reserva_cargos');
    const precioTotalHiddenInput = document.getElementById('pethome_reserva_precio_total_hidden');
    const entregaHiddenInput = document.getElementById('pethome_reserva_entrega_hidden');
    const saldoFinalHiddenInput = document.getElementById('pethome_reserva_saldo_final_hidden');
    const telefonoAreaInput = document.getElementById('pethome_cliente_telefono_area');
    const telefonoNumeroInput = document.getElementById('pethome_cliente_telefono_numero');
    const telefonoCompletoHiddenInput = document.getElementById('pethome_cliente_telefono_completo');
    const costoGuardaSelect = document.getElementById('pethome_costo_guarda_select');
    const tipoClienteSelect = document.getElementById('pethome_reserva_tipo_cliente_id');
    const $statusButtons = $('.pethome-status-buttons .button');
    const $priorityInput = $('#pethome_reserva_prioridad_status');
    let selectedDates = [];
    let flatpickrInstance;

    function formatCurrency(value, currencySymbol = '$') {
        const numValue = parseFloat(value);
        if (isNaN(numValue)) { return currencySymbol + ' 0,00'; }
        let parts = numValue.toFixed(2).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        return currencySymbol + parts.join(',');
    }

    function updateCalculations() {
        let cantidadDias = selectedDates.length;
        if (cantidadDiasDisplay) cantidadDiasDisplay.textContent = cantidadDias > 0 ? cantidadDias : '0';
        if (cantidadDiasHiddenInput) cantidadDiasHiddenInput.value = cantidadDias;

        // --- 1. Main Reservation Calculation (Based on selected Service/Product) ---
        const selectedServiceOption = servicioProductoSelect.options[servicioProductoSelect.selectedIndex];
        const costoDiario = parseFloat(selectedServiceOption.dataset.cost || '0');
        let reservaSubTotal = costoDiario * cantidadDias;

        if (costoDiarioDisplay) costoDiarioDisplay.textContent = formatCurrency(costoDiario);
        if (subTotalBaseDisplay) subTotalBaseDisplay.textContent = formatCurrency(reservaSubTotal);
        if (subTotalHiddenInput) subTotalHiddenInput.value = reservaSubTotal.toFixed(2);
        
        // --- 2. Separate Calculation for "Costo Base Calculado" field (Based on Costo Guarda tables) ---
        const manualCostConfigId = costoGuardaSelect ? costoGuardaSelect.value : null;
        let costConfigToUse = null;
        if (manualCostConfigId && pethomeAllCostConfigs[manualCostConfigId]) {
            costConfigToUse = pethomeAllCostConfigs[manualCostConfigId];
        }

        let costoBaseCalculado = 0;
        if (costConfigToUse && cantidadDias > 0) {
            let costoTotalAvanzado = 0;
            const tamano = tamanioMascotaSelect.value;
            let costoPequenoAnterior = parseFloat(costConfigToUse.costoBase) || 0;
            for (let i = 0; i < cantidadDias; i++) {
                const diaConfig = costConfigToUse.dias[i] || costConfigToUse.dias[costConfigToUse.dias.length - 1];
                const incrDia = parseFloat(diaConfig.incrDia) || 0;
                const porcPM = parseFloat(diaConfig.porcPM) || 0;
                const porcMG = parseFloat(diaConfig.porcMG) || 0;
                let costoPequenoActual = (i === 0) ? costoPequenoAnterior : costoPequenoAnterior * (1 + incrDia / 100);
                let costoDelDia = costoPequenoActual;
                if (tamano === 'mediano' || tamano === 'grande') {
                    const costoMediano = costoPequenoActual * (1 + porcPM / 100);
                    costoDelDia = costoMediano;
                    if (tamano === 'grande') {
                        const costoGrande = costoMediano * (1 + porcMG / 100);
                        costoDelDia = costoGrande;
                    }
                }
                costoTotalAvanzado += costoDelDia;
                costoPequenoAnterior = costoPequenoActual;
            }
            costoBaseCalculado = costoTotalAvanzado;
        }
        if (costoTotalCalculadoDisplay) costoTotalCalculadoDisplay.textContent = formatCurrency(costoBaseCalculado);

        // --- 3. Continue Main Reservation Calculation (Charges, Total, etc.) ---
        let totalPorcentajeRecargos = 0;
        document.querySelectorAll('select[data-p-select]').forEach(select => {
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption && selectedOption.dataset.p) {
                totalPorcentajeRecargos += (parseFloat(selectedOption.dataset.p) / 100);
            }
        });
        const montoTotalRecargosDescuentos = reservaSubTotal * totalPorcentajeRecargos;
        if (reservaCargosDisplay) reservaCargosDisplay.textContent = formatCurrency(montoTotalRecargosDescuentos);
        if (cargosHiddenInput) cargosHiddenInput.value = montoTotalRecargosDescuentos.toFixed(2);
        
        const totalConRecargos = reservaSubTotal + montoTotalRecargosDescuentos;

        let precioTotalFinal = totalConRecargos;
        if (tipoClienteSelect && tipoClienteSelect.value) {
            const selectedClientOption = tipoClienteSelect.options[tipoClienteSelect.selectedIndex];
            const clientModifierPercent = parseFloat(selectedClientOption.dataset.modifier || '0');
            if (clientModifierPercent !== 0) {
                const clientModifierAmount = totalConRecargos * (clientModifierPercent / 100);
                precioTotalFinal += clientModifierAmount;
            }
        }

        if (precioTotalDisplay) precioTotalDisplay.textContent = formatCurrency(precioTotalFinal);
        if (precioTotalHiddenInput) precioTotalHiddenInput.value = precioTotalFinal.toFixed(2);
        
        const entrega = precioTotalFinal * 0.10;
        if (reservaEntregaDisplay) reservaEntregaDisplay.textContent = formatCurrency(entrega);
        if (entregaHiddenInput) entregaHiddenInput.value = entrega.toFixed(2);

        const saldoFinal = precioTotalFinal - entrega;
        if (reservaSaldoFinalDisplay) reservaSaldoFinalDisplay.textContent = formatCurrency(saldoFinal);
        if (saldoFinalHiddenInput) saldoFinalHiddenInput.value = saldoFinal.toFixed(2);
        
        updateFechasSeleccionadasTexto();
    }
    
    function updateFechasSeleccionadasTexto() {
        if (fechasSeleccionadasTexto && flatpickrInstance && flatpickrInstance.selectedDates.length > 0) {
            const formattedDates = flatpickrInstance.selectedDates
                .sort((a, b) => a - b)
                .map(date => flatpickrInstance.formatDate(date, "d/m/Y"));
            fechasSeleccionadasTexto.value = formattedDates.join(', ');
        } else if (fechasSeleccionadasTexto) {
            fechasSeleccionadasTexto.value = '';
        }
    }
    
    const flatpickrContainer = document.getElementById('pethome_flatpickr_inline_calendar_container');
    if (calendarioFechasInput && flatpickrContainer) {
        let defaultDatesForPicker = [];
        if (calendarioFechasInput.value) {
            defaultDatesForPicker = calendarioFechasInput.value.split(',').map(d => d.trim()).filter(d => d);
        }
        flatpickrInstance = flatpickr(flatpickrContainer, {
            mode: "multiple",
            dateFormat: "Y-m-d",
            minDate: "today",
            locale: "es",
            inline: true,
            defaultDate: defaultDatesForPicker,
            onChange: function(selectedDatesArr, dateStr, instance) {
                selectedDates = selectedDatesArr.sort((a, b) => a - b);
                const formattedForInput = selectedDates.map(date => instance.formatDate(date, "Y-m-d"));
                calendarioFechasInput.value = formattedForInput.join(", ");
                updateCalculations();
            }
        });
    }
    
    if(servicioProductoSelect) servicioProductoSelect.addEventListener('change', updateCalculations);
    if(costoGuardaSelect) costoGuardaSelect.addEventListener('change', updateCalculations);
    if(tamanioMascotaSelect) tamanioMascotaSelect.addEventListener('change', updateCalculations);
    if(tipoClienteSelect) tipoClienteSelect.addEventListener('change', updateCalculations);
    document.querySelectorAll('select[data-p-select]').forEach(select => {
        select.addEventListener('change', updateCalculations);
    });
    
    const mascotaTipoSelect = document.getElementById('pethome_mascota_tipo');
    const mascotaRazaSelect = document.getElementById('pethome_mascota_raza');
    const razasPorTipo = <?php echo json_encode($razas_por_tipo); ?>;
    let currentSelectedRaza = "<?php echo esc_js($guarda_data['mascota_raza']); ?>";
    
    function updateRazas(isInitialLoad = false) {
        if (!mascotaTipoSelect || !mascotaRazaSelect) return;
        const selectedTipoValue = mascotaTipoSelect.value;
        const previousRazaValue = mascotaRazaSelect.value; 
        mascotaRazaSelect.innerHTML = '<option value=""><?php echo esc_js(__("Seleccionar raza", "pethomehoney-plugin")); ?></option>';
        if (selectedTipoValue && razasPorTipo[selectedTipoValue]) {
            razasPorTipo[selectedTipoValue].forEach(raza => {
                const option = document.createElement('option');
                option.value = raza; option.textContent = raza;
                if (isInitialLoad && raza === currentSelectedRaza) { option.selected = true; }
                mascotaRazaSelect.appendChild(option);
            });
             if (!isInitialLoad && razasPorTipo[selectedTipoValue].includes(previousRazaValue)) { mascotaRazaSelect.value = previousRazaValue; }
             else if (isInitialLoad && currentSelectedRaza && razasPorTipo[selectedTipoValue] && razasPorTipo[selectedTipoValue].includes(currentSelectedRaza)){ mascotaRazaSelect.value = currentSelectedRaza; }
        } else if (isInitialLoad && currentSelectedRaza && selectedTipoValue === "<?php echo esc_js(sanitize_title($guarda_data['mascota_tipo'])); ?>") {
             const option = document.createElement('option');
             option.value = currentSelectedRaza; option.textContent = currentSelectedRaza; option.selected = true;
             mascotaRazaSelect.appendChild(option);
        }
    }
    if(mascotaTipoSelect) {
        mascotaTipoSelect.addEventListener('change', function() { updateRazas(false); });
        updateRazas(true);
    }

    const dniInput = document.getElementById('pethome_cliente_dni');
    if (dniInput) {
        function formatDNIForDisplay(numericValue) {
            if (!numericValue) return '';
            const len = numericValue.length; let formattedDNI = '';
            if (len <= 2) { formattedDNI = numericValue; } 
            else if (len <= 5) { formattedDNI = numericValue.substring(0, len - 3) + '.' + numericValue.substring(len - 3); } 
            else { 
                const part3 = numericValue.substring(len - 3);
                const part2 = numericValue.substring(Math.max(0, len - 6), len - 3);
                const part1 = numericValue.substring(0, Math.max(0, len - 6));
                formattedDNI = (part1 ? part1 + '.' : '') + part2 + '.' + part3;
            }
            return formattedDNI;
        }
        dniInput.value = formatDNIForDisplay(dniInput.value.replace(/\D/g, '')); 
        dniInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 8) value = value.substring(0, 8);
            this.value = formatDNIForDisplay(value);
        });
    }

    function handleTelefonoInput() {
        if (!telefonoAreaInput || !telefonoNumeroInput || !telefonoCompletoHiddenInput) return;
        let area = telefonoAreaInput.value.replace(/\D/g, '').substring(0, 4);
        let numero = telefonoNumeroInput.value.replace(/\D/g, '').substring(0, 8);
        telefonoAreaInput.value = area;
        telefonoNumeroInput.value = numero;
        if (area.length >= 2 && numero.length >= 6) {
            telefonoCompletoHiddenInput.value = area + numero;
        } else {
            telefonoCompletoHiddenInput.value = '';
        }
    }
    if (telefonoAreaInput) telefonoAreaInput.addEventListener('input', handleTelefonoInput);
    if (telefonoNumeroInput) telefonoNumeroInput.addEventListener('input', handleTelefonoInput);
    handleTelefonoInput();

    $('.media-button').on('click', function(e){
        e.preventDefault();
        var targetInputId = $(this).data('target');
        var previewContainerId = $(this).data('preview');
        if(typeof wp === 'undefined' || typeof wp.media === 'undefined') { return; }
        var frame = wp.media({ title:'Seleccionar imagen', button:{text:'Usar esta imagen'}, multiple:false, library:{type:'image'} });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $('#' + targetInputId).val(attachment.id);
            $('#' + previewContainerId).html('<img src="' + attachment.url + '" alt="<?php esc_attr_e('Vista previa', 'pethomehoney-plugin'); ?>" style="max-width:150px; height:auto;">');
        });
        frame.open();
    });

    const clienteEmailInput = document.getElementById('pethome_cliente_email');
    if (clienteEmailInput) {
        const handleEmailInputFunction = function() {
            const email = this.value.trim();
            if (email && email.includes('@') && typeof ajax_object !== 'undefined' && ajax_object.ajax_url) {
                fetchClientData(email);
            }
        };
        clienteEmailInput.addEventListener('blur', handleEmailInputFunction);
        clienteEmailInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') { event.preventDefault(); handleEmailInputFunction.call(this); }
        });
    }

    function fetchClientData(email) {
        if (typeof ajax_object === 'undefined') { return; }
        $.ajax({
            url: ajax_object.ajax_url, type: 'POST',
            data: { action: 'pethome_get_client_data_by_email', email: email, nonce: ajax_object.nonce },
            success: function(response) {
                if (response.success && response.data) {
                    const d = response.data;
                    if(d.first_name && $('#pethome_cliente_nombre').length) $('#pethome_cliente_nombre').val(d.first_name);
                    if(d.last_name && $('#pethome_cliente_apellido').length) $('#pethome_cliente_apellido').val(d.last_name);
                    if (d.billing_phone && telefonoAreaInput && telefonoNumeroInput && telefonoCompletoHiddenInput) {
                        let phone_digits = d.billing_phone.replace(/\D/g, '');
                        if (phone_digits.startsWith('549')) { phone_digits = phone_digits.substring(3); }
                        else if (phone_digits.startsWith('54')) { phone_digits = phone_digits.substring(2); }
                        else if (phone_digits.startsWith('9') && phone_digits.length > 9) { phone_digits = phone_digits.substring(1); } 
                        let area = '', numero = '';
                        if (phone_digits.length === 9) { area = phone_digits.substring(0, 4); numero = phone_digits.substring(4); } 
                        else if (phone_digits.length === 10) {
                           let foundArea = false;
                           for(let i=4; i>=2; i--){
                               let potentialArea = phone_digits.substring(0,i);
                               if(phone_digits.length - i >= 6 && phone_digits.length - i <=8 ){ area = potentialArea; numero = phone_digits.substring(i); foundArea = true; break; }
                           }
                           if(!foundArea) { numero = phone_digits; }
                        } else { numero = phone_digits; }
                        telefonoAreaInput.value = area; telefonoNumeroInput.value = numero; handleTelefonoInput();
                    }
                    if(d.billing_address_1 && $('#pethome_cliente_calle').length) $('#pethome_cliente_calle').val(d.billing_address_1);
                    if(d.billing_address_2 && $('#pethome_cliente_numero').length) $('#pethome_cliente_numero').val(d.billing_address_2);
                    if(d.billing_city && $('#pethome_cliente_barrio').length) $('#pethome_cliente_barrio').val(d.billing_city);
                }
            },
            error: function(xhr, status, error) { console.error('Error AJAX:', status, error); }
        });
    }
    
    function setActivePriorityButton(selectedStatus) {
        $statusButtons.removeClass('active-status'); 
        $priorityInput.val(selectedStatus); 
        
        if (selectedStatus === 'urgente') { $('#btn_urgente').addClass('active-status'); } 
        else if (selectedStatus === 'normal') { $('#btn_normal').addClass('active-status'); } 
        else if (selectedStatus === 'pendiente') { $('#btn_pendiente').addClass('active-status'); }
    }
    $statusButtons.on('click', function() {
        var status = $(this).data('status'); 
        setActivePriorityButton(status);
    });
    var initialPriority = $priorityInput.val();
    setActivePriorityButton(initialPriority && ['urgente', 'normal', 'pendiente'].includes(initialPriority) ? initialPriority : 'normal'); 
    
    updateCalculations();
});
</script>