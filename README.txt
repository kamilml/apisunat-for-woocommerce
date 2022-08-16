=== APISUNAT Facturación Electrónica para WooCommerce - SUNAT - PERU ===
Contributors: kamilml
Donate link: https://apisunat.com/
Tags: WP, apisunat, facturacion, facturacion electronica, factura, boleta, WooCommerce, CPE, Peru
Requires at least: 5.8
Tested up to: 6.0.1
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Emite tus comprobantes electrónicos para SUNAT - PERU directamente desde tu tienda en WooCommerce.

== Description ==

Este plugin emite tus comprobantes electrónicos a partir de una orden generada en WooCommerce, y los envía a SUNAT mediante el servicio de facturación de [APISUNAT.com](https://apisunat.com/)

= Algunas cosas que te gustarán =

*   Se instala y configura rápido y fácil
*   Soporte por teléfono y por WhatsApp [(+51) 955 184 284](https://wa.me/51955184284)
*   Compatible con cualquier otro plugin
*   Sirve para **régimen NUEVO RUS**
*   Sirve para **PRICOS** y **obligados OSE**
*   Este plugin es GRATIS!

= Modos: MANUAL / AUTOMATICO =

El modo manual te permite elegir cuales ordenes quieres facturar. Mientras que el modo automático factura todo sin que tú hagas nada.

= Mapeo de casilleros en el checkout =

Puedes usar los casilleros que el plugin crea en el checkout (tipo de documento, nombre, RUC, DNI, etc), o puedes crear o utilizar los que tu quieras. Esto te sirve cuando tienes otro plugin que modifica tu checkout.

= Modo de prueba =

Antes de empezar a facturar en modo real, puedes probar el modo DESARROLLO generando comprobantes sin valor GRATIS!

== Instalación ==

1. Abre tu panel de WordPress
2. Ve a **Plugins / Agregar nuevo**
3. Busca **“APISUNAT”** e instálalo

== Configuración ==

1. Crea una empresa en [APISUNAT.com](https://apisunat.com/)
2. Ve a la **Configuración de Empresa / API REST** y busca los valores **personaId** y **personaToken**
3. Vuelve a la configuración del plugin en WordPress y usa esos valores

== Configuración Avanzada ==

Nuestro plugin crea automáticamente los campos necesarios en el checkout para obtener la información de facturación que le falta a WooCommerce. Como el tipo de comprobante a emitir (Boleta o Factura) y el tipo de documento (RUC o DNI).

Si utilizas otros plugins para editar esos campos, o si no te gustan los que nosotros hemos creado, puedes usar esta sección para mapear la key de las casillas que tú quieras usar.

== Frequently Asked Questions ==

= Dónde encuentro los valores que piden en la configuración (personaId y personaToken) =

Debes registrar tu empresa en [APISUNAT.com](https://apisunat.com/) y ahí podrás encontrarlos.

= Cómo hago para empezar a emitir comprobantes reales que lleguen a SUNAT =

Esta guía te muestra como pasar al modo PRODUCCION (modo real). O puedes contactarnos al WhatsApp [(+51) 955 184 284](https://wa.me/51955184284) y te ayudaremos

= Tengo que pagar algo? =

Este plugin es gratuito. [APISUNAT.com](https://apisunat.com/) tiene un costo (el mejor del mercado) que puedes consultar por su WhatsApp.

== Screenshots ==
1. APISUNAT.com
2. Instalación
3. Configuración

== Changelog ==

= 1.2.0 =
Se agregaron opciones para anular o corregir comprobantes. Se mejoró la compatibilidad con órdenes de compra creadas sin datos como (RUC, DNI, etc) para poder facturar incluso ventas anteriores.

= 1.0.8 =
Correcciones recomendadas por el equipo de revisión de plugins de wordpress

= 1.0.7 =
Se corrigió el bug de no poner nada en los inputs de la configuración del Checkout personalizado.

= 1.0.6 =
Primer parche público del plugin. Compatibilidad con otros plugins que modifican el checkout y modo debug.

== Upgrade Notice ==

= 1.2.0 =
* Anulación con Nota de Crédito agregada
* Edición de datos (RUC, DNI, nombre, dirección, etc)
* Uso del API v1.2
* Compatibilidad para facturar órdenes creadas sin nuestra metadata


`<?php code(); // goes in backticks ?>`