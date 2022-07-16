<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://apisunat.com/
 * @since      1.0.0
 *
 * @package    Apisunat
 * @subpackage Apisunat/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="wrap">

    <img src="<?php echo plugin_dir_url(__FILE__); ?>logoMin.png" height="40" width="40">
    <h2> APISUNAT Settings</h2>
    <hr>
    <!--NEED THE settings_errors below so that the errors/success messages are shown after submission - wasn't working once we started using add_menu_page and stopped using add_options_page so needed this-->
    <?php settings_errors(); ?>
    <form method="POST" action="options.php">
        <?php
        do_settings_sections('apisunat_general_settings');
        settings_fields('apisunat_general_settings');
        ?>
        <?php submit_button(); ?>
    </form>
</div>
