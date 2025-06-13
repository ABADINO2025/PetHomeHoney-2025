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
 * Muestra el panel de importación y sincronización.
 */
function pethome_importador_panel() {
?>

<style>
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
    .section-block h2 i {
        margin-right: 10px;
    }
    .section-block p {
        padding: 0 5px;
        font-size: 14px;
        line-height: 1.5;
    }
    .section-block .button i {
        margin-right: 5px;
    }
    .pethome-import-actions { 
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 20px;
    }
    .import-results {
        margin-top: 15px;
        padding: 12px;
        border-left: 4px solid;
        display: none;
        background-color: #fff;
    }
    .import-results.success {
        border-color: #28a745;
        color: #155724;
    }
    .import-results.error {
        border-color: #dc3545;
        color: #721c24;
    }
    .import-results p {
        margin: 0;
        padding: 0;
        font-weight: bold;
    }
</style>

<div class="wrap">
    <h1 style="color:#5e4365; text-align:center;"><i class="fa-thin fa-upload"></i> Importación y Sincronización</h1>
    
    <div class="section-block" id="import_clientes_section">
        <h2><i class="fa-thin fa-users-gear"></i>Sincronización de Clientes</h2>
        <p>Convertí usuarios de WordPress en Clientes de PetHomeHoney, excluyendo los roles que selecciones en la página de <a href="<?php echo admin_url('admin.php?page=pethome_configuracion#import_settings_section'); ?>">Configuración</a>.</p>
        <div class="pethome-import-actions">
            <button type="button" id="pethome-import-clientes-btn" class="button button-primary"><i class="fa-thin fa-users"></i> Sincronizar Clientes</button>
            <button type="button" id="pethome-reset-clientes-btn" class="button"><i class="fa-thin fa-arrow-rotate-left"></i> Resetear Clientes</button>
            <span class="spinner" id="import-clientes-spinner"></span>
        </div>
        <div id="import-clientes-results" class="import-results"></div>
    </div>

    <div class="section-block" id="import_cuidadores_section">
        <h2><i class="fa-thin fa-user-gear"></i>Importación de Cuidadores</h2>
        <p>Importá usuarios de WordPress con el rol de <strong>"Employee"</strong> o <strong>"Cuidador"</strong> al listado de Cuidadores de PetHomeHoney.</p>
        <p class="description">El sistema tomará el alias, nombre, apellido y email del usuario. Los demás datos deberán completarse manualmente desde el panel de cuidadores.</p>
        <div class="pethome-import-actions">
            <button type="button" id="pethome-import-cuidadores-btn" class="button button-primary"><i class="fa-thin fa-download"></i> Importar Cuidadores</button>
            <span class="spinner" id="import-cuidadores-spinner"></span>
        </div>
        <div id="import-cuidadores-results" class="import-results"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Sincronización de Clientes
    $('#pethome-import-clientes-btn').on('click', function() {
        var spinner = $('#import-clientes-spinner');
        var resultsDiv = $('#import-clientes-results');
        spinner.addClass('is-active');
        resultsDiv.hide();

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pethome_importar_clientes',
                nonce: ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.removeClass('error').addClass('success').html('<p>' + response.data.message + '<br>Nuevos: ' + response.data.nuevos + ' | Existentes: ' + response.data.existentes + ' | Eliminados: ' + response.data.eliminados + '</p>').show();
                } else {
                    resultsDiv.removeClass('success').addClass('error').html('<p>Error: ' + response.data.message + '</p>').show();
                }
            },
            error: function() {
                resultsDiv.removeClass('success').addClass('error').html('<p>Error de conexión al intentar sincronizar.</p>').show();
            },
            complete: function() {
                spinner.removeClass('is-active');
            }
        });
    });

    // Resetear Clientes
    $('#pethome-reset-clientes-btn').on('click', function() {
        if (!confirm('¿Estás seguro de que querés eliminar la marca de "cliente" de TODOS los usuarios? Esta acción no se puede deshacer.')) {
            return;
        }
        var spinner = $('#import-clientes-spinner');
        var resultsDiv = $('#import-clientes-results');
        spinner.addClass('is-active');
        resultsDiv.hide();

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pethome_reset_clientes',
                nonce: ajax_object.nonce,
                role: 'all_roles'
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.removeClass('error').addClass('success').html('<p>' + response.data.message + ' Se quitaron ' + response.data.eliminados + ' marcas de cliente.</p>').show();
                } else {
                    resultsDiv.removeClass('success').addClass('error').html('<p>Error: ' + response.data.message + '</p>').show();
                }
            },
            error: function() {
                resultsDiv.removeClass('success').addClass('error').html('<p>Error de conexión al intentar resetear.</p>').show();
            },
            complete: function() {
                spinner.removeClass('is-active');
            }
        });
    });

    // Importación de Cuidadores
    $('#pethome-import-cuidadores-btn').on('click', function() {
        var spinner = $('#import-cuidadores-spinner');
        var resultsDiv = $('#import-cuidadores-results');
        spinner.addClass('is-active');
        resultsDiv.hide();

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'pethome_importar_cuidadores',
                nonce: ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    resultsDiv.removeClass('error').addClass('success').html('<p>' + response.data.message + '<br>Nuevos Cuidadores Importados: ' + response.data.nuevos + ' | Ya existentes: ' + response.data.existentes + '</p>').show();
                } else {
                    resultsDiv.removeClass('success').addClass('error').html('<p>Error: ' + response.data.message + '</p>').show();
                }
            },
            error: function() {
                resultsDiv.removeClass('success').addClass('error').html('<p>Error de conexión al intentar importar cuidadores.</p>').show();
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