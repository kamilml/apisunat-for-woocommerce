<?php
/**
 * Fired during plugin activation
 *
 * @link       https://apisunat.com/
 * @since      1.0.0
 *
 * @package    Apisunat
 * @subpackage Apisunat/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Apisunat
 * @subpackage Apisunat/includes
 * @author     Heikel Villar <heikelvillar@gmail.com>
 */
class Apisunat_Activator {

	/**
	 * Activate plugin
	 *
	 * @since    1.0.0
	 */
	public static function activate(): void {
		// if ( ! wp_next_scheduled( 'apisunat_five_minutes_event' ) ) {
		// 	wp_schedule_event( time(), 'wp_1_wc_regenerate_images_cron_interval', 'apisunat_five_minutes_event' );
		// }

		if (!wp_next_scheduled('apisunat_one_minute_event')) {
			wp_schedule_event(time(), 'every_minute', 'apisunat_one_minute_event');
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'semaphore';

		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$sql = "CREATE TABLE $table_name (id INT PRIMARY KEY, is_locked BOOLEAN)";
			$wpdb->query($sql);
			$wpdb->insert($table_name, array('id' => 1, 'is_locked' => false));
		}
	}
}