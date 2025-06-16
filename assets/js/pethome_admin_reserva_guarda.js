jQuery(document).ready(function($) {

    // Solo ejecutar si estamos en una pantalla de 'reserva_guarda'.
    if ($('body').hasClass('post-type-reserva_guarda')) {
        
        // --- INICIALIZACIÓN DE FLATPICKR ---
        const calendarioContainer = document.querySelector("#pethome_flatpickr_inline_calendar_container");
        if (calendarioContainer) {
            const campoOcultoFechas = document.querySelector("#pethome_reserva_fechas");
            const campoOcultoDias = document.querySelector("#pethome_reserva_cantidad_dias");
            const displayDias = document.querySelector("#cantidad_dias_display");
            const displayFechasContainer = document.querySelector("#lista_fechas_seleccionadas");

            const fpInstance = flatpickr(calendarioContainer, {
                inline: true,
                mode: "multiple",
                dateFormat: "Y-m-d",
                locale: "es",
                defaultDate: campoOcultoFechas.value ? campoOcultoFechas.value.split(', ') : [],
                
                onChange: function(selectedDates, dateStr, instance) {
                    selectedDates.sort((a, b) => a.getTime() - b.getTime());
                    const fechasFormateadas = selectedDates.map(date => instance.formatDate(date, "Y-m-d"));
                    if (campoOcultoFechas) campoOcultoFechas.value = fechasFormateadas.join(', ');
                    
                    const cantidadDias = selectedDates.length;
                    if (displayDias) displayDias.textContent = cantidadDias;
                    if (campoOcultoDias) campoOcultoDias.value = cantidadDias;
                    
                    if (displayFechasContainer) {
                        let fechasHtml = '';
                        selectedDates.forEach(date => {
                            const fechaVisible = instance.formatDate(date, "d/m/Y");
                            fechasHtml += `<span style="background: #5e4365; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; margin: 2px;">${fechaVisible}</span>`;
                        });
                        displayFechasContainer.innerHTML = fechasHtml;
                    }
                    if (window.jQuery) {
                        jQuery(campoOcultoDias).trigger('change');
                    }
                }
            });
            if(fpInstance.selectedDates.length > 0) {
                fpInstance.config.onChange(fpInstance.selectedDates, fpInstance.input.value, fpInstance);
            }
        }

        // --- LÓGICA PARA DNI ---
        const dniInput = document.getElementById('pethome_cliente_dni_metabox');
        if (dniInput) {
            dniInput.style.textAlign = 'right';
            const formatDNI = (value) => {
                let numericValue = value.replace(/\D/g, '').substring(0, 8);
                const len = numericValue.length;
                if (len <= 2) { return numericValue; }
                if (len <= 5) { return numericValue.substring(0, 2) + '.' + numericValue.substring(2); }
                const part3 = numericValue.substring(len - 3);
                const part2 = numericValue.substring(Math.max(0, len - 6), len - 3);
                const part1 = numericValue.substring(0, Math.max(0, len - 6));
                return (part1 ? part1 + '.' : '') + part2 + '.' + part3;
            };
            dniInput.value = formatDNI(dniInput.value);
            dniInput.addEventListener('input', function() {
                this.value = formatDNI(this.value);
            });
        }

        // --- LÓGICA PARA TELÉFONO ---
        const areaInput = $('#pethome_cliente_telefono_area');
        const numInput = $('#pethome_cliente_telefono_numero');
        const fullNumHiddenInput = $('#pethome_cliente_telefono');
        const wppLink = $('#pethome_whatsapp_link');

        const updateWhatsappLink = function() {
            const area = areaInput.val().replace(/\D/g, '').substring(0, 4);
            const num = numInput.val().replace(/\D/g, '');
            areaInput.val(area);
            numInput.val(num);
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
        
        // --- LÓGICA PARA RAZAS DEPENDIENTES ---
        const tipoSelect = $('#pethome_mascota_tipo');
        const razaSelect = $('#pethome_mascota_raza');
        
        // pethome_raza_data es el objeto que pasamos desde PHP con wp_localize_script.
        const razasData = window.pethome_raza_data.razas || {};

        const slugify = (text) => {
            if (!text) return '';
            return text.toString().toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^\w\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '')
                .replace(/-+$/, '');
        };

        const updateRazaOptions = () => {
            const selectedTipo = tipoSelect.val();
            const tipoSlug = slugify(selectedTipo);
            const savedRaza = razaSelect.attr('data-saved-value');

            razaSelect.empty();

            if (selectedTipo && razasData[tipoSlug]) {
                razaSelect.append($('<option>', { value: '', text: 'Seleccionar Raza...' }));
                razasData[tipoSlug].forEach(raza => {
                    razaSelect.append($('<option>', {
                        value: raza.value,
                        text: raza.text
                    }));
                });
            } else {
                razaSelect.append($('<option>', { value: '', text: 'Seleccionar un tipo primero...' }));
            }
            
            if (savedRaza) {
                razaSelect.val(savedRaza);
            }
        };

        tipoSelect.on('change', updateRazaOptions);
        
        // Ejecutar al cargar la página para poblar el campo de raza si ya hay un tipo seleccionado.
        updateRazaOptions();
    }
});
