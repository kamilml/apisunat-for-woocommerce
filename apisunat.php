<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://apisunat.com/
 * @since             1.0.0
 * @package           Apisunat
 *
 * @wordpress-plugin
 * Plugin Name:       APISUNAT - Facturación Electrónica
 * Plugin URI:        https://github.com/kamilml/apisunat-for-woocommerce
 * Description:       Emite tus comprobantes electrónicos para SUNAT-PERU directamente desde tu tienda en WooCommerce.
 * Version:           1.3.14
 * Author:            APISUNAT
 * Author URI:        https://apisunat.com/
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       APISUNAT
 **/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Test to see if WooCommerce is active (including network activated).
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	add_action(
		'admin_notices',
		function () {
			printf(
				'<div class=\"notice notice-error is-dismissible\">
	        				<p>APISUNAT está <strong>Activado</strong> pero necesita <a href=\"https://wordpress.org/plugins/woocommerce/\" target=\"_blank\">WooCommerce</a> para funcionar. Por favor instala <a href=\"https://wordpress.org/plugins/woocommerce/\" target=\"_blank\">WooCommerce</a> antes de continuar.
    				</div>'
			);

		}
	);

	return;
}

/**
 * Currently plugin version.
 */
const APISUNAT_VERSION = '1.2.1';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-apisunat-activator.php
 */
function activate_apisunat(): void {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-apisunat-activator.php';
	Apisunat_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-apisunat-deactivator.php
 */
function deactivate_apisunat(): void {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-apisunat-deactivator.php';
	Apisunat_Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstall.
 * This action is documented in includes/class-apisunat-uninstaller.php
 */
function uninstall_apisunat(): void {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-apisunat-uninstaller.php';
	Apisunat_Uninstaller::uninstall();
}

register_activation_hook( __FILE__, 'activate_apisunat' );
register_deactivation_hook( __FILE__, 'deactivate_apisunat' );
register_uninstall_hook( __FILE__, 'uninstall_apisunat' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-apisunat.php';

/**
 * Begins execution of the plugin.
 */
function run_apisunat(): void {

	$plugin = new Apisunat();
	$plugin->run();

}
run_apisunat();
