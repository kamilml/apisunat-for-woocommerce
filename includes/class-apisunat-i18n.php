<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://apisunat.com/
 * @since      1.0.0
 *
 * @package    Apisunat
 * @subpackage Apisunat/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Apisunat
 * @subpackage Apisunat/includes
 * @author     Heikel Villar <heikelvillar@gmail.com>
 */
class Apisunat_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain(): void
    {

		load_plugin_textdomain(
			'apisunat',
			false,
			dirname(plugin_basename(__FILE__), 2) . '/languages/'
		);

	}



}
