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

// pethome-guardas.js

    // --- Recalc costos ---
    // Asegúrate de que `prices` se pasa correctamente desde PHP.
    // Usamos `pethome_guardas_vars` que encolaremos via wp_localize_script
    const prices = typeof pethome_guardas_vars !== 'undefined' ? pethome_guardas_vars.price_map : {};

    const productoReserva = document.getElementById('producto_reserva');
    if (productoReserva) {
        productoReserva.addEventListener('change', recalcCosts);
    }

    function recalcCosts(){
        const daysElement = document.getElementById('dias');
        const precioDiarioElement = document.getElementById('precio_diario');
        const precioTotalElement = document.getElementById('precio_total');

        if (!daysElement || !precioDiarioElement || !precioTotalElement) {
            console.warn('Elementos de cálculo de costos no encontrados.');
            return;
        }

        const days = calendar.selectedDates.length;
        const selectedProductValue = productoReserva ? productoReserva.value : '';
        const daily = parseFloat(prices[selectedProductValue] || 0);

        precioDiarioElement.value = daily ? daily.toFixed(2) : '';
        precioTotalElement.value = (daily && days) ? (daily * days).toFixed(2) : '';
    }
    recalcCosts(); // Llama al inicio para calcular si ya hay fechas seleccionadas (ej. al editar)

    // --- Auto-formato DNI (si tienes un campo DNI en el formulario) ---
    // Noté que el DNI no estaba en el HTML del formulario,
    // pero si lo agregas, esta lógica sería útil.
    const clienteDni = document.getElementById('cliente_dni');
    if (clienteDni) {
        clienteDni.addEventListener('input', function(){
            let v = this.value.replace(/\D/g,'').slice(0,8);
            let rev = v.split('').reverse().join('');
            let grp = rev.match(/.{1,3}/g);
            if(grp) v = grp.join('.').split('').reverse().join('');
            this.value = v;
        });
    }

    // --- Media selector mascota (usando jQuery como lo tenías) ---
    // Asegúrate de que jQuery esté encolado antes de este script.
    if (typeof jQuery !== 'undefined') {
        jQuery(function($){
            $('.media-button').on('click', function(e){
                e.preventDefault();
                var target = $('#' + $(this).data('target'));
                var preview = $('#' + $(this).data('preview'));
                var frame = wp.media({
                    title:'Seleccionar imagen',
                    button:{text:'Usar esta imagen'},
                    multiple:false,
                    library:{type:'image'}
                });
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    target.val(att.url);
                    preview.attr('src', att.url).show();
                });
                frame.open();
            });
        });
    }
});