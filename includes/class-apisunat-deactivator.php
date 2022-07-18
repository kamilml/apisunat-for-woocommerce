<?php

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
