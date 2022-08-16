<?php
/**
 * Register all actions and filters for the plugin
 *
 * @link       https://apisunat.com/
 * @since      1.0.0
 *
 * @package    Apisunat
 * @subpackage Apisunat/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Apisunat
 * @subpackage Apisunat/includes
 * @author     Apisunat
 */
class Apisunat_Uninstaller {

	/**
	 * Unninstall plugin.
	 *
	 * @since    1.0.0
	 */
	public static function uninstall(): void {

		delete_option( 'apisunat_ruc' );
		delete_option( 'apisunat_personal_id' );
		delete_option( 'apisunat_personal_token' );
		delete_option( 'apisunat_company_name' );
		delete_option( 'apisunat_company_address' );
		delete_option( 'apisunat_serie_factura' );
		delete_option( 'apisunat_serie_boleta' );

	}

}
