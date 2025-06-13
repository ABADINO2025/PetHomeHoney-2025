/**
 * Plugin Name: PetHomeHoney Plugin
 * Plugin URI:  https://pethomehoney.com.ar
 * Description: Plugin para gestionar reservas de guarda con WooCommerce y CPT.
 * Version:     1.0 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrolla   www.streaminginternacional.com 
 */

document.getElementById("dni").addEventListener("blur", function () {
  const dni = this.value.replace(/\D/g, '');
  if (dni.length >= 7) {
    fetch(ajaxurl + '?action=buscar_cliente_por_dni&dni=' + encodeURIComponent(dni))
      .then(res => res.json())
      .then(data => {
        if (data.existe) {
          document.querySelector('input[name="nombre"]').value = data.nombre || '';
          document.querySelector('input[name="apellido"]').value = data.apellido || '';
          document.querySelector('input[name="telefono"]').value = data.telefono || '';
          document.querySelector('input[name="email"]').value = data.email || '';
          document.querySelector('input[name="domicilio"]').value = data.domicilio || '';
        }
      });
  }
});
