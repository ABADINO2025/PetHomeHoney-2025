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
 * Función principal que renderiza el panel de "Preguntas Frecuentes".
 */
function pethome_faq_panel() {
    
    // Clave para guardar las FAQs en la base de datos
    $option_name = 'pethome_faqs';

    // --- LÓGICA DE PROCESAMIENTO DE DATOS (POST/GET) ---

    // Obtener el índice que se está editando (si aplica)
    $editing_index = isset($_GET['edit']) ? intval($_GET['edit']) : -1;

    // Lógica para guardar/actualizar una FAQ
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['pethome_faq_nonce']) && wp_verify_nonce($_POST['pethome_faq_nonce'], 'pethome_save_faq_action') ) {
        $faqs = get_option($option_name, []);
        
        // Agregar nueva FAQ
        if (isset($_POST['pethome_add_faq']) && !empty($_POST['faq_question']) && !empty($_POST['faq_answer'])) {
            $new_faq = [
                'question' => sanitize_text_field($_POST['faq_question']),
                'answer'   => sanitize_textarea_field($_POST['faq_answer']),
            ];
            $faqs[] = $new_faq;
            update_option($option_name, $faqs);
            echo '<div class="notice notice-success is-dismissible"><p><strong><i class="fa-thin fa-check" style="margin-right: 8px;"></i>Pregunta frecuente agregada correctamente.</strong></p></div>';
        }

        // Actualizar una FAQ existente
        if (isset($_POST['pethome_update_faq']) && isset($_POST['faq_index'])) {
            $index_to_update = intval($_POST['faq_index']);
            if (isset($faqs[$index_to_update])) {
                $faqs[$index_to_update]['question'] = sanitize_text_field($_POST['faq_question_edit']);
                $faqs[$index_to_update]['answer'] = sanitize_textarea_field($_POST['faq_answer_edit']);
                update_option($option_name, $faqs);
                echo '<div class="notice notice-success is-dismissible"><p><strong><i class="fa-thin fa-check" style="margin-right: 8px;"></i>Pregunta frecuente actualizada.</strong></p></div>';
                $editing_index = -1; // Salir del modo edición
            }
        }
    }

    // Lógica para eliminar una FAQ
    if (isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'pethome_delete_faq_' . $_GET['delete'])) {
            $index_to_delete = intval($_GET['delete']);
            $faqs = get_option($option_name, []);
            if (isset($faqs[$index_to_delete])) {
                unset($faqs[$index_to_delete]);
                update_option($option_name, array_values($faqs)); // Reindexar el array
                echo '<div class="notice notice-success is-dismissible"><p><strong><i class="fa-thin fa-trash-can" style="margin-right: 8px;"></i>Pregunta eliminada.</strong></p></div>';
            }
        }
    }
    
    // Obtener todas las FAQs para mostrarlas
    $all_faqs = get_option($option_name, []);
    ?>

    <style>
        .pethome-admin-wrap { box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 25px; background-color: #fff; border-radius: 8px; margin-top: 20px; }
        .pethome-section { border: 1px solid #ddd; padding: 20px; background-color: #f9f9f9; border-radius: 8px; margin-bottom: 25px; }
        .pethome-section h2 i { margin-right: 12px; }
        .form-table th { width: 150px; }
        .form-table textarea { width: 100%; min-height: 100px; }
        .faq-list .faq-question { font-weight: bold; font-size: 1.1em; color: #5e4365; }
        .faq-list .faq-answer { padding-left: 20px; border-left: 3px solid #5e4365; margin-left: 5px; }
        .faq-actions a { margin-right: 10px; text-decoration: none; }
        .faq-actions .fa-pen-to-square { color: #0073aa; }
        .faq-actions .fa-trash-can { color: #d63638; }
    </style>

    <div class="wrap pethome-admin-wrap">
        <h1 style="color:#5e4365;"><i class="fa-thin fa-circle-question"></i> Panel de Preguntas Frecuentes</h1>
        <p>Desde aquí podés gestionar las preguntas y respuestas que se mostrarán en la sección de FAQ de tu sitio.</p>

        <div class="pethome-section">
            <h2><i class="fa-thin fa-plus"></i> Agregar Nueva Pregunta</h2>
            <form method="POST">
                <?php wp_nonce_field('pethome_save_faq_action', 'pethome_faq_nonce'); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="faq_question">Pregunta</label></th>
                            <td><input type="text" id="faq_question" name="faq_question" class="large-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="faq_answer">Respuesta</label></th>
                            <td><textarea id="faq_answer" name="faq_answer" class="large-text" required></textarea></td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <button type="submit" name="pethome_add_faq" class="button button-primary"><i class="fa-thin fa-floppy-disk" style="margin-right: 8px;"></i>Guardar Pregunta</button>
                </p>
            </form>
        </div>

        <div class="pethome-section">
            <h2><i class="fa-thin fa-list"></i> Listado de Preguntas</h2>
            <div id="faq-list">
                <?php if (!empty($all_faqs)) : ?>
                    <?php foreach ($all_faqs as $index => $faq) : ?>
                        <div class="faq-item" style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                            <?php if ($editing_index === $index) : ?>
                                <form method="POST">
                                    <?php wp_nonce_field('pethome_save_faq_action', 'pethome_faq_nonce'); ?>
                                    <input type="hidden" name="faq_index" value="<?php echo $index; ?>">
                                    <p><input type="text" name="faq_question_edit" value="<?php echo esc_attr($faq['question']); ?>" class="large-text"></p>
                                    <p><textarea name="faq_answer_edit" class="large-text"><?php echo esc_textarea($faq['answer']); ?></textarea></p>
                                    <button type="submit" name="pethome_update_faq" class="button button-primary"><i class="fa-thin fa-floppy-disk" style="margin-right: 8px;"></i>Actualizar</button>
                                    <a href="?page=pethome_faq" class="button">Cancelar</a>
                                </form>
                            <?php else : ?>
                                <p class="faq-question"><?php echo esc_html($faq['question']); ?></p>
                                <div class="faq-answer"><?php echo nl2br(esc_html($faq['answer'])); ?></div>
                                <div class="faq-actions" style="margin-top:10px;">
                                    <a href="<?php echo esc_url(add_query_arg(['page' => 'pethome_faq', 'edit' => $index])); ?>" title="Editar"><i class="fa-thin fa-pen-to-square"></i> Editar</a>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'pethome_faq', 'delete' => $index]), 'pethome_delete_faq_' . $index)); ?>" title="Eliminar" onclick="return confirm('¿Estás seguro de que querés eliminar esta pregunta?');"><i class="fa-thin fa-trash-can"></i> Eliminar</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No hay preguntas frecuentes guardadas todavía.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
?>