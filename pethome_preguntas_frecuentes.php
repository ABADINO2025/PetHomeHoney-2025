<?php
/**
 * Plantilla para la página de Preguntas Frecuentes (FAQ) de PetHomeHoney.
 * Panel de gestión de Clientes con buscador y paginación.
 * Plugin Name: PetHomeHoney Plugin
 * Plugin URI:  https://pethomehoney.com.ar
 * Description: Plugin para gestionar reservas de guarda con WooCommerce y CPT.
 * Version:     1.0 (Final y Estable)
 * Author:      Adrián Enrique Badino
 * Author URI:  https://pethomehoney.com.ar
 * Desarrolla   www.streaminginternacional.com 
 */
 
?>

<style>
    /* Estilos básicos para el acordeón de Preguntas Frecuentes */
    /* Podés mover esto a tu archivo de CSS principal */
    .pethome-faq-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 1rem;
        font-family: sans-serif;
    }

    .pethome-faq-container h1 {
        text-align: center;
        margin-bottom: 2rem;
        color: #333;
    }

    .pethome-faq-container h1 .fa-circle-question {
        margin-right: 10px;
    }

    .pethome-accordion-item {
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .pethome-accordion-header {
        background-color: #f7f7f7;
        padding: 15px 20px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
        color: #555;
        user-select: none;
    }
    
    .pethome-accordion-header h3 {
        margin: 0;
        font-size: 1.1em;
    }

    .pethome-accordion-content {
        padding: 0 20px;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out, padding 0.3s ease-out;
    }

    .pethome-accordion-content p {
        margin: 15px 0;
        line-height: 1.6;
        color: #666;
    }

    .pethome-accordion-item.active .pethome-accordion-content {
        max-height: 300px; /* Ajustar si el contenido es más largo */
        padding: 15px 20px;
        transition: max-height 0.5s ease-in, padding 0.5s ease-in;
    }
    
    .pethome-accordion-header .fa-plus,
    .pethome-accordion-header .fa-minus {
        transition: transform 0.3s ease;
    }

    .pethome-accordion-item.active .pethome-accordion-header .fa-plus {
       display: none;
    }
    .pethome-accordion-item:not(.active) .pethome-accordion-header .fa-minus {
       display: none;
    }

</style>

<div class="wrap pethome-faq-container">
    <h1><i class="fa-thin fa-circle-question"></i> Preguntas Frecuentes</h1>

    <div id="pethome-faq-accordion">
        
        <div class="pethome-accordion-item">
            <div class="pethome-accordion-header">
                <h3><i class="fa-thin fa-paw"></i> ¿Qué es PetHomeHoney?</h3>
                <div>
                    <i class="fa-thin fa-plus"></i>
                    <i class="fa-thin fa-minus"></i>
                </div>
            </div>
            <div class="pethome-accordion-content">
                <p>PetHomeHoney es una plataforma digital diseñada para conectar refugios de animales y rescatistas con personas que desean adoptar una mascota. Nuestra misión es simplificar y agilizar el proceso de adopción para encontrarle un hogar lleno de amor a cada animal.</p>
            </div>
        </div>

        <div class="pethome-accordion-item">
            <div class="pethome-accordion-header">
                <h3><i class="fa-thin fa-file-circle-question"></i> ¿Cómo funciona el proceso de adopción?</h3>
                 <div>
                    <i class="fa-thin fa-plus"></i>
                    <i class="fa-thin fa-minus"></i>
                </div>
            </div>
            <div class="pethome-accordion-content">
                <p>Navegá por los perfiles de las mascotas disponibles. Cuando encuentres una que te interese, podrás contactar directamente al refugio o rescatista a través de la plataforma para iniciar el proceso. Cada organización tiene sus propios requisitos y pasos a seguir, los cuales te serán informados al momento del contacto.</p>
            </div>
        </div>
        
        <div class="pethome-accordion-item">
            <div class="pethome-accordion-header">
                <h3><i class="fa-thin fa-magnifying-glass"></i> ¿Cómo puedo buscar una mascota específica?</h3>
                 <div>
                    <i class="fa-thin fa-plus"></i>
                    <i class="fa-thin fa-minus"></i>
                </div>
            </div>
            <div class="pethome-accordion-content">
                <p>Usá la barra de búsqueda y los filtros en la página principal. Podés filtrar por tipo de animal (perro, gato, etc.), raza, edad, tamaño y ubicación geográfica para acotar los resultados y encontrar tu compañero ideal de forma más rápida.</p>
            </div>
        </div>

        <div class="pethome-accordion-item">
            <div class="pethome-accordion-header">
                <h3><i class="fa-thin fa-user-plus"></i> ¿Necesito crear una cuenta para adoptar?</h3>
                 <div>
                    <i class="fa-thin fa-plus"></i>
                    <i class="fa-thin fa-minus"></i>
                </div>
            </div>
            <div class="pethome-accordion-content">
                <p>Sí, para poder contactar a los refugios y gestionar tus solicitudes, es necesario que te registres. El registro es gratuito y te permite guardar tus búsquedas, marcar mascotas como favoritas y llevar un seguimiento de tus procesos de adopción.</p