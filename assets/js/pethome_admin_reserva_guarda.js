jQuery(document).ready(function($) {

    // Solo ejecutar si estamos en una pantalla de 'reserva_guarda'.
    if ($('body').hasClass('post-type-reserva_guarda')) {
        
        // --- INICIALIZACIÓN DE FLATPICKR ---
        const campoFechas = $('#pethome_reserva_fechas');
        const campoCantidadDias = $('#pethome_reserva_cantidad_dias');

        if (campoFechas.length > 0) {
            flatpickr(campoFechas[0], {
                mode: "range",
                dateFormat: "Y-m-d",
                locale: "es",
                onChange: function(selectedDates) {
                    if (selectedDates.length === 2) {
                        const unDia = 24 * 60 * 60 * 1000;
                        const fechaInicio = selectedDates[0];
                        const fechaFin = selectedDates[1];
                        const diffDias = Math.round(Math.abs((fechaFin - fechaInicio) / unDia)) + 1;
                        if (campoCantidadDias.length > 0) {
                            campoCantidadDias.val(diffDias).trigger('change'); // trigger change para que otros scripts reaccionen
                        }
                    }
                }
            });
        }

        // --- LÓGICA PARA FORMATEAR DNI ---
        const dniInput = $('#pethome_cliente_dni_metabox');
        if (dniInput.length) {
            dniInput.css('text-align', 'right');
            const formatDNI = function(value) {
                let dni = value.replace(/\D/g, '').substring(0, 8);
                if (dni.length > 5) { return dni.slice(0, 2) + '.' + dni.slice(2, 5) + '.' + dni.slice(5); }
                if (dni.length > 2) { return dni.slice(0, 2) + '.' + dni.slice(2); }
                return dni;
            };
            dniInput.val(formatDNI(dniInput.val()));
            dniInput.on('input', function(e) {
                const selectionStart = e.target.selectionStart, originalLength = e.target.value.length;
                const formattedValue = formatDNI(e.target.value);
                $(this).val(formattedValue);
                const newLength = formattedValue.length;
                e.target.setSelectionRange(selectionStart + (newLength - originalLength), selectionStart + (newLength - originalLength));
            });
        }

        // --- LÓGICA PARA TELÉFONO DIVIDIDO Y LINK DE WHATSAPP ---
        const areaInput = $('#pethome_cliente_telefono_area');
        const numInput = $('#pethome_cliente_telefono_numero');
        const fullNumHiddenInput = $('#pethome_cliente_telefono');
        const wppLink = $('#pethome_whatsapp_link');

        const updateWhatsappLink = function() {
            const area = areaInput.val().replace(/\D/g, '');
            const num = numInput.val().replace(/\D/g, '');
            if (area && num) {
                wppLink.attr('href', 'https://wa.me/549' + area + num).show();
            } else {
                wppLink.hide();
            }
            fullNumHiddenInput.val(area + num);
        };

        const parseAndSetPhone = function() {
            const fullNum = fullNumHiddenInput.val();
            if (fullNum) {
                let area = '', num = '';
                if (fullNum.length > 7 && (fullNum.startsWith('11') || fullNum.length == 11)) {
                    area = fullNum.substring(0, 4);
                    num = fullNum.substring(4);
                } else if (fullNum.length > 6) {
                    area = fullNum.substring(0, 3);
                    num = fullNum.substring(3);
                } else {
                    num = fullNum;
                }
                areaInput.val(area);
                numInput.val(num);
            }
            updateWhatsappLink();
        };

        if (areaInput.length && numInput.length) {
            parseAndSetPhone();
            areaInput.on('input', updateWhatsappLink);
            numInput.on('input', updateWhatsappLink);
        }
    }
});