<?php

class Apisunat_Activator
{
    /**
     * Activate plugin
     *
     * @since    1.0.0
     */
    public static function activate(): void
    {
        if (!wp_next_scheduled('apisunat_five_minute_event')) {
            wp_schedule_event(time(), 'wp_1_wc_regenerate_images_cron_interval', 'apisunat_five_minutes_event');
        }

    }
}
