<?php

/**
 * Fired during plugin uninstall
 *
 * @link       https://apisunat.com/
 * @since      1.0.0
 *
 * @package    Apisunat
 * @subpackage Apisunat/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's uninstall.
 *
 * @since      1.0.0
 * @package    Apisunat
 * @subpackage Apisunat/includes
 * @author     Heikel Villar <heikelvillar@gmail.com>
 */
class Apisunat_Uninstaller {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function uninstall() {

        delete_option('apisunat_ruc');
        delete_option('apisunat_personal_id');
        delete_option('apisunat_personal_token');
        delete_option('apisunat_company_name');
        delete_option('apisunat_company_address');
        delete_option('apisunat_serie_factura');
        delete_option('apisunat_serie_boleta');

	}

}
