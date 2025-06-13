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
if (!defined('ABSPATH')) exit;

/**
 * Muestra el panel de configuración con el formulario y la tabla.
 */
function pethome_configuracion_panel() {
    // Leer opciones existentes
    $precios_base = get_option('pethome_precios_base', []);
    $cliente_mensaje = get_option('pethome_cliente_mensaje', '');
    $tipos_mascotas  = get_option('pethome_tipos_mascotas', []);
    $razas           = get_option('pethome_razas', []);
    $costos_configs  = get_option('pethomehoney_costos_guardas_configs', []);
    $relaciones_costos = get_option('pethome_relaciones_costos', []);
    $whatsapp_contacts = get_option('pethome_whatsapp_numbers', []);
    $excluded_roles = get_option('pethome_importer_excluded_roles', ['employee', 'administrator']);
    
    $productos_booking = new WP_Query(['post_type' => 'product', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'booking']], 'post_status' => 'publish']);

    // Índices de edición por GET
    $editando_precio = isset($_GET['editar']) ? intval($_GET['editar']) : -1;
    $editando_tipo   = isset($_GET['editar_tipo']) ? intval($_GET['editar_tipo']) : -1;
    $editando_raza   = isset($_GET['editar_raza']) ? intval($_GET['editar_raza']) : -1;
    $editando_relacion = isset($_GET['editar_relacion']) ? intval($_GET['editar_relacion']) : -1;
    $editando_whatsapp = isset($_GET['edit_whatsapp']) ? sanitize_text_field($_GET['edit_whatsapp']) : null;
    $contact_to_edit = null;
    if ($editando_whatsapp) {
        foreach ($whatsapp_contacts as $contact) {
            if (isset($contact['id']) && $contact['id'] === $editando_whatsapp) {
                $contact_to_edit = $contact;
                break;
            }
        }
    }
    ?>

    <style>
        .pethome-config-wrap { margin: 20px 25px; }
        .form-container { background-color: #fff; padding: 25px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-bottom: 30px; }
        .pethome-config-wrap h1, .pethome-config-wrap h2 { color: #5e4365; }
        .pethome-config-wrap h1 { font-size: 28px; text-align: center; margin-bottom: 30px; }
        .pethome-config-wrap h1 i, .pethome-config-wrap h2 i { margin-right: 12px; }
        .pethome-config-wrap .form-table { border-radius: 8px; overflow: hidden; }
        .pethome-config-wrap .form-table th, .pethome-config-wrap .widefat th { padding: 15px; }
        .pethome-config-wrap .widefat thead th { background-color: #5e4365; color: #ffffff; border-bottom: none !important; }
        .pethome-config-wrap .widefat { border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; }
        .pethome-config-wrap input[type="text"], .pethome-config-wrap input[type="number"], .pethome-config-wrap select, .pethome-config-wrap textarea { width: 100%; padding: 8px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; }
        .pethome-config-wrap .small-text { width: auto; }
        .pethome-config-wrap .button i { margin-right: 5px; }
        .whatsapp-icon-group { color: #007bff; }
        .whatsapp-icon-number { color: #25D366; }
        .roles-checkbox-group { display: flex; flex-wrap: wrap; gap: 15px; }
        .roles-checkbox-group label { display: block; }
        .roles-selection-actions { margin-bottom: 10px; display: flex; gap: 10px; }
    </style>

    <div class="wrap pethome-config-wrap">
        <h1><i class="fa-thin fa-gear"></i>Configuración General</h1>
        
        <?php if (isset($_GET['settings-updated'])) : ?>
            <div id="message" class="updated notice is-dismissible"><p><?php _e('Ajustes guardados.', 'pethomehoney-plugin'); ?></p></div>
        <?php endif; ?>

        <div class="form-container">
            <h2><i class="fa-thin fa-money-bill-1"></i>Precios Base de Servicios</h2>
            <form method="post" action="">
                <?php wp_nonce_field('pethome_guardar_configuracion_nonce'); ?>
                <table class="widefat striped">
                    <thead><tr><th>Servicio</th><th>Precio</th><th style="width:120px;">Acción</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><input type="text" name="nuevo_servicio" class="regular-text" required placeholder="Ej: Guardería Diurna"></td>
                            <td><input type="number" step="0.01" name="nuevo_precio" class="regular-text" required placeholder="Ej: 2500.00"></td>
                            <td><button type="submit" name="pethome_guardar_configuracion" class="button button-primary"><i class="fa-thin fa-plus"></i>Agregar</button></td>
                        </tr>
                    </tbody>
                </table>
            </form>
            <h3 class="wp-heading-inline" style="margin-top:20px;">Servicios Creados</h3>
            <table class="widefat striped">
                <thead><tr><th>Servicio</th><th>Precio</th><th colspan="2" style="width:180px;">Acciones</th></tr></thead>
                <tbody>
                    <?php if ($precios_base) : foreach ($precios_base as $idx => $srv) : ?>
                        <?php if ($editando_precio === $idx) : ?>
                            <tr><form method="post" action="">
                                <?php wp_nonce_field('pethome_guardar_configuracion_nonce'); ?>
                                <td><input type="text" name="servicio_editado" value="<?php echo esc_attr($srv['servicio']); ?>" class="regular-text" required></td>
                                <td><input type="number" step="0.01" name="precio_editado" value="<?php echo esc_attr($srv['precio']); ?>" class="regular-text" required></td>
                                <td><input type="hidden" name="indice_edit" value="<?php echo $idx; ?>"><button type="submit" name="editar_servicio_guardar" class="button button-primary"><i class="fa-thin fa-floppy-disk"></i>Guardar</button></td>
                                <td><a href="?page=pethome_configuracion" class="button"><i class="fa-thin fa-xmark"></i>Cancelar</a></td>
                            </form></tr>
                        <?php else : ?>
                            <tr>
                                <td><?php echo esc_html($srv['servicio']); ?></td>
                                <td><?php echo function_exists('wc_price') ? wc_price($srv['precio']) : '$' . $srv['precio']; ?></td>
                                <td><a href="<?php echo esc_url(add_query_arg('editar', $idx, menu_page_url('pethome_configuracion', false))); ?>" class="button button-small"><i class="fa-thin fa-pen-to-square"></i></a></td>
                                <td><a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'delete', 'borrar_precio' => $idx]), 'pethome_delete_item'); ?>" class="button button-small is-destructive" onclick="return confirm('¿Estás seguro?')"><i class="fa-thin fa-trash-can"></i></a></td>
                            </tr>
                        <?php endif; endforeach; else : ?>
                        <tr><td colspan="4">No hay servicios creados todavía.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="form-container">
            <h2><i class="fa-thin fa-paw"></i>Tipos de Mascotas</h2>
             <form method="post" action="">
                <?php wp_nonce_field('pethome_tipos_action_nonce'); ?>
                <table class="widefat striped">
                    <thead><tr><th>Tipo</th><th>% Recargo/Desc.</th><th style="width:120px;">Acción</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><input type="text" name="tipo_mascota" class="regular-text" required placeholder="Ej: Perro"></td>
                            <td><input type="number" step="0.01" name="recargo_mascota" class="small-text" required> %</td>
                            <td><button type="submit" name="guardar_tipos_mascotas" class="button button-primary"><i class="fa-thin fa-plus"></i>Agregar</button></td>
                        </tr>
                    </tbody>
                </table>
            </form>
            <h3 class="wp-heading-inline" style="margin-top:20px;">Mascotas Creadas</h3>
            <table class="widefat striped">
                <thead><tr><th>Tipo</th><th>% Recargo</th><th colspan="2" style="width:180px;">Acciones</th></tr></thead>
                <tbody>
                    <?php if ($tipos_mascotas) : foreach ($tipos_mascotas as $idx => $t) : ?>
                        <?php if ($editando_tipo === $idx) : ?>
                            <tr><form method="post" action="">
                                <?php wp_nonce_field('pethome_tipos_action_nonce'); ?>
                                <td><input type="text" name="tipo_mascota_edit" value="<?php echo esc_attr($t['tipo']); ?>" class="regular-text" required></td>
                                <td><input type="number" step="0.01" name="recargo_mascota_edit" value="<?php echo esc_attr($t['recargo']); ?>" class="small-text" required> %</td>
                                <td><input type="hidden" name="indice_tipo" value="<?php echo $idx; ?>"><button type="submit" name="editar_tipo_guardar" class="button button-primary"><i class="fa-thin fa-floppy-disk"></i>Guardar</button></td>
                                <td><a href="?page=pethome_configuracion" class="button"><i class="fa-thin fa-xmark"></i>Cancelar</a></td>
                            </form></tr>
                        <?php else : ?>
                            <tr>
                                <td><?php echo esc_html($t['tipo']); ?></td>
                                <td><?php echo esc_html($t['recargo']); ?> %</td>
                                <td><a href="<?php echo esc_url(add_query_arg('editar_tipo', $idx, menu_page_url('pethome_configuracion', false))); ?>" class="button button-small"><i class="fa-thin fa-pen-to-square"></i></a></td>
                                <td><a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'delete', 'borrar_tipo' => $idx]), 'pethome_delete_item'); ?>" class="button button-small is-destructive" onclick="return confirm('¿Estás seguro?')"><i class="fa-thin fa-trash-can"></i></a></td>
                            </tr>
                        <?php endif; endforeach; else : ?>
                        <tr><td colspan="4">No hay tipos de mascotas creados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="form-container">
            <h2><i class="fa-thin fa-shield-dog"></i>Razas</h2>
            <form method="post" action="">
                <?php wp_nonce_field('pethome_razas_action_nonce'); ?>
                <table class="widefat striped">
                    <thead><tr><th>Tipo de Mascota</th><th>Raza</th><th>% Recargo/Desc.</th><th style="width:120px;">Acción</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="tipo_mascota_para_raza" required><option value="">Seleccionar Tipo</option>
                                    <?php foreach ($tipos_mascotas as $tipo) : if (!isset($tipo['tipo']) || empty($tipo['tipo'])) continue; ?>
                                    <option value="<?php echo esc_attr(sanitize_title($tipo['tipo'])); ?>"><?php echo esc_html($tipo['tipo']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="raza" class="regular-text" required placeholder="Ej: Labrador"></td>
                            <td><input type="number" step="0.01" name="recargo_raza" class="small-text" required> %</td>
                            <td><button type="submit" name="guardar_razas" class="button button-primary"><i class="fa-thin fa-plus"></i>Agregar</button></td>
                        </tr>
                    </tbody>
                </table>
            </form>
            <h3 class="wp-heading-inline" style="margin-top:20px;">Razas Creadas</h3>
            <table class="widefat striped">
                <thead><tr><th>Raza</th><th>Tipo de Mascota</th><th>% Recargo</th><th colspan="2" style="width:180px;">Acciones</th></tr></thead>
                <tbody>
                    <?php if ($razas) : foreach ($razas as $idx => $r) : ?>
                        <?php if ($editando_raza === $idx) : ?>
                            <tr><form method="post" action="">
                                <?php wp_nonce_field('pethome_razas_action_nonce'); ?>
                                <td><input type="text" name="raza_edit" value="<?php echo esc_attr($r['raza']); ?>" class="regular-text" required></td>
                                <td>
                                    <select name="tipo_mascota_edit_para_raza" required>
                                        <?php foreach ($tipos_mascotas as $tipo) : if (!isset($tipo['tipo']) || empty($tipo['tipo'])) continue;
                                            $tipo_slug = sanitize_title($tipo['tipo']);
                                            $selected = selected($r['tipo_mascota'], $tipo_slug, false);
                                            echo "<option value='" . esc_attr($tipo_slug) . "' $selected>" . esc_html($tipo['tipo']) . "</option>";
                                        endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="recargo_raza_edit" value="<?php echo esc_attr($r['recargo']); ?>" class="small-text" required> %</td>
                                <td><input type="hidden" name="indice_raza" value="<?php echo $idx; ?>"><button type="submit" name="editar_raza_guardar" class="button button-primary"><i class="fa-thin fa-floppy-disk"></i>Guardar</button></td>
                                <td><a href="?page=pethome_configuracion" class="button"><i class="fa-thin fa-xmark"></i>Cancelar</a></td>
                            </form></tr>
                        <?php else : ?>
                            <tr>
                                <td><?php echo esc_html($r['raza']); ?></td>
                                <td><?php echo esc_html(ucwords(str_replace('-', ' ', $r['tipo_mascota'] ?? 'N/A'))); ?></td>
                                <td><?php echo esc_html($r['recargo']); ?> %</td>
                                <td><a href="<?php echo esc_url(add_query_arg('editar_raza', $idx, menu_page_url('pethome_configuracion', false))); ?>" class="button button-small"><i class="fa-thin fa-pen-to-square"></i></a></td>
                                <td><a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'delete', 'borrar_raza' => $idx]), 'pethome_delete_item'); ?>" class="button button-small is-destructive" onclick="return confirm('¿Estás seguro?')"><i class="fa-thin fa-trash-can"></i></a></td>
                            </tr>
                        <?php endif; endforeach; else : ?>
                        <tr><td colspan="5">No hay razas creadas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
      
        <div class="form-container">
            <h2><i class="fa-thin fa-link"></i>Relación de Costos</h2>
            <form method="post" action="">
                <?php wp_nonce_field('pethome_relaciones_action_nonce'); ?>
                <table class="widefat striped">
                    <thead><tr><th>Nombre de la Relación</th><th>Servicio</th><th>Tabla de Costo</th><th style="width:120px;">Acción</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><input type="text" name="relacion_nombre" class="regular-text" required placeholder="Ej: Tarifa Feriados"></td>
                            <td>
                                <select name="relacion_servicio" required><option value="">Seleccionar Servicio</option>
                                    <optgroup label="Servicios Propios">
                                    <?php foreach ($precios_base as $idx => $srv): ?>
                                        <option value="custom_service:<?php echo esc_attr($idx); ?>"><?php echo esc_html($srv['servicio']); ?></option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Servicios de Booking (WC)">
                                    <?php if ($productos_booking->have_posts()) { while ($productos_booking->have_posts()) { $productos_booking->the_post(); echo '<option value="product_id:' . get_the_ID() . '">' . get_the_title() . '</option>'; } wp_reset_postdata(); } ?>
                                    </optgroup>
                                </select>
                            </td>
                            <td>
                                <select name="relacion_costo_tabla" required><option value="">Seleccionar Tabla</option>
                                    <?php foreach ($costos_configs as $config): ?>
                                        <option value="<?php echo esc_attr($config['id']); ?>"><?php echo esc_html($config['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><button type="submit" name="guardar_relacion_costo" class="button button-primary"><i class="fa-thin fa-plus"></i>Agregar</button></td>
                        </tr>
                    </tbody>
                </table>
            </form>
            <h3 class="wp-heading-inline" style="margin-top:20px;">Relaciones Creadas</h3>
            <table class="widefat striped">
                <thead><tr><th>Nombre</th><th>Servicio Vinculado</th><th>Tabla de Costo Vinculada</th><th colspan="2" style="width:180px;">Acciones</th></tr></thead>
                <tbody>
                    <?php if ($relaciones_costos) : foreach ($relaciones_costos as $idx => $rel) : ?>
                        <?php if ($editando_relacion === $idx) : ?>
                            <tr><form method="post" action="">
                                <?php wp_nonce_field('pethome_relaciones_action_nonce'); ?>
                                <td><input type="text" name="relacion_nombre_edit" value="<?php echo esc_attr($rel['nombre']); ?>" class="regular-text" required></td>
                                <td>
                                    <select name="relacion_servicio_edit" required>
                                        <optgroup label="Servicios Propios">
                                        <?php foreach ($precios_base as $srv_idx => $srv): $value = "custom_service:" . $srv_idx; $selected = selected($rel['servicio_id'], $value, false); ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php echo $selected; ?>><?php echo esc_html($srv['servicio']); ?></option>
                                        <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="Servicios de Booking (WC)">
                                        <?php if ($productos_booking->have_posts()) { while ($productos_booking->have_posts()) { $productos_booking->the_post(); $value = "product_id:" . get_the_ID(); $selected = selected($rel['servicio_id'], $value, false); echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . get_the_title() . '</option>'; } wp_reset_postdata(); } ?>
                                        </optgroup>
                                    </select>
                                </td>
                                <td>
                                    <select name="relacion_costo_tabla_edit" required>
                                        <?php foreach ($costos_configs as $config): $selected = selected($rel['costo_id'], $config['id'], false); ?>
                                            <option value="<?php echo esc_attr($config['id']); ?>" <?php echo $selected; ?>><?php echo esc_html($config['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="hidden" name="indice_relacion" value="<?php echo $idx; ?>"><button type="submit" name="editar_relacion_guardar" class="button button-primary"><i class="fa-thin fa-floppy-disk"></i>Guardar</button></td>
                                <td><a href="?page=pethome_configuracion" class="button"><i class="fa-thin fa-xmark"></i>Cancelar</a></td>
                            </form></tr>
                        <?php else : 
                            $nombre_servicio = 'Servicio no encontrado';
                            if (isset($rel['servicio_id'])) {
                                $parts = explode(':', $rel['servicio_id']);
                                if (count($parts) === 2) {
                                    list($tipo_servicio, $id_servicio) = $parts;
                                    if ($tipo_servicio === 'custom_service' && isset($precios_base[$id_servicio])) { $nombre_servicio = $precios_base[$id_servicio]['servicio']; } 
                                    elseif ($tipo_servicio === 'product_id' && function_exists('wc_get_product')) { $producto = wc_get_product($id_servicio); if ($producto) { $nombre_servicio = $producto->get_name() . ' (WC Booking)'; } }
                                }
                            }
                            $nombre_costo = 'Tabla no encontrada';
                            foreach ($costos_configs as $config) { if ($config['id'] === $rel['costo_id']) { $nombre_costo = $config['name']; break; } }
                        ?>
                            <tr>
                                <td><?php echo esc_html($rel['nombre']); ?></td>
                                <td><?php echo esc_html($nombre_servicio); ?></td>
                                <td><?php echo esc_html($nombre_costo); ?></td>
                                <td><a href="<?php echo esc_url(add_query_arg('editar_relacion', $idx, menu_page_url('pethome_configuracion', false))); ?>" class="button button-small"><i class="fa-thin fa-pen-to-square"></i></a></td>
                                <td><a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'delete', 'borrar_relacion' => $idx]), 'pethome_delete_item'); ?>" class="button button-small is-destructive" onclick="return confirm('¿Estás seguro?')"><i class="fa-thin fa-trash-can"></i></a></td>
                            </tr>
                        <?php endif; endforeach; else : ?>
                        <tr><td colspan="5">No hay relaciones creadas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="form-container" id="whatsapp_section">
            <h2><i class="fa-brands fa-whatsapp"></i>Contactos de WhatsApp</h2>
            <p><?php _e('Gestioná los números individuales y grupos de WhatsApp.', 'pethomehoney-plugin'); ?></p>
            <?php
            $form_action_wa = $contact_to_edit ? 'update' : 'add';
            $form_contact_id_wa = $contact_to_edit ? $contact_to_edit['id'] : '';
            $form_contact_type_wa = $contact_to_edit ? ($contact_to_edit['type'] ?? 'number') : 'number';
            $form_contact_nombre_wa = $contact_to_edit ? ($contact_to_edit['nombre'] ?? '') : '';
            $form_contact_valor_wa = $contact_to_edit ? ($contact_to_edit['valor'] ?? ($contact_to_edit['numero'] ?? '')) : '';
            ?>
            <form method="post" action="">
                <?php wp_nonce_field('pethome_whatsapp_settings_action', 'pethome_whatsapp_nonce'); ?>
                <input type="hidden" name="pethome_whatsapp_action" value="<?php echo esc_attr($form_action_wa); ?>">
                <?php if ($contact_to_edit) : ?>
                    <input type="hidden" name="contact_id" value="<?php echo esc_attr($form_contact_id_wa); ?>">
                <?php endif; ?>
                <table class="widefat striped">
                    <thead><tr>
                        <th>Tipo de Contacto</th>
                        <th>Nombre</th>
                        <th id="valor_label"><?php echo ($form_contact_type_wa === 'group') ? 'Enlace del Grupo' : 'Número'; ?></th>
                        <th style="width:180px;">Acción</th>
                    </tr></thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="contact_type" id="contact_type_select">
                                    <option value="number" <?php selected($form_contact_type_wa, 'number'); ?>>Número</option>
                                    <option value="group" <?php selected($form_contact_type_wa, 'group'); ?>>Grupo</option>
                                </select>
                            </td>
                            <td><input type="text" name="nombre" class="regular-text" required placeholder="Ej: Ventas o Grupo Cuidadores" value="<?php echo esc_attr($form_contact_nombre_wa); ?>"></td>
                            <td><input type="text" name="valor" id="valor_input" class="regular-text" required value="<?php echo esc_attr($form_contact_valor_wa); ?>"></td>
                            <td>
                                <?php if ($contact_to_edit) : ?>
                                    <button type="submit" class="button button-primary"><i class="fa-thin fa-floppy-disk"></i>Guardar</button>
                                    <a href="?page=pethome_configuracion#whatsapp_section" class="button"><i class="fa-thin fa-xmark"></i>Cancelar</a>
                                <?php else : ?>
                                    <button type="submit" class="button button-primary"><i class="fa-thin fa-plus"></i>Agregar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
            <h3 class="wp-heading-inline" style="margin-top: 20px;">Contactos Guardados</h3>
            <table class="widefat striped">
                <thead><tr><th>Nombre</th><th>Tipo</th><th>Valor y Enlace</th><th colspan="2" style="width:180px;">Acciones</th></tr></thead>
                <tbody>
                    <?php if ($whatsapp_contacts) : foreach ($whatsapp_contacts as $contact) : 
                            $is_group = (isset($contact['type']) && $contact['type'] === 'group');
                            $valor_seguro = $contact['valor'] ?? '';
                            $link = $is_group ? esc_url($valor_seguro) : 'https://wa.me/' . esc_attr($valor_seguro);
                            $display_valor = $is_group ? esc_html($valor_seguro) : '+' . esc_html($valor_seguro);
                            $icon_class = $is_group ? 'fa-thin fa-users whatsapp-icon-group' : 'fa-brands fa-whatsapp whatsapp-icon-number';
                        ?>
                        <tr>
                            <td><?php echo esc_html($contact['nombre'] ?? ''); ?></td>
                            <td><?php echo esc_html(ucfirst($contact['type'] ?? 'number')); ?></td>
                            <td>
                                <?php echo $display_valor; ?>
                                <a href="<?php echo $link; ?>" target="_blank" style="text-decoration:none; margin-left:8px;" title="Contactar">
                                    <i class="<?php echo $icon_class; ?>" style="font-size: 1.4em; vertical-align: middle;"></i>
                                </a>
                            </td>
                            <td><a href="<?php echo esc_url(add_query_arg('edit_whatsapp', $contact['id'], menu_page_url('pethome_configuracion', false).'#whatsapp_section')); ?>" class="button button-small"><i class="fa-thin fa-pen-to-square"></i></a></td>
                            <td><a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'delete', 'borrar_whatsapp' => $contact['id']]), 'pethome_delete_whatsapp_item'); ?>" class="button button-small is-destructive" onclick="return confirm('¿Estás seguro?')"><i class="fa-thin fa-trash-can"></i></a></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="5">No hay contactos de WhatsApp creados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="form-container" id="import_settings_section">
            <h2><i class="fa-thin fa-users-gear"></i>Configuración de Sincronización</h2>
            <p>Seleccioná los roles de usuario que querés **excluir** de la sincronización de Clientes y Cuidadores.</p>
            <form method="post" action="">
                <?php wp_nonce_field('pethome_guardar_import_settings_action', 'pethome_guardar_import_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label><?php _e('Excluir Roles', 'pethomehoney-plugin'); ?></label></th>
                        <td>
                            <div class="roles-selection-actions">
                                <button type="button" class="button button-small" id="select-all-roles">Seleccionar Todos</button>
                                <button type="button" class="button button-small" id="select-none-roles">Seleccionar Ninguno</button>
                            </div>
                            <div class="roles-checkbox-group">
                                <?php
                                $editable_roles = get_editable_roles();
                                foreach ($editable_roles as $role => $details) {
                                    $checked = in_array($role, $excluded_roles) ? 'checked' : '';
                                    echo '<label><input type="checkbox" name="excluded_roles[]" value="' . esc_attr($role) . '" ' . $checked . '> ' . esc_html($details['name']) . '</label>';
                                }
                                ?>
                            </div>
                            <p class="description">Los usuarios con los roles seleccionados no serán importados ni como Clientes ni como Cuidadores.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit"><button type="submit" class="button button-primary"><i class="fa-thin fa-floppy-disk"></i> Guardar Exclusiones</button></p>
            </form>
        </div>
        
        <div class="form-container">
            <h2><i class="fa-thin fa-comment-pen"></i>Personalización del Mensaje WhatsApp</h2>
            <form method="post" action="">
              <?php wp_nonce_field( 'pethome_guardar_configuracion_nonce' ); ?>
              <table class="form-table">
                <tr>
                  <th><label for="cliente_mensaje">Texto personalizado</label></th>
                  <td><textarea id="cliente_mensaje" name="cliente_mensaje" rows="5" style="width:100%;" placeholder="Ej: Nombre: {cliente_nombre}\nDNI: {cliente_dni}"><?php echo esc_textarea( $cliente_mensaje ); ?></textarea></td>
                </tr>
              </table>
              <p><button type="submit" name="pethome_guardar_mensaje" class="button button-primary"><i class="fa-thin fa-floppy-disk"></i>Guardar Mensaje</button></p>
            </form>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        // Script para el selector de tipo de contacto de WhatsApp
        const typeSelect = $('#contact_type_select');
        const valorLabel = $('#valor_label');
        const valorInput = $('#valor_input');
        function toggleValorInput(type) {
            if (type === 'group') {
                valorLabel.text('Enlace del Grupo');
                valorInput.attr('placeholder', 'https://chat.whatsapp.com/CODIGO');
            } else {
                valorLabel.text('Número');
                valorInput.attr('placeholder', 'Ej: 5491122334455');
            }
        }
        if(typeSelect.length > 0) {
            toggleValorInput(typeSelect.val());
            typeSelect.on('change', function() {
                toggleValorInput($(this).val());
            });
        }

        // Script para botones de selección de roles
        $('#select-all-roles').on('click', function(e) {
            e.preventDefault();
            $('.roles-checkbox-group input[type="checkbox"]').prop('checked', true);
        });

        $('#select-none-roles').on('click', function(e) {
            e.preventDefault();
            $('.roles-checkbox-group input[type="checkbox"]').prop('checked', false);
        });
    });
    </script>
<?php
}
?>