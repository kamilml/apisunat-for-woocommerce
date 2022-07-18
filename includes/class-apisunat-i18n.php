<?php

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
