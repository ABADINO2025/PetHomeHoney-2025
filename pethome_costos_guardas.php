<?php
/**
 * pethome_costos_guardas.php - Panel para configurar las tablas de costos.
 * Panel de gestión de Clientes con buscador y paginación.
 * Plugin Name: PetHomeHoney Plugin
 * Plugin URI:  https://pethomehoney.com.ar
 * Description: Plugin para gestionar reservas de guarda con WooCommerce y CPT.
 * Version:     1.0 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrolla   www.streaminginternacional.com 
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function pethome_costos_guardas_panel() {
    $opcion_guardado = 'pethomehoney_costos_guardas_configs';
    $opcion_predefinido = 'pethome_default_cost_config_id';
    $config_para_editar = null;

    // --- LÓGICA DE MANEJO DE DATOS (GUARDAR, EDITAR, BORRAR) ---
    
    if ( isset( $_GET['action'], $_GET['config_id'], $_GET['_wpnonce'] ) && $_GET['action'] === 'delete' && wp_verify_nonce( $_GET['_wpnonce'], 'pethome_delete_config_' . $_GET['config_id'] ) ) {
        $configs_guardadas = get_option( $opcion_guardado, [] );
        $id_a_borrar = sanitize_text_field( $_GET['config_id'] );
        $configs_actualizadas = array_filter( $configs_guardadas, function( $c ) use ( $id_a_borrar ) {
            return $c['id'] !== $id_a_borrar;
        } );
        update_option( $opcion_guardado, array_values($configs_actualizadas) );
        if( get_option($opcion_predefinido) === $id_a_borrar ) {
            delete_option($opcion_predefinido);
        }
        echo '<div class="notice notice-success is-dismissible"><p>Configuración borrada correctamente.</p></div>';
    }

    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['pethome_action'], $_POST['_wpnonce_costos'] ) && wp_verify_nonce( $_POST['_wpnonce_costos'], 'pethome_save_update_costs_' . ($_POST['config_id'] ?? 'new') ) ) {
        $configs_guardadas = get_option( $opcion_guardado, [] );
        $nombre_config = sanitize_text_field( $_POST['config_name'] );
        $costo_base = sanitize_text_field( $_POST['costo_base'] );
        $dias_data = [];
        for ($i=1; $i <= 31; $i++) {
            $dias_data[] = [
                'incrDia' => sanitize_text_field($_POST["incr_dia_{$i}"]),
                'porcPM'  => sanitize_text_field($_POST["porc_pm_{$i}"]),
                'porcMG'  => sanitize_text_field($_POST["porc_mg_{$i}"]),
            ];
        }
        $datos_config = ['costoBase' => $costo_base, 'dias' => $dias_data];

        if ( $_POST['pethome_action'] === 'save_new' ) {
            $nueva_config = [ 'id' => uniqid('conf_'), 'name' => $nombre_config, 'data' => $datos_config ];
            $configs_guardadas[] = $nueva_config;
            echo '<div class="notice notice-success is-dismissible"><p>Nueva configuración guardada.</p></div>';
        
        } elseif ( $_POST['pethome_action'] === 'update_existing' && !empty($_POST['config_id']) ) {
            $id_a_actualizar = sanitize_text_field( $_POST['config_id'] );
            foreach ( $configs_guardadas as $idx => $config ) {
                if ( $config['id'] === $id_a_actualizar ) {
                    $configs_guardadas[$idx]['name'] = $nombre_config;
                    $configs_guardadas[$idx]['data'] = $datos_config;
                    break;
                }
            }
            echo '<div class="notice notice-success is-dismissible"><p>Configuración actualizada.</p></div>';
        }
        update_option( $opcion_guardado, $configs_guardadas );
    }

    if ( isset( $_GET['action'], $_GET['config_id'] ) && $_GET['action'] === 'edit' ) {
        $configs_guardadas = get_option( $opcion_guardado, [] );
        $id_a_editar = sanitize_text_field( $_GET['config_id'] );
        foreach ( $configs_guardadas as $config ) {
            if ( $config['id'] === $id_a_editar ) {
                $config_para_editar = $config;
                break;
            }
        }
    }

    $configs_guardadas = get_option( $opcion_guardado, [] );
    $default_config_id = get_option( $opcion_predefinido, null );
?>
<div class="wrap pethomehoney-cost-generator">
    
    <style>
        .pethomehoney-cost-generator { margin: 20px 25px; }
        .form-container { background-color: #fff; padding: 25px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-bottom: 30px; }
        .pethomehoney-cost-generator h1 .fa-thin, .pethomehoney-cost-generator h2 .fa-thin { margin-right: 10px; color: #5A4A9C; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
        .form-container .form-group input[type="text"],
        .form-container .form-group input[type="number"],
        .cost-table td input.percentage-input { background-color: #e9ecef; }
        .cost-table { width: 90%; border-collapse: separate; border-spacing: 0; margin-top: 20px; margin-left: auto; margin-right: auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; }
        .cost-table thead th { background-color: #5e4365; color: #ffffff; font-weight: 600; padding: 12px 8px; border: none; white-space: nowrap; }
        .cost-table td { text-align: center; border-top: 1px solid #e0e0e0; padding: 2px 4px; }
        .cost-table tbody tr:nth-child(even) { background-color: #fdfdff; }
        .cost-table td input { width: 100%; border: none; background-color: transparent; text-align: center; font-size: 14px; padding: 4px; box-sizing: border-box; }
        .cost-table td input.percentage-input { font-weight: bold; border-radius: 3px; padding: 6px 4px; }
        .cost-table td input.percentage-input:focus { outline: 1px solid #5A4A9C; }
        .cost-table td input:read-only { color: #333; font-weight: 500; background-color: transparent !important; }
        .config-actions { margin-top: 20px; display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 0 5%; }
        .delete-action a { color: #d63638; text-decoration: none; }
        .delete-action a:hover { color: #a02122; }
        .config-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .config-header h2 { margin: 0; }
        .default-selector { background: #f0f0f0; border: 1px solid #ddd; padding: 5px 10px; border-radius: 4px; font-size: 12px; }
        .default-selector label { font-weight: 600; cursor: pointer; color: #3c434a;}
        .default-selector .spinner { float: none; vertical-align: middle; }
        .default-selector .saved-notice { color: #28a745; margin-left: 8px; font-style: italic; }
    </style>

    <h1><i class="fa-thin fa-cogs"></i>Configurador de Costos de Guardas</h1>
    
    <div class="form-container">
        <form class="cost-calculator-form" method="POST">
            <h2><i class="fa-thin fa-file-pen"></i>Crear Nueva Configuración</h2>
            <input type="hidden" name="pethome_action" value="save_new">
            <input type="hidden" name="config_id" value="new">
            <?php wp_nonce_field( 'pethome_save_update_costs_new', '_wpnonce_costos' ); ?>
            
            <div class="form-group">
                <label for="config_name_new">Nombre de la Configuración</label>
                <input type="text" id="config_name_new" name="config_name" placeholder="Ej: Temporada Baja 2025" required>
            </div>
            <div class="form-group">
                <label for="costo_base_new">Costo Diario Base (Día 1, Pequeño)</label>
                <input type="number" id="costo_base_new" name="costo_base" class="costo-base-input" placeholder="Ej: 10000" min="0" step="100">
            </div>
            
            <table class="cost-table">
                <thead><tr><th>Día</th><th style="width: 130px;">Incr. % por Día</th><th>Pequeño</th><th style="width: 120px;">% Peq a Med</th><th>Mediano</th><th style="width: 120px;">% Med a Gde</th><th>Grande</th></tr></thead>
                <tbody class="tabla-costos-body">
                    <?php for ($i=1; $i <= 31; $i++): ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td><input type="number" name="incr_dia_<?php echo $i; ?>" class="percentage-input incr-dia-input" value="0" min="0"></td>
                        <td><input type="text" class="output-pequeno" readonly></td>
                        <td><input type="number" name="porc_pm_<?php echo $i; ?>" class="percentage-input porcentaje-pm-input" value="20" min="0"></td>
                        <td><input type="text" class="output-mediano" readonly></td>
                        <td><input type="number" name="porc_mg_<?php echo $i; ?>" class="percentage-input porcentaje-mg-input" value="20" min="0"></td>
                        <td><input type="text" class="output-grande" readonly></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <div class="config-actions">
                <div></div>
                <button type="submit" class="button button-primary"><i class="fa-thin fa-save"></i> Guardar Nueva Configuración</button>
            </div>
        </form>
    </div>

    <hr>
    <h2><i class="fa-thin fa-list-ul"></i>Configuraciones Guardadas</h2>
    <?php if (empty($configs_guardadas)): ?>
        <p>No hay configuraciones guardadas.</p>
    <?php else: ?>
        <?php foreach($configs_guardadas as $config): ?>
            <div class="form-container">
                <div class="config-header">
                    <h2><i class="fa-thin fa-file-invoice-dollar"></i> <?php echo esc_html($config['name']); ?></h2>
                    <div class="default-selector">
                        <label>
                            <input type="radio" 
                                   name="pethome_default_config" 
                                   value="<?php echo esc_attr($config['id']); ?>"
                                   <?php checked($default_config_id, $config['id']); ?>>
                            Marcar como predefinido
                        </label>
                        <span class="spinner"></span>
                    </div>
                </div>
                <form class="cost-calculator-form" method="POST">
                    <input type="hidden" name="pethome_action" value="update_existing">
                    <input type="hidden" name="config_id" value="<?php echo esc_attr($config['id']); ?>">
                    <?php wp_nonce_field( 'pethome_save_update_costs_' . $config['id'], '_wpnonce_costos' ); ?>

                    <div class="form-group">
                        <label for="config_name_<?php echo esc_attr($config['id']); ?>">Nombre de la Configuración</label>
                        <input type="text" id="config_name_<?php echo esc_attr($config['id']); ?>" name="config_name" value="<?php echo esc_attr($config['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="costo_base_<?php echo esc_attr($config['id']); ?>">Costo Diario Base (Día 1, Pequeño)</label>
                        <input type="number" id="costo_base_<?php echo esc_attr($config['id']); ?>" name="costo_base" class="costo-base-input" value="<?php echo esc_attr($config['data']['costoBase']); ?>" min="0" step="100">
                    </div>

                    <table class="cost-table">
                        <thead><tr><th>Día</th><th style="width: 130px;">Incr. % por Día</th><th>Pequeño</th><th style="width: 120px;">% Peq a Med</th><th>Mediano</th><th style="width: 120px;">% Med a Gde</th><th>Grande</th></tr></thead>
                        <tbody class="tabla-costos-body">
                            <?php for ($i=1; $i <= 31; $i++): 
                                $day_data = $config['data']['dias'][$i-1];
                            ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><input type="number" name="incr_dia_<?php echo $i; ?>" class="percentage-input incr-dia-input" value="<?php echo esc_attr($day_data['incrDia']); ?>" min="0"></td>
                                <td><input type="text" class="output-pequeno" readonly></td>
                                <td><input type="number" name="porc_pm_<?php echo $i; ?>" class="percentage-input porcentaje-pm-input" value="<?php echo esc_attr($day_data['porcPM']); ?>" min="0"></td>
                                <td><input type="text" class="output-mediano" readonly></td>
                                <td><input type="number" name="porc_mg_<?php echo $i; ?>" class="percentage-input porcentaje-mg-input" value="<?php echo esc_attr($day_data['porcMG']); ?>" min="0"></td>
                                <td><input type="text" class="output-grande" readonly></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                    <div class="config-actions">
                         <?php
                            $delete_nonce = wp_create_nonce('pethome_delete_config_' . $config['id']);
                            $delete_url = '?page=pethome_costos_guardas&action=delete&config_id=' . esc_attr($config['id']) . '&_wpnonce=' . $delete_nonce;
                        ?>
                        <div class="delete-action"><a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('¿Estás seguro de que querés borrar esta configuración?');"><i class="fa-thin fa-trash-alt"></i> Borrar</a></div>
                        <button type="submit" class="button button-primary"><i class="fa-thin fa-sync-alt"></i> Actualizar Cambios</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        function formatCurrency(value) {
            if (isNaN(value) || value === 0) return '$ 0,00';
            let valorString = value.toFixed(2);
            let partes = valorString.split('.');
            let parteEntera = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            return `$ ${parteEntera},${partes[1]}`;
        }

        class CostCalculator {
            constructor(formElement) { this.form = formElement; this.costoBaseInput = this.form.querySelector('.costo-base-input'); this.rows = this.form.querySelectorAll('.tabla-costos-body tr'); this.init(); }
            init() { this.attachEventListeners(); this.calculateAndRender(); }
            attachEventListeners() { this.costoBaseInput.addEventListener('input', () => this.calculateAndRender()); this.form.querySelectorAll('.percentage-input').forEach(input => { input.addEventListener('input', () => this.calculateAndRender()); }); }
            calculateAndRender() {
                const costoBase = parseFloat(this.costoBaseInput.value) || 0;
                let costoPequenoAnterior = costoBase;
                this.rows.forEach((row, index) => {
                    const dayIndex = index + 1;
                    const incrDia = parseFloat(row.querySelector('.incr-dia-input').value) || 0;
                    const porcPM = parseFloat(row.querySelector('.porcentaje-pm-input').value) || 0;
                    const porcMG = parseFloat(row.querySelector('.porcentaje-mg-input').value) || 0;
                    let costoPequenoActual = (dayIndex === 1) ? costoBase : costoPequenoAnterior * (1 + incrDia / 100);
                    const costoMediano = costoPequenoActual * (1 + porcPM / 100);
                    const costoGrande = costoMediano * (1 + porcMG / 100);
                    row.querySelector('.output-pequeno').value = formatCurrency(costoPequenoActual);
                    row.querySelector('.output-mediano').value = formatCurrency(costoMediano);
                    row.querySelector('.output-grande').value = formatCurrency(costoGrande);
                    costoPequenoAnterior = costoPequenoActual;
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const calculatorForms = document.querySelectorAll('.cost-calculator-form');
            calculatorForms.forEach(form => { new CostCalculator(form); });

            // Lógica para guardar la config predefinida
            const radios = document.querySelectorAll('input[name="pethome_default_config"]');
            
            radios.forEach(radio => {
                radio.addEventListener('change', function() {
                    const configId = this.value;
                    const spinner = this.closest('.default-selector').querySelector('.spinner');
                    const label = this.closest('label');

                    // Eliminar avisos anteriores
                    document.querySelectorAll('.saved-notice').forEach(n => n.remove());

                    spinner.classList.add('is-active');

                    // Usamos el ajax_object que ya está disponible en el admin de WordPress
                    const formData = new FormData();
                    formData.append('action', 'pethome_set_default_cost_config');
                    formData.append('config_id', configId);
                    formData.append('nonce', ajax_object.nonce); // Reutilizamos el nonce global

                    fetch(ajaxurl, { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(result => {
                            spinner.classList.remove('is-active');
                            if(result.success) {
                                const notice = document.createElement('span');
                                notice.className = 'saved-notice';
                                notice.textContent = 'Guardado';
                                label.appendChild(notice);
                                setTimeout(() => { notice.remove(); }, 2000);
                            } else {
                                alert('Error: ' + result.data.message);
                            }
                        })
                        .catch(error => {
                            spinner.classList.remove('is-active');
                            alert('Error de conexión.');
                        });
                });
            });
        });
    </script>
</div>
<?php
}
?>