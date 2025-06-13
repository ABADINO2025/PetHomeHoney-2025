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
 
if (!isset($_GET['id'])) {
    echo '<div class="notice notice-error"><p>Error: Falta el parámetro ID del cuidador.</p></div>';
    return;
}

$id = intval($_GET['id']);
$cuidadores = get_option('pethome_cuidadores', []);

if (!isset($cuidadores[$id])) {
    echo '<div class="notice notice-error"><p>Error: No se encontró el cuidador con ID ' . $id . '.</p></div>';
    return;
}

$cuidador = $cuidadores[$id];

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cuidador_id'])) {
    $cuidador_actualizado = [
        "alias" => sanitize_text_field($_POST["cuidador_alias"]),
        "nombre" => sanitize_text_field($_POST["cuidador_nombre"]),
        "apellido" => sanitize_text_field($_POST["cuidador_apellido"]),
        "email" => sanitize_email($_POST["cuidador_email"]),
        "telefono" => sanitize_text_field($_POST["cuidador_telefono"]),
        "calle" => sanitize_text_field($_POST["cuidador_domicilio_calle"]),
        "numero" => sanitize_text_field($_POST["cuidador_domicilio_numero"]),
        "piso" => sanitize_text_field($_POST["cuidador_domicilio_piso"]),
        "barrio" => sanitize_text_field($_POST["cuidador_domicilio_barrio"]),
        "dni" => sanitize_text_field($_POST["cuidador_dni"]),
        "alias_bancario" => sanitize_text_field($_POST["cuidador_alias_bancario"]),
        "temp1" => sanitize_text_field($_POST["cuidador_temp1"]),
        "cuidador" => esc_url_raw($_POST["imagen_cuidador"]),
        "dni_frente" => esc_url_raw($_POST["imagen_dni_frente"]),
        "dni_dorso" => esc_url_raw($_POST["imagen_dni_dorso"]),
        "phh" => esc_url_raw($_POST["imagen_phh"]),
        "domicilio" => esc_url_raw($_POST["imagen_domicilio"]),
    ];

    $cuidadores[$id] = $cuidador_actualizado;
    update_option('pethome_cuidadores', $cuidadores);
    $cuidador = $cuidador_actualizado;

    echo '<div class="notice notice-success is-dismissible"><p>✅ Cuidador actualizado correctamente.</p></div>';
}

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
    margin-bottom: 20px;
    color: #5e4365;
}
.pethome-grid {
    display: grid;
    gap: 16px;
}
.grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
.grid-5 { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
.pethome-grid label {
    display: block;
    font-weight: bold;
    margin-bottom: 4px;
    color: #5e4365;
}
.pethome-grid input[type="text"],
.pethome-grid input[type="email"] {
    width: 100%;
    background: #f0f0f1;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 6px;
}
.imagen-previa {
    width: 100%;
    height: auto; /* mantiene proporciones */
    border-radius: 8px;
    margin-top: 10px;
    display: block;
}
.guardar-cuidador {
    background: #5e4365;
    color: white;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    padding: 10px 30px;
    margin-top: 30px;
    height: 46px;
    cursor: pointer;
}
.guardar-cuidador:hover {
    background: #7a5d8d;
}

.button-media {
    background: #5e4365;
    color: white;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    padding: 10px;
    width: 100%;
    height: 46px;
    cursor: pointer;
    text-align: center;
}
.button-media:hover {
    background: #7a5d8d;
}

.popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.85);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.popup-image-container {
    max-height: 550px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 8px;
    transform: translateY(100px); /* 👈 mueve todo 100px hacia abajo */
}

.popup-image-container img {
    height: 500px;
    width: auto;
    border-radius: 8px;
    box-shadow: 0 0 30px rgba(0,0,0,0.4);
}

.popup-close {
    position: absolute;
    top: 60px;       /* antes 30px → 30px más abajo */
    right: 60px;     /* antes 30px → 30px más adentro */
    font-size: 28px;
    color: #ffffff;
    font-weight: bold;
    cursor: pointer;
    z-index: 10000;
}
	
</style>

<h1 style="color:#5e4365;">✏️ Editar Cuidador</h1>
<form method="post">
    <input type="hidden" name="cuidador_id" value="<?php echo esc_attr($id); ?>">

    <!-- Datos del cuidador -->
    <div class="section-block">
        <h2>👤 Datos del Cuidador</h2>
        <div class="pethome-grid grid-4">
            <?php
            $campos = [
                ['cuidador_alias', 'Alias'],
                ['cuidador_nombre', 'Nombre'],
                ['cuidador_apellido', 'Apellido'],
                ['cuidador_email', 'Email'],
                ['cuidador_telefono', 'Teléfono'],
                ['cuidador_domicilio_calle', 'Calle'],
                ['cuidador_domicilio_numero', 'Número'],
                ['cuidador_domicilio_piso', 'Piso'],
                ['cuidador_domicilio_barrio', 'Barrio'],
                ['cuidador_dni', 'DNI'],
                ['cuidador_alias_bancario', 'Alias Bancario'],
                ['cuidador_temp1', 'Temp1'],
            ];

            foreach ($campos as $campo) {
                $name = $campo[0];
                $label = $campo[1];
                $valor = esc_attr($cuidador[str_replace('cuidador_', '', $name)] ?? '');
                echo "<label>{$label}<input type='text' name='{$name}' value='{$valor}'></label>";
            }
            ?>
        </div>
    </div>

    <!-- Imágenes -->
    <div class="section-block">
        <h2>📂 Archivos del Cuidador</h2>
        <div class="pethome-grid grid-5">
            <?php
            $imagenes = [
                ['imagen_cuidador', '📸 Foto del Cuidador'],
                ['imagen_dni_frente', '🪪 DNI Frente'],
                ['imagen_dni_dorso', '🪪 DNI Dorso'],
                ['imagen_phh', '📘 PHH'],
                ['imagen_domicilio', '🏠 Domicilio'],
            ];

            foreach ($imagenes as $img) {
                $campo = $img[0];
                $label = $img[1];
                $url = esc_url($cuidador[str_replace('imagen_', '', $campo)] ?? '');
                echo "<div>
                    <label>{$label}</label>
                    <input type='hidden' name='{$campo}' id='{$campo}_input' value='{$url}'>
                    <button type='button' class='button-media select-media' data-target='{$campo}_input' data-preview='{$campo}_preview'>📎 Cambiar imagen</button>
                    <img id='{$campo}_preview' class='imagen-previa' src='{$url}' " . ($url ? '' : 'style="display:none;"') . ">
                </div>";
            }
            ?>
        </div>
    </div>

    <input type="submit" class="guardar-cuidador" value="Guardar Cambios">
</form>

<script>
jQuery(document).ready(function($) {
    $('.select-media').click(function(e) {
        e.preventDefault();
        var inputID = $(this).data('target');
        var previewID = $(this).data('preview');

        var frame = wp.media({
            title: 'Seleccionar imagen',
            multiple: false,
            library: { type: 'image' },
            button: { text: 'Usar esta imagen' }
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#' + inputID).val(attachment.url);
            $('#' + previewID).attr('src', attachment.url).show();
        });

        frame.open();
    });
});
</script>

<div class="popup-overlay" id="popupOverlay">
    <div class="popup-close" id="popupClose">✖</div>
    <div class="popup-image-container">
        <img id="popupImage" src="" alt="Vista ampliada">
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Popup ampliar imagen
    $('.imagen-previa').click(function() {
        var src = $(this).attr('src');
        $('#popupImage').attr('src', src);
        $('#popupOverlay').fadeIn();
    });

    $('#popupClose, #popupOverlay').click(function(e) {
        if (e.target.id === 'popupOverlay' || e.target.id === 'popupClose') {
            $('#popupOverlay').fadeOut();
        }
    });
});
</script>
