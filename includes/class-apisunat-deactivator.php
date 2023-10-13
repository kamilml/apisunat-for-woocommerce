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
	public static function deactivate(): void {

		$event_names = array('apisunat_five_minutes_event', 'apisunat_one_minute_event');

		foreach ($event_names as $event_name) {
			$timestamp = wp_next_scheduled($event_name);
			wp_unschedule_event($timestamp, $event_name);
		}
	}
}
