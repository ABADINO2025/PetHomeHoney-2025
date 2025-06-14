<?php
/**
 * Panel de gestión de Clientes con buscador y paginación.
 * Version:     1.0 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrolla   www.streaminginternacional.com 
 */
function pethome_cuidadores_panel() {
    // Procesar la acción de borrado
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['borrar_cuidador'])) {
        $indice_a_borrar = intval($_GET['borrar_cuidador']);
        $nonce = $_REQUEST['_wpnonce'] ?? '';

        if (wp_verify_nonce($nonce, 'pethome_delete_cuidador_' . $indice_a_borrar)) {
            $cuidadores = get_option('pethome_cuidadores', []);
            if (isset($cuidadores[$indice_a_borrar])) {
                unset($cuidadores[$indice_a_borrar]);
                // Re-indexar el array para mantener la consistencia
                $cuidadores = array_values($cuidadores);
                update_option('pethome_cuidadores', $cuidadores);
                echo '<div class="notice notice-success is-dismissible"><p><i class="fa-thin fa-check"></i> Cuidador eliminado correctamente.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Error de seguridad. La operación fue cancelada.</p></div>';
        }
    }

    // Título principal centrado
    echo '<h1 style="color:#5e4365; text-align:center;"><i class="fa-thin fa-users"></i> Gestión de Cuidadores</h1>';

    // Procesar formulario de alta
    if ( $_SERVER["REQUEST_METHOD"] === "POST" && ! empty( $_POST["cuidador_alias"] ) ) {
        $cuidadores = get_option( 'pethome_cuidadores', [] );

        $nuevo = [
            "alias"              => sanitize_text_field( $_POST["cuidador_alias"] ),
            "nombre"             => sanitize_text_field( $_POST["cuidador_nombre"] ),
            "apellido"           => sanitize_text_field( $_POST["cuidador_apellido"] ),
            "email"              => sanitize_email( $_POST["cuidador_email"] ),
            "telefono"           => sanitize_text_field( $_POST["cuidador_telefono"] ),
            "calle"              => sanitize_text_field( $_POST["cuidador_domicilio_calle"] ),
            "numero"             => sanitize_text_field( $_POST["cuidador_domicilio_numero"] ),
            "piso"               => sanitize_text_field( $_POST["cuidador_domicilio_piso"] ),
            "barrio"             => sanitize_text_field( $_POST["cuidador_domicilio_barrio"] ),
            "dni"                => sanitize_text_field( $_POST["cuidador_dni"] ),
            "alias_bancario"     => sanitize_text_field( $_POST["cuidador_alias_bancario"] ),
            "temp1"              => sanitize_text_field( $_POST["cuidador_temp1"] ),
            "imagen"             => esc_url_raw( $_POST["imagen_cuidador"]   ?? '' ),
            "dni_frente"         => esc_url_raw( $_POST["imagen_dni_frente"] ?? '' ),
            "dni_dorso"          => esc_url_raw( $_POST["imagen_dni_dorso"]  ?? '' ),
            "phh"                => esc_url_raw( $_POST["imagen_phh"]        ?? '' ),
            "domicilio_img"      => esc_url_raw( $_POST["imagen_domicilio"]  ?? '' ),
        ];

        $cuidadores[] = $nuevo;
        update_option( 'pethome_cuidadores', $cuidadores );
        echo '<div class="notice notice-success is-dismissible"><p><i class="fa-thin fa-check"></i> Cuidador guardado correctamente.</p></div>';
    }

    // Estilos
    echo '<style>
    .section-block {
        background: #f9f9f9;
        border: 2px solid #ccc;
        border-radius: 16px;
        padding: 20px;
        margin-top: 30px;
        overflow-x: auto; 
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
    .pethome-grid { display: grid; gap: 16px; }
    .grid-4 { grid-template-columns: repeat(4, 1fr); }
    .grid-5 { grid-template-columns: repeat(5, 1fr); }
    .pethome-grid label { display: block; font-weight: bold; margin-bottom: 4px; color: #5e4365; }
    .pethome-grid label i {
        margin-right: 8px;
        width: 18px;
        text-align: center;
    }
    .pethome-grid input[type="text"],
    .pethome-grid input[type="email"] {
        width: 100%; background: #f0f0f1; padding: 8px; border: 1px solid #ccc; border-radius: 6px;
    }
    .media-button {
        display: block; width: 100%; text-align: center; height: 46px; line-height: 46px;
        background: #5e4365; color: #fff; font-weight: bold; border: none; border-radius: 6px;
        cursor: pointer; margin-bottom: 10px;
    }
    .media-button:hover { background: #7a5d8d; }
    .preview-image { margin-top: 10px; width: 100%; height: auto; border-radius: 6px; }
    table.cuidadores-listado {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 20px;
        border: 1px solid #ddd;
        border-radius: 16px;
        overflow: hidden;
        table-layout: fixed;
    }
    table.cuidadores-listado th {
        background-color: #5e4365;
        color: #ffffff !important;
        text-align: center !important; /* CORRECCIÓN: Se asegura el centrado */
        font-weight: bold;
    }
    table.cuidadores-listado td {
        background-color: #ffffff;
    }
    table.cuidadores-listado th, table.cuidadores-listado td {
        padding: 12px 8px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
        overflow-wrap: break-word;
    }
    table.cuidadores-listado td {
        text-align: left; /* Restablece la alineación para las celdas de datos */
    }
    table.cuidadores-listado tr:hover td {
        background-color: #f3eef4;
    }
    table.cuidadores-listado th:first-child { border-top-left-radius: 12px; }
    table.cuidadores-listado th:last-child  { border-top-right-radius: 12px; }
    table.cuidadores-listado tr:last-child td {
        border-bottom: none;
    }
    
    td.foto-cuidador-cell {
        padding: 5px !important;
        text-align: center;
    }
    td.foto-cuidador-cell img {
        width: 70px;
        height: 70px;
        border-radius: 8px;
        object-fit: cover;
        display: inline-block;
        vertical-align: middle;
    }
    
    .whatsapp-link {
        margin-left: 8px;
        vertical-align: middle;
    }
    .whatsapp-link .fa-whatsapp {
        font-size: 24px;
        color: #25D366;
        line-height: 1;
    }

    .user-linked-icon {
        color: #5e4365;
        font-size: 1.1em;
        margin-left: 5px;
    }

    .guardar-cuidador {
        display: block; margin: 20px auto; width: 200px; height: 46px; line-height: 46px;
        background: #5e4365; color: #fff; font-weight: bold; border: none; border-radius: 6px;
        cursor: pointer;
    }
    .guardar-cuidador:hover { background: #7a5d8d; }
    .button.button-small {
        min-width: 30px;
        text-align: center;
    }
    </style>';

    // Formulario de alta
    echo '<form method="post">';
    echo '<div class="section-block"><h2><i class="fa-thin fa-user-plus"></i> Datos del Cuidador</h2><div class="pethome-grid grid-4">';
    $fields = [
        ['cuidador_alias','Alias'], ['cuidador_nombre','Nombre'], ['cuidador_apellido','Apellido'],
        ['cuidador_email','Email'], ['cuidador_telefono','Teléfono'], ['cuidador_domicilio_calle','Calle'],
        ['cuidador_domicilio_numero','Número'], ['cuidador_domicilio_piso','Piso'],
        ['cuidador_domicilio_barrio','Barrio'], ['cuidador_dni','DNI'],
        ['cuidador_alias_bancario','Alias Bancario'], ['cuidador_temp1','Temp1'],
    ];
    foreach ( $fields as $f ) {
        echo "<label>{$f[1]}<input type='text' name='{$f[0]}' required></label>";
    }
    echo '</div></div>';

    echo '<div class="section-block"><h2><i class="fa-thin fa-folder-open"></i> Archivos del Cuidador</h2><div class="pethome-grid grid-5">';
    $imgFields = [
        ['imagen_cuidador','<i class="fa-thin fa-camera"></i> Cuidador'],
        ['imagen_dni_frente','<i class="fa-thin fa-id-card"></i> DNI Frente'],
        ['imagen_dni_dorso','<i class="fa-thin fa-id-card"></i> DNI Dorso'],
        ['imagen_phh','<i class="fa-thin fa-book"></i> PHH'],
        ['imagen_domicilio','<i class="fa-thin fa-house"></i> Domicilio'],
    ];
    foreach ( $imgFields as $img ) {
        echo "<div>
            <label>{$img[1]}</label>
            <input type='hidden' id='{$img[0]}_input' name='{$img[0]}'>
            <button type='button' class='media-button' data-target='{$img[0]}_input' data-preview='{$img[0]}_preview'>
                Seleccionar imagen
            </button>
            <img id='{$img[0]}_preview' class='preview-image' src='' style='display:none;'>
        </div>";
    }
    echo '</div></div>';

    echo '<input type="submit" class="guardar-cuidador" value="Guardar Cuidador">';
    echo '</form>';

    // Listado de cuidadores
    $cuidadores = get_option( 'pethome_cuidadores', [] );
    if ( ! empty( $cuidadores ) ) {
        echo '<div class="section-block"><h2><i class="fa-thin fa-list-check"></i> Listado de Cuidadores</h2>';
        echo '<table class="cuidadores-listado widefat"><thead><tr>
            <th style="width:90px;">Foto</th>
            <th style="width:150px;">Alias</th>
            <th>Nombre y Apellido</th>
            <th>Domicilio</th>
            <th style="width:150px;">Teléfono</th>
            <th>Email</th>
            <th>Alias Bancario</th>
            <th colspan="2" style="width:100px;">Acciones</th>
        </tr></thead><tbody>';
        foreach ( $cuidadores as $i => $c ) {
            $img = ! empty( $c['imagen'] ) ? esc_url( $c['imagen'] ) : '';
            $is_user_linked = !empty($c['user_id']);
            $alias = esc_html( $c['alias'] ?? '' );
            $nombre = esc_html( trim( ($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? '') ) );
            $dom = esc_html( ($c['calle'] ?? '') . ' ' . ($c['numero'] ?? '') );
            $telefono = $c['telefono'] ?? '';
            $email = esc_html( $c['email'] ?? '' );
            $alias_bancario = esc_html( $c['alias_bancario'] ?? '' );

            $edit_url = esc_url(add_query_arg(['page' => 'pethome_cuidador_editar', 'id' => $i], admin_url('admin.php')));
            $delete_url = wp_nonce_url(add_query_arg(['action' => 'delete', 'borrar_cuidador' => $i]), 'pethome_delete_cuidador_' . $i);

            echo '<tr>';
                echo '<td class="foto-cuidador-cell">';
                    if ( $img ) {
                        echo "<img src='{$img}' alt='Foto de {$nombre}'>";
                    }
                echo '</td>';
                
                echo "<td>{$alias}";
                    if ($is_user_linked) {
                        echo "<i class='fa-thin fa-user-check user-linked-icon' title='Vinculado a usuario de WordPress ID: ".esc_attr($c['user_id'])."'></i>";
                    }
                echo "</td>";
                
                echo "<td>{$nombre}</td>";
                echo "<td>{$dom}</td>";

                echo '<td>' . esc_html( $telefono );
                    if ( ! empty( $telefono ) ) {
                        $telefono_wa = preg_replace('/\D/', '', $telefono);
                        echo "<a href='https://wa.me/{$telefono_wa}' target='_blank' class='whatsapp-link'><i class=\"fa-brands fa-whatsapp\"></i></a>";
                    }
                echo '</td>';

                echo "<td>{$email}</td>";
                echo "<td>{$alias_bancario}</td>";

                echo "<td style='text-align:center;'><a href='{$edit_url}' class='button button-small'><i class='fa-thin fa-pen-to-square'></i></a></td>";
                echo "<td style='text-align:center;'><a href='{$delete_url}' class='button button-small is-destructive' onclick=\"return confirm('¿Estás seguro de que querés eliminar a este cuidador? Esta acción no se puede deshacer.')\"><i class='fa-thin fa-trash-can'></i></a></td>";
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    } else {
        echo '<div class="notice notice-info is-dismissible"><p><i class="fa-thin fa-circle-info"></i> No hay cuidadores registrados aún.</p></div>';
    }

    // Script para media uploader de WP
    echo '<script>
    jQuery(function($){
        $(".media-button").on("click", function(e){
            e.preventDefault();
            var target  = $("#" + $(this).data("target"));
            var preview = $("#" + $(this).data("preview"));
            var frame = wp.media({
                title: "Seleccionar imagen",
                multiple: false,
                library: { type: "image" },
                button: { text: "Usar esta imagen" }
            });
            frame.on("select", function(){
                var attach = frame.state().get("selection").first().toJSON();
                target.val(attach.url);
                preview.attr("src", attach.url).show();
            });
            frame.open();
        });
    });
    </script>';
}
?>