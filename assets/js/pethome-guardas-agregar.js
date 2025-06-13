/**
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

// pethome-guardas-agregar.js
// Contiene lógica específica para el formulario de agregar guarda:
// - Formateo de DNI
// - Modal numérico para el campo de teléfono
// - Media Selector de imagen (para la mascota)

document.addEventListener('DOMContentLoaded', function() {
    // ────────── Lógica del DNI ──────────
    const clienteDniInput = document.getElementById('pethome_cliente_dni'); // CORREGIDO ID

    if (clienteDniInput) {
        clienteDniInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Eliminar caracteres no numéricos
            let formattedValue = '';

            // Formatear DNI con puntos (ej. 12.345.678)
            if (value.length > 0) {
                formattedValue = value.substring(0, 2);
            }
            if (value.length > 2) {
                formattedValue += '.' + value.substring(2, 5);
            }
            if (value.length > 5) {
                formattedValue += '.' + value.substring(5, 8);
            }

            e.target.value = formattedValue; // Asignar el valor formateado
        });
    }

    // ────────── Lógica del Modal del Teléfono ──────────
    const openPhoneModalBtn = document.getElementById('open_phone_modal');
    const phoneModal = document.getElementById('phone_modal');
    const phoneModalInput = document.getElementById('phone_modal_input');
    // Asegurarse de que phoneModal existe antes de intentar querySelectorAll
    const keypadBtns = phoneModal ? phoneModal.querySelectorAll('.keypad-button') : [];
    const phoneModalOk = document.getElementById('phone_modal_ok');
    const phoneMain = document.getElementById('pethome_cliente_telefono'); // CORREGIDO ID

    if (openPhoneModalBtn && phoneModal && phoneModalInput && phoneModalOk && phoneMain) {
        // Abrir modal
        openPhoneModalBtn.addEventListener('click', () => {
            phoneModal.style.display = 'block';
            phoneModalInput.value = phoneMain.value; // Copiar el valor actual al input del modal
            phoneModalInput.focus();
        });

        // Manejar clics en el teclado numérico del modal
        keypadBtns.forEach(button => {
            button.addEventListener('click', function() {
                const value = this.dataset.value;
                let current = phoneModalInput.value;

                if (value === 'clear') {
                    current = '';
                } else if (value === 'back') {
                    current = current.slice(0, -1);
                } else {
                    current += value;
                }
                phoneModalInput.value = current;
                phoneModalInput.focus(); // Mantener el foco en el input del modal
            });
        });

        // Botón "OK" del modal
        phoneModalOk.addEventListener('click', () => {
            phoneMain.value = phoneModalInput.value; // Pasar el valor del modal al input principal
            phoneModal.style.display = 'none'; // Cerrar el modal
        });

        // Cerrar modal al hacer clic fuera de su contenido
        phoneModal.addEventListener('click', (e) => {
            // Si el clic ocurrió directamente en el fondo del modal (no en su contenido)
            if (e.target === phoneModal) {
                phoneMain.value = phoneModalInput.value; // Guardar valor antes de cerrar
                phoneModal.style.display = 'none';
            }
        });

        // Evitar que el clic en el contenido del modal cierre el modal
        // Asegurarse de que el elemento '.modal-content' existe
        const modalContent = phoneModal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.addEventListener('click', e => e.stopPropagation());
        }

        // Lógica de focus/blur para el campo principal del teléfono
        phoneMain.addEventListener('focus', function() {
            if (this.value === '') {
                this.value = '+549'; // Sugerir inicio de número de Argentina
            }
        });
        phoneMain.addEventListener('blur', function() {
            if (this.value === '+54' || this.value === '+549') {
                this.value = ''; // Limpiar si solo quedan prefijos
            }
        });
    }

    // ────────── Media Selector de Imagen para Mascota (requiere jQuery) ──────────
    // Asumimos que este es el media selector para la imagen de la Mascota
    // y que jQuery está disponible (encolado por WordPress).
    if (typeof jQuery !== 'undefined') {
        jQuery(function($) { // Uso explícito de jQuery para evitar conflictos
            $('.media-button').on('click', function(e) {
                e.preventDefault();
                var target = $('#' + $(this).data('target')); // Input oculto para guardar el ID de la imagen
                var preview = $('#' + $(this).data('preview')); // Elemento IMG o DIV para mostrar la vista previa

                var frame = wp.media({
                    title: pethomehoney_vars.mediaTitle || 'Seleccionar imagen', // Usa la variable localizada
                    button: {
                        text: pethomehoney_vars.mediaButton || 'Usar esta imagen' // Usa la variable localizada
                    },
                    multiple: false, // Solo una imagen
                    library: {
                        type: 'image'
                    }
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    target.val(attachment.id); // Guardar el ID de la imagen en el input oculto

                    // Actualizar la vista previa
                    if (preview.is('img')) { // Si el elemento de vista previa es una etiqueta <img>
                        preview.attr('src', attachment.url);
                        preview.show(); // Asegúrate de que la imagen se muestre
                    } else { // Si es un div u otro contenedor, inserta una etiqueta <img>
                        preview.html('<img src="' + attachment.url + '" style="max-width:150px; height:auto; display:block; margin: 0 auto;">');
                    }
                });

                frame.open();
            });
        });
    }

}); // Fin de DOMContentLoaded