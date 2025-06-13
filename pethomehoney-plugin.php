<?php
/**
 * Plugin Name: PetHomeHoney Plugin
 * Plugin URI:  https://pethomehoney.com.ar
 * Description: Plugin para gestionar reservas de guarda con WooCommerce y CPT.
 * Version:     1.0 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrollado por www.streaminginternacional.com 
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'pethome_clientes_fusionar.php';


/**
 * 1) Registrar CPT “reserva_guarda”
 */
add_action('init', function () {
    $labels = [
        'name'               => 'Reservas de Guarda',
        'singular_name'      => 'Reserva de Guarda',
        'menu_name'          => 'Reservas de Guarda',
        'name_admin_bar'     => 'Reserva de Guarda',
        'all_items'          => 'Todas las Reservas',
        'add_new_item'       => 'Agregar Nueva Reserva',
        'edit_item'          => 'Editar Reserva',
        'new_item'           => 'Nueva Reserva',
        'view_item'          => 'Ver Reserva',
        'search_items'       => 'Buscar Reservas',
        'not_found'          => 'No se encontraron reservas',
        'not_found_in_trash' => 'No hay reservas en la papelera',
    ];
    register_post_type('reserva_guarda', [
        'labels'        => $labels,
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => false,
        'menu_position' => 57,
        'menu_icon'     => 'dashicons-calendar-alt',
        'supports'      => ['title', 'custom-fields'],
        'has_archive'   => false,
        'rewrite'       => false,
    ]);
});

/**
 * 2) Todo lo de admin (menús, assets, metaboxes, handlers…)
 */
if (is_admin()) {
    add_action('admin_menu', function () {
        add_menu_page('Guardería de Mascotas', 'Guardería de Mascotas', 'manage_options', 'pethome_main', function () {
            ?>
            <div class="wrap">
                <h1 style="color:#5e4365;">👋 Guardería de Mascotas</h1>
                <p style="font-size: 16px;">Gestioná reservas, cuidadores, estadísticas y configuración.</p>

                <div class="section-block">
                    <h2><i class="fa-thin fa-paw"></i>¿Qué es PetHomeHoney?</h2>
                    
                    <p>
                        ¡Bienvenido a <i class="fa-thin fa-house"></i> <strong>PetHomeHoney</strong>! Somos tu opción de guardería para mascotas en Córdoba, donde tu compañero peludo se sentirá como en su propio hogar. Nos diferenciamos por ofrecer un ambiente libre de caniles y jaulas.
                    </p>
                    <p>
                        Para tu <i class="fa-thin fa-dog"></i> amigo, esto significa disfrutar de paseos diarios y mucha libertad para explorar en un entorno seguro y supervisado. Entendemos que cada mascota es única, por eso te invitamos a traer sus objetos familiares para que su estadía sea aún más confortable.
                    </p>
                    <p>
                        Ya sea que tengas un <i class="fa-thin fa-dog"></i> juguetón o un <i class="fa-thin fa-cat"></i> curioso (¡que deberá venir con su transportín y arena!), en PetHomeHoney los recibimos con los brazos abiertos. Ofrecemos servicios flexibles por hora, día, semana o mes, adaptándonos a tus necesidades.
                    </p>
                    <p>
                        Para reservar tu lugar, solo tenés que completar nuestro formulario y abonar una seña del 10%. ¡Esperamos darle la bienvenida a tu consentido en PetHomeHoney!
                    </p>
                    
                    <img src="https://placehold.co/800x200/EEE/31343C?text=Mascotas+Felices+en+Nuestra+Guarder%C3%ADa" alt="Imagen de mascotas felices en PetHomeHoney" style="max-width: 100%; height: auto; border-radius: 6px; margin-top: 10px; display: block;">
                </div>

                <style type="text/css">
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
                        font-size: 20px;
                    }
                    .section-block h2 i {
                        margin-right: 10px;
                    }
                    .section-block p {
                        font-size: 16px;
                        line-height: 1.6;
                        margin-bottom: 12px;
                        padding: 0 5px;
                    }
                    .section-block p i {
                        margin-right: 8px;
                        color: #5e4365;
                        width: 20px;
                        text-align: center;
                        font-size: 18px;
                        vertical-align: -2px;
                    }
                    .section-block strong {
                        font-weight: 600;
                        color: #333;
                    }
                </style>

            </div>
            <?php
        }, 'dashicons-pets', 56);

        add_submenu_page('pethome_main', 'Agregar Guarda', 'Agregar Guarda', 'manage_options', 'pethome_guardas_agregar', function () {
            echo '<div class="wrap"><h1>Agregar Guarda</h1>';
            $file = plugin_dir_path(__FILE__) . 'pethome_guardas_agregar.php';
            if (file_exists($file)) {
                include $file;
            }
        });

        add_submenu_page('pethome_main', 'Panel de Reservas', 'Reservas', 'manage_options', 'pethome_reservas', function () {
            echo '<div class="wrap"><h1>Panel de Reservas</h1>';
            $file = plugin_dir_path(__FILE__) . 'pethome_reservas.php';
            if (file_exists($file)) {
                include_once $file;
                if (function_exists('pethome_reservas')) {
                    pethome_reservas();
                }
            }
        });

        add_submenu_page('pethome_main', 'Todas las Reservas', 'Todas las Reservas', 'manage_options', 'pethome_todas_las_reservas', 'pethome_todas_las_reservas_page_callback');
        add_submenu_page('pethome_main', 'Clientes', 'Clientes', 'manage_options', 'pethome_clientes', 'pethome_clientes_callback');
        
        add_submenu_page(null, 'Editar Cliente', 'Editar Cliente', 'manage_options', 'pethome_cliente_editar', 'pethome_cliente_editar_panel');

        add_submenu_page('pethome_main', 'Cuidadores', 'Cuidadores', 'manage_options', 'pethome_cuidadores', 'pethome_cuidadores_callback');
        add_submenu_page(null, 'Editar Cuidador', 'Editar Cuidador', 'manage_options', 'pethome_cuidador_editar', 'pethome_cuidador_editar_panel');
        
        add_submenu_page('pethome_main', 'Costos de Guardas', 'Costos Guardas', 'manage_options', 'pethome_costos_guardas', 'pethome_render_costos_page');
        add_submenu_page('pethome_main', 'Estadísticas', 'Estadísticas', 'manage_options', 'pethome_estadisticas', 'pethome_estadisticas_callback');
        add_submenu_page('pethome_main', 'Importar', 'Importar', 'manage_options', 'pethome_importador', 'pethome_importador_callback');
        
        add_submenu_page('pethome_main', 'Fusionar Clientes', 'Fusionar Clientes', 'manage_options', 'pethome_clientes_fusionar', 'pethome_clientes_fusionar_panel');

        add_submenu_page('pethome_main', 'Configuración', 'Configuración', 'manage_options', 'pethome_configuracion', 'pethome_configuracion_callback');
        
        // Eliminar el primer submenú que duplica el título principal
        remove_submenu_page('pethome_main', 'pethome_main');
    });
    
    function pethome_cliente_editar_panel() {
        $file = plugin_dir_path(__FILE__) . 'pethome_cliente_editar.php';
        if (file_exists($file)) { include $file; }
    }
    function pethome_cuidador_editar_panel() {
        $file = plugin_dir_path(__FILE__) . 'pethome_cuidador_editar.php';
        if (file_exists($file)) { include $file; }
    }
    function pethome_cuidadores_callback() {
        $file = plugin_dir_path(__FILE__) . 'pethome_cuidadores.php';
        if (file_exists($file)) { include_once $file; if (function_exists('pethome_cuidadores_panel')) { pethome_cuidadores_panel(); } }
    }
    function pethome_estadisticas_callback() {
        $file = plugin_dir_path(__FILE__) . 'pethome_estadisticas.php';
        if (file_exists($file)) { include_once $file; if (function_exists('pethome_estadisticas_panel')) { pethome_estadisticas_panel(); } }
    }
    function pethome_configuracion_callback() {
        $file = plugin_dir_path(__FILE__) . 'pethome_configuracion.php';
        if (file_exists($file)) { include_once $file; if (function_exists('pethome_configuracion_panel')) { pethome_configuracion_panel(); } }
    }
    function pethome_render_costos_page() {
        $file = plugin_dir_path(__FILE__) . 'pethome_costos_guardas.php';
        if (file_exists($file)) { include_once $file; if (function_exists('pethome_costos_guardas_panel')) { pethome_costos_guardas_panel(); } }
    }
    function pethome_todas_las_reservas_page_callback() {
        $file = plugin_dir_path(__FILE__) . 'pethome_todas_las_reservas.php';
        if (file_exists($file)) { include_once $file; if (function_exists('pethome_todas_las_reservas_panel')) { pethome_todas_las_reservas_panel(); } }
    }
    function pethome_importador_callback() {
        $file = plugin_dir_path(__FILE__) . 'pethome_importador.php';
        if (file_exists($file)) { include_once $file; if (function_exists('pethome_importador_panel')) { pethome_importador_panel(); } }
    }
    function pethome_clientes_callback() {
        $file = plugin_dir_path(__FILE__) . 'pethome_clientes.php';
        if (file_exists($file)) { include_once $file; if (function_exists('pethome_clientes_panel')) { pethome_clientes_panel(); } }
    }

    add_filter('parent_file', 'pethome_set_active_menu_for_cpt');
    function pethome_set_active_menu_for_cpt($parent_file) {
        global $current_screen, $pagenow;
        
        $base = $current_screen->base;

        if ($current_screen->post_type == 'reserva_guarda' && ($pagenow == 'post.php' || $pagenow == 'post-new.php')) {
            return 'pethome_main';
        }
        
        $pages_to_highlight = [
            'guarder-a-de-mascotas_page_pethome_cliente_editar',
            'guarder-a-de-mascotas_page_pethome_clientes_fusionar',
            'guarder-a-de-mascotas_page_pethome_cuidador_editar'
        ];
        if ( in_array($base, $pages_to_highlight) ) {
             return 'pethome_main';
        }

        return $parent_file;
    }

    add_action('admin_head', 'pethome_global_admin_styles');
    function pethome_global_admin_styles() {
        echo '<style>
            #toplevel_page_pethome_main.wp-has-current-submenu > a,
            #toplevel_page_pethome_main.wp-menu-open > a {
                background-color: #5e4365 !important;
                color: #fff !important;
            }
            #toplevel_page_pethome_main.wp-has-current-submenu .wp-menu-image::before,
            #toplevel_page_pethome_main.wp-menu-open .wp-menu-image::before {
                color: #fff !important;
            }
            #toplevel_page_pethome_main .wp-submenu {
                background-color: #4a3550;
            }
            #toplevel_page_pethome_main .wp-submenu a {
                color: #e0dce1 !important;
                padding-left: 30px !important;
            }
            #toplevel_page_pethome_main .wp-submenu a:hover {
                color: #ffffff !important;
                background-color: #553f5c;
            }
            #toplevel_page_pethome_main .wp-submenu li.current a,
            #toplevel_page_pethome_main .wp-submenu li.current a:hover {
                color: #ffffff !important;
                font-weight: bold;
            }
        </style>';
        
        $screen = get_current_screen();
        if ($screen && 'reserva_guarda' === $screen->post_type && $screen->base === 'post') {
            echo '<style>
                .postbox { border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.08); border-radius: 8px; }
                .postbox .hndle { background-color: #5e4365; color: #ffffff; border-top-left-radius: 7px; border-top-right-radius: 7px; border-bottom: 1px solid #4a3550; font-weight: 600; }
                .postbox .inside { padding: 15px !important; margin: 0 !important; }
                .pethome-metabox-section { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
                .pethome-metabox-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px 20px; }
                .pethome-metabox-item.full-width-item { grid-column: 1 / -1; }
                .pethome-metabox-item label { display: block; color: #555; font-size: 13px; margin-bottom: 4px; font-weight: 600; }
                .pethome-metabox-item input, .pethome-metabox-item select, .pethome-metabox-item textarea { width: 100%; padding: 8px; background-color: #e9ecef; border: 1px solid #ddd; border-radius: 4px; }
                .pethome-totals-metabox p { margin: 0 0 8px; }
                .pethome-totals-metabox .spinner { float: none; vertical-align: middle; }
                .horarios-input-linea, .address-input-linea, .telefono-input-linea { display: flex; align-items: center; gap: 10px; }
                .pethome-total-azul { color: #007BFF; } .pethome-total-verde { color: #28A745; } .pethome-total-rojo { color: #DC3545; }
                .pethome-status-buttons { display: flex; justify-content: space-between; margin-bottom: 15px; }
                .pethome-status-buttons .button { flex-grow: 1; margin: 0 2px; padding: 5px 10px; line-height: normal; height: auto; font-weight: 600; border-width: 1px; border-style: solid; transition: all 0.15s ease-in-out; cursor: pointer; }
                .pethome-button-urgente:not(.active-status) { background-color: transparent !important; color: #DC3545 !important; border-color: #DC3545 !important; }
                .pethome-button-normal:not(.active-status) { background-color: transparent !important; color: #28A745 !important; border-color: #28A745 !important; }
                .pethome-button-pendiente:not(.active-status) { background-color: transparent !important; color: #007BFF !important; border-color: #007BFF !important; }
                .pethome-button-urgente.active-status { background-color: #DC3545 !important; border-color: #DC3545 !important; color: white !important; }
                .pethome-button-normal.active-status { background-color: #28A745 !important; border-color: #28A745 !important; color: white !important; }
                .pethome-button-pendiente.active-status { background-color: #007BFF !important; border-color: #007BFF !important; color: white !important; }
                .pethome-resumen-metabox .resumen-details, .pethome-resumen-metabox .resumen-details p { font-size: 15px; }
                .pethome-resumen-metabox .resumen-details p { margin: 0 0 10px 0; display: flex; align-items: center; }
                .pethome-resumen-metabox .resumen-details p i { margin-right: 8px; color: #5e4365; width: 16px; text-align: center; }
                .pethome-resumen-metabox .resumen-details hr { border: 0; border-top: 1px solid #eee; margin: 10px 0; }
                .pethome-resumen-metabox .resumen-sub-detail { padding-left: 24px; margin-top: -8px !important; font-style: italic; font-size: 14px !important; }
                .pethome-resumen-metabox .resumen-obs { max-height: 80px; overflow-y: auto; background: #f9f9f9; border: 1px solid #eee; padding: 8px; border-radius: 4px; font-size: 13px; font-style: italic; }
            </style>';
        }
    }

    add_filter('default_hidden_meta_boxes', 'pethome_hide_custom_fields_metabox', 10, 2);
    function pethome_hide_custom_fields_metabox($hidden, $screen) {
        if (isset($screen->id) && 'reserva_guarda' === $screen->id) {
            $hidden[] = 'postcustom';
        }
        return $hidden;
    }

    add_action('admin_enqueue_scripts', function ($hook) {
        wp_enqueue_media();
        $font_awesome_path = plugin_dir_url(__FILE__) . 'fontawesome/css/all.min.css';
        wp_enqueue_style('pethome-fontawesome-pro', $font_awesome_path, [], '6.5.2');
        wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css', [], '4.6.13');
        $css_file_path = plugin_dir_path(__FILE__) . 'assets/css/pethome-agregar-guardas-styles.css';
        $custom_css_version = file_exists($css_file_path) ? filemtime($css_file_path) : '1.0';
        wp_enqueue_style('pethome-agregar-guardas-styles', plugin_dir_url(__FILE__) . 'assets/css/pethome-agregar-guardas-styles.css', ['flatpickr-css'], $custom_css_version);
        wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js', [], '4.6.13', true);
        wp_enqueue_script('flatpickr-es', 'https://npmcdn.com/flatpickr/dist/l10n/es.js', ['flatpickr-js'], '4.6.13', true);
        wp_localize_script('jquery', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('pethome_client_data_nonce')]);
        
        $current_screen = get_current_screen();
        if ($current_screen && $current_screen->id === 'guarder-a-de-mascotas_page_pethome_clientes_fusionar') {
            wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0-rc.0');
            wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);
        }
    });

    add_action('add_meta_boxes', function () {
        remove_post_type_support('reserva_guarda', 'editor');
        add_meta_box('pethome_reserva_details', __('Detalles Editables de la Reserva', 'pethomehoney-plugin'), 'pethomehoney_reserva_details_cb', 'reserva_guarda', 'normal', 'high');
        add_meta_box('pethome_mascota_imagen', __('Imagen de la Mascota', 'pethomehoney-plugin'), 'pethomehoney_mascota_imagen_cb', 'reserva_guarda', 'side', 'high');
        add_meta_box('pethome_reserva_totales', __('Totales y Estado de Pago', 'pethomehoney-plugin'), 'pethomehoney_reserva_totales_cb', 'reserva_guarda', 'side', 'default');
        add_meta_box('pethome_resumen_guarda_metabox', __('Resumen de Guarda', 'pethomehoney-plugin'), 'pethomehoney_resumen_guarda_cb', 'reserva_guarda', 'side', 'high');
    });

    if (!function_exists('pethome_get_booking_daily_cost')) { 
        function pethome_get_booking_daily_cost( $product_id ) {
            $block_cost = (float) get_post_meta( $product_id, '_wc_booking_block_cost', true );
            $base_cost  = (float) get_post_meta( $product_id, '_wc_booking_cost', true );
            if ( $block_cost > 0 ) return $block_cost;
            if ( $base_cost > 0 ) return $base_cost;
            return 0;
        }
    }
    
    function pethomehoney_reserva_details_cb($post){
        wp_nonce_field('pethome_update_reserva_details_action', 'pethome_reserva_details_nonce');
        $bookings = wc_get_products( [ 'type' => 'booking', 'limit' => -1 ] );
        $servicios_creados = get_option( 'pethome_precios_base', [] );
        $yes_no_options = ['si' => 'Sí', 'no' => 'No'];
        $fields_config = [
            'Reserva' => ['icon' => 'fa-thin fa-calendar-check', 'items' => [
                'pethome_reserva_servicio' => ['label' => 'Servicio Contratado', 'type' => 'service_select', 'bookings' => $bookings, 'servicios_creados' => $servicios_creados],
                'pethome_reserva_fechas' => ['label' => 'Fechas Reservadas', 'type' => 'text'],
                'pethome_reserva_cantidad_dias' => ['label' => 'Cantidad de Días', 'type' => 'number'],
                'pethome_horarios' => ['label' => 'Horarios (Ingreso / Egreso)', 'type' => 'horarios_split'],
            ]],
            'Cliente' => ['icon' => 'fa-thin fa-user', 'items' => [
                'pethome_cliente_nombre' => ['label' => 'Nombre', 'type' => 'text'],
                'pethome_cliente_apellido' => ['label' => 'Apellido', 'type' => 'text'],
                'pethome_cliente_dni' => ['label' => 'DNI', 'type' => 'text', 'id' => 'pethome_cliente_dni_metabox'],
                'pethome_cliente_email' => ['label' => 'Email', 'type' => 'email'],
                'pethome_cliente_alias_bancario' => ['label' => 'Alias Bancario', 'type' => 'text'],
                'pethome_cliente_telefono' => ['label' => 'Teléfono', 'type' => 'tel_split'],
                'pethome_cliente_direccion' => ['label' => 'Dirección', 'type' => 'address_split'],
            ]],
            'Mascota' => ['icon' => 'fa-thin fa-paw', 'items' => [
                'pethome_mascota_nombre' => ['label' => 'Nombre', 'type' => 'text'],
                'pethome_mascota_tipo' => ['label' => 'Tipo', 'type' => 'text'],
                'pethome_mascota_raza' => ['label' => 'Raza', 'type' => 'text'],
                'pethome_mascota_edad' => ['label' => 'Edad (Años)', 'type' => 'number'],
                'pethome_mascota_edad_meses' => ['label' => 'Edad (Meses)', 'type' => 'number'],
                'pethome_mascota_sexo' => ['label' => 'Sexo', 'type' => 'select', 'options' => ['' => 'Seleccionar', 'macho' => 'Macho', 'hembra' => 'Hembra']],
                'pethome_mascota_tamano' => ['label' => 'Tamaño', 'type' => 'select_with_p', 'options' => ['chico' => ['label'=>'Chico', 'p'=>0], 'mediano' => ['label'=>'Mediano', 'p'=>25], 'grande' => ['label'=>'Grande', 'p'=>50]]],
                'pethome_mascota_castrada' => ['label' => 'Castrado', 'type' => 'select_with_p', 'options' => ['castrado' => ['label'=>'Sí', 'p'=>0], 'no_castrado' => ['label'=>'No (+2%)', 'p'=>2]]],
                'pethome_mascota_sociable_perros' => ['label' => 'Sociable con Perros', 'type' => 'select_with_p', 'options' => ['si' => ['label'=>'Sí', 'p'=>0], 'no' => ['label'=>'No (+20%)', 'p'=>20]]],
                'pethome_mascota_sociable_ninios' => ['label' => 'Sociable con Niños', 'type' => 'select_with_p', 'options' => ['si' => ['label'=>'Sí', 'p'=>0], 'no' => ['label'=>'No (+20%)', 'p'=>20]]],
                'pethome_mascota_vacunas_completas' => ['label' => 'Vacunación', 'type' => 'select_with_p', 'options' => ['vacunado' => ['label'=>'Vacunado', 'p'=>0], 'sin_vacuna' => ['label'=>'Sin Vacunar (+5%)', 'p'=>5]]],
                'pethome_mascota_desparasitado' => ['label' => 'Desparasitado', 'type' => 'select', 'options' => $yes_no_options],
                'pethome_mascota_antipulgas' => ['label' => 'Antipulgas', 'type' => 'select', 'options' => $yes_no_options],
                'pethome_mascota_con_pechera' => ['label' => 'Usa Pechera', 'type' => 'select_with_p', 'options' => ['con' => ['label'=>'Sí', 'p'=>0], 'sin' => ['label'=>'No (+20%)', 'p'=>20]]],
                'pethome_mascota_cobertura_salud' => ['label' => 'Cobertura de Salud', 'type' => 'select_with_p', 'options' => ['con_cobertura' => ['label'=>'Sí (-10%)', 'p'=>-10], 'sin_cobertura' => ['label'=>'No', 'p'=>0]]],
                'pethome_mascota_enfermedades' => ['label' => 'Enfermedades', 'type' => 'text'],
                'pethome_mascota_medicamentos' => ['label' => 'Medicamentos', 'type' => 'text'],
                'pethome_mascota_alergias' => ['label' => 'Alergias', 'type' => 'text'],
                'pethome_mascota_veterinario_nombre' => ['label' => 'Veterinario', 'type' => 'text'],
                'pethome_mascota_veterinario_telefono' => ['label' => 'Tel. Veterinario', 'type' => 'text'],
                'pethome_mascota_chip' => ['label' => 'Chip', 'type' => 'select', 'options' => $yes_no_options],
                'pethome_mascota_collar_identificacion' => ['label' => 'Collar Identificatorio', 'type' => 'select', 'options' => $yes_no_options],
                'pethome_mascota_con_correa' => ['label' => 'Usa Correa', 'type' => 'select', 'options' => $yes_no_options],
            ]],
            'Observaciones' => ['icon' => 'fa-thin fa-file-lines', 'items' => [
                'pethome_reserva_observaciones' => ['label' => 'Observaciones de la Reserva', 'type' => 'textarea'],
            ]]
        ];
        ?>
        <div class="pethome-metabox-container">
            <?php foreach ($fields_config as $section_title => $section_data) : ?>
                <div class="pethome-metabox-section">
                    <h3><i class="<?php echo esc_attr($section_data['icon']); ?>"></i> <?php echo esc_html($section_title); ?></h3>
                    <div class="pethome-metabox-grid">
                        <?php foreach ($section_data['items'] as $meta_key => $field) : ?>
                            <?php
                            $item_classes = 'pethome-metabox-item';
                            if ( in_array($meta_key, ['pethome_reserva_fechas', 'pethome_reserva_observaciones']) || in_array($field['type'], ['address_split', 'service_select']) ) {
                                $item_classes .= ' full-width-item';
                            }
                            ?>
                            <div class="<?php echo esc_attr($item_classes); ?>">
                                <label for="<?php echo esc_attr($field['id'] ?? $meta_key); ?>"><?php echo esc_html($field['label']); ?></label>
                                <?php $value = get_post_meta($post->ID, $meta_key, true); ?>

                                <?php if ($field['type'] === 'textarea') : ?>
                                    <textarea id="<?php echo esc_attr($meta_key); ?>" name="<?php echo esc_attr($meta_key); ?>" class="widefat"><?php echo esc_textarea($value); ?></textarea>
                                <?php elseif ($field['type'] === 'select'): ?>
                                    <select id="<?php echo esc_attr($meta_key); ?>" name="<?php echo esc_attr($meta_key); ?>">
                                        <?php foreach ($field['options'] as $opt_val => $opt_label): ?>
                                            <option value="<?php echo esc_attr($opt_val); ?>" <?php selected($value, $opt_val); ?>><?php echo esc_html($opt_label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($field['type'] === 'select_with_p'): ?>
                                    <select id="<?php echo esc_attr($meta_key); ?>" name="<?php echo esc_attr($meta_key); ?>" data-p-select>
                                        <?php foreach ($field['options'] as $opt_val => $opt_data): ?>
                                            <option value="<?php echo esc_attr($opt_val); ?>" data-p="<?php echo esc_attr($opt_data['p']); ?>" <?php selected($value, $opt_val); ?>><?php echo esc_html($opt_data['label']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($field['type'] === 'service_select'): ?>
                                    <select id="<?php echo esc_attr($meta_key); ?>" name="<?php echo esc_attr($meta_key); ?>">
                                        <option value="" data-cost="0"><?php _e('Seleccionar...', 'pethomehoney-plugin'); ?></option>
                                        <optgroup label="<?php _e('Productos de Booking', 'pethomehoney-plugin'); ?>">
                                            <?php foreach ( $field['bookings'] as $product ) :
                                                $product_cost = pethome_get_booking_daily_cost( $product->get_id() ); ?>
                                                <option value="product_id:<?php echo esc_attr( $product->get_id() ); ?>" data-cost="<?php echo esc_attr( $product_cost ); ?>" <?php selected( $value, 'product_id:' . $product->get_id() ); ?>>
                                                    <?php echo esc_html( $product->get_name() ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="<?php _e('Servicios Creados', 'pethomehoney-plugin'); ?>">
                                        <?php foreach ( $field['servicios_creados'] as $idx => $servicio ) :
                                                $servicio_nombre = isset($servicio['servicio']) ? $servicio['servicio'] : __('Servicio sin nombre', 'pethomehoney-plugin');
                                                $servicio_precio_base = isset($servicio['precio']) ? (float) $servicio['precio'] : 0.0;
                                                ?>
                                                <option value="custom_service:<?php echo esc_attr( $idx ); ?>" data-cost="<?php echo esc_attr( $servicio_precio_base ); ?>" <?php selected( $value, 'custom_service:' . $idx ); ?>>
                                                    <?php echo esc_html( $servicio_nombre ); ?>
                                                </option>
                                        <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                <?php elseif ($field['type'] === 'tel_split') : ?>
                                    <div class="telefono-input-linea">
                                        <span class="fijo">+54 9</span>
                                        <input type="text" id="pethome_cliente_telefono_area" name="pethome_cliente_telefono_area" placeholder="Cód. Área" style="width: 80px;">
                                        <input type="text" id="pethome_cliente_telefono_numero" name="pethome_cliente_telefono_numero" placeholder="Número" style="flex-grow:1;">
                                        <a href="#" id="pethome_whatsapp_link" target="_blank" class="whatsapp-link-metabox" style="display:none;"><i class="fa-brands fa-whatsapp"></i></a>
                                    </div>
                                    <input type="hidden" id="pethome_cliente_telefono" name="pethome_cliente_telefono" value="<?php echo esc_attr($value); ?>">
                                <?php elseif ($field['type'] === 'horarios_split') : ?>
                                    <div class="horarios-input-linea">
                                        <input type="time" name="pethome_reserva_hora_ingreso" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_reserva_hora_ingreso', true)); ?>">
                                        <input type="time" name="pethome_reserva_hora_egreso" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_reserva_hora_egreso', true)); ?>">
                                    </div>
                                <?php elseif ($field['type'] === 'address_split') : ?>
                                    <div class="address-input-linea">
                                        <input type="text" name="pethome_cliente_calle" placeholder="Calle" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_cliente_calle', true)); ?>" style="flex-basis: 50%;">
                                        <input type="text" name="pethome_cliente_numero" placeholder="Número" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_cliente_numero', true)); ?>" style="flex-basis: 20%;">
                                        <input type="text" name="pethome_cliente_barrio" placeholder="Barrio" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_cliente_barrio', true)); ?>" style="flex-basis: 30%;">
                                    </div>
                                <?php else : ?>
                                    <input type="<?php echo esc_attr($field['type']); ?>" id="<?php echo esc_attr($field['id'] ?? $meta_key); ?>" name="<?php echo esc_attr($meta_key); ?>" value="<?php echo esc_attr($value); ?>" class="widefat">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <script>
            jQuery(document).ready(function($) {
                var dniInput = $('#pethome_cliente_dni_metabox');
                if (dniInput.length) {
                    dniInput.css('text-align', 'right');
                    function formatDNI(value) {
                        var dni = value.replace(/\D/g, '').substring(0, 8);
                        if (dni.length > 5) { return dni.slice(0, 2) + '.' + dni.slice(2, 5) + '.' + dni.slice(5); } 
                        else if (dni.length > 2) { return dni.slice(0, 2) + '.' + dni.slice(2); }
                        return dni;
                    }
                    dniInput.val(formatDNI(dniInput.val()));
                    dniInput.on('input', function(e) {
                        var selectionStart = e.target.selectionStart, originalLength = e.target.value.length;
                        var formattedValue = formatDNI(e.target.value);
                        $(this).val(formattedValue);
                        var newLength = formattedValue.length;
                        e.target.setSelectionRange(selectionStart + (newLength - originalLength), selectionStart + (newLength - originalLength));
                    });
                }
                var areaInput = $('#pethome_cliente_telefono_area'), numInput = $('#pethome_cliente_telefono_numero'),
                    fullNumHiddenInput = $('#pethome_cliente_telefono'), wppLink = $('#pethome_whatsapp_link');
                function updateWhatsappLink() {
                    var area = areaInput.val().replace(/\D/g, ''), num = numInput.val().replace(/\D/g, '');
                    if (area && num) { wppLink.attr('href', 'https://wa.me/549' + area + num).show(); } 
                    else { wppLink.hide(); }
                    fullNumHiddenInput.val(area + num);
                }
                function parseAndSetPhone() {
                    var fullNum = fullNumHiddenInput.val();
                    if(fullNum) {
                        var area = '', num = '';
                        if (fullNum.length > 7 && (fullNum.startsWith('11') || fullNum.length == 11)) { 
                             area = fullNum.substring(0, 4); num = fullNum.substring(4);
                        } else if (fullNum.length > 6) { 
                             area = fullNum.substring(0, 3); num = fullNum.substring(3);
                        } else { num = fullNum; }
                        areaInput.val(area); numInput.val(num);
                    }
                    updateWhatsappLink();
                }
                if (areaInput.length && numInput.length) { parseAndSetPhone(); areaInput.on('input', updateWhatsappLink); numInput.on('input', updateWhatsappLink); }
            });
        </script>
    <?php
    }

    function pethomehoney_mascota_imagen_cb($post) {
        $image_id = get_post_meta($post->ID, 'pethome_mascota_imagen_id', true);
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
        ?>
        <div class="pethome-image-uploader">
            <div id="pethome-image-preview-wrapper" style="margin-bottom: 10px; text-align: center;">
                <?php if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width:100%; height:auto; border-radius: 4px;">
                <?php else: ?>
                    <p>No hay imagen cargada.</p>
                <?php endif; ?>
            </div>
            <input type="hidden" name="pethome_mascota_imagen_id" id="pethome_mascota_imagen_id" value="<?php echo esc_attr($image_id); ?>">
            <button type="button" class="button" id="pethome-upload-image-button"><i class="fa-thin fa-upload"></i> <?php echo $image_id ? 'Cambiar Imagen' : 'Cargar Imagen'; ?></button>
            <button type="button" class="button button-link is-destructive" id="pethome-remove-image-button" style="display:<?php echo $image_id ? 'inline-block' : 'none'; ?>;">Quitar</button>
        </div>
        <script>
        jQuery(document).ready(function($){
            var frame;
            $('#pethome-upload-image-button').on('click', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({ title: 'Seleccionar o Cargar Imagen', button: { text: 'Usar esta imagen' }, multiple: false });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    var imageUrl = attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                    $('#pethome-image-preview-wrapper').html('<img src="' + imageUrl + '" style="max-width:100%; height:auto; border-radius: 4px;">');
                    $('#pethome_mascota_imagen_id').val(attachment.id);
                    $('#pethome-remove-image-button').show();
                    $('#pethome-upload-image-button').html('<i class="fa-thin fa-rotate"></i> Cambiar Imagen');
                });
                frame.open();
            });
            $('#pethome-remove-image-button').on('click', function(e) {
                e.preventDefault();
                $('#pethome-image-preview-wrapper').html('<p>No hay imagen cargada.</p>');
                $('#pethome_mascota_imagen_id').val('');
                $(this).hide();
                $('#pethome-upload-image-button').html('<i class="fa-thin fa-upload"></i> Cargar Imagen');
            });
        });
        </script>
        <?php
    }

    function pethomehoney_reserva_totales_cb($post) {
        wp_nonce_field('pethome_recalcular_costo_nonce', 'pethome_recalcular_costo_nonce_field');
        
        $costos_configs = get_option('pethomehoney_costos_guardas_configs', []);
        $costo_guarda_id = get_post_meta($post->ID, 'pethome_reserva_costo_guarda_id', true);
        $pago_anticipado = get_post_meta($post->ID, 'pethome_reserva_pago_anticipado', true);
        $estado_pago = get_post_meta($post->ID, 'pethome_reserva_estado_pago', true);
        $metodo_pago = get_post_meta($post->ID, 'pethome_reserva_metodo_pago', true);
    ?>
        <div class="pethome-totals-metabox">
             <p>
                <label for="pethome_reserva_costo_guarda_id" style="font-weight:bold;">Tabla de Costo Aplicada:</label>
            </p>
            <select name="pethome_reserva_costo_guarda_id" id="pethome_reserva_costo_guarda_id" class="widefat" style="margin-bottom: 12px;">
                <option value=""><?php _e('Automático por Servicio', 'pethomehoney-plugin'); ?></option>
                <?php
                if (is_array($costos_configs)) {
                    foreach ($costos_configs as $config) {
                        if (isset($config['id']) && isset($config['name'])) {
                            echo '<option value="' . esc_attr($config['id']) . '" ' . selected($costo_guarda_id, $config['id'], false) . '>' . esc_html($config['name']) . '</option>';
                        }
                    }
                }
                ?>
            </select>
            <hr>
            <p>
                <label><strong>Totales:</strong></label>
                <button type="button" id="recalcular_costo_btn" class="button button-small" style="float: right;"><i class="fa-thin fa-calculator"></i> Recalcular</button>
                <span class="spinner"></span>
            </p>
            <div id="pethome-financial-summary" style="margin-top: 10px; font-size: 13px;">
                <p style="display: flex; justify-content: space-between;"><span>Subtotal:</span> <strong id="display_subtotal">$0.00</strong></p>
                <p style="display: flex; justify-content: space-between;"><span>Cargos/Desc:</span> <span id="display_cargos">$0.00</span></p>
                <p style="display: flex; justify-content: space-between; border-top: 1px solid #eee; padding-top: 5px;"><strong class="pethome-total-azul">Total:</strong> <strong id="display_precio_total" class="pethome-total-azul">$0.00</strong></p>
                <hr>
                <p style="display: flex; justify-content: space-between;"><strong class="pethome-total-verde">Seña (10%):</strong> <strong id="display_entrega" class="pethome-total-verde">$0.00</strong></p>
                <p style="display: flex; justify-content: space-between;"><strong class="pethome-total-rojo">Saldo a Pagar:</strong> <strong id="display_saldo_final" class="pethome-total-rojo">$0.00</strong></p>
            </div>
            <hr>
            
            <input type="hidden" name="pethome_reserva_subtotal" id="pethome_reserva_subtotal" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_reserva_subtotal', true)); ?>">
            <input type="hidden" name="pethome_reserva_cargos" id="pethome_reserva_cargos" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_reserva_cargos', true)); ?>">
            <input type="hidden" name="pethome_reserva_precio_total" id="pethome_reserva_precio_total" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_reserva_precio_total', true)); ?>">
            <input type="hidden" name="pethome_reserva_entrega" id="pethome_reserva_entrega" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_reserva_entrega', true)); ?>">
            <input type="hidden" name="pethome_reserva_saldo_final" id="pethome_reserva_saldo_final" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_reserva_saldo_final', true)); ?>">
            
            <p><label for="pethome_reserva_pago_anticipado"><strong>Monto Pagado Manualmente ($):</strong></label></p>
            <input type="number" step="0.01" id="pethome_reserva_pago_anticipado" name="pethome_reserva_pago_anticipado" value="<?php echo esc_attr($pago_anticipado); ?>" class="widefat">
            <p><label for="pethome_reserva_estado_pago"><strong>Estado del Pago:</strong></label></p>
            <select id="pethome_reserva_estado_pago" name="pethome_reserva_estado_pago" class="widefat">
                <option value="no_pagada" <?php selected($estado_pago, 'no_pagada'); ?>>No Pagada</option>
                <option value="parcial" <?php selected($estado_pago, 'parcial'); ?>>Pago Parcial</option>
                <option value="pagada" <?php selected($estado_pago, 'pagada'); ?>>Pagada</option>
            </select>
            <p><label for="pethome_reserva_metodo_pago"><strong>Método de Pago:</strong></label></p>
            <input type="text" id="pethome_reserva_metodo_pago" name="pethome_reserva_metodo_pago" value="<?php echo esc_attr($metodo_pago); ?>" class="widefat">
        </div>
        <script>
            jQuery(document).ready(function($){
                function formatCurrency(value) { return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value); }
                function displayInitialTotals() {
                    $('#display_subtotal').text(formatCurrency($('#pethome_reserva_subtotal').val() || 0));
                    $('#display_cargos').text(formatCurrency($('#pethome_reserva_cargos').val() || 0));
                    $('#display_precio_total').text(formatCurrency($('#pethome_reserva_precio_total').val() || 0));
                    $('#display_entrega').text(formatCurrency($('#pethome_reserva_entrega').val() || 0));
                    $('#display_saldo_final').text(formatCurrency($('#pethome_reserva_saldo_final').val() || 0));
                }
                displayInitialTotals();
                
                function doRecalculation() {
                    var btn = $('#recalcular_costo_btn'), spinner = btn.next('.spinner');
                    var data_to_send = {
                        action: 'pethome_recalcular_costo', nonce: $('#pethome_recalcular_costo_nonce_field').val(),
                        dias: $('input[name="pethome_reserva_cantidad_dias"]').val(),
                        servicio_id: $('select[name="pethome_reserva_servicio"]').val(),
                        costo_guarda_id: $('#pethome_reserva_costo_guarda_id').val(),
                        modificadores: {}
                    };
                    $('select[data-p-select]').each(function() { var name = $(this).attr('name').replace('pethome_mascota_', ''); data_to_send.modificadores[name] = $(this).val(); });

                    if (!data_to_send.dias || data_to_send.dias <= 0) { alert('Por favor, ingrese una cantidad de días válida.'); return; }
                    if (!data_to_send.servicio_id && !data_to_send.costo_guarda_id) { alert('Por favor, seleccione un Servicio o una Tabla de Costo.'); return; }
                    
                    btn.prop('disabled', true); spinner.addClass('is-active');
                    $.ajax({
                        url: ajax_object.ajax_url, type: 'POST', data: data_to_send,
                        success: function(response) {
                            if(response.success) {
                                let data = response.data;
                                $('#display_subtotal').text(formatCurrency(data.subtotal));
                                $('#display_cargos').text(formatCurrency(data.cargos));
                                $('#display_precio_total').text(formatCurrency(data.precio_total));
                                $('#display_entrega').text(formatCurrency(data.entrega));
                                $('#display_saldo_final').text(formatCurrency(data.saldo_final));
                                $('#pethome_reserva_subtotal').val(data.subtotal.toFixed(2));
                                $('#pethome_reserva_cargos').val(data.cargos.toFixed(2));
                                $('#pethome_reserva_precio_total').val(data.precio_total.toFixed(2));
                                $('#pethome_reserva_entrega').val(data.entrega.toFixed(2));
                                $('#pethome_reserva_saldo_final').val(data.saldo_final.toFixed(2));
                            } else { alert('Error: ' + response.data.message); }
                        },
                        error: function() { alert('Ocurrió un error de conexión.'); },
                        complete: function() { spinner.removeClass('is-active'); btn.prop('disabled', false); }
                    });
                }
                
                $('#recalcular_costo_btn').on('click', doRecalculation);
                $('#pethome_reserva_costo_guarda_id').on('change', doRecalculation);
            });
        </script>
    <?php
    }

    function pethomehoney_resumen_guarda_cb($post) {
        $prioridad = get_post_meta($post->ID, 'pethome_reserva_prioridad', true) ?: 'normal';
    
        $costo_base_guarda = get_post_meta($post->ID, 'pethome_reserva_subtotal', true); 
        $nombre_mascota = get_post_meta($post->ID, 'pethome_mascota_nombre', true);
        $fechas_str = get_post_meta($post->ID, 'pethome_reserva_fechas', true);
        $hora_ingreso = get_post_meta($post->ID, 'pethome_reserva_hora_ingreso', true);
        $hora_egreso = get_post_meta($post->ID, 'pethome_reserva_hora_egreso', true);
        $tipo = get_post_meta($post->ID, 'pethome_mascota_tipo', true);
        $raza = get_post_meta($post->ID, 'pethome_mascota_raza', true);
        $sexo = get_post_meta($post->ID, 'pethome_mascota_sexo', true);
        $edad_anos = get_post_meta($post->ID, 'pethome_mascota_edad', true);
        $edad_meses = get_post_meta($post->ID, 'pethome_mascota_edad_meses', true);
        $tamano = get_post_meta($post->ID, 'pethome_mascota_tamano', true);
        $castracion_val = get_post_meta($post->ID, 'pethome_mascota_castrada', true);
        $pechera_val = get_post_meta($post->ID, 'pethome_mascota_con_pechera', true);
        $observaciones = get_post_meta($post->ID, 'pethome_reserva_observaciones', true);
        $image_id = get_post_meta($post->ID, 'pethome_mascota_imagen_id', true);
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';

        $whatsapp_link = '';
        $whatsapp_contacts = get_option('pethome_whatsapp_numbers', []);
        if (is_array($whatsapp_contacts)) {
            foreach ($whatsapp_contacts as $contact) {
                if (isset($contact['nombre']) && $contact['nombre'] === 'Grupo Publicación de Guardas') {
                    $is_group = (isset($contact['type']) && $contact['type'] === 'group');
                    $whatsapp_link = $is_group ? esc_url($contact['valor']) : 'https://wa.me/' . esc_attr($contact['valor']);
                    break;
                }
            }
        }

        $fecha_ingreso_str = 'N/A'; $fecha_egreso_str = 'N/A';
        if (!empty($fechas_str)) {
            $fechas = explode(',', str_replace(' a ', ',', $fechas_str)); $fechas = array_map('trim', $fechas);
            if (count($fechas) > 0) {
                try { $fecha_ingreso_dt = new DateTime($fechas[0]); $fecha_ingreso_str = $fecha_ingreso_dt->format('d/m/Y'); } catch(Exception $e) {}
                try { $fecha_egreso_dt = new DateTime(end($fechas)); $fecha_egreso_str = $fecha_egreso_dt->format('d/m/Y'); } catch(Exception $e) {}
            }
        }
        $edad_str = '';
        if (!empty($edad_anos)) $edad_str .= $edad_anos . ' años ';
        if (!empty($edad_meses)) $edad_str .= $edad_meses . ' meses';
        $castracion_texto = ($castracion_val === 'castrado') ? 'Sí' : 'No';
        $pechera_texto = ($pechera_val === 'con') ? 'Sí' : 'No';

        $texto_a_copiar  = "🐾 *Resumen de Guarda* 🐾\n\n";
        $texto_a_copiar .= "Reserva #: " . $post->ID . "\n";
        $texto_a_copiar .= "Mascota: " . ucfirst($nombre_mascota) . "\n";
        $texto_a_copiar .= "Tipo/Raza: " . ucfirst($tipo) . ' / ' . ucfirst($raza) . "\n";
        $texto_a_copiar .= "Ingreso: " . $fecha_ingreso_str . ' - ' . $hora_ingreso . "\n";
        $texto_a_copiar .= "Egreso: " . $fecha_egreso_str . ' - ' . $hora_egreso . "\n";
        if($image_url) {
            $texto_a_copiar .= "\nFoto de la mascota:\n" . $image_url;
        }

        ?>
        <div class="pethome-resumen-metabox">
            <div class="pethome-status-buttons">
                <button type="button" class="button pethome-button-urgente <?php echo ($prioridad === 'urgente') ? 'active-status' : ''; ?>" data-status="urgente">URGENTE</button>
                <button type="button" class="button pethome-button-normal <?php echo ($prioridad === 'normal') ? 'active-status' : ''; ?>" data-status="normal">NORMAL</button>
                <button type="button" class="button pethome-button-pendiente <?php echo ($prioridad === 'pendiente') ? 'active-status' : ''; ?>" data-status="pendiente">PENDIENTE</button>
                <input type="hidden" name="pethome_reserva_prioridad" id="pethome_reserva_prioridad_input" value="<?php echo esc_attr($prioridad); ?>">
            </div>

            <div class="resumen-details">
                <p><i class="fa-thin fa-hand-holding-dollar"></i><?php echo function_exists('wc_price') ? wc_price($costo_base_guarda) : '$' . number_format($costo_base_guarda, 2); ?></p>
                <hr>
                <p><i class="fa-thin fa-hashtag"></i> Reserva #<?php echo $post->ID; ?></p>
                <p><i class="fa-thin fa-paw"></i> <?php echo esc_html(ucfirst($nombre_mascota)); ?></p>
                <p class="resumen-sub-detail"><?php echo esc_html(ucfirst($tipo) . ' &bull; ' . ucfirst($raza)); ?></p>
                <hr>
                <p><i class="fa-thin fa-calendar-arrow-down"></i> <?php echo esc_html($fecha_ingreso_str . ' / ' . $hora_ingreso); ?></p>
                <p><i class="fa-thin fa-calendar-arrow-up"></i> <?php echo esc_html($fecha_egreso_str . ' / ' . $hora_egreso); ?></p>
                <hr>
                <p><i class="fa-thin fa-venus-mars"></i> <?php echo esc_html(ucfirst($sexo) . ' &bull; ' . trim($edad_str)); ?></p>
                <p><i class="fa-thin fa-ruler-combined"></i> <?php echo esc_html(ucfirst($tamano)); ?></p>
                <p><strong>Castración:</strong> <?php echo esc_html($castracion_texto); ?></p>
                <p><strong>Pechera:</strong> <?php echo esc_html($pechera_texto); ?></p>
                <?php if(!empty($observaciones)): ?>
                <hr>
                <div class="resumen-obs">
                    <p style="white-space: pre-wrap;"><?php echo esc_html($observaciones); ?></p>
                </div>
                <?php endif; ?>
                
                <hr>
                <div class="whatsapp-actions">
                    <button type="button" id="copy_summary_btn" class="button button-secondary" style="width:100%; text-align:center; margin-bottom: 5px;">
                        <i class="fa-thin fa-clipboard"></i> Copiar Resumen
                    </button>
                    <span id="copy-success-msg" style="color: green; display: none; text-align:center; font-size: 12px; font-style: italic;">¡Resumen copiado!</span>
                    <?php if (!empty($whatsapp_link)): ?>
                    <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="button" style="width: 100%; text-align: center; background-color: #25D366; color: white; border-color: #1ebe56; display: flex; justify-content: center; align-items: center; padding: 5px 0; margin-top: 5px;">
                        <i class="fa-brands fa-whatsapp" style="margin-right: 8px;"></i> Enviar a Grupo
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('.pethome-status-buttons .button').on('click', function() {
                    var status = $(this).data('status');
                    $('#pethome_reserva_prioridad_input').val(status);
                    $('.pethome-status-buttons .button').removeClass('active-status');
                    $(this).addClass('active-status');
                });

                $('#copy_summary_btn').on('click', function() {
                    const textToCopy = `<?php echo esc_js($texto_a_copiar); ?>`;
                    navigator.clipboard.writeText(textToCopy).then(function() {
                        const successMsg = $('#copy-success-msg');
                        successMsg.show();
                        setTimeout(function() {
                            successMsg.hide();
                        }, 2000);
                    }, function(err) {
                        alert('Error al copiar el texto.');
                    });
                });
            });
        </script>
        <?php
    }
    
    if (!function_exists('pethome_get_custom_service_name_by_id')) { function pethome_get_custom_service_name_by_id($service_id) { $custom_services = get_option('pethome_precios_base', []); return isset($custom_services[$service_id]['servicio']) ? $custom_services[$service_id]['servicio'] : 'Servicio Desconocido'; } }
    if (!function_exists('pethome_get_service_display_name')) { function pethome_get_service_display_name($service_key) { if (empty($service_key)) return 'No especificado'; $parts = explode(':', $service_key); if (count($parts) === 2) { $type = $parts[0]; $id = $parts[1]; switch ($type) { case 'product_id': if (function_exists('wc_get_product')) { $product = wc_get_product($id); return $product ? $product->get_name() : 'Producto no encontrado'; } break; case 'custom_service': return pethome_get_custom_service_name_by_id($id); break; } } return $service_key; } }
    if (!function_exists('pethome_get_status_badge')) { function pethome_get_status_badge($status) { $status_map = [ 'confirmed' => ['label' => 'Confirmada', 'color' => '#28a745'], 'paid' => ['label' => 'Pagada', 'color' => '#17a2b8'], 'pending-confirmation' => ['label' => 'Pendiente', 'color' => '#ffc107'], 'unpaid' => ['label' => 'No Pagada', 'color' => '#fd7e14'], 'cancelled' => ['label' => 'Cancelada', 'color' => '#6c757d'], 'complete' => ['label' => 'Completada', 'color' => '#007bff'], 'in-cart' => ['label' => 'En Carrito', 'color' => '#e83e8c'], 'urgente' => ['label' => 'Urgente', 'color' => '#dc3545'], 'normal' => ['label' => 'Normal', 'color' => '#28a745'], 'pendiente' => ['label' => 'Pendiente', 'color' => '#007BFF'], ]; $style = 'display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: bold; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 10px;'; $label = ucfirst($status); $color = '#6c757d'; if (isset($status_map[$status])) { $label = $status_map[$status]['label']; $color = $status_map[$status]['color']; } return '<span style="' . $style . ' background-color:' . esc_attr($color) . '">' . esc_html($label) . '</span>'; } }
    
    add_action('wp_ajax_pethome_recalcular_costo', 'pethome_recalcular_costo_callback');
    function pethome_recalcular_costo_callback() {
        check_ajax_referer('pethome_recalcular_costo_nonce', 'nonce');
        
        $dias = isset($_POST['dias']) ? intval($_POST['dias']) : 0;
        $servicio_id_str = isset($_POST['servicio_id']) ? sanitize_text_field($_POST['servicio_id']) : '';
        $costo_guarda_id = isset($_POST['costo_guarda_id']) ? sanitize_text_field($_POST['costo_guarda_id']) : null;
        $modificadores = isset($_POST['modificadores']) && is_array($_POST['modificadores']) ? $_POST['modificadores'] : [];

        if ($dias <= 0) { wp_send_json_error(['message' => 'La cantidad de días debe ser mayor a cero.']); }
        if (empty($servicio_id_str) && empty($costo_guarda_id)) { wp_send_json_error(['message' => 'Debe seleccionar un Servicio o una Tabla de Costo.']); }

        $subTotal = 0;
        
        if (!empty($costo_guarda_id)) {
            $costos_configs = get_option('pethomehoney_costos_guardas_configs', []);
            $cost_config_data = null;
            foreach($costos_configs as $config) {
                if (isset($config['id']) && $config['id'] === $costo_guarda_id) {
                    $cost_config_data = $config['data'];
                    break;
                }
            }
            if ($cost_config_data) {
                 $costo_diario = floatval($cost_config_data['costoBase'] ?? 0);
                 $subTotal = $costo_diario * $dias;
            }
        } else if (!empty($servicio_id_str)) {
            if (strpos($servicio_id_str, ':') === false) { wp_send_json_error(['message' => 'ID de servicio no válido.']); }
            list($type, $id) = explode(':', $servicio_id_str, 2);
            $costo_diario = 0;
            if ($type === 'product_id') { 
                $costo_diario = pethome_get_booking_daily_cost(intval($id));
            } elseif ($type === 'custom_service') {
                $servicios_creados = get_option('pethome_precios_base', []);
                $costo_diario = isset($servicios_creados[$id]['precio']) ? floatval($servicios_creados[$id]['precio']) : 0;
            }
            $subTotal = $costo_diario * $dias;
        }
        
        $mapa_porcentajes = [ 'tamano' => ['chico' => 0, 'mediano' => 25, 'grande' => 50], 'castrada' => ['castrado' => 0, 'no_castrado' => 2], 'sociable_perros' => ['si' => 0, 'no' => 20], 'sociable_ninios' => ['si' => 0, 'no' => 20], 'vacunas_completas' => ['vacunado' => 0, 'sin_vacuna' => 5], 'con_pechera' => ['con' => 0, 'sin' => 20], 'cobertura_salud' => ['con_cobertura' => -10, 'sin_cobertura' => 0] ];
        $totalPorcentajeRecargos = 0;
        foreach ($modificadores as $key => $value) {
            if (isset($mapa_porcentajes[$key][$value])) {
                $totalPorcentajeRecargos += floatval($mapa_porcentajes[$key][$value]);
            }
        }
        $montoTotalRecargosDescuentos = $subTotal * ($totalPorcentajeRecargos / 100);
        $precioTotal = $subTotal + $montoTotalRecargosDescuentos;
        
        $entrega = $precioTotal * 0.10;
        $saldoFinal = $precioTotal * 0.90;

        wp_send_json_success(['subtotal' => $subTotal, 'cargos' => $montoTotalRecargosDescuentos, 'precio_total' => $precioTotal, 'entrega' => $entrega, 'saldo_final' => $saldoFinal]);
    }

    add_action('wp_ajax_pethome_importar_clientes', 'pethome_importar_clientes_callback');
    function pethome_importar_clientes_callback() {
        check_ajax_referer('pethome_client_data_nonce', 'nonce');
        set_time_limit(300);

        $excluded_roles = get_option('pethome_importer_excluded_roles', ['employee', 'administrator']);
        $all_users = get_users(); 

        if (empty($all_users)) {
            wp_send_json_success(['nuevos' => 0, 'eliminados' => 0, 'existentes' => 0, 'message' => 'No se encontraron usuarios en el sitio.']);
            return;
        }
    
        $nuevos_clientes = 0;
        $clientes_eliminados = 0;
        $clientes_existentes = 0;
    
        foreach ($all_users as $user) {
            $user_id = $user->ID;
            $user_roles = (array) $user->roles;
            $is_client = get_user_meta($user_id, '_pethome_is_cliente', true) === '1';
    
            $is_excluded = !empty(array_intersect($user_roles, $excluded_roles));
    
            if ($is_excluded) {
                if ($is_client) {
                    delete_user_meta($user_id, '_pethome_is_cliente');
                    $clientes_eliminados++;
                }
            } else {
                if (!$is_client) {
                    update_user_meta($user_id, '_pethome_is_cliente', '1');
                    $nuevos_clientes++;
                } else {
                    $clientes_existentes++;
                }
            }
        }
    
        wp_send_json_success([
            'nuevos' => $nuevos_clientes,
            'eliminados' => $clientes_eliminados,
            'existentes' => $clientes_existentes,
            'message' => 'Proceso de sincronización completado.'
        ]);
    }

    add_action('wp_ajax_pethome_importar_cuidadores', 'pethome_importar_cuidadores_callback');
    function pethome_importar_cuidadores_callback() {
        check_ajax_referer('pethome_client_data_nonce', 'nonce');
        set_time_limit(300);

        $target_roles = ['employee', 'cuidador'];
        $cuidadores = get_option('pethome_cuidadores', []);

        $cuidadores_filtrados = array_filter($cuidadores, function($c) { 
            return is_array($c) && !empty($c['user_id']); 
        });
        $imported_user_ids = array_column($cuidadores_filtrados, 'user_id');

        $users_to_import = get_users(['role__in' => $target_roles]);

        if (empty($users_to_import)) {
            wp_send_json_success(['nuevos' => 0, 'existentes' => count($imported_user_ids), 'message' => 'No se encontraron nuevos usuarios con los roles requeridos ("Employee", "Cuidador").']);
            return;
        }

        $nuevos_cuidadores = 0;
        $cuidadores_existentes = 0;

        foreach ($users_to_import as $user) {
            if (in_array($user->ID, $imported_user_ids)) {
                $cuidadores_existentes++;
                continue;
            }

            $nuevo_cuidador = [
                "user_id"            => $user->ID,
                "alias"              => $user->user_login,
                "nombre"             => $user->first_name,
                "apellido"           => $user->last_name,
                "email"              => $user->user_email,
                "telefono"           => '',
                "calle"              => '',
                "numero"             => '',
                "piso"               => '',
                "barrio"             => '',
                "dni"                => '',
                "alias_bancario"     => '',
                "temp1"              => '',
                "imagen"             => '',
                "dni_frente"         => '',
                "dni_dorso"          => '',
                "phh"                => '',
                "domicilio_img"      => '',
            ];

            $cuidadores[] = $nuevo_cuidador;
            $nuevos_cuidadores++;
        }

        if ($nuevos_cuidadores > 0) {
            update_option('pethome_cuidadores', $cuidadores);
        }

        wp_send_json_success([
            'nuevos' => $nuevos_cuidadores,
            'existentes' => count($users_to_import) - $nuevos_cuidadores,
            'message' => 'Proceso de importación de cuidadores completado.'
        ]);
    }
    
    add_action('wp_ajax_pethome_reset_clientes', 'pethome_reset_clientes_callback');
    function pethome_reset_clientes_callback() {
        check_ajax_referer('pethome_client_data_nonce', 'nonce');

        $role_to_reset = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'all_roles';
        $args = [
            'meta_key'   => '_pethome_is_cliente',
            'meta_value' => '1',
            'fields'     => 'ID',
        ];
        if ($role_to_reset !== 'all_roles') {
            $args['role'] = $role_to_reset;
        }
        
        $client_users = get_users($args);

        if (empty($client_users)) {
            wp_send_json_success(['eliminados' => 0, 'message' => 'No se encontraron clientes para resetear con el filtro seleccionado.']);
        }

        $deleted_count = 0;
        foreach ($client_users as $user_id) {
            if (delete_user_meta($user_id, '_pethome_is_cliente')) {
                $deleted_count++;
            }
        }

        wp_send_json_success([
            'eliminados' => $deleted_count,
            'message' => 'Reseteo completado.'
        ]);
    }

    add_action('wp_ajax_pethome_remove_client_flag', 'pethome_remove_client_flag_callback');
    function pethome_remove_client_flag_callback() {
        check_ajax_referer('pethome_client_data_nonce', 'nonce');
        if (!isset($_POST['user_id']) || !current_user_can('edit_users')) {
            wp_send_json_error(['message' => 'Permiso denegado o ID de usuario no proporcionado.']);
        }
        $user_id = intval($_POST['user_id']);
        if(delete_user_meta($user_id, '_pethome_is_cliente')) {
            wp_send_json_success(['message' => 'El usuario ya no es un cliente de PetHome.']);
        } else {
            wp_send_json_error(['message' => 'No se pudo quitar la marca de cliente o ya no existía.']);
        }
    }

    add_action('wp_ajax_pethome_search_merge_users', 'pethome_search_merge_users_callback');
    add_action('wp_ajax_pethome_preview_user_merge', 'pethome_preview_user_merge_callback');
    add_action('wp_ajax_pethome_execute_user_merge', 'pethome_execute_user_merge_callback');

    add_action('save_post_reserva_guarda', 'pethome_save_reserva_guarda_meta_data', 10, 2);
    function pethome_save_reserva_guarda_meta_data($post_id, $post_object) {
        if (!isset($_POST['pethome_reserva_details_nonce']) || !wp_verify_nonce($_POST['pethome_reserva_details_nonce'], 'pethome_update_reserva_details_action')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields_to_save = [
            'pethome_cliente_nombre', 'pethome_cliente_apellido', 'pethome_cliente_dni', 'pethome_cliente_email', 'pethome_cliente_alias_bancario', 'pethome_cliente_telefono', 'pethome_cliente_calle', 'pethome_cliente_numero', 'pethome_cliente_barrio',
            'pethome_reserva_servicio', 'pethome_reserva_fechas', 'pethome_reserva_cantidad_dias', 'pethome_reserva_hora_ingreso', 'pethome_reserva_hora_egreso', 'pethome_reserva_observaciones',
            'pethome_mascota_nombre', 'pethome_mascota_tipo', 'pethome_mascota_raza', 'pethome_mascota_edad', 'pethome_mascota_edad_meses', 'pethome_mascota_sexo', 'pethome_mascota_tamano', 'pethome_mascota_castrada', 'pethome_mascota_sociable_perros', 'pethome_mascota_sociable_ninios', 'pethome_mascota_enfermedades', 'pethome_mascota_medicamentos', 'pethome_mascota_alergias', 'pethome_mascota_vacunas_completas', 'pethome_mascota_desparasitado', 'pethome_mascota_antipulgas', 'pethome_mascota_veterinario_nombre', 'pethome_mascota_veterinario_telefono', 'pethome_mascota_chip', 'pethome_mascota_collar_identificacion', 'pethome_mascota_con_correa', 'pethome_mascota_con_pechera', 'pethome_mascota_cobertura_salud',
            'pethome_mascota_imagen_id', 'pethome_reserva_prioridad', 'pethome_reserva_cuidador_asignado',
            'pethome_reserva_subtotal', 'pethome_reserva_cargos', 'pethome_reserva_precio_total', 'pethome_reserva_entrega', 'pethome_reserva_saldo_final', 'pethome_reserva_pago_anticipado', 'pethome_reserva_estado_pago', 'pethome_reserva_metodo_pago',
            'pethome_reserva_costo_guarda_id'
        ];

        foreach ($fields_to_save as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'pethome_cliente_dni') {
                    $dni_sin_formato = preg_replace('/\D/', '', $_POST[$field]);
                    update_post_meta($post_id, $field, sanitize_text_field($dni_sin_formato));
                } elseif (strpos($field, 'email') !== false) {
                    update_post_meta($post_id, $field, sanitize_email($_POST[$field]));
                } elseif (strpos($field, 'observaciones') !== false) {
                    update_post_meta($post_id, $field, sanitize_textarea_field($_POST[$field]));
                } else {
                    update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
                }
            }
        }
    }
    
/**
 * 6) Widget para el Escritorio de WordPress
 * Muestra estadísticas, accesos directos y últimas reservas.
 */
add_action('wp_dashboard_setup', 'pethome_registrar_widget_escritorio');

/**
 * Registra el widget en el escritorio de WordPress.
 */
function pethome_registrar_widget_escritorio() {
    // Aumentar la prioridad para que aparezca más arriba
    global $wp_meta_boxes;
    
    wp_add_dashboard_widget(
        'pethome_resumen_widget',
        '<i class="fa-thin fa-paw" style="margin-right: 8px;"></i> Resumen de PetHomeHoney',
        'pethome_render_widget_escritorio'
    );

    // Mover nuestro widget al principio
    $dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
    $my_widget = ['pethome_resumen_widget' => $dashboard['pethome_resumen_widget']];
    unset($dashboard['pethome_resumen_widget']);
    $sorted_dashboard = array_merge($my_widget, $dashboard);
    $wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
}

/**
 * Renderiza el contenido HTML y CSS del widget.
 */
function pethome_render_widget_escritorio() {
    // --- 1. OBTENER Y PROCESAR DATOS ---
    
    // -- Estadísticas Simples --
    $query_clientes = new WP_User_Query(['meta_key'   => '_pethome_is_cliente', 'meta_value' => '1', 'fields' => 'ID']);
    $total_clientes = $query_clientes->get_total();
    $cuidadores = get_option('pethome_cuidadores', []);
    $total_cuidadores = count($cuidadores);

    // -- Preparación para Gráficos y Top 10 --
    $cuidador_counts = array_fill_keys(array_keys($cuidadores), 0);
    $months = [];
    $reservations_by_month = [];
    $revenue_by_month = [];

    for ($i = 11; $i >= 0; $i--) {
        $month_key = date('Y-m', strtotime("-$i months"));
        $month_label = date_i18n('M Y', strtotime("-$i months"));
        $months[$month_key] = $month_label;
        $reservations_by_month[$month_key] = 0;
        $revenue_by_month[$month_key] = 0;
    }

    $all_items_query = new WP_Query([
        'post_type'      => ['reserva_guarda', 'wc_booking'],
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'date_query'     => [['after' => '1 year ago', 'inclusive' => true]],
    ]);

    if ($all_items_query->have_posts()) {
        while ($all_items_query->have_posts()) {
            $all_items_query->the_post();
            $item_id = get_the_ID();
            $item_type = get_post_type($item_id);
            $item_date_key = get_the_date('Y-m');
            
            // Sumar para los gráficos
            if (isset($months[$item_date_key])) {
                $reservations_by_month[$item_date_key]++;
                $monto = 0;
                if ($item_type === 'wc_booking') {
                    $booking = new WC_Booking($item_id);
                    $monto = floatval($booking->get_cost());
                } else {
                    $monto = floatval(get_post_meta($item_id, 'pethome_reserva_precio_total', true));
                }
                $revenue_by_month[$item_date_key] += $monto;
            }

            // Contar para el Top 10 Cuidadores
            $cuidador_id = get_post_meta($item_id, 'pethome_reserva_cuidador_asignado', true);
            if ($cuidador_id !== '' && isset($cuidador_counts[$cuidador_id])) {
                $cuidador_counts[$cuidador_id]++;
            }
        }
    }
    wp_reset_postdata();

    // Ordenar y cortar el Top 10
    arsort($cuidador_counts);
    $top_cuidadores = array_slice($cuidador_counts, 0, 10, true);

    $latest_reservations_query = new WP_Query([
        'post_type'      => ['reserva_guarda', 'wc_booking'], 'posts_per_page' => 5,
        'orderby'        => 'date', 'order' => 'DESC', 'post_status' => 'any',
    ]);


    // --- 2. ESTILOS DEL WIDGET ---
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .phh-widget-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .phh-stat-card { background-color: #f9f9f9; border: 2px solid #ccc; border-radius: 12px; padding: 15px; text-align: center; text-decoration: none; color: #333; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .phh-stat-card:hover { transform: translateY(-4px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-color: #5e4365; }
        .phh-stat-card .stat-icon { font-size: 28px; color: #5e4365; margin-bottom: 8px; }
        .phh-stat-card .stat-number { font-size: 32px; font-weight: bold; line-height: 1; color: #3c3c3c; margin: 0; }
        .phh-stat-card .stat-label { margin: 5px 0 0 0; font-weight: 600; color: #555; font-size: 13px; }
        
        .phh-section { border-top: 2px solid #eee; padding-top: 20px; margin-top: 25px; }
        .phh-section h3 { margin-top: 0; margin-bottom: 15px; text-align: center; color: #555; font-size: 14px; font-weight: bold; }
        
        .phh-main-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 1200px) { .phh-main-layout { grid-template-columns: 1fr; } }
        
        .chart-wrapper {
            height: 200px;
            margin-bottom: 25px;
        }
        .chart-wrapper:last-child {
            margin-bottom: 0;
        }

        .phh-links-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .phh-links-grid .phh-button { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; text-decoration: none; font-weight: bold; border-radius: 8px; background-color: #5e4365; color: #fff; transition: background-color 0.2s ease; }
        .phh-links-grid .phh-button:hover { background-color: #4a3550; }
        .phh-links-grid .phh-button.add-new { background-color: #28a745; }
        .phh-links-grid .phh-button.add-new:hover { background-color: #218838; }

        .phh-latest-list { list-style: none; margin: 0; padding: 0; }
        .phh-latest-list li a { display: flex; align-items: center; padding: 12px 8px; border-bottom: 1px solid #eee; text-decoration: none; color: #3c3c3c; transition: background-color 0.2s ease; border-radius: 4px; }
        .phh-latest-list li:last-child a { border-bottom: none; }
        .phh-latest-list li a:hover { background-color: #f3eef4; }
        .phh-latest-list .item-icon { font-size: 18px; color: #5e4365; margin-right: 12px; width: 20px; text-align: center; }
        .phh-latest-list .item-details { flex-grow: 1; }
        .phh-latest-list .item-details strong { font-weight: 600; }
        .phh-latest-list .item-details .item-meta { font-size: 12px; color: #666; }
        .phh-latest-list .item-exit-time { font-size: 13px; font-weight: 600; text-align: right; white-space: nowrap; color: #5e4365;}

        .phh-top-list { list-style: none; margin: 0; padding: 0; }
        .phh-top-list li { display: flex; align-items: center; padding: 8px 5px; border-bottom: 1px solid #eee; }
        .phh-top-list li .pos { font-weight: bold; color: #5e4365; width: 25px; }
        .phh-top-list li .name { flex-grow: 1; font-weight: 500; }
        .phh-top-list li .count { font-weight: bold; background-color: #e9e5ea; padding: 3px 8px; border-radius: 6px; font-size: 12px; }
    </style>
    <?php

    // --- 3. HTML DEL WIDGET ---
    ?>
    <div class="phh-widget-container">
        
        <div class="phh-widget-grid">
            <a href="<?php echo admin_url('admin.php?page=pethome_clientes'); ?>" class="phh-stat-card">
                <div class="stat-icon"><i class="fa-thin fa-users"></i></div>
                <h3 class="stat-number"><?php echo esc_html($total_clientes); ?></h3><p class="stat-label">Clientes</p>
            </a>
            <a href="<?php echo admin_url('admin.php?page=pethome_cuidadores'); ?>" class="phh-stat-card">
                <div class="stat-icon"><i class="fa-thin fa-user-nurse"></i></div>
                <h3 class="stat-number"><?php echo esc_html($total_cuidadores); ?></h3><p class="stat-label">Cuidadores</p>
            </a>
            <a href="<?php echo admin_url('admin.php?page=pethome_todas_las_reservas'); ?>" class="phh-stat-card">
                <div class="stat-icon"><i class="fa-thin fa-calendar-check"></i></div>
                <h3 class="stat-number"><?php echo esc_html($all_items_query->found_posts); ?></h3><p class="stat-label">Reservas (Año)</p>
            </a>
        </div>

        <div class="phh-section">
            <div class="chart-wrapper">
                <h3><i class="fa-thin fa-chart-simple"></i> Guardas por Mes</h3>
                <canvas id="phhReservasChart"></canvas>
            </div>
            <div class="chart-wrapper">
                <h3><i class="fa-thin fa-sack-dollar"></i> Montos por Mes</h3>
                <canvas id="phhMontosChart"></canvas>
            </div>
        </div>

        <div class="phh-section">
            <div class="phh-main-layout">
                <div>
                    <h3><i class="fa-thin fa-star"></i> Top 10 Cuidadores</h3>
                    <?php if (!empty($top_cuidadores)) : ?>
                        <ol class="phh-top-list">
                            <?php $pos = 1; foreach ($top_cuidadores as $cuidador_id => $count) : ?>
                                <li>
                                    <span class="pos"><?php echo $pos++; ?>.</span>
                                    <span class="name"><?php echo esc_html($cuidadores[$cuidador_id]['alias'] ?? 'ID ' . $cuidador_id); ?></span>
                                    <span class="count"><?php echo esc_html($count); ?> guardas</span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p>No hay datos de cuidadores para mostrar.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <h3><i class="fa-thin fa-clock"></i> Últimas Reservas Creadas</h3>
                    <?php if ($latest_reservations_query->have_posts()) : ?>
                        <ul class="phh-latest-list">
                            <?php while ($latest_reservations_query->have_posts()) : $latest_reservations_query->the_post(); ?>
                            <li>
                                <a href="<?php echo get_edit_post_link(get_the_ID()); ?>">
                                    <span class="item-icon"><i class="fa-thin fa-calendar-days"></i></span>
                                    <div class="item-details">
                                        <strong>
                                        <?php 
                                            $nombre_mascota = 'N/D';
                                            if (get_post_type() === 'wc_booking') {
                                                $booking = new WC_Booking(get_the_ID());
                                                $producto = $booking->get_product();
                                                $nombre_mascota = $producto ? $producto->get_name() : 'Servicio Eliminado';
                                            } else {
                                                $nombre_mascota = get_post_meta(get_the_ID(), 'pethome_mascota_nombre', true) ?: 'Sin Nombre';
                                            }
                                            echo esc_html($nombre_mascota);
                                        ?>
                                        </strong>
                                        <div class="item-meta">Reserva #<?php echo get_the_ID(); ?></div>
                                    </div>
                                    <div class="item-exit-time">
                                        <?php
                                             $fecha_salida = 'N/D';
                                             if (get_post_type() === 'wc_booking') {
                                                $booking = new WC_Booking(get_the_ID());
                                                $fecha_salida = date_i18n('d/m/Y H:i', $booking->get_end());
                                             } else {
                                                $fechas_str = get_post_meta(get_the_ID(), 'pethome_reserva_fechas', true);
                                                $hora_egreso = get_post_meta(get_the_ID(), 'pethome_reserva_hora_egreso', true);
                                                $fecha_fin_str = !empty($fechas_str) ? substr($fechas_str, strpos($fechas_str, ' a ') + 3) : '';
                                                if ($fecha_fin_str) { $fecha_salida = date_i18n('d/m/Y', strtotime($fecha_fin_str)) . ($hora_egreso ? ' ' . $hora_egreso : ''); }
                                             }
                                             echo esc_html($fecha_salida);
                                        ?>
                                    </div>
                                </a>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else : ?>
                        <p style="text-align:center; font-style:italic; color:#777;">No hay reservas para mostrar.</p>
                    <?php endif; wp_reset_postdata(); ?>
                </div>
            </div>
        </div>
        
        <div class="phh-section">
             <h3>Accesos Directos</h3>
             <div class="phh-links-grid">
                <a href="<?php echo admin_url('admin.php?page=pethome_guardas_agregar'); ?>" class="phh-button add-new"><i class="fa-thin fa-plus"></i><span>Nueva Guarda</span></a>
                <a href="<?php echo admin_url('admin.php?page=pethome_todas_las_reservas'); ?>" class="phh-button"><i class="fa-thin fa-list-ul"></i><span>Ver Reservas</span></a>
                <a href="<?php echo admin_url('admin.php?page=pethome_clientes'); ?>" class="phh-button"><i class="fa-thin fa-users"></i><span>Ver Clientes</span></a>
                <a href="<?php echo admin_url('admin.php?page=pethome_cuidadores'); ?>" class="phh-button"><i class="fa-thin fa-user-nurse"></i><span>Ver Cuidadores</span></a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartData = {
            labels: <?php echo json_encode(array_values($months)); ?>,
            reservations: <?php echo json_encode(array_values($reservations_by_month)); ?>,
            revenue: <?php echo json_encode(array_values($revenue_by_month)); ?>
        };

        const ctxReservas = document.getElementById('phhReservasChart');
        if (ctxReservas) {
            new Chart(ctxReservas, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Cantidad de Guardas',
                        data: chartData.reservations,
                        backgroundColor: 'rgba(94, 67, 101, 0.6)',
                        borderColor: 'rgba(94, 67, 101, 1)',
                        borderWidth: 1
                    }]
                },
                options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, responsive: true, maintainAspectRatio: false }
            });
        }

        const ctxMontos = document.getElementById('phhMontosChart');
        if (ctxMontos) {
            new Chart(ctxMontos, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Monto Generado ($)',
                        data: chartData.revenue,
                        backgroundColor: 'rgba(40, 167, 69, 0.6)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    }]
                },
                options: { scales: { y: { beginAtZero: true } }, responsive: true, maintainAspectRatio: false }
            });
        }
    });
    </script>
    <?php
}
}