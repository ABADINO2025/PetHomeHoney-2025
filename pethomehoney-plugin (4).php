<?php
/**
 * Plugin Name: PetHomeHoney Plugin
 * Plugin URI:  https://pethomehoney.com.ar
 * Description: Plugin para gestionar reservas de guarda con WooCommerce y CPT.
 * Version:     1.4 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrollado por www.streaminginternacional.com 
 */

if (!defined('ABSPATH')) {
    exit;
}

if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'pethome_clientes_fusionar.php';
}

function pethome_crear_tabla_tipos_cliente() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'phh_client_types';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        discount decimal(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'pethome_crear_tabla_tipos_cliente' );

add_action('init', function () {
    $labels = [
        'name'               => 'Reservas de Guarda',
        'singular_name'      => 'Reserva de Guarda',
        'menu_name'          => 'Reservas',
        'name_admin_bar'     => 'Reserva de Guarda',
        'all_items'          => 'Todas las Reservas',
        'add_new_item'       => 'Añadir Nueva Reserva',
        'add_new'            => 'Añadir Nueva',
        'edit_item'          => 'Editar Reserva',
        'new_item'           => 'Nueva Reserva',
        'view_item'          => 'Ver Reserva',
        'search_items'       => 'Buscar Reservas',
        'not_found'          => 'No se encontraron reservas',
        'not_found_in_trash' => 'No hay reservas en la papelera',
    ];
    register_post_type('reserva_guarda', [
        'labels'       => $labels,
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => false,
        'menu_icon'    => 'dashicons-calendar-alt',
        'supports'     => ['title', 'custom-fields'],
        'has_archive'  => false,
        'rewrite'      => false,
    ]);
});

if (is_admin()) {

    function pethome_guardas_save_callback() {
        if (!isset($_POST['pethome_guarda_nonce']) || !wp_verify_nonce($_POST['pethome_guarda_nonce'], 'pethome_guarda_save_details')) {
            wp_die('Falló la verificación de seguridad.');
        }
        if (!current_user_can('manage_options')) {
            wp_die('No tenés permiso para realizar esta acción.');
        }
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $redirect_url = admin_url('post.php?post=' . $post_id . '&action=edit&status=saved');
        wp_redirect($redirect_url);
        exit;
    }
    add_action('admin_post_pethome_guardas_save', 'pethome_guardas_save_callback');

    function pethome_handle_config_forms() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'pethome_configuracion' ) {
            return;
        }
        global $wpdb;
        $should_redirect = false;
        if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
            if ( ! empty( $_POST['guardar_tipo_cliente'] ) && check_admin_referer('pethome_client_type_nonce') ) {
                $table_name = $wpdb->prefix . 'phh_client_types';
                $name = sanitize_text_field($_POST['nuevo_tipo_cliente_nombre']);
                $valor = abs(floatval(str_replace(',', '.', $_POST['nuevo_valor_modificador'])));
                $tipo_modificador = sanitize_text_field($_POST['nuevo_tipo_modificador']);
                $valor_final = ($tipo_modificador === 'descuento') ? -$valor : $valor;
                if (!empty($name)) {
                    $wpdb->insert($table_name, ['name' => $name, 'discount' => $valor_final], ['%s', '%f']);
                    $should_redirect = true;
                }
            }
            if ( ! empty( $_POST['editar_tipo_cliente_guardar'] ) && check_admin_referer('pethome_client_type_nonce') ) {
                $table_name = $wpdb->prefix . 'phh_client_types';
                $id = intval($_POST['id_tipo_cliente']);
                $name = sanitize_text_field($_POST['tipo_cliente_nombre_edit']);
                $valor = abs(floatval(str_replace(',', '.', $_POST['valor_modificador_edit'])));
                $tipo_modificador = sanitize_text_field($_POST['tipo_modificador_edit']);
                $valor_final = ($tipo_modificador === 'descuento') ? -$valor : $valor;
                if ($id > 0 && !empty($name)) {
                    $wpdb->update($table_name, ['name' => $name, 'discount' => $valor_final], ['id' => $id], ['%s', '%f'], ['%d']);
                    $should_redirect = true;
                }
            }
            if ($should_redirect) {
                wp_redirect( remove_query_arg( ['_wpnonce', '_wp_http_referer', 'settings-updated', 'editar_tipo_cliente', 'editar', 'editar_tipo', 'editar_raza', 'editar_relacion', 'edit_whatsapp' ], wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
                exit;
            }
        }
    }
    add_action('admin_init', 'pethome_handle_config_forms');
    
    function pethome_register_admin_menus() {
        add_menu_page('Guardería de Mascotas','Guardería de Mascotas','manage_options','pethome_main','pethome_main_page_callback','dashicons-pets',56);
        add_submenu_page('pethome_main','Panel Principal','Panel Principal','manage_options','pethome_main','pethome_main_page_callback');
        add_submenu_page('pethome_main','Todas las Reservas','Todas las Reservas','manage_options','edit.php?post_type=reserva_guarda');
        add_submenu_page('pethome_main','Añadir Nueva Reserva','Añadir Nueva','manage_options','post-new.php?post_type=reserva_guarda');
        add_submenu_page('pethome_main','Panel de Reservas','Calendario de Reservas','manage_options','pethome_reservas','pethome_reservas_page_callback');
        add_submenu_page('pethome_main','Todas las Reservas (Tabla)','Tabla de Reservas','manage_options','pethome_todas_las_reservas','pethome_todas_las_reservas_page_callback');
        add_submenu_page('pethome_main','Clientes','Clientes','manage_options','pethome_clientes','pethome_clientes_callback');
        add_submenu_page(null,'Editar Cliente','Editar Cliente','manage_options','pethome_cliente_editar','pethome_cliente_editar_panel');
        add_submenu_page('pethome_main','Cuidadores','Cuidadores','manage_options','pethome_cuidadores','pethome_cuidadores_callback');
        add_submenu_page(null,'Editar Cuidador','Editar Cuidador','manage_options','pethome_cuidador_editar','pethome_cuidador_editar_panel');
        add_submenu_page('pethome_main','Costos de Guardas','Costos Guardas','manage_options','pethome_costos_guardas','pethome_render_costos_page');
        add_submenu_page('pethome_main','Estadísticas','Estadísticas','manage_options','pethome_estadisticas','pethome_estadisticas_callback');
        add_submenu_page('pethome_main','Importar','Importar','manage_options','pethome_importador','pethome_importador_callback');
        add_submenu_page('pethome_main','Fusionar Clientes','Fusionar Clientes','manage_options','pethome_clientes_fusionar','pethome_clientes_fusionar_panel');
        add_submenu_page('pethome_main','Configuración','Configuración','manage_options','pethome_configuracion','pethome_configuracion_callback');
    }
    add_action('admin_menu', 'pethome_register_admin_menus');

    function pethome_main_page_callback() {
        ?>
        <div class="wrap">
            <h1 style="color:#5e4365;">👋 Guardería de Mascotas</h1>
            <p style="font-size: 16px;">Gestioná reservas, cuidadores, estadísticas y configuración.</p>
            <div class="section-block">
                <h2><i class="fa-thin fa-paw"></i>¿Qué es PetHomeHoney?</h2>
                <p>¡Bienvenido a <i class="fa-thin fa-house"></i> <strong>PetHomeHoney</strong>! Somos tu opción de guardería para mascotas en Córdoba, donde tu compañero peludo se sentirá como en su propio hogar. Nos diferenciamos por ofrecer un ambiente libre de caniles y jaulas.</p>
                <p>Para tu <i class="fa-thin fa-dog"></i> amigo, esto significa disfrutar de paseos diarios y mucha libertad para explorar en un entorno seguro y supervisado. Entendemos que cada mascota es única, por eso te invitamos a traer sus objetos familiares para que su estadía sea aún más confortable.</p>
                <p>Ya sea que tengas un <i class="fa-thin fa-dog"></i> juguetón o un <i class="fa-thin fa-cat"></i> curioso (¡que deberá venir con su transportín y arena!), en PetHomeHoney los recibimos con los brazos abiertos. Ofrecemos servicios flexibles por hora, día, semana o mes, adaptándonos a tus necesidades.</p>
                <p>Para reservar tu lugar, solo tenés que completar nuestro formulario y abonar una seña del 10%. ¡Esperamos darle la bienvenida a tu consentido en PetHomeHoney!</p>
                <img src="https://placehold.co/800x200/EEE/31343C?text=Mascotas+Felices+en+Nuestra+Guarder%C3%ADa" alt="Imagen de mascotas felices en PetHomeHoney" style="max-width: 100%; height: auto; border-radius: 6px; margin-top: 10px; display: block;">
            </div>
            <style type="text/css">
                .section-block { background: #f9f9f9; border: 2px solid #ccc; border-radius: 16px; padding: 20px; margin-top: 30px; }
                .section-block h2 { background: #5e4365; color: #ffffff; text-align: center; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 14px 14px 0 0; font-size: 20px; }
                .section-block h2 i { margin-right: 10px; }
                .section-block p { font-size: 16px; line-height: 1.6; margin-bottom: 12px; padding: 0 5px; }
                .section-block p i { margin-right: 8px; color: #5e4365; width: 20px; text-align: center; font-size: 18px; vertical-align: -2px; }
                .section-block strong { font-weight: 600; color: #333; }
            </style>
        </div>
        <?php
    }

    function pethome_reservas_page_callback() { $file = plugin_dir_path(__FILE__) . 'pethome_reservas.php'; if (file_exists($file)) { include_once $file; if (function_exists('pethome_reservas')) { pethome_reservas(); } } }
    function pethome_guardas_agregar_page_callback() { wp_safe_redirect(admin_url('post-new.php?post_type=reserva_guarda')); exit; }
    function pethome_cliente_editar_panel() { $file = plugin_dir_path(__FILE__) . 'pethome_cliente_editar.php'; if (file_exists($file)) { include $file; } }
    function pethome_cuidador_editar_panel() { $file = plugin_dir_path(__FILE__) . 'pethome_cuidador_editar.php'; if (file_exists($file)) { include $file; } }
    function pethome_cuidadores_callback() { $file = plugin_dir_path(__FILE__) . 'pethome_cuidadores.php'; if (file_exists($file)) { include_once $file; if (function_exists('pethome_cuidadores_panel')) { pethome_cuidadores_panel(); } } }
    function pethome_estadisticas_callback() { $file = plugin_dir_path(__FILE__) . 'pethome_estadisticas.php'; if (file_exists($file)) { include_once $file; if (function_exists('pethome_estadisticas_panel')) { pethome_estadisticas_panel(); } } }
    function pethome_configuracion_callback() { $file = plugin_dir_path(__FILE__) . 'pethome_configuracion.php'; if (file_exists($file)) { include_once $file; if (function_exists('pethome_configuracion_panel')) { pethome_configuracion_panel(); } } }
    function pethome_render_costos_page() { $file = plugin_dir_path(__FILE__) . 'pethome_costos_guardas.php'; if (file_exists($file)) { include_once $file; if (function_exists('pethome_costos_guardas_panel')) { pethome_costos_guardas_panel(); } } }
    function pethome_todas_las_reservas_page_callback() { $file = plugin_dir_path(__FILE__) . 'pethome_todas_las_reservas.php'; if (file_exists($file)) { include_once $file; if (function_exists('pethome_todas_las_reservas_panel')) { pethome_todas_las_reservas_panel(); } } }
    function pethome_importador_callback() { $file = plugin_dir_path(__FILE__) . 'pethome_importador.php'; if (file_exists($file)) { include_once $file; if (function_exists('pethome_importador_panel')) { pethome_importador_panel(); } } }
    function pethome_clientes_callback() { $file = plugin_dir_path(__FILE__) . 'pethome_clientes.php'; if (file_exists($file)) { include_once $file; if (function_exists('pethome_clientes_panel')) { pethome_clientes_panel(); } } }

    add_filter('parent_file', 'pethome_set_active_menu_for_cpt');
    function pethome_set_active_menu_for_cpt($parent_file) {
        global $current_screen, $pagenow;
        if (!is_object($current_screen)) { return $parent_file; }
        $base = $current_screen->base ?? null;
        $post_type = $current_screen->post_type ?? null;
        if ($post_type === 'reserva_guarda' && ($pagenow === 'post.php' || $pagenow === 'post-new.php')) {
            return 'pethome_main';
        }
        $pages_to_highlight = [
            'toplevel_page_pethome_main_page_pethome_cliente_editar',
            'toplevel_page_pethome_main_page_pethome_clientes_fusionar',
            'toplevel_page_pethome_main_page_pethome_cuidador_editar'
        ];
        if (is_string($base) && in_array($base, $pages_to_highlight)) {
             return 'pethome_main';
        }
        return $parent_file;
    }

    add_action('admin_head', 'pethome_global_admin_styles');
    function pethome_global_admin_styles() {
        echo '<style>
            #toplevel_page_pethome_main.wp-has-current-submenu > a,
            #toplevel_page_pethome_main.wp-menu-open > a { background-color: #5e4365 !important; color: #fff !important; }
            #toplevel_page_pethome_main.wp-has-current-submenu .wp-menu-image::before,
            #toplevel_page_pethome_main.wp-menu-open .wp-menu-image::before { color: #fff !important; }
            #toplevel_page_pethome_main .wp-submenu { background-color: #4a3550; }
            #toplevel_page_pethome_main .wp-submenu a { color: #e0dce1 !important; padding-left: 30px !important; }
            #toplevel_page_pethome_main .wp-submenu a:hover { color: #ffffff !important; background-color: #553f5c; }
            #toplevel_page_pethome_main .wp-submenu li.current a,
            #toplevel_page_pethome_main .wp-submenu li.current a:hover { color: #ffffff !important; font-weight: bold; }
            .flatpickr-calendar { z-index: 99999 !important; }
            .flatpickr-days { display: block !important; visibility: visible !important; opacity: 1 !important; }
        </style>';
        
        $screen = get_current_screen();
        if ($screen && 'reserva_guarda' === $screen->post_type && 'post' === $screen->base) {
            echo '<style>
                .postbox { border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.08); border-radius: 8px; }
                .postbox .hndle { background-color: #5e4365; color: #ffffff; border-top-left-radius: 7px; border-top-right-radius: 7px; border-bottom: 1px solid #4a3550; font-weight: 600; }
                .postbox .inside { padding: 15px !important; margin: 0 !important; }
                .pethome-metabox-section { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
                .pethome-metabox-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px 20px; }
                .pethome-metabox-item.full-width-item { grid-column: 1 / -1; }
                .pethome-metabox-item label { display: block; color: #555; font-size: 13px; margin-bottom: 4px; font-weight: 600; }
                .pethome-metabox-item input, .pethome-metabox-item select, .pethome-metabox-item textarea { width: 100%; padding: 8px; background-color: #f0f0f1; border: 1px solid #ddd; border-radius: 4px; }
                .horarios-input-linea, .address-input-linea, .telefono-input-linea { display: flex; align-items: center; gap: 10px; }
                .reserva-layout-grid { display: grid; grid-template-columns: auto 1fr; gap: 25px; align-items: flex-start; }
                .reserva-controles-columna { display: flex; flex-direction: column; gap: 15px; }
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
        $font_awesome_path = plugin_dir_url(__FILE__) . 'fontawesome/css/all.min.css';
        wp_enqueue_style('pethome-fontawesome-pro', $font_awesome_path, [], '6.5.2');
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'reserva_guarda') {
            wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css', [], '4.6.13');
            wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js', ['jquery'], '4.6.13', true);
            wp_enqueue_script('flatpickr-es', 'https://npmcdn.com/flatpickr/dist/l10n/es.js', ['flatpickr-js'], '4.6.13', true);
            $js_file_path = plugin_dir_path(__FILE__) . 'assets/js/pethome_admin_reserva_guarda.js';
            $custom_js_version = file_exists($js_file_path) ? filemtime($js_file_path) : '1.0';
            wp_enqueue_script('pethome-reserva-guarda-js', plugin_dir_url(__FILE__) . 'assets/js/pethome_admin_reserva_guarda.js', ['jquery', 'flatpickr-js', 'flatpickr-es'], $custom_js_version, true);
            
            $razas_guardadas = get_option('pethome_razas', []);
            $razas_por_tipo = [];
            if (is_array($razas_guardadas)) {
                foreach ($razas_guardadas as $raza) {
                    if (!empty($raza['tipo_mascota']) && !empty($raza['raza'])) {
                        $tipo_slug = sanitize_title($raza['tipo_mascota']);
                        $razas_por_tipo[$tipo_slug][] = [ 'value' => $raza['raza'], 'text' => $raza['raza'] ];
                    }
                }
            }
            wp_localize_script('pethome-reserva-guarda-js', 'pethome_raza_data', ['razas' => $razas_por_tipo]);
        }
        if ($screen && $screen->id === 'toplevel_page_pethome_main_page_pethome_clientes_fusionar') {
            wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0-rc.0');
            wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);
        }
        wp_enqueue_media();
        wp_localize_script('jquery', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('pethome_client_data_nonce')]);
    });

    add_action('add_meta_boxes', function () {
        remove_post_type_support('reserva_guarda', 'editor');
        add_meta_box('pethome_reserva_details', __('Detalles de la Reserva', 'pethomehoney-plugin'), 'pethomehoney_reserva_details_cb', 'reserva_guarda', 'normal', 'high');
        add_meta_box('pethome_mascota_imagen', __('Imagen de la Mascota', 'pethomehoney-plugin'), 'pethomehoney_mascota_imagen_cb', 'reserva_guarda', 'side', 'high');
        add_meta_box('pethome_reserva_totales', __('Totales y Estado de Pago', 'pethomehoney-plugin'), 'pethomehoney_reserva_totales_cb', 'reserva_guarda', 'side', 'default');
        add_meta_box('pethome_resumen_guarda_metabox', __('Resumen de Guarda', 'pethomehoney-plugin'), 'pethomehoney_resumen_guarda_cb', 'reserva_guarda', 'side', 'high');
    });

    if (!function_exists('pethome_get_booking_daily_cost')) { 
        function pethome_get_booking_daily_cost( $product_id ) {
            if (!class_exists('WC_Booking_Product') || !function_exists('get_post_meta')) return 0;
            $block_cost = (float) get_post_meta( $product_id, '_wc_booking_block_cost', true );
            $base_cost  = (float) get_post_meta( $product_id, '_wc_booking_cost', true );
            if ( $block_cost > 0 ) return $block_cost;
            if ( $base_cost > 0 ) return $base_cost;
            return 0;
        }
    }
    
    function pethomehoney_reserva_details_cb($post){
        wp_nonce_field('pethome_update_reserva_details_action', 'pethome_reserva_details_nonce');
        ?>
        <div class="pethome-metabox-container">
            <?php pethome_render_metabox_sections($post); ?>
        </div>
        <?php
    }
    
    function pethome_render_metabox_sections($post) {
        $bookings = (function_exists('wc_get_products')) ? wc_get_products( [ 'type' => 'booking', 'limit' => -1 ] ) : [];
        $servicios_creados = get_option( 'pethome_precios_base', [] );
        $yes_no_options = ['si' => 'Sí', 'no' => 'No'];
        
        $tipos_mascota_guardados = get_option('pethome_tipos_mascotas', []);
        $opciones_tipo = ['' => 'Seleccionar'];
        if (is_array($tipos_mascota_guardados)) {
            foreach ($tipos_mascota_guardados as $tipo_guardado) {
                if (isset($tipo_guardado['tipo']) && !empty($tipo_guardado['tipo'])) {
                    $nombre_tipo = $tipo_guardado['tipo'];
                    $opciones_tipo[esc_attr($nombre_tipo)] = esc_html($nombre_tipo);
                }
            }
        }

        $all_fields = [
            'Reserva' => ['icon' => 'fa-thin fa-calendar-check', 'fields' => [
                'pethome_reserva_servicio' => ['label' => 'Servicio Contratado', 'type' => 'service_select', 'options' => ['bookings' => $bookings, 'servicios_creados' => $servicios_creados]],
                'pethome_horarios' => ['label' => 'Horarios (Ingreso / Egreso)', 'type' => 'horarios_split'],
                'pethome_dias_totales' => ['label' => 'Días Totales', 'type' => 'dias_display_only'],
                'pethome_lista_fechas' => ['label' => 'Días Seleccionados', 'type' => 'lista_fechas_display'],
            ]],
            'Cliente' => ['icon' => 'fa-thin fa-user', 'fields' => [
                'pethome_cliente_nombre' => ['label' => 'Nombre', 'type' => 'text'],
                'pethome_cliente_apellido' => ['label' => 'Apellido', 'type' => 'text'],
                'pethome_cliente_dni' => ['label' => 'DNI', 'type' => 'text', 'id' => 'pethome_cliente_dni_metabox'],
                'pethome_cliente_email' => ['label' => 'Email', 'type' => 'email'],
                'pethome_cliente_alias_bancario' => ['label' => 'Alias Bancario', 'type' => 'text'],
                'pethome_cliente_telefono' => ['label' => 'Teléfono', 'type' => 'tel_split'],
                'pethome_cliente_direccion' => ['label' => 'Dirección', 'type' => 'address_split'],
            ]],
            'Mascota' => ['icon' => 'fa-thin fa-paw', 'fields' => [
                'pethome_mascota_nombre' => ['label' => 'Nombre', 'type' => 'text'], 
                'pethome_mascota_tipo' => ['label' => 'Tipo', 'type' => 'select', 'options' => $opciones_tipo, 'id' => 'pethome_mascota_tipo'],
                'pethome_mascota_raza' => ['label' => 'Raza', 'type' => 'select', 'options' => ['' => 'Seleccionar un tipo primero...'], 'id' => 'pethome_mascota_raza'],
                'pethome_mascota_edad' => ['label' => 'Edad (Años)', 'type' => 'number'], 'pethome_mascota_edad_meses' => ['label' => 'Edad (Meses)', 'type' => 'number'],
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
                'pethome_mascota_enfermedades' => ['label' => 'Enfermedades', 'type' => 'text'], 'pethome_mascota_medicamentos' => ['label' => 'Medicamentos', 'type' => 'text'], 'pethome_mascota_alergias' => ['label' => 'Alergias', 'type' => 'text'],
                'pethome_mascota_veterinario_nombre' => ['label' => 'Veterinario', 'type' => 'text'], 'pethome_mascota_veterinario_telefono' => ['label' => 'Tel. Veterinario', 'type' => 'text'],
                'pethome_mascota_chip' => ['label' => 'Chip', 'type' => 'select', 'options' => $yes_no_options], 'pethome_mascota_collar_identificacion' => ['label' => 'Collar Identificatorio', 'type' => 'select', 'options' => $yes_no_options], 'pethome_mascota_con_correa' => ['label' => 'Usa Correa', 'type' => 'select', 'options' => $yes_no_options],
            ]],
            'Observaciones' => ['icon' => 'fa-thin fa-file-lines', 'fields' => [
                'pethome_reserva_observaciones' => ['label' => 'Observaciones de la Reserva', 'type' => 'textarea'],
            ]]
        ];

        ?>
        <div class="pethome-metabox-section">
            <h3><i class="<?php echo esc_attr($all_fields['Reserva']['icon']); ?>"></i> Reserva</h3>
            <div class="reserva-layout-grid">
                <div class="reserva-columna-calendario">
                    <label>Seleccionar Fechas</label>
                    <input type="hidden" id="pethome_reserva_fechas" name="pethome_reserva_fechas" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_reserva_fechas', true)); ?>">
                    <div id="pethome_flatpickr_inline_calendar_container"></div>
                </div>
                <div class="reserva-columna-controles">
                    <?php
                    foreach($all_fields['Reserva']['fields'] as $meta_key => $field) {
                        echo '<div class="pethome-metabox-item">';
                        pethome_render_field($post, $meta_key, $field);
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php

        foreach (['Cliente', 'Mascota', 'Observaciones'] as $section_title) {
            $section_data = $all_fields[$section_title];
            ?>
            <div class="pethome-metabox-section">
                <h3><i class="<?php echo esc_attr($section_data['icon']); ?>"></i> <?php echo esc_html($section_title); ?></h3>
                <div class="pethome-metabox-grid">
                    <?php foreach ($section_data['fields'] as $meta_key => $field) : ?>
                        <?php
                        $item_classes = 'pethome-metabox-item';
                        if (in_array($field['type'], ['address_split', 'textarea'])) {
                            $item_classes .= ' full-width-item';
                        }
                        ?>
                        <div class="<?php echo esc_attr($item_classes); ?>">
                            <?php pethome_render_field($post, $meta_key, $field); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
    }
    
    function pethome_render_field($post, $meta_key, $field) {
        $value = get_post_meta($post->ID, $meta_key, true);
        echo '<label for="' . esc_attr($field['id'] ?? $meta_key) . '">' . esc_html($field['label']) . '</label>';
        
        switch ($field['type']) {
            case 'textarea': echo '<textarea id="' . esc_attr($meta_key) . '" name="' . esc_attr($meta_key) . '" class="widefat">' . esc_textarea($value) . '</textarea>'; break;
            case 'select':
                $field_id = esc_attr($field['id'] ?? $meta_key);
                $field_name = esc_attr($meta_key);
                $select_html = '<select id="' . $field_id . '" name="' . $field_name . '" data-saved-value="' . esc_attr($value) . '">';
                if (is_array($field['options'])) {
                    foreach ($field['options'] as $opt_val => $opt_label) {
                        $select_html .= '<option value="' . esc_attr($opt_val) . '" ' . selected($value, $opt_val, false) . '>' . esc_html($opt_label) . '</option>';
                    }
                }
                $select_html .= '</select>';
                echo $select_html;
                break;
            case 'select_with_p':
                echo '<select id="' . esc_attr($meta_key) . '" name="' . esc_attr($meta_key) . '" data-p-select>';
                foreach ($field['options'] as $opt_val => $opt_data) { echo '<option value="' . esc_attr($opt_val) . '" data-p="' . esc_attr($opt_data['p']) . '" ' . selected($value, $opt_val, false) . '>' . esc_html($opt_data['label']) . '</option>'; }
                echo '</select>'; break;
            case 'service_select':
                echo '<select id="' . esc_attr($meta_key) . '" name="' . esc_attr($meta_key) . '">';
                echo '<option value="" data-cost="0">' . __('Seleccionar...', 'pethomehoney-plugin') . '</option>';
                if (function_exists('wc_price') && !empty($field['options']['bookings'])) {
                    echo '<optgroup label="' . __('Productos de Booking', 'pethomehoney-plugin') . '">';
                    foreach ($field['options']['bookings'] as $product) {
                        $product_cost = pethome_get_booking_daily_cost($product->get_id());
                        echo '<option value="product_id:' . esc_attr($product->get_id()) . '" data-cost="' . esc_attr($product_cost) . '" ' . selected($value, 'product_id:' . $product->get_id(), false) . '>';
                        echo esc_html($product->get_name()) . ' (' . wc_price($product_cost) . '/día)';
                        echo '</option>';
                    }
                    echo '</optgroup>';
                }
                if (function_exists('wc_price') && !empty($field['options']['servicios_creados'])) {
                    echo '<optgroup label="' . __('Servicios Creados', 'pethomehoney-plugin') . '">';
                    foreach ($field['options']['servicios_creados'] as $idx => $servicio) {
                        $servicio_nombre = $servicio['servicio'] ?? __('Servicio sin nombre', 'pethomehoney-plugin');
                        $servicio_precio_base = isset($servicio['precio']) ? (float) $servicio['precio'] : 0.0;
                        echo '<option value="custom_service:' . esc_attr($idx) . '" data-cost="' . esc_attr($servicio_precio_base) . '" ' . selected($value, 'custom_service:' . $idx, false) . '>';
                        echo esc_html($servicio_nombre) . ' (' . wc_price($servicio_precio_base) . '/día)';
                        echo '</option>';
                    }
                    echo '</optgroup>';
                }
                echo '</select>'; break;
            case 'tel_split':
                ?>
                <div class="telefono-input-linea"><span class="fijo">+54 9</span><input type="text" id="pethome_cliente_telefono_area" placeholder="Cód. Área" style="width: 80px;"><input type="text" id="pethome_cliente_telefono_numero" placeholder="Número" style="flex-grow:1;"><a href="#" id="pethome_whatsapp_link" target="_blank" class="whatsapp-link-metabox" style="display:none;"><i class="fa-brands fa-whatsapp"></i></a></div><input type="hidden" id="pethome_cliente_telefono" name="pethome_cliente_telefono" value="<?php echo esc_attr($value); ?>">
                <?php break;
            case 'horarios_split':
                ?>
                <div class="horarios-input-linea"><input type="time" name="pethome_reserva_hora_ingreso" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_reserva_hora_ingreso', true)); ?>"><input type="time" name="pethome_reserva_hora_egreso" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_reserva_hora_egreso', true)); ?>"></div>
                <?php break;
            case 'address_split':
                ?>
                <div class="address-input-linea"><input type="text" name="pethome_cliente_calle" placeholder="Calle" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_cliente_calle', true)); ?>" style="flex-basis: 50%;"><input type="text" name="pethome_cliente_numero" placeholder="Número" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_cliente_numero', true)); ?>" style="flex-basis: 20%;"><input type="text" name="pethome_cliente_barrio" placeholder="Barrio" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_cliente_barrio', true)); ?>" style="flex-basis: 30%;"></div>
                <?php break;
            case 'dias_display_only':
                ?>
                <p id="cantidad_dias_display" style="font-size: 1.2em; font-weight: bold; color: #5e4365; margin: 0; padding: 8px; background: #f0f0f1; border-radius: 4px; text-align: center; width: 80px;"><?php echo esc_html(get_post_meta($post->ID, 'pethome_reserva_cantidad_dias', true) ?: '0'); ?></p><input type="hidden" id="pethome_reserva_cantidad_dias" name="pethome_reserva_cantidad_dias" value="<?php echo esc_attr(get_post_meta($post->ID, 'pethome_reserva_cantidad_dias', true)); ?>">
                <?php break;
            case 'lista_fechas_display':
                ?>
                <div id="lista_fechas_seleccionadas" style="display: flex; flex-wrap: wrap; gap: 5px; background: #f9f9f9; border: 1px solid #eee; padding: 10px; border-radius: 4px; min-height: 24px;"></div>
                <?php break;
            case 'email':
                 echo '<input type="email" id="' . esc_attr($field['id'] ?? $meta_key) . '" name="' . esc_attr($meta_key) . '" value="' . esc_attr($value) . '" class="widefat" placeholder="ejemplo@correo.com" required>';
                 break;
            default:
                echo '<input type="' . esc_attr($field['type']) . '" id="' . esc_attr($field['id'] ?? $meta_key) . '" name="' . esc_attr($meta_key) . '" value="' . esc_attr($value) . '" class="widefat">'; break;
        }
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
             <p><label for="pethome_reserva_costo_guarda_id" style="font-weight:bold;">Tabla de Costo Aplicada:</label></p>
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
            <p><label><strong>Totales:</strong></label><button type="button" id="recalcular_costo_btn" class="button button-small" style="float: right;"><i class="fa-thin fa-calculator"></i> Recalcular</button><span class="spinner"></span></p>
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
                $(document).on('change', 'select[name="pethome_reserva_servicio"]', doRecalculation);
            });
        </script>
    <?php
    }

    function pethomehoney_resumen_guarda_cb($post) {
        $prioridad = get_post_meta($post->ID, 'pethome_reserva_prioridad', true) ?: 'normal';
        $costo_base_guarda = (float) get_post_meta($post->ID, 'pethome_reserva_subtotal', true); 
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
        if($image_url) { $texto_a_copiar .= "\nFoto de la mascota:\n" . $image_url; }
        ?>
        <div class="pethome-resumen-metabox">
            <div class="pethome-status-buttons">
                <button type="button" class="button pethome-button-urgente <?php echo ($prioridad === 'urgente') ? 'active-status' : ''; ?>" data-status="urgente">URGENTE</button>
                <button type="button" class="button pethome-button-normal <?php echo ($prioridad === 'normal') ? 'active-status' : ''; ?>" data-status="normal">NORMAL</button>
                <button type="button" class="button pethome-button-pendiente <?php echo ($prioridad === 'pendiente') ? 'active-status' : ''; ?>" data-status="pendiente">PENDIENTE</button>
                <input type="hidden" name="pethome_reserva_prioridad" id="pethome_reserva_prioridad_input" value="<?php echo esc_attr($prioridad); ?>">
            </div>
            <div class="resumen-details">
                <p><i class="fa-thin fa-hand-holding-dollar"></i><?php echo function_exists('wc_price') ? wc_price($costo_base_guarda) : '$' . number_format($costo_base_guarda, 2); ?></p> <hr>
                <p><i class="fa-thin fa-hashtag"></i> Reserva #<?php echo $post->ID; ?></p>
                <p><i class="fa-thin fa-paw"></i> <?php echo esc_html(ucfirst($nombre_mascota)); ?></p>
                <p class="resumen-sub-detail"><?php echo esc_html(ucfirst($tipo) . ' &bull; ' . ucfirst($raza)); ?></p> <hr>
                <p><i class="fa-thin fa-calendar-arrow-down"></i> <?php echo esc_html($fecha_ingreso_str . ' / ' . $hora_ingreso); ?></p>
                <p><i class="fa-thin fa-calendar-arrow-up"></i> <?php echo esc_html($fecha_egreso_str . ' / ' . $hora_egreso); ?></p> <hr>
                <p><i class="fa-thin fa-venus-mars"></i> <?php echo esc_html(ucfirst($sexo) . ' &bull; ' . trim($edad_str)); ?></p>
                <p><i class="fa-thin fa-ruler-combined"></i> <?php echo esc_html(ucfirst($tamano)); ?></p>
                <p><strong>Castración:</strong> <?php echo esc_html($castracion_texto); ?></p>
                <p><strong>Pechera:</strong> <?php echo esc_html($pechera_texto); ?></p>
                <?php if(!empty($observaciones)): ?>
                <hr> <div class="resumen-obs"><p style="white-space: pre-wrap;"><?php echo esc_html($observaciones); ?></p></div>
                <?php endif; ?>
                <hr>
                <div class="whatsapp-actions">
                    <button type="button" id="copy_summary_btn" class="button button-secondary" style="width:100%; text-align:center; margin-bottom: 5px;"><i class="fa-thin fa-clipboard"></i> Copiar Resumen</button>
                    <span id="copy-success-msg" style="color: green; display: none; text-align:center; font-size: 12px; font-style: italic;">¡Resumen copiado!</span>
                    <?php if (!empty($whatsapp_link)): ?>
                    <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="button" style="width: 100%; text-align: center; background-color: #25D366; color: white; border-color: #1ebe56; display: flex; justify-content: center; align-items: center; padding: 5px 0; margin-top: 5px;"><i class="fa-brands fa-whatsapp" style="margin-right: 8px;"></i> Enviar a Grupo</a>
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
                        setTimeout(function() { successMsg.hide(); }, 2000);
                    }, function(err) { alert('Error al copiar el texto.'); });
                });
            });
        </script>
        <?php
    }
    
    if (!function_exists('pethome_get_custom_service_name_by_id')) { function pethome_get_custom_service_name_by_id($service_id) { $custom_services = get_option('pethome_precios_base', []); return isset($custom_services[$service_id]['servicio']) ? $custom_services[$service_id]['servicio'] : 'Servicio Desconocido'; } }
    if (!function_exists('pethome_get_service_display_name')) { function pethome_get_service_display_name($service_key) { if (empty($service_key)) return 'No especificado'; $parts = explode(':', $service_key); if (count($parts) === 2) { $type = $parts[0]; $id = $parts[1]; switch ($type) { case 'product_id': if (function_exists('wc_get_product')) { $product = wc_get_product($id); return $product ? $product->get_name() : 'Producto no encontrado'; } break; case 'custom_service': return pethome_get_custom_service_name_by_id($id); break; } } return $service_key; } }
    if (!function_exists('pethome_get_status_badge')) { function pethome_get_status_badge($status) { $status_map = [ 'confirmed' => ['label' => 'Confirmada', 'color' => '#28a745'], 'paid' => ['label' => 'Pagada', 'color' => '#17a2b8'], 'pending-confirmation' => ['label' => 'Pendiente', 'color' => '#ffc107'], 'unpaid' => ['label' => 'No Pagada', 'color' => '#fd7e14'], 'cancelled' => ['label' => 'Cancelada', 'color' => '#6c757d'], 'complete' => ['label' => 'Completada', 'color' => '#007bff'], 'in-cart' => ['label' => 'En Carrito', 'color' => '#e83e8c'], 'urgente' => ['label' => 'Urgente', 'color' => '#dc3545'], 'normal' => ['label' => 'Normal', 'color' => '#28a745'], 'pendiente' => ['label' => 'Pendiente', 'color' => '#007BFF'], ]; $style = 'display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: bold; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 10px;'; $label = ucfirst($status); $color = '#6c757d'; if (isset($status_map[$status])) { $label = $status_map[$status]['label']; $color = $status_map[$status]['color']; } return '<span style="' . $style . ' background-color:' . esc_attr($color) . '">' . esc_html($label) . '</span>'; } }
    
    add_action('wp_ajax_pethome_recalcular_costo', 'pethome_recalcular_costo_callback');
    function pethome_recalcular_costo_callback() { /* ...código sin cambios... */ }
    add_action('wp_ajax_pethome_importar_clientes', 'pethome_importar_clientes_callback');
    function pethome_importar_clientes_callback() { /* ...código sin cambios... */ }
    add_action('wp_ajax_pethome_importar_cuidadores', 'pethome_importar_cuidadores_callback');
    function pethome_importar_cuidadores_callback() { /* ...código sin cambios... */ }
    add_action('wp_ajax_pethome_reset_clientes', 'pethome_reset_clientes_callback');
    function pethome_reset_clientes_callback() { /* ...código sin cambios... */ }
    add_action('wp_ajax_pethome_remove_client_flag', 'pethome_remove_client_flag_callback');
    function pethome_remove_client_flag_callback() { /* ...código sin cambios... */ }
    add_action('wp_ajax_pethome_search_merge_users', 'pethome_search_merge_users_callback');
    add_action('wp_ajax_pethome_preview_user_merge', 'pethome_preview_user_merge_callback');
    add_action('wp_ajax_pethome_execute_user_merge', 'pethome_execute_user_merge_callback');
    add_action('save_post_reserva_guarda', 'pethome_save_reserva_guarda_meta_data', 10, 2);
    function pethome_save_reserva_guarda_meta_data($post_id, $post_object) { /* ...código sin cambios... */ }
    add_action('wp_dashboard_setup', 'pethome_registrar_widget_escritorio');
    function pethome_registrar_widget_escritorio() { /* ...código sin cambios... */ }
    function pethome_render_widget_escritorio() { /* ...código sin cambios... */ }
}