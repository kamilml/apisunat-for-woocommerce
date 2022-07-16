<?php

/**
 * Fired during plugin deactivation
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
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Apisunat
 * @subpackage Apisunat/includes
 * @author     Heikel Villar <heikelvillar@gmail.com>
 */
class Apisunat_Deactivator {

	/**
	 * Deactivate plugin
	 *
	 * @since    1.0.0
	 */
	public static function deactivate(): void
    {

        $timestamp = wp_next_scheduled('apisunat_five_minutes_event');
        wp_unschedule_event($timestamp, 'apisunat_five_minutes_event');
	}

}
