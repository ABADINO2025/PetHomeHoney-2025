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

jQuery(document).ready(function($) {
    // Inicializar Flatpickr en el campo de fechas
    // Asegúrate de que el input en pethome_guardas_agregar.php tenga la clase "pethomehoney-datepicker"
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".pethomehoney-datepicker", {
            mode: "range",          // Permite seleccionar un rango de fechas
            dateFormat: "Y-m-d",    // Formato de fecha AAAA-MM-DD
            locale: "es",           // Carga el idioma español
            // Puedes añadir más opciones aquí según necesites:
            // minDate: "today",
            // enableTime: false,
            // inline: true,
        });
    }

    // Lógica para el selector de medios de imagen de la mascota
    $(document).on('click', '.pethomehoney_upload_image_button', function(e) {
        e.preventDefault();
        var button = $(this);
        var custom_uploader = wp.media({
            title: pethomehoney_vars.mediaTitle, // Título del modal
            library: {
                type: 'image'
            },
            button: {
                text: pethomehoney_vars.mediaButton // Texto del botón
            },
            multiple: false
        }).on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            button.prev('input[type="hidden"]').val(attachment.id);
            button.siblings('.pethomehoney_image_preview').html('<img src="' + attachment.url + '" style="max-width:100px; height:auto;" />');
        }).open();
    });

    // Lógica para eliminar la imagen
    $(document).on('click', '.pethomehoney_remove_image_button', function(e) {
        e.preventDefault();
        var button = $(this);
        button.prevAll('input[type="hidden"]').val('');
        button.siblings('.pethomehoney_image_preview').html('');
    });

    // Funcionalidad para el selector de razas basado en el tipo de mascota
    $('#pethome_mascota_tipo').on('change', function() {
        var tipoMascota = $(this).val();
        var $razaSelect = $('#pethome_mascota_raza');
        $razaSelect.empty(); // Limpiar opciones anteriores

        if (pethomehoney_vars.razasPorTipo[tipoMascota]) { // Accede a razasPorTipo
            $.each(pethomehoney_vars.razasPorTipo[tipoMascota], function(index, raza) {
                $razaSelect.append($('<option>', {
                    value: raza,
                    text: raza
                }));
            });
        }
        // Seleccionar la raza actual si está en edición
        if (pethomehoney_vars.mascota_raza_actual && $razaSelect.find('option[value="' + pethomehoney_vars.mascota_raza_actual + '"]').length) {
            $razaSelect.val(pethomehoney_vars.mascota_raza_actual);
        } else {
            $razaSelect.val($razaSelect.find('option:first').val()); // Seleccionar la primera si no hay coincidencia
        }
    }).trigger('change'); // Disparar al cargar la página para poblar las razas iniciales


    // Lógica para el cálculo de costos de reserva y servicios
    function calculateTotalCost() {
        var startDateStr = $('.pethomehoney-datepicker').val().split(' to ')[0];
        var endDateStr = $('.pethomehoney-datepicker').val().split(' to ')[1];
        var days = 0;

        if (startDateStr && endDateStr) {
            var startDate = new Date(startDateStr);
            var endDate = new Date(endDateStr);
            var timeDiff = Math.abs(endDate.getTime() - startDate.getTime());
            days = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // +1 para incluir el día de egreso
        }

        var selectedProduct = $('#pethome_reserva_producto_o_servicio').val();
        var pricePerDay = 0;
        if (selectedProduct && pethomehoney_vars.priceMap[selectedProduct]) {
            pricePerDay = parseFloat(pethomehoney_vars.priceMap[selectedProduct].price); // Accede a priceMap
        }

        var subtotalBooking = pricePerDay * days;
        $('#pethome_reserva_costo_diario').val(formatPrice(pricePerDay));
        $('#pethome_reserva_subtotal').val(formatPrice(subtotalBooking));

        var totalExtraServices = 0;
        $('.pethomehoney-custom-service-row').each(function() {
            var serviceName = $(this).find('.pethomehoney_custom_service_name').val();
            var servicePrice = parseFloat($(this).find('.pethomehoney_custom_service_price').val() || 0);
            totalExtraServices += servicePrice;
        });

        var total = subtotalBooking + totalExtraServices;
        $('#pethome_reserva_saldo_a_pagar').val(formatPrice(total)); // Asumiendo que "saldo a pagar" es el total inicial
    }

    // Formatear precio usando las variables de localización de WooCommerce
    function formatPrice(price) {
        if (typeof pethomehoney_vars.wc_price_format_params === 'undefined') {
            return price.toFixed(2); // Fallback si no hay parámetros de WC
        }
        var params = pethomehoney_vars.wc_price_format_params;
        var formattedPrice = parseFloat(price).toFixed(params.decimals);
        formattedPrice = formattedPrice.replace('.', params.decimal_sep);
        formattedPrice = formattedPrice.replace(/\B(?=(\d{3})+(?!\d))/g, params.thousand_sep);

        var priceFormat = params.price_format;
        return priceFormat.replace('%s', params.currency_symbol).replace('%v', formattedPrice);
    }

    // Disparar el cálculo cuando cambian las fechas o el producto/servicio
    // El Flatpickr también dispara un evento 'change' en el input cuando se selecciona una fecha/rango.
    $('.pethomehoney-datepicker, #pethome_reserva_producto_o_servicio').on('change', calculateTotalCost);

    // Lógica para añadir y eliminar servicios personalizados
    $('#add_custom_service').on('click', function() {
        var serviceCount = $('.pethomehoney-custom-service-row').length;
        var newRow = `
            <div class="pethomehoney-custom-service-row" data-index="${serviceCount}">
                <input type="text" name="pethome_guardas_servicios_custom[${serviceCount}][nombre]" class="pethomehoney_custom_service_name" placeholder="Nombre del servicio">
                <input type="number" step="0.01" name="pethome_guardas_servicios_custom[${serviceCount}][precio_unitario]" class="pethomehoney_custom_service_price" placeholder="Precio unitario">
                <button type="button" class="button remove-custom-service">Eliminar</button>
            </div>
        `;
        $('#custom_services_container').append(newRow);
        calculateTotalCost(); // Recalcular al añadir nuevo servicio
    });

    $(document).on('click', '.remove-custom-service', function() {
        $(this).closest('.pethomehoney-custom-service-row').remove();
        calculateTotalCost(); // Recalcular al eliminar servicio
    });

    // Disparar el cálculo cuando cambian los precios de servicios personalizados
    $(document).on('input', '.pethomehoney_custom_service_price', calculateTotalCost);


    // Llamar la función de cálculo al cargar la página si ya hay datos
    calculateTotalCost();

});